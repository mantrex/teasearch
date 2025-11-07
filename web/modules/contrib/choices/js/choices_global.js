/**
 * @file
 * Attaches behaviors for the Choices module.
 */
/* global Choices */
// eslint-disable-next-line func-names
(function (Drupal, Choices) {
  Drupal.behaviors.choices = {
    /**
     * Drupal attach behavior.
     */
    attach(context, settings) {
      let selector = settings.choices.global.cssSelector;
      if (settings.choices.facets && settings.choices.facets.hasFacetsWidget) {
        // Also initialize on .js-facets-choices
        selector += ',.js-facets-choices';
      }
      if (!selector.length) {
        return;
      }
      let selects = context.querySelectorAll(selector);
      // Exclude .field--widget-choices-widget, which has its own implementation.
      // Exclude select inputs that are part of Drupal core table drag rows.
      selects = [...selects].filter((element) => {
        return (
          !element.closest('.field--widget-choices-widget') &&
          !element.parentNode.closest('.draggable')
        );
      });
      if (selects.length > 0) {
        let configurationOptions = {};
        // If choices widget configuration_options is set use them:
        if (
          settings.choices.global &&
          settings.choices.global.configurationOptions
        ) {
          configurationOptions = settings.choices.global.configurationOptions;
        }
        selects.forEach((select) => {
          // eslint-disable-next-line no-new
          new Choices(select, configurationOptions);
        });
      }
    },
  };
})(Drupal, Choices);
