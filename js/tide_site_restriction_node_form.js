/**
 * @file
 * tide_site_restriction_node_form.js
 */

(function($, Drupal) {
  "use strict";
  Drupal.behaviors.tide_site_restriction_node_form = {
    attach: function(context, settings) {
      $(function() {
        // Disable Primary sites to editor user.
        $("[data-check]").each(function() {
          $(this).attr("disabled", true);
        });
        $("[data-check]").on("click", function(e) {
          var checked = $(this).prop("checked");
          var id = $(this).data("check");
          $('[data-parent="' + id + '"],[data-check="' + id + '"]').prop(
            "checked",
            checked
          );
        });
        $("[data-parent]").on("click", function(e) {
          var is_checked = $(this).prop("checked");
          var checked_id = $(this).data("parent");
          if (is_checked) {
            $('[data-check="' + checked_id + '"]').prop("checked", true);
          } else {
            if ($('[data-parent="' + checked_id + '"]:checked').length == 0) {
              $('[data-check="' + checked_id + '"]').prop("checked", false);
            }
          }
        });
      });
      // Submit disabled options.
      $("form").bind("submit", function() {
        var $inputs = $(this).find(":input"),
          disabledInputs = [],
          $curInput;
        for (var i = 0; i < $inputs.length; i++) {
          $curInput = $($inputs[i]);

          if ($curInput.attr("disabled") !== undefined) {
            $curInput.removeAttr("disabled");
            disabledInputs.push(true);
          } else {
            disabledInputs.push(false);
          }
        }
        setTimeout(function() {
          for (var i = 0; i < $inputs.length; i++) {
            if (disabledInputs[i] === true) {
              $($inputs[i]).attr("disabled", true);
            }
          }
        }, 1);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
