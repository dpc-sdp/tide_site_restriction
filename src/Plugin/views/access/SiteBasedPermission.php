<?php

namespace Drupal\tide_site_restriction\Plugin\views\access;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\Plugin\views\access\Permission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides site based permission access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "site_based_permission",
 *   title = @Translation("Site based permission"),
 *   help = @Translation("Access will be granted to users with 2 layers permission.")
 * )
 */
class SiteBasedPermission extends Permission {

  /**
   * The site helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, Helper $helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $permission_handler, $module_handler);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $container->get('tide_site_restriction.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if ($this->helper->canBypassRestriction($account)) {
      return parent::access($account);
    }
    $sites = array_keys(array_filter($this->options['sites']));
    if (empty($sites)) {
      return parent::access($account);
    }
    $user_sites = $this->helper->getUserSites(User::load($account->id()));
    $assigned_site = array_intersect_key($user_sites, array_flip($sites));
    return $account->hasPermission($this->options['perm']) && !empty($assigned_site);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('sites', 0, 1);
    $options = [];
    foreach ($tree as $item) {
      $options[$item->tid] = $item->name;
    }
    $form['sites'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Sites'),
      '#default_value' => $this->options['sites'],
      '#description' => $this->t('Only users with the selected site(s) will be able to access this display.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', 'tide_site_restriction.site_access_route_check:viewsAccess');
    $route->setOption('_views_sites', $this->options['sites']);
    $route->setOption('_views_permission', $this->options['perm']);
  }

}
