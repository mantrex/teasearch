/**
 * @file
 * Init Choices.js widget.
 */

(function (Drupal, document, $) {

  'use strict';

  Drupal.facets = Drupal.facets || {};

  /**
   * Add event handler to all Choices.js widgets.
   */
  Drupal.facets.initChoices = function (context, settings) {
    var facetsWidgets = context.querySelectorAll('.js-facets-choices.js-facets-widget');
    for (var i = 0; i < facetsWidgets.length; i++) {
      facetsWidgets[i].addEventListener(
          'choice',
          function (event) {
            if (!event.detail.choice.value) {
              return;
            }
            $(this).trigger('facets_filter', [event.detail.choice.value]);
          },
          false,
      );
    }
  };

  /**
   * Behavior to register select2 widget to be used for facets.
   */
  Drupal.behaviors.facetsChoicesWidget = {
    attach: function (context, settings) {
      Drupal.facets.initChoices(context, settings);
    }
  };

})(Drupal, document, jQuery);
