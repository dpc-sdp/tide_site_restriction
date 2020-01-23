/**
 * @file
 * tide_site_restriction_node_form.js
 */

(function($, Drupal) {
  "use strict";
  Drupal.behaviors.tide_site_restriction_node_form = {
    attach: function (context, settings) {
      // Submit disabled options.
      $("form").bind("submit", function () {
        var $inputs = $(this).find(":input"),
          disabledInputs = [],
          $curInput;
        for (var i = 0; i < $inputs.length; i++) {
          $curInput = $($inputs[i]);

          if ($curInput.attr("disabled") !== undefined) {
            $curInput.removeAttr("disabled");
            disabledInputs.push(true);
          }
          else {
            disabledInputs.push(false);
          }
        }
        setTimeout(function () {
          for (var i = 0; i < $inputs.length; i++) {
            if (disabledInputs[i] === true) {
              $($inputs[i]).attr("disabled", true);
            }
          }
        }, 1);
      });
    }
  };
})(jQuery, Drupal);
