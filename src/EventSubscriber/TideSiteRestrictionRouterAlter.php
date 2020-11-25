<?php

namespace Drupal\tide_site_restriction\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class TideSiteRestrictionRouterAlter.
 *
 * @package Drupal\tide_site_restriction\EventSubscriber
 */
class TideSiteRestrictionRouterAlter extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.entity_hierarchy_reorder')) {
      $route->setRequirement('_custom_access', '\Drupal\tide_site_restriction\EventSubscriber\TideSiteRestrictionRouterAlter::childPageAccess');
    }
    foreach (\Drupal::service('entity_type.manager')->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $collection->get("entity.$entity_type_id.clone_form")) {
        $route->setRequirement('_custom_access', '\Drupal\tide_site_restriction\EventSubscriber\TideSiteRestrictionRouterAlter::childPageAccess');
      }
    }
  }

  /**
   * Permission check for node's children pages.
   */
  public static function childPageAccess(AccountInterface $account, NodeInterface $node) {
    /** @var \Drupal\tide_site_restriction\Helper $helper */
    $helper = \Drupal::service('tide_site_restriction.helper');
    if ($helper->canBypassRestriction($account)) {
      return AccessResult::allowed()
        ->addCacheableDependency($node)
        ->addCacheTags(['site_restriction'])
        ->cachePerUser();
    }
    return AccessResult::allowedIf($helper->hasEntitySitesAccess($node, $helper->getUserSites(User::load($account->id()))))
      ->addCacheableDependency($node)
      ->addCacheTags(['site_restriction'])
      ->cachePerUser();
  }

}
