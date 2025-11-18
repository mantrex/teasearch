/**
 * @file
 * Attaches behaviors for the Choices module on choices_widgets.
 */
/* global Choices */
// eslint-disable-next-line func-names
(function (Drupal, Choices) {
  Drupal.behaviors.choices_widget = {
    /**
     * Drupal attach behavior.
     */
    attach(context, settings) {
      if (settings.choices.widget.fields) {
        const choicesWidgetFields = settings.choices.widget.fields;
        Object.entries(choicesWidgetFields).forEach(([fieldName, value]) => {
          const selects = context.querySelectorAll(
            `select[name="${fieldName}"],select[name="${fieldName}[]"]`,
          );
          if (selects.length > 0) {
            let configurationOptions = {};
            // If choices widget configuration_options is set use them:
            if (value.configurationOptions) {
              configurationOptions = value.configurationOptions;
            }
            selects.forEach((select) => {
              // eslint-disable-next-line no-new
              new Choices(select, configurationOptions);
            });
          }
        });
      }
    },
  };
})(Drupal, Choices);
