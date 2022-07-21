<?php

namespace Drupal\tide_site_restriction;

/**
 * Tide site restriction modules operations.
 */
class TideSiteRestrictionOperation {

  /**
   * Adds sub_sites_filter filter.
   */
  public function addSubSitesFilter() {
    $value = [
      'id' => 'sub_sites_filter',
      'table' => 'node_field_data',
      'field' => 'sub_sites_filter',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'operator' => 'or',
      'value' => [],
      'group' => 1,
      'exposed' => TRUE,
      'expose' => [
        'operator_id' => 'sub_sites_filter_op',
        'label' => 'Sub-Sites',
        'description' => '',
        'use_operator' => FALSE,
        'operator' => 'sub_sites_filter_op',
        'operator_limit_selection' => FALSE,
        'operator_list' => [],
        'identifier' => 'sub_sites_filter',
        'required' => FALSE,
        'remember' => FALSE,
        'multiple' => TRUE,
        'remember_roles' => [
          'authenticated' => 'authenticated',
          'anonymous' => '0',
          'administrator' => '0',
          'approver' => '0',
          'site_admin' => '0',
          'editor' => '0',
          'previewer' => '0',
        ],
        'reduce' => 0,
      ],
      'is_grouped' => FALSE,
      'group_info' => [
        'label' => '',
        'description' => '',
        'identifier' => '',
        'optional' => TRUE,
        'widget' => 'select',
        'multiple' => FALSE,
        'remember' => FALSE,
        'default_group' => 'All',
        'default_group_multiple' => [],
        'group_items' => [],
      ],
      'reduce_duplicates' => 0,
      'entity_type' => 'node',
      'plugin_id' => 'sub_sites_filter',
    ];
    $view_config = \Drupal::service('config.factory')
      ->getEditable('views.view.summary_contents_filters');
    $display = $view_config->get('display');
    if (isset($display['default']['display_options']['filters']['field_node_site_target_id'])) {
      $new_filters = [];
      foreach ($display['default']['display_options']['filters'] as $key => $detail) {
        if ($key === 'field_node_site_target_id') {
          $new_filters['sub_sites_filter'] = $value;
          continue;
        }
        $new_filters[$key] = $detail;
      }
      $display['default']['display_options']['filters'] = $new_filters;
      $view_config->set('display', $display);
      $view_config->save();
    }
  }

}
