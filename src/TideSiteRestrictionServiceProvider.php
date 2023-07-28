<?php

namespace Drupal\tide_site_restriction;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class tide_site_restriction service provider.
 *
 * Altering Drupal Core behavior.
 *
 * @package Drupal\tide_site_restriction
 */
class TideSiteRestrictionServiceProvider extends ServiceProviderBase implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('content_moderation.state_transition_validation');
    $definition->setClass('Drupal\tide_site_restriction\Access\ModerationStateAccess')
      ->addArgument(new Reference('tide_site_restriction.helper'));

    $definition = $container->getDefinition('node.revision_access');
    $definition->setClass('Drupal\tide_site_restriction\Access\RevisionAccessCheck')
      ->setArguments([
        new Reference('entity_type.manager'),
        new Reference('tide_site_restriction.helper'),
      ]);
  }

}
