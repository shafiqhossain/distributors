<?php

/**
 * @file
 * Contains Drupal\distributors\Form\DistributorsSearchForm.
 */

namespace Drupal\distributors\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax;
use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Component\Serialization\Json;


/**
  * Distributors Search form
  */
class DistributorsSearchForm extends FormBase {
  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * The Entity Manager.
   *
   * @var EntityManagerInterface $manager
   */
  protected $manager;

  /**
   * The Entity Query.
   *
   * @var QueryFactory $entity_query
   */
  protected $entity_query;


  /**
   * GeoLocation settings.
   *
   * @var array $options
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entity_query, EntityManagerInterface $manager, AccountInterface $account) {
    $this->entity_query = $entity_query;
    $this->manager = $manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'distributors_search_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$address_box = ($form_state->hasValue('address_box') ? trim($form_state->getValue('address_box')) : '');
	$form['address_box'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('Address'),
	  '#size' => 60,
	  '#maxlength' => 255,
	  '#default_value' => $address_box,
	  '#placeholder' => $this->t('Enter location'),
	  '#required' => FALSE,
	  '#title_display' => 'invisible',
	);

	$form['search'] = array(
	  '#type' => 'button',
	  '#is_button' => TRUE,
	  '#value' => $this->t('Search'),
	  '#ajax' => array(
	    'wrapper' => 'distributors-search-results-wrapper',
	    'callback' => [$this, 'searchCallback'],
		'event' => 'click',
		'progress' => array(
		  'type' => 'throbber',
		  'message' => NULL,
		),
	  ),
	);

	/*
	$form['location'] = array(
	  '#type' => 'image_button',
	  //'#value' => t('Location'),
	  '#src' => drupal_get_path('module', 'distributors').'/images/icon-locate.png',
	  '#prefix' => '<div id="distributors-location-icon">',
	  '#suffix' => '</div>',
	);
	*/
	$form['location'] = array(
	  //'#markup' => Markup::create('<img src="'.drupal_get_path('module', 'distributors').'/images/icon-locate.png" />'),
	  '#markup' => '<span class="label">' . t('Location') . '</span>',
	  '#prefix' => '<div id="distributors-location-icon">',
	  '#suffix' => '</div>',
	);

	//load all data initially
	$grid_output = '';
	$lat = 0;
	$long = 0;
    $is_centre_set = 0;
	$mapData = [];

	//check if ajax submit
	$is_ajax = \Drupal::request()->isXmlHttpRequest();

	//get the configuration
	$config = \Drupal::config('distributors.settings');
	$map_initial_filter = $config->get('map_initial_filter');
	$lang_code = '';
	$country_code = '';

	//get the current language
  	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  	$filters = explode(",", $map_initial_filter);
  	if(count($filters)>0) {
  	  foreach($filters as $r) {
		$r_arr = explode("|", $r);
		$lang_code = (isset($r_arr[0]) & !empty($r_arr[0]) ? $r_arr[0] : '');
		$country_code = (isset($r_arr[1]) & !empty($r_arr[1]) ? $r_arr[1] : '');
		if($lang_code == $language) break;
  	  }
  	}

