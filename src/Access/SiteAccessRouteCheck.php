<?php

namespace Drupal\tide_site_restriction\Access;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

/**
 * Checks access for routers when tide_site_restriction module gets enabled.
 */
class SiteAccessRouteCheck implements AccessInterface {

  /**
   * Tide site restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * Constructs a new SiteAccessRouteCheck.
   *
   * @param \Drupal\tide_site_restriction\Helper $helper
   *   Tide site restriction helper.
   */
  public function __construct(Helper $helper) {
    $this->helper = $helper;
  }

  /**
   * Check if the user can access the route attached to a node.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A result.
   */
  public function access(AccountInterface $account, NodeInterface $node) {
    if ($this->helper->canBypassRestriction($account)) {
      return AccessResult::allowed()
        ->addCacheableDependency($node)
        ->addCacheTags(['site_restriction'])
        ->cachePerUser();
    }
    // Computes the result.
    $result = tide_site_compute_access($account, $node, $this->helper);
    if (!$result) {
      throw new AccessDeniedHttpException();
    }
    // Abstaining will generate 'denies access'.
    if ($result instanceof AccessResultNeutral) {
      return AccessResult::allowed()
        ->addCacheableDependency($node)
        ->addCacheTags(['site_restriction'])
        ->cachePerUser();
    }
    return $result;
  }

  /**
   * Check if the user can access the route attached to a view.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Symfony\Component\Routing\Route $route
   *   The node.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A result.
   */
  public function viewsAccess(AccountInterface $account, Route $route) {
    if ($this->helper->canBypassRestriction($account)) {
      return AccessResult::allowed();
    }
    $permission = $route->getOption('_views_permission');
    $sites = array_keys(array_filter($route->getOption('_views_sites')));
    if (empty($sites)) {
      return AccessResult::neutral();
    }
    $user_sites = $this->helper->getUserSites(User::load($account->id()));
    $assigned_site = array_intersect_key($user_sites, array_flip($sites));
    if ($account->hasPermission($permission) && !empty($assigned_site)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
