<?php

namespace Drupal\tide_site_restriction\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Access\NodeRevisionAccessCheck;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;

/**
 * Provides a custom checkAccess for node revisions based on sites.
 *
 * @ingroup node_access
 */
class RevisionAccessCheck extends NodeRevisionAccessCheck {

  /**
   * Tide site restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Helper $helper) {
    parent::__construct($entity_type_manager);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(NodeInterface $node, AccountInterface $account, $op = 'view') {
    $parent = parent::checkAccess($node, $account, $op);
    // If the user can bypass the permissions check, returns its parent result.
    if ($this->helper->canBypassRestriction($account)) {
      return $parent;
    }
    $user_sites = $this->helper->getUserSites(User::load($account->id()));
    // If the user has permission, returns its parent result.
    if ($this->helper->hasEntitySitesAccess($node, $user_sites)) {
      return $parent;
    }
    // Otherwise, returns false.
    return FALSE;
  }

}
