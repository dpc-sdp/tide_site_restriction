<?php

namespace Drupal\tide_site_restriction\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Controller which serves to the tide_site_restriction module.
 */
class TideSiteRestrictionController extends ControllerBase {

  /**
   * Renders render array to tide_site_restriction.user_tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user in the router.
   *
   * @return array
   *   Render array.
   */
  public function sitesAllocationTab(UserInterface $user) {
    $helper = \Drupal::service('tide_site_restriction.helper');
    if ($helper->canBypassRestriction(\Drupal::currentUser())) {
      return ['#markup' => t('You could access all sites')];
    }
    if ($user->field_user_site->isEmpty()) {
      return ['#markup' => t('No sites assigned.')];
    }
    return $user->field_user_site->view();
  }

}
