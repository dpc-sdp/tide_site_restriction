/**
 * @file
 * tide_site_restriction_user_form.js
 */

(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.tide_site_restriction_user_form = {
    attach: function (context, settings) {
      var roles = settings.tide_site_restriction_roles;
      for (var i = 0; i < roles.length; i++) {
        switch (roles[i]) {
          case "editor":
            $(function () {
              $('[data-check]').each(function () {
                $(this).attr('disabled', true);
              });
              $('[data-check]').on('click', function (e) {
                e.preventDefault();
              });
              $('[data-parent]').on('click', function (e) {
                var is_checked = $(this).prop('checked');
                var checked_id = $(this).data('parent');
                if (is_checked) {
                  $(this).parent().siblings('.form-item').find(':checkbox').prop('checked', false);
                  $('[data-check="' + checked_id + '"]').prop('checked', true);
                }
                else {
                  if ($('[data-parent="' + checked_id + '"]:checked').length == 0) {
                    $('[data-check="' + checked_id + '"]').prop('checked', false);
                  }
                }
              });
            });
            break;
        }
      }
      // Submit disabled options.
      $('form').bind('submit', function () {
        var $inputs = $(this).find(':input'),
            disabledInputs = [],
            $curInput;
        for (var i = 0; i < $inputs.length; i++) {
          $curInput = $($inputs[i]);

          if ($curInput.attr('disabled') !== undefined) {
            $curInput.removeAttr('disabled');
            disabledInputs.push(true);
          }
          else {
            disabledInputs.push(false);
          }
        }
        setTimeout(function () {
          for (var i = 0; i < $inputs.length; i++) {
            if (disabledInputs[i] === true) {
              $($inputs[i]).attr('disabled', true);
            }
          }
        }, 1);
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