	//if not ajax submit, load all data
	if(!$is_ajax) {
		$query = $this->entity_query->get('node')
		  ->condition('type', 'distributor', '=')
		  ->sort('created', 'DESC');

		if(!empty($country_code)) {
		  $query->condition('field_address.country_code', $country_code, '=');
		}

		$nids = $query->execute();

		//get the storage
		$node_storage = $this->manager->getStorage('node');
		if(count($nids)>0) {
		  foreach($nids as $nid) {
			$node = $node_storage->load($nid);
			if($node != false) {
			  $title = $node->get('title')->value;
			  $field_address = $node->get('field_address')->getValue();
			  $field_location = $node->get('field_location')->getValue();
			  $phone = $node->get('field_phone_number')->value;
			  //$fax = $node->get('field_fax_number')->value;
			  $website = $node->get('field_website')->value;
			  $email = $node->get('field_contact_email_address')->value;

			  $address1 = $field_address[0]['address_line1'];
			  $address2 = $field_address[0]['address_line2'];
			  $state = $field_address[0]['administrative_area'];
			  $city = $field_address[0]['locality'];
			  $area = $field_address[0]['dependent_locality'];
			  $postal_code = $field_address[0]['postal_code'];
			  $country_code = $field_address[0]['country_code'];
			  $sorting_code = $field_address[0]['sorting_code'];


			  //grid content
			  $grid_output .= '<div class="distributor-item" id="distributor-item-'.$nid.'">';
			  if(!empty($title)) {
				$grid_output .= '<div class="title"><a id="distributor-title-'.$nid.'" href="#" data-lat="'.$field_location[0]['lat'].'" data-lng="'.$field_location[0]['lng'].'">'.$title.'</a></div>';
			  }
			  if(!empty($address1)) {
				$grid_output .= '<div class="address1">'.$address1.'</div>';
			  }
			  if(!empty($address2)) {
				$grid_output .= '<div class="address2">'.$address2.'</div>';
			  }
			  if(!empty($city) || !empty($state) || !empty($postal_code)) {
				$grid_output .= '<div class="city-state">'.$city.', '.$state.' '.$postal_code.'</div>';
			  }
			  if(!empty($country_code)) {
				$grid_output .= '<div class="country-code">'.$country_code.'</div>';
			  }
			  if(!empty($phone)) {
				$grid_output .= '<div class="phone"><a href="tel:' . str_replace(' ', '', $phone) .'"><span>'.$phone.'</span></a></div>';
			  }
			  /*
			  if(!empty($fax)) {
				$grid_output .= '	<div class="fax">'.$this->t('F.').$fax.'</div>';
			  }
			  */
			  if(!empty($email)) {
				$grid_output .= '<div class="email"><a href="mailto:' . $email . '"><span>'.$email.'</span></a></div>';
			  }
			  if(!empty($website)) {
				$grid_output .= '<div class="website"><a href="' . $website . '" target="_blank"><span>'.t('Visit website').'</span></a></div>';
			  }
			  $grid_output .= '</div>';


			  //map content
		      $marker_content = '<div class="readmore"><a id="distributor-link-'.$nid.'" href="#" data-lat="'.$field_location[0]['lat'].'" data-lng="'.$field_location[0]['lat'].'">'.$this->t('Read more').'</a></div>';
			  /*
			  if(!empty($address1)) {
				$marker_content .= $address1.'<br>';
			  }
			  if(!empty($address2)) {
				$marker_content .= $address2.'<br>';
			  }
			  $marker_content .= $city.', '.$state.' '.$postal_code.'<br>';
			  $marker_content .= $country_code;
			  */

			  $mapData[] = [
				'location_name' => '<h3 class="location-name">'.$title.'</h3>',
				'location_address' => Markup::create($marker_content),
				'lat' => $field_location[0]['lat'],
				'long' => $field_location[0]['lng'],
			  ];

			  //set the first address as center
			  if($is_centre_set == 0) {
				$centre_lat = $field_location[0]['lat'];
				$centre_lng = $field_location[0]['lng'];
				$is_centre_set = 1;
			  }

			}
		  }
		}

		//get the centre point
		$lat = $centre_lat;
		$long = $centre_lng;

		// Get data form Google Map config setting.
		$api_key = $config->get('api_key');
		$map_height = $config->get('map_height');
		$map_width = $config->get('map_width');
		$map_zoom_level = $config->get('map_zoom_level');
		$map_distance = $config->get('map_distance');
		$map_distance_unit = $config->get('map_distance_unit');


		$map_build = [
		  '#theme' => 'distributors_googlemap_results',
		  '#map_height' => $map_height,
		  '#map_width' => $map_width,
		  '#attached' => [
			'library' => [
			  'distributors/googlemap',
			],
			'drupalSettings' => [
			  'api_key' => $api_key,
			  'all_address' => $mapData,
			  'map_zoom_level' => $map_zoom_level,
			  'lat' => $lat,
			  'long' => $long,
			],
		  ],
		  '#cache' => ['max-age' => 0],
		];

		$renderer = \Drupal::service('renderer');
		$html = $renderer->render($map_build);
	}
	else {
	  $html = '';
	}

