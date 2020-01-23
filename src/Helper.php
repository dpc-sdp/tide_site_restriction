<?php

namespace Drupal\tide_site_restriction;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\tide_site\TideSiteHelper;
use Drupal\user\UserInterface;

/**
 * Class Helper.
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
    $this->supportedEntityTypes[] = 'user';
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
   * @param \Drupal\node\NodeInterface $node
   *   The node passed in.
   * @param array $user_sites
   *   The sites that assigned to an user.
   *
   * @return array|mixed
   *   Return TRUE or FALSE.
   */
  public function hasRestrictionSites(NodeInterface $node, array $user_sites) {
    if (empty($user_sites)) {
      return TRUE;
    }
    $field_names = ['field_node_primary_site', 'field_node_site'];
    foreach ($field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $values = $node->get($field_name)->getValue();
        $site_ids = array_column($values, 'target_id');
        if (count(array_intersect($site_ids, $user_sites)) > 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
