<?php

namespace Drupal\distributors\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormBuilderInterface;


/**
 * Controller routines
 */
class DistributorsController extends ControllerBase {
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
   * The Database Connection.
   *
   * @var Connection $database
   */
  protected $database;

  /**
   * The Kill Switch Service.
   *
   * @var KillSwitch $killSwitch
   */
  protected $killSwitch;


  /**
   * The form_builder Service.
   *
   * @var FormBuilder $formBuilder
   */
  protected $formBuilder;


  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $query_factory, EntityManagerInterface $manager, AccountInterface $current_account, Connection $database, KillSwitch $killSwitch, FormBuilderInterface $formBuilder) {
    $this->entity_query = $query_factory;
    $this->manager = $manager;
    $this->account = $current_account;
	$this->database = $database;
	$this->killSwitch = $killSwitch;
	$this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager'),
      $container->get('current_user'),
	  $container->get('database'),
	  $container->get('page_cache_kill_switch'),
	  $container->get('form_builder')
    );
  }


  /**
   * Message
   */
  public function message() {
	$session = new \Symfony\Component\HttpFoundation\Session\Session();
	$output = $session->get('distributors_message');

    $build = [
      '#markup' => Markup::create($output),
      '#prefix' => '<div class="distributors-message">',
      '#suffix' => '</div>',
    ];

    return $build;
  }

}