	$form['no_results'] = array(
	  '#markup' => '',
	  '#prefix' => '<div id="distributors-search-no-results-wrapper">',
	  '#suffix' => '</div>',
	);
	$form['results'] = array(
	  '#markup' => Markup::create($html),
	);
	$form['lists'] = array(
	  '#markup' => $grid_output,
	);

	$form['#attached']['library'][] = 'distributors/search_page';
	$form['#cache'] = ['max-age' => 0];

	//\Drupal::service('page_cache_kill_switch')->trigger();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$form_state->setRebuild(TRUE);
  }


  /**
   * {@inheritdoc}
   */
  public function searchCallback(array &$form, FormStateInterface $form_state) {

	if($form_state->hasValue('address_box')) {
	  $address = $form_state->getValue('address_box');
	}
	else {
	  $address = '';
	}

	// Get data form Google Map config setting.
	$config = \Drupal::config('distributors.settings');
	$api_key = $config->get('api_key');
	$map_height = $config->get('map_height');
	$map_width = $config->get('map_width');
	$map_zoom_level = $config->get('map_zoom_level');
	$map_distance = $config->get('map_distance');
	$map_distance_unit = $config->get('map_distance_unit');

	//get the current language
  	$language = \Drupal::languageManager()->getCurrentLanguage()->getId();

	if(!empty($address)) {
		$prepAddr = str_replace(' ','+',$address);
		$geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false&key='.$api_key.'&language='.$language);
		$geooutput = json_decode($geocode);
		//print_r($output);

		$status = (isset($geooutput->status) ? $geooutput->status : '');
		if($status == 'OK') {
		  $latitude = $geooutput->results[0]->geometry->location->lat;
		  $longitude = $geooutput->results[0]->geometry->location->lng;
		}
		else {
		  $latitude = '';
		  $longitude = '';
		}
	}
	else {
	  $latitude = '';
	  $longitude = '';
	}

    $content = '';
    $count = 0;

	// Instantiate an AjaxResponse Object to return.
	$ajax_response = new \Drupal\Core\Ajax\AjaxResponse();

	if(!empty($latitude) && !empty($longitude)) {
      $args = [$latitude.','.$longitude.'<='.$map_distance.$map_distance_unit];
      //$args = ['19.8967662,-155.5827818<=50000miles'];
      $view = \Drupal\views\Views::getView('distributors');

      if(is_object($view)) {
        $view->setArguments($args);
        $view->setDisplay('block_1');
        $view->preExecute();
        $view->execute();
	    $count = count($view->result);

    	$mapData = [];
    	$is_centre_set = 0;
    	$centre_lat = 0;
    	$centre_lng = 0;
		$grid_output = '';


		/*
		 * Add locations to output.
		 */
		foreach ($view->result as $row_number => $row) {
		  $obj = (isset($row->_entity) ? $row->_entity : null);
		  $nid = $obj->get('nid')->value;
		  $type = $obj->get('type')->target_id;
		  $langcode = $obj->get('langcode')->value;
		  $title_build = $obj->get('title')->value;
		  $field_address = $obj->get('field_address')->getValue();
		  $field_location = $obj->get('field_location')->getValue();
		  $phone = $obj->get('field_phone_number')->value;
		  //$fax = $obj->get('field_fax_number')->value;
		  $website = $obj->get('field_website')->value;
		  $email = $obj->get('field_contact_email_address')->value;

		  $address1 = $field_address[0]['address_line1'];
		  $address2 = $field_address[0]['address_line2'];
		  $state = $field_address[0]['administrative_area'];
		  $city = $field_address[0]['locality'];
		  $area = $field_address[0]['dependent_locality'];
		  $postal_code = $field_address[0]['postal_code'];
		  $country_code = $field_address[0]['country_code'];
		  $sorting_code = $field_address[0]['sorting_code'];

		  //grid content
		  $grid_output .= '<div class="distributor-item" id="distributor-item-'.$nid.'">';
		  if(!empty($title_build)) {
		    $grid_output .= '<div class="title"><a id="distributor-title-'.$nid.'" href="#" data-lat="'.$field_location[0]['lat'].'" data-lng="'.$field_location[0]['lng'].'">'.$title_build.'</a></div>';
		  }

		  if(!empty($address1)) {
		    $grid_output .= '<div class="address1">'.$address1.'</div>';
		  }
		  if(!empty($address2)) {
		    $grid_output .= '<div class="address2">'.$address2.'</div>';
		  }
		  if(!empty($city) || !empty($state) || !empty($postal_code)) {
		    $grid_output .= '<div class="city-state">'.$city.', '.$state.' '.$postal_code.'</div>';
		  }
		  if(!empty($country_code)) {
		    $grid_output .= '<div class="country_code">'.$country_code.'</div>';
		  }
		  if(!empty($phone)) {
		    $grid_output .= '<div class="phone"><a href="tel:' . str_replace(' ', '', $phone) .'"><span>'.$phone.'</span></a></div>';
		  }
		  /*
		  if(!empty($fax)) {
		    $grid_output .= '	<div class="fax">'.$this->t('F.').$fax.'</div>';
		  }
		  */
		  if(!empty($email)) {
		  	$grid_output .= '<div class="email"><a href="mailto:' . $email . '"><span>'.$email.'</span></a></div>';
		  }
		  if(!empty($website)) {
		  	$grid_output .= '<div class="website"><a href="' . $website . '" target="_blank"><span>'.t('Visit website').'</span></a></div>';
		  }
		  $grid_output .= '</div>';

		  //$marker_content = '<h2 class="location-name">'.$title_build.'</h2><br>';
		  $marker_content = '<div class="readmore"><a id="distributor-link-'.$nid.'" href="#" data-lat="'.$field_location[0]['lat'].'" data-lng="'.$field_location[0]['lat'].'">'.$this->t('Read more').'</a></div>';
		  /*
		  if(!empty($address1)) {
		    $marker_content .= $address1.'<br>';
		  }
		  if(!empty($address2)) {
		    $marker_content .= $address2.'<br>';
		  }
		  $marker_content .= $city.', '.$state.' '.$postal_code.'<br>';
		  $marker_content .= $country_code;
		  */

		  $mapData[] = [
			'location_name' => '<h3 class="location-name">'.$title_build.'</h3>',
			'location_address' => Markup::create($marker_content),
			'lat' => $field_location[0]['lat'],
			'long' => $field_location[0]['lng'],
		  ];

		  //set the first address as center
    	  if($is_centre_set == 0) {
    		$centre_lat = $field_location[0]['lat'];
    		$centre_lng = $field_location[0]['lng'];
    		$is_centre_set = 1;
    	  }
		}

		//get the centre point
		$lat = $centre_lat;
		$long = $centre_lng;

		$build = [
		  '#theme' => 'distributors_googlemap_results',
		  '#map_height' => $map_height,
		  '#map_width' => $map_width,
		  '#attached' => [
			'library' => [
			  'distributors/googlemap',
			],
			'drupalSettings' => [
			  'api_key' => $api_key,
			  'all_address' => $mapData,
			  'map_zoom_level' => $map_zoom_level,
			  'lat' => $lat,
			  'long' => $long,
			],
		  ],
		  '#cache' => ['max-age' => 0],
		];
      }
	  $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-no-results-wrapper', 'html' , array('')));
	  $ajax_response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#distributors-search-results-wrapper', $build));
	  $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-list-grid-wrapper', 'html' , array($grid_output)));
	  $ajax_response->addCommand(new SettingsCommand([
		   'mapData' => json_encode($mapData),
	  ], TRUE));

      if(!$count) {
        $content = '<div class="distributors-no-results-message"><i class="fa fa-exclamation-triangle"></i>&nbsp;&nbsp;'.$this->t('No Results Found.').'</div>';
	    $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-no-results-wrapper', 'html' , array($content)));
	    $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-results-wrapper', 'html' , array('')));
      }
    }
    else {
      $content = '<div class="distributors-no-results-message"><i class="fa fa-exclamation-triangle"></i>&nbsp;&nbsp;'.$this->t('Please enter your address and search again.').'</div>';
	  $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-no-results-wrapper', 'html' , array($content)));
	  $ajax_response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#distributors-search-results-wrapper', 'html' , array('')));
    }

	// Return the AjaxResponse Object.
	return $ajax_response;
  }


}
