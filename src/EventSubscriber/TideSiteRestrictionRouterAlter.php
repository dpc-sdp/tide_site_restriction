<?php

namespace Drupal\tide_site_restriction\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class tide_site_restriction router alter.
 *
 * @package Drupal\tide_site_restriction\EventSubscriber
 */
class TideSiteRestrictionRouterAlter extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.entity_hierarchy_reorder')) {
      $route->setRequirement('_site_access_route_check', 'TRUE');
    }
    if ($route = $collection->get('quick_node_clone.node.quick_clone')) {
      $route->setRequirement('_site_access_route_check', 'TRUE');
    }
  }

}
