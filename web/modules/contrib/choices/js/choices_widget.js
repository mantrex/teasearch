/**
 * @file
 * Attaches behaviors for the Choices module on choices_widgets.
 */

 (function (Drupal, Choices) {

  'use strict';

  Drupal.behaviors.choices_widget = {
    /**
     * Drupal attach behavior.
     */
    attach: function (context, settings) {
      if (settings.choices.widget.fields) {
        let choicesWidgetFields = settings.choices.widget.fields;
        Object.entries(choicesWidgetFields).forEach(([fieldName, value]) => {
          let selects = context.querySelectorAll('select[name="' + fieldName + '"],select[name="' + fieldName + '[]"]');
          if (selects.length > 0) {
            let configuration_options = {};
            // If choices widget configuration_options is set use them:
            if (value.configurationOptions) {
              configuration_options = value.configurationOptions;
            }
            selects.forEach(function (select) {
              new Choices(select, configuration_options);
            });
          }
        });

      }
    }
  }

})(Drupal, Choices);
