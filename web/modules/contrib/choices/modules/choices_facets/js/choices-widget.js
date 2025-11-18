/**
 * @file
 * Init Choices.js widget.
 */
// eslint-disable-next-line func-names
(function (Drupal, document, $) {
  Drupal.facets = Drupal.facets || {};

  /**
   * Add event handler to all Choices.js widgets.
   */
  Drupal.facets.initChoices = function initChoices(context) {
    const facetsWidgets = context.querySelectorAll(
      '.js-facets-choices.js-facets-widget',
    );
    for (let i = 0; i < facetsWidgets.length; i++) {
      facetsWidgets[i].addEventListener(
        'choice',
        function choiceListener(event) {
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
    attach(context, settings) {
      Drupal.facets.initChoices(context, settings);
    },
  };
})(Drupal, document, jQuery);
