<?php
/**
* @file
* Contains \Drupal\distributors\Plugin\Block\DistributorsSearchBlock.
*/

namespace Drupal\distributors\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query;
use Drupal\Core\Form\FormBuilderInterface;


/**
* Distributors: Search Block.
*
* @Block(
* id = "distributors_search_block",
* admin_label = @Translation("Distributors: Search Block"),
* category = @Translation("Blocks")
* )
*/
class DistributorsSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The form_builder Service.
   *
   * @var FormBuilder $formBuilder
   */
  protected $formBuilder;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param QueryFactory $query_factory
   * @param EntityManagerInterface $manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $query_factory, EntityManagerInterface $manager, AccountInterface $current_account, Connection $database, FormBuilderInterface $formBuilder) {
    //Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queryFactory = $query_factory;
    $this->manager = $manager;
    $this->account = $current_account;
	$this->database = $database;
	$this->formBuilder = $formBuilder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
	  $plugin_id,
      $plugin_definition,
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('current_user'),
	  $container->get('database'),
	  $container->get('form_builder')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = false) {
	if($account->hasPermission('access content')) {
	  return AccessResult::allowed();
	}
	return AccessResult::forbidden();
  }


  /**
  * {@inheritdoc}
  */
  public function build() {

	//get the search form
    $build['search_form'] = $this->formBuilder->getForm('Drupal\distrbutors\Form\DistributorsSearchForm');

    //don't cache
    \Drupal::service('page_cache_kill_switch')->trigger();

	return $build;
  }

}
