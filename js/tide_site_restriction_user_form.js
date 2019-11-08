/**
 * @file
 * tide_site_restriction_user_form.js
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.tide_site_restriction_user_form = {
    attach: function (context, settings) {
      $(function () {
        $('[data-check]').on('click', function (e) {
          var checked = $(this).prop('checked');
          var id = $(this).data('check');
          $('[data-parent="' + id + '"]').prop('checked', checked);
        });

        $('[data-parent]').on('click', function (e) {
          var is_checked = $(this).prop('checked');
          var checked_id = $(this).data('parent');
          if (!is_checked) {
            $('[data-check="' + checked_id + '"]').prop('checked', false);
          }
        });
      });
    }
  };
}(jQuery, Drupal));
