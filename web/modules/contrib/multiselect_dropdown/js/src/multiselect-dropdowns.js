/**
 * @file
 * Object for interacting with multiselect dropdowns and tracking state.
 */

'use strict';

export default class MultiselectDropdowns {
  /**
   * Multiselect dropdown data attribute base.
   *
   * @type {String}
   */
  #attribute = 'data-multiselect-dropdown';

  /**
   * Multiselect dropdown selector.
   *
   * @type {String}
   */
  #selector = `[${this.#attribute}]`;

  /**
   * Drupal data selector attribute.
   *
   * @type {String}
   */
  #drupalAttribute = 'data-drupal-selector';

  /**
   * Opened multiselect dropdown dialogs.
   *
   * @type {Array}
   */
  #opened = [];

  /**
   * Multiselect dropdown dialogs to open on load.
   *
   * @type {Array}
   */
  #persistedOnLoad = [];

  /**
   * Selector to focus on AJAX load.
   *
   * @type {String}
   */
  #focus = '';

  /**
   * Focus should happen on load.
   *
   * @type {Boolean}
   */
  #shouldFocus = false;

  /**
   * Search text to add on AJAX load.
   *
   * @type {Map}
   */
  #search = new Map();

  /**
   * The type of modal.
   *
   * Values map to the ModalType enum.
   *
   * @type {{breakpoint: String, dialog: String, modal: String}}
   */
  #type = {
    breakpoint: 'breakpoint',
    dialog: 'dialog',
    modal: 'modal',
  };

  /**
   * Get the ID of a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {String}
   */
  getId(multiselect) {
    return multiselect.getAttribute(this.#drupalAttribute);
  }

  /**
   * Generate a unique selector for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {String}
   */
  getSelector(multiselect) {
    return `[${this.#drupalAttribute}="${this.getId(multiselect)}"]`;
  }

  /**
   * Get the toggle button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getToggle(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-toggle]`);
  }

  /**
   * Get the dialog for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLDialogElement|null}
   */
  getDialog(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-dialog]`);
  }

  /**
   * Get the dialog close button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLDialogElement|null}
   */
  getDialogClose(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-dialog-close]`);
  }

  /**
   * Get the select all button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getSelectAll(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-select-all]`);
  }

  /**
   * Get the select none button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getSelectNone(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-select-none]`);
  }

  /**
   * Get the search input for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @returns {HTMLInputElement|null}
   */
  getSearchInput(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-search]`);
  }

  /**
   * Get the checkbox list for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLUListElement|null}
   */
  getList(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-list]`);
  }

  /**
   * Get the checkbox list items for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {NodeListOf<HTMLLIElement>}
   */
  getListItems(multiselect) {
    return this.getList(multiselect).querySelectorAll('li');
  }

  /**
   * Get the submit button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getSubmit(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-submit]`);
  }

  /**
   * Get the clear button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getClear(multiselect) {
    return multiselect.querySelector(`[${this.#attribute}-clear]`);
  }

  /**
   * Get the multiselect dropdown checkboxes.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {NodeListOf<Element|HTMLInputElement>}
   */
  static getCheckboxes(multiselect) {
    return multiselect.querySelectorAll('[type="checkbox"]');
  }

  /**
   * Get the checked multiselect dropdown checkboxes.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {NodeListOf<Element|HTMLInputElement>}
   */
  static getCheckboxesChecked(multiselect) {
    return multiselect.querySelectorAll('[type="checkbox"]:checked');
  }

  /**
   * Get the unchecked multiselect dropdown checkboxes.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {NodeListOf<Element|HTMLInputElement>}
   */
  static getCheckboxesUnchecked(multiselect) {
    return multiselect.querySelectorAll('[type="checkbox"]:not(:checked)');
  }

  /**
   * Get the breakpoint of a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Number}
   */
  getBreakpoint(multiselect) {
    const breakpoint = multiselect.getAttribute(
      'data-multiselect-dropdown-breakpoint',
    );
    switch (breakpoint) {
      case this.#type.dialog:
        return 0;
      case this.#type.modal:
        return 99999999;
      default:
        return Number.parseInt(breakpoint, 10);
    }
  }

  /**
   * Get all multiselect dropdown instances contained in an HTML element.
   *
   * @param {Document|HTMLElement} html
   *
   * @return {NodeListOf<Element|HTMLElement>|HTMLElement[]}
   */
  getInstances(html) {
    if (html instanceof HTMLElement && html.matches(this.#selector)) {
      return [html];
    }
    return html.querySelectorAll(this.#selector);
  }

  /**
   * Get a multiselect from an element contained in the multiselect.
   *
   * @param {Element|HTMLElement} element
   *
   * @return {Element|HTMLElement|null}
   */
  getInstance(element) {
    return element.closest(this.#selector);
  }

  /**
   * Set an element to focus on.
   *
   * @param {HTMLElement|null} element
   */
  setFocus(element) {
    this.#shouldFocus = true;
    this.#focus = element
      ? `[data-drupal-selector="${element.getAttribute('data-drupal-selector')}"]`
      : '';
  }

  /**
   * Get the focus selector.
   *
   * If no focus selector is set, focus the view results.
   *
   * @return {String}
   */
  getFocus() {
    if (!this.#shouldFocus) {
      return '';
    }
    return this.#focus
      ? this.#focus
      : '.view-content, .view-empty, [data-multiselect-dropdown-view-results]';
  }

  /**
   * Set the search text for a multiselect.
   *
   * @param {HTMLElement} multiselect
   * @param {String} value
   */
  setSearch(multiselect, value) {
    if (value) {
      this.#search.set(this.getId(multiselect), value);
    } else {
      this.#search.delete(this.getId(multiselect));
    }
  }

  /**
   * Get the search text for a multiselect.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {String}
   */
  getSearch(multiselect) {
    const value = this.#search.get(this.getId(multiselect));
    return value === undefined ? '' : value;
  }

  /**
   * Check if an element is a multiselect dropdown.
   *
   * @param {HTMLElement} element
   *
   * @return {Boolean}
   */
  isMultiselect(element) {
    return element.hasAttribute(this.#attribute);
  }

  /**
   * Check if a multiselect dropdown is open.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Boolean}
   */
  isOpen(multiselect) {
    return this.#opened.includes(this.getId(multiselect));
  }

  /**
   * Open a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   * @param {Boolean} userTriggered
   */
  open(multiselect, userTriggered) {
    const dialog = this.getDialog(multiselect);
    if (!dialog) {
      throw new Error(
        'Multiselect does not contain an HTMLDialogElement with the attribute data-multiselect-dropdown-dialog.',
      );
    }

    // When opening a dialog using HTMLDialogElement.show() focus is set to the
    // element with the autofocus attribute set, or the first focusable element
    // found in the dialog. There exist some non-user triggered openings, for
    // instance re-opening a dialog after AJAX load. In this case the user may
    // have submitted a form from a different element and did not intend for any
    // dialog to receive focus after AJAX completes.
    if (window.innerWidth <= this.getBreakpoint(multiselect)) {
      dialog.showModal();
    } else if (userTriggered) {
      dialog.show();
    } else {
      dialog.open = true;
    }

    multiselect.setAttribute('data-multiselect-dropdown-open', '');
    const toggle = this.getToggle(multiselect);
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
    }

    dialog.addEventListener('close', () => {
      this.close(multiselect);
    });

    if (!this.isOpen(multiselect)) {
      this.#opened.push(this.getId(multiselect));
    }

    if (this.shouldOpenOnLoad(multiselect)) {
      this.persistOnLoad(multiselect);
    }
  }

  /**
   * Close a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   */
  close(multiselect) {
    const dialog = this.getDialog(multiselect);
    if (!dialog) {
      throw new Error(
        'Multiselect does not contain an HTMLDialogElement with the attribute data-multiselect-dropdown-dialog.',
      );
    }

    dialog.close();

    multiselect.removeAttribute('data-multiselect-dropdown-open');
    const toggle = this.getToggle(multiselect);
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (toggle && document.activeElement === this.getList(multiselect)) {
      toggle.focus({ preventScroll: true });
    }

    if (this.isOpen(multiselect)) {
      this.#opened.splice(this.#opened.indexOf(this.getId(multiselect)), 1);
    }

    this.doNotPersistOnLoad(multiselect);
  }

  /**
   * Switch the multiselect dropdown open state.
   *
   * @param {HTMLElement} multiselect
   * @param {Boolean} userTriggered
   */
  toggle(multiselect, userTriggered) {
    if (this.isOpen(multiselect)) {
      this.close(multiselect);
    } else {
      this.open(multiselect, userTriggered);
    }
  }

  /**
   * Check if a multiselect should open on load.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Boolean}
   */
  shouldOpenOnLoad(multiselect) {
    return multiselect.hasAttribute(`${this.#attribute}-persist-open`);
  }

  /**
   * Check if a multiselect is marked as open on load.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Boolean}
   */
  isPersistedOnLoad(multiselect) {
    return this.#persistedOnLoad.includes(this.getId(multiselect));
  }

  /**
   * Open a multiselect on load.
   *
   * @param {HTMLElement} multiselect
   */
  persistOnLoad(multiselect) {
    if (!this.isPersistedOnLoad(multiselect)) {
      this.#persistedOnLoad.push(this.getId(multiselect));
    }
  }

  /**
   * Do not open a multiselect on load.
   *
   * @param {HTMLElement} multiselect
   */
  doNotPersistOnLoad(multiselect) {
    if (this.isPersistedOnLoad(multiselect)) {
      this.#persistedOnLoad.splice(
        this.#persistedOnLoad.indexOf(this.getId(multiselect)),
        1,
      );
      this.setSearch(multiselect, '');
    }
  }

  /**
   * Update the multiselect dropdown toggle label.
   *
   * @param {HTMLElement} multiselect
   */
  updateLabel(multiselect) {
    let attribute;

    const count =
      MultiselectDropdowns.getCheckboxesChecked(multiselect)?.length ?? 0;
    switch (count) {
      case 0:
        attribute = `${this.#attribute}-label-none`;
        break;

      case 1:
        attribute = `${this.#attribute}-label-single`;
        break;

      case MultiselectDropdowns.getCheckboxes(multiselect).length:
        attribute = `${this.#attribute}-label-all`;
        break;

      default:
        attribute = `${this.#attribute}-label-plural`;
        break;
    }

    this.getToggle(multiselect).innerText = (
      multiselect.getAttribute(attribute) ?? ''
    ).replace(/%d/, count.toString());
  }

  /**
   * Submit a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   */
  submit(multiselect) {
    const submitButton = multiselect
      .closest('form')
      .querySelector('[type="submit"]');
    if (submitButton) {
      submitButton.click();
    } else {
      multiselect.closest('form').requestSubmit();
    }
    this.#opened = [];
  }

  /**
   * Filter a multiselect's items.
   *
   * @param {HTMLElement} multiselect
   */
  filter(multiselect) {
    const searchInput = this.getSearchInput(multiselect);
    const input = searchInput.value.toLowerCase();
    const threshold = searchInput.getAttribute(
      `${this.#attribute}-search-character-threshold`,
    );
    this.setSearch(multiselect, searchInput.value);
    this.getListItems(multiselect).forEach((item) => {
      if (input.length < threshold) {
        item.style.display = 'initial';
      } else {
        const match =
          item.querySelector('label').innerText.toLowerCase().indexOf(input) ===
          -1;
        item.style.display = match ? 'none' : 'initial';
      }
    });
  }
}
