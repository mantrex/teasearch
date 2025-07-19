/**
 * @file
 * Views behaviors for Multiselect Dropdown.
 */

'use strict';

import MultiselectDropdowns from './multiselect-dropdowns.js';

((Drupal, once, dropdowns) => {
  /**
   * Submit the view directly from checkboxes.
   */
  Drupal.behaviors.multiselectDropdownSubmitCheckboxes = {
    attach: (context) => {
      once(
        'multiselect-dropdown-submit-checkboxes',
        dropdowns.getInstances(context),
      ).forEach((multiselect) => {
        MultiselectDropdowns.getCheckboxes(multiselect).forEach((checkbox) => {
          checkbox.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              if (!event.shiftKey) {
                dropdowns.persistOnLoad(multiselect);
              } else {
                dropdowns.doNotPersistOnLoad(multiselect);
              }
              dropdowns.setFocus(event.shiftKey ? null : event.target);
              dropdowns.submit(multiselect);
            }
          });
        });
      });
    },
  };

  /**
   * Submit the multiselect dropdown item values.
   */
  Drupal.behaviors.multiselectDropdownSubmitButton = {
    attach: (context) => {
      once(
        'multiselect-dropdown-submit-button',
        dropdowns.getInstances(context),
      ).forEach((multiselect) => {
        const submitButton = dropdowns.getSubmit(multiselect);
        if (submitButton) {
          submitButton.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              if (!event.shiftKey) {
                dropdowns.persistOnLoad(multiselect);
              } else {
                dropdowns.doNotPersistOnLoad(multiselect);
              }
              dropdowns.setFocus(event.shiftKey ? null : event.target);
              dropdowns.submit(multiselect);
            }
          });
        }
      });
    },
  };

  /**
   * Clear a multiselect dropdown and submit the form.
   */
  Drupal.behaviors.multiselectDropdownClearButton = {
    attach: (context) => {
      once(
        'multiselect-dropdown-clear-button',
        dropdowns.getInstances(context),
      ).forEach((multiselect) => {
        const clearButton = dropdowns.getClear(multiselect);
        if (clearButton) {
          clearButton.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              MultiselectDropdowns.getCheckboxes(multiselect).forEach(
                (checkbox) => {
                  checkbox.checked = false;
                },
              );
              if (!event.shiftKey) {
                dropdowns.persistOnLoad(multiselect);
              } else {
                dropdowns.doNotPersistOnLoad(multiselect);
              }
              dropdowns.setFocus(event.shiftKey ? null : event.target);
              dropdowns.submit(multiselect);
            }
          });
        }
      });
    },
  };

  /**
   * Open a multiselect dropdown on AJAX load.
   */
  Drupal.behaviors.multiselectDropdownLoadOpen = {
    attach: (context) => {
      once(
        'multiselect-dropdown-load-open',
        dropdowns.getInstances(context),
      ).forEach((multiselect) => {
        if (dropdowns.isPersistedOnLoad(multiselect)) {
          const searchInput = dropdowns.getSearchInput(multiselect);
          if (searchInput) {
            searchInput.value = dropdowns.getSearch(multiselect);
            dropdowns.filter(multiselect);
          }
          dropdowns.open(multiselect, !!dropdowns.getFocus());
        }
      });
    },
  };

  /**
   * Focus the multiselect dropdown on AJAX load.
   */
  Drupal.behaviors.multiselectDropdownLoadFocus = {
    attach: (context) => {
      if (
        once('multiselect-dropdown-load-focus', 'form', context) &&
        dropdowns.getFocus()
      ) {
        const focus = document.querySelector(dropdowns.getFocus());
        if (focus) {
          focus.setAttribute('tabindex', '0');
          focus.focus({ preventScroll: true });
        }
        dropdowns.setFocus();
      }
    },
  };
})(Drupal, once, window.MultiselectDropdowns);
