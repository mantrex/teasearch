/**
 * @file
 * Attaches behaviors for the Choices module.
 */

(function (Drupal, Choices) {

  'use strict';

  Drupal.behaviors.choices = {

    /**
     * Drupal attach behavior.
     */
    attach: function (context, settings) {
      var selector = settings.choices.global.cssSelector;
      if (settings.choices.facets && settings.choices.facets.hasFacetsWidget) {
        // Also initialize on .js-facets-choices
        selector += ',.js-facets-choices';
      }
      if (!selector.length) {
        return;
      }
      var selects = context.querySelectorAll(selector);
      // Exclude .field--widget-choices-widget, which has its own implementation.
      // Exclude select inputs that are part of Drupal core table drag rows.
      selects = [...selects].filter(element => {
        return !element.closest('.field--widget-choices-widget') && !element.parentNode.closest('.draggable');
      });
      if (selects.length > 0) {
        var configuration_options = {};
        // If choices widget configuration_options is set use them:
        if (settings.choices.global && settings.choices.global.configurationOptions) {
          configuration_options = settings.choices.global.configurationOptions;
        }
        selects.forEach(function (select) {
          new Choices(select, configuration_options);
        });
      }
    },
  }

})(Drupal, Choices);
