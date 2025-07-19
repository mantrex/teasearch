/**
 * @file
 * Behaviors for Multiselect Dropdowns.
 */

'use strict';

import MultiselectDropdowns from './multiselect-dropdowns.js';
window.MultiselectDropdowns = new MultiselectDropdowns();
((Drupal, once, dropdowns) => {
  /**
   * Open or close the multiselect dropdown when the toggle button is clicked.
   */
  Drupal.behaviors.multiselectDropdownToggle = {
    attach: context => {
      once('multiselect-dropdown-toggle', dropdowns.getInstances(context)).forEach(multiselect => {
        const toggle = dropdowns.getToggle(multiselect);
        if (toggle) {
          toggle.addEventListener('click', event => {
            event.preventDefault();
            dropdowns.toggle(multiselect, true);
          });
        }
      });
    }
  };

  /**
   * Update the multiselect dropdown toggle label when the checked items change.
   */
  Drupal.behaviors.multiselectDropdownToggleLabel = {
    attach: context => {
      once('multiselect-dropdown-label', dropdowns.getInstances(context)).forEach(multiselect => {
        MultiselectDropdowns.getCheckboxes(multiselect).forEach(checkbox => {
          // Set the number of checked items on load.
          dropdowns.updateLabel(multiselect);
          checkbox.addEventListener('change', () => {
            dropdowns.updateLabel(multiselect);
          });
        });
      });
    }
  };

  /**
   * Close the multiselect dropdown dialog when the dialog button is clicked.
   */
  Drupal.behaviors.multiselectDropdownDialogClose = {
    attach: context => {
      once('multiselect-dropdown-dialog-close', dropdowns.getInstances(context)).forEach(multiselect => {
        const close = dropdowns.getDialogClose(multiselect);
        if (close) {
          close.addEventListener('click', event => {
            event.preventDefault();
            dropdowns.close(multiselect);
          });
        }
      });
    }
  };

  /**
   * Close all multiselect dropdown dialogs when the escape key is pressed.
   */
  Drupal.behaviors.multiselectDropdownEscapeClose = {
    attach: () => {
      if (once('multiselect-dropdown-escape-close', 'html').length) {
        window.addEventListener('keydown', event => {
          if (event.key === 'Escape') {
            dropdowns.getInstances(document).forEach(multiselect => {
              dropdowns.close(multiselect);
            });
          }
        });
      }
    }
  };

  /**
   * Close a multiselect dropdown dialog when clicking outside it.
   */
  Drupal.behaviors.multiselectDropdownClickClose = {
    attach: () => {
      if (once('multiselect-dropdown-click-close', 'html').length) {
        window.addEventListener('click', event => {
          const clickedMultiselect = dropdowns.getInstance(event.target);
          dropdowns.getInstances(document).forEach(multiselect => {
            if (multiselect !== clickedMultiselect) {
              dropdowns.close(multiselect);
            }
          });
        });
      }
    }
  };

  /**
   * Clear a multiselect dropdown and submit the form.
   */
  Drupal.behaviors.multiselectDropdownClearButton = {
    attach: context => {
      once('multiselect-dropdown-clear-button', dropdowns.getInstances(context)).forEach(multiselect => {
        const clear = dropdowns.getClear(multiselect);
        if (clear) {
          clear.addEventListener('click', event => {
            event.preventDefault();
            MultiselectDropdowns.getCheckboxes(multiselect).forEach(checkbox => {
              checkbox.checked = false;
            });
            dropdowns.submit(multiselect);
          });
        }
      });
    }
  };

  /**
   * Select all multiselect dropdown items when the all button is clicked.
   */
  Drupal.behaviors.multiselectDropdownSelectAll = {
    attach: context => {
      once('multiselect-dropdown-select-all', dropdowns.getInstances(context)).forEach(multiselect => {
        const selectAll = dropdowns.getSelectAll(multiselect);
        if (selectAll) {
          const changeEvent = new Event('change');
          selectAll.addEventListener('click', () => {
            MultiselectDropdowns.getCheckboxes(multiselect).forEach(checkbox => {
              checkbox.checked = true;
              checkbox.dispatchEvent(changeEvent);
            });
          });
        }
      });
    }
  };

  /**
   * Deselect all multiselect dropdown items when the none button is clicked.
   */
  Drupal.behaviors.multiselectDropdownSelectNone = {
    attach: context => {
      once('multiselect-dropdown-select-none', dropdowns.getInstances(context)).forEach(multiselect => {
        const selectNone = dropdowns.getSelectNone(multiselect);
        if (selectNone) {
          const changeEvent = new Event('change');
          selectNone.addEventListener('click', () => {
            MultiselectDropdowns.getCheckboxes(multiselect).forEach(checkbox => {
              checkbox.checked = false;
              checkbox.dispatchEvent(changeEvent);
            });
          });
        }
      });
    }
  };

  /**
   * Search in multiselect dropdown item lists.
   */
  Drupal.behaviors.multiselectDropdownSearch = {
    attach: context => {
      once('multiselect-dropdown-search', dropdowns.getInstances(context)).forEach(multiselect => {
        const search = dropdowns.getSearchInput(multiselect);
        if (search) {
          search.addEventListener('input', () => {
            dropdowns.filter(multiselect);
          });
        }
      });
    }
  };
})(Drupal, once, window.MultiselectDropdowns);