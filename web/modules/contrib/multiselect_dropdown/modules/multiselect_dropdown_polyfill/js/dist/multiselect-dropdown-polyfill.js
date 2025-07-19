/**
 * @file
 * Behaviors for the Multiselect Dropdowns dialog-polyfill.
 */

'use strict';

import dialogPolyfill from '../../node_modules/dialog-polyfill/dist/dialog-polyfill.esm.js';
((Drupal, once, dropdowns, dialogPolyfill) => {
  /**
   * Register the multiselect dropdowns with the polyfill.
   */
  Drupal.behaviors.multiselectDropdownPolyfill = {
    attach: context => {
      once('multiselect-dropdown-dialog-polyfill', dropdowns.getInstances(context)).forEach(multiselect => {
        dialogPolyfill.registerDialog(dropdowns.getDialog(multiselect));
      });
    }
  };
})(Drupal, once, window.MultiselectDropdowns, dialogPolyfill);