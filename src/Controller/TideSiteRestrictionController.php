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
    if ($user->field_user_site->isEmpty()) {
      return ['#markup' => t('No sites assigned.')];
    }
    $build = $user->field_user_site->view();
    $build['#title'] = t('Site permissions');
    return $build;
  }

}
