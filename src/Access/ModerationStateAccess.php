<?php

namespace Drupal\tide_site_restriction\Access;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidation;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extends StateTransitionValidation's logic.
 */
class ModerationStateAccess extends StateTransitionValidation implements ContainerInjectionInterface {

  /**
   * Tide site restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModerationInformationInterface $moderation_info, Helper $helper) {
    parent::__construct($moderation_info);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('tide_site_restriction.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $account) {
    $results = parent::getValidTransitions($entity, $account);
    $user = User::load($account->id());
    $sites = $this->helper->getUserSites($user);
    // Another wrapped logic on the top of its parent.
    return array_filter($results, function () use ($entity, $sites, $account) {
      if ($this->helper->canBypassRestriction($account)) {
        return TRUE;
      }
      // If we were in node.add page returns its parent result.
      if ($entity->isNew()) {
        return TRUE;
      }
      return $this->helper->hasEntitySitesAccess($entity, $sites);
    });
  }

}
