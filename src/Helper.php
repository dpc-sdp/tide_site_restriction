<?php

namespace Drupal\tide_site_restriction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tide_site\TideSiteHelper;
use Drupal\user\UserInterface;

/**
 * Class Helper for tide_site_restriction.
 *
 * @package Drupal\tide_site_restriction
 */
class Helper extends TideSiteHelper {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, AccountProxyInterface $current_user) {
    parent::__construct($entity_type_manager, $entity_repository);
    $this->currentUser = $current_user;
  }

  /**
   * Returns the Sites of a user account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The User entity object.
   * @param bool $reset
   *   Whether to reset cache.
   *
   * @return array
   *   The sites.
   */
  public function getUserSites(UserInterface $user, $reset = FALSE) {
    $sites = [];
    $field_name = 'field_user_site';

    // Only process if the entity has Site field.
    if ($user->hasField($field_name)) {
      $field_site = $user->get($field_name);
      // Only process if its Site field has values.
      if (!$field_site->isEmpty()) {
        foreach ($field_site->getValue() as $value) {
          $sites[$value['target_id']] = $value['target_id'];
        }
      }
    }

    return $sites;
  }

  /**
   * Check if a user account can bypass the site restriction.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account, default to current user.
   *
   * @return bool
   *   Whether the account can bypass site restriction.
   */
  public function canBypassRestriction(AccountInterface $account = NULL) {
    if (!$account) {
      $account = $this->currentUser;
    }

    return $account->hasPermission('bypass node access')
      || $account->hasPermission('administer nodes')
      || $account->hasPermission('bypass site restriction');
  }

  /**
   * Check if the user has node's sites.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Node or Media.
   * @param array $user_sites
   *   User's sites.
   *
   * @return bool
   *   True or FALSE
   */
  public function hasEntitySitesAccess(EntityInterface $entity, array $user_sites) {
    if (empty($user_sites)) {
      // Get the current user roles.
      $user_roles = ($this->currentUser->id()) ? User::load($this->currentUser->id())->getRoles() : '';
      if (!empty($user_roles)) {
        // Administrator role can bypass the restriction.
        return (in_array('administrator', $user_roles));
      }
      return FALSE;
    }
    $field_names = $this->getSiteFieldsName();
    foreach ($field_names as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $values = $entity->get($field_name)->getValue();
        $site_ids = array_column($values, 'target_id');
        if (count(array_intersect($site_ids, $user_sites)) > 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Returns site field names.
   *
   * @return array
   *   Array.
   */
  public function getSiteFieldsName() {
    return ['field_node_site', 'field_node_primary_site', 'field_media_site'];
  }

  /**
   * Build user's site trail.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array
   *   Sites array or empty array.
   */
  public function getUserSitesTrail(UserInterface $user) {
    $user_sites = $this->getUserSites($user);
    $result = [];
    foreach ($user_sites as $site) {
      $trail = $this->getSiteTrail($site);
      $parent_id = reset($trail);
      $result[$parent_id] = $parent_id;
      $result[$site] = $site;
    }
    return $result;
  }

}
