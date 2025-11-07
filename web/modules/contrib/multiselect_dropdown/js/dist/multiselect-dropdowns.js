/**
 * @file
 * Object for interacting with multiselect dropdowns and tracking state.
 */

'use strict';

function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
function _classPrivateFieldSet(s, a, r) { return s.set(_assertClassBrand(s, a), r), r; }
function _classPrivateFieldGet(s, a) { return s.get(_assertClassBrand(s, a)); }
function _assertClassBrand(e, t, n) { if ("function" == typeof e ? e === t : e.has(t)) return arguments.length < 3 ? t : n; throw new TypeError("Private element is not present on this object"); }
var _attribute = /*#__PURE__*/new WeakMap();
var _selector = /*#__PURE__*/new WeakMap();
var _drupalAttribute = /*#__PURE__*/new WeakMap();
var _opened = /*#__PURE__*/new WeakMap();
var _persistedOnLoad = /*#__PURE__*/new WeakMap();
var _focus = /*#__PURE__*/new WeakMap();
var _shouldFocus = /*#__PURE__*/new WeakMap();
var _search = /*#__PURE__*/new WeakMap();
var _type = /*#__PURE__*/new WeakMap();
export default class MultiselectDropdowns {
  constructor() {
    /**
     * Multiselect dropdown data attribute base.
     *
     * @type {String}
     */
    _classPrivateFieldInitSpec(this, _attribute, 'data-multiselect-dropdown');
    /**
     * Multiselect dropdown selector.
     *
     * @type {String}
     */
    _classPrivateFieldInitSpec(this, _selector, "[".concat(_classPrivateFieldGet(_attribute, this), "]"));
    /**
     * Drupal data selector attribute.
     *
     * @type {String}
     */
    _classPrivateFieldInitSpec(this, _drupalAttribute, 'data-drupal-selector');
    /**
     * Opened multiselect dropdown dialogs.
     *
     * @type {Array}
     */
    _classPrivateFieldInitSpec(this, _opened, []);
    /**
     * Multiselect dropdown dialogs to open on load.
     *
     * @type {Array}
     */
    _classPrivateFieldInitSpec(this, _persistedOnLoad, []);
    /**
     * Selector to focus on AJAX load.
     *
     * @type {String}
     */
    _classPrivateFieldInitSpec(this, _focus, '');
    /**
     * Focus should happen on load.
     *
     * @type {Boolean}
     */
    _classPrivateFieldInitSpec(this, _shouldFocus, false);
    /**
     * Search text to add on AJAX load.
     *
     * @type {Map}
     */
    _classPrivateFieldInitSpec(this, _search, new Map());
    /**
     * The type of modal.
     *
     * Values map to the ModalType enum.
     *
     * @type {{breakpoint: String, dialog: String, modal: String}}
     */
    _classPrivateFieldInitSpec(this, _type, {
      breakpoint: 'breakpoint',
      dialog: 'dialog',
      modal: 'modal'
    });
  }
  /**
   * Get the ID of a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {String}
   */
  getId(multiselect) {
    return multiselect.getAttribute(_classPrivateFieldGet(_drupalAttribute, this));
  }

  /**
   * Generate a unique selector for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {String}
   */
  getSelector(multiselect) {
    return "[".concat(_classPrivateFieldGet(_drupalAttribute, this), "=\"").concat(this.getId(multiselect), "\"]");
  }

  /**
   * Get the toggle button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getToggle(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-toggle]"));
  }

  /**
   * Get the dialog for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLDialogElement|null}
   */
  getDialog(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-dialog]"));
  }

  /**
   * Get the dialog close button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLDialogElement|null}
   */
  getDialogClose(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-dialog-close]"));
  }

  /**
   * Get the select all button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getSelectAll(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-select-all]"));
  }

  /**
   * Get the select none button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getSelectNone(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-select-none]"));
  }

  /**
   * Get the search input for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @returns {HTMLInputElement|null}
   */
  getSearchInput(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-search]"));
  }

  /**
   * Get the checkbox list for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLUListElement|null}
   */
  getList(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-list]"));
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
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-submit]"));
  }

  /**
   * Get the clear button for a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {HTMLButtonElement|null}
   */
  getClear(multiselect) {
    return multiselect.querySelector("[".concat(_classPrivateFieldGet(_attribute, this), "-clear]"));
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
    const breakpoint = multiselect.getAttribute('data-multiselect-dropdown-breakpoint');
    switch (breakpoint) {
      case _classPrivateFieldGet(_type, this).dialog:
        return 0;
      case _classPrivateFieldGet(_type, this).modal:
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
    if (html instanceof HTMLElement && html.matches(_classPrivateFieldGet(_selector, this))) {
      return [html];
    }
    return html.querySelectorAll(_classPrivateFieldGet(_selector, this));
  }

  /**
   * Get a multiselect from an element contained in the multiselect.
   *
   * @param {Element|HTMLElement} element
   *
   * @return {Element|HTMLElement|null}
   */
  getInstance(element) {
    return element.closest(_classPrivateFieldGet(_selector, this));
  }

  /**
   * Set an element to focus on.
   *
   * @param {HTMLElement|null} element
   */
  setFocus(element) {
    _classPrivateFieldSet(_shouldFocus, this, true);
    _classPrivateFieldSet(_focus, this, element ? "[data-drupal-selector=\"".concat(element.getAttribute('data-drupal-selector'), "\"]") : '');
  }

  /**
   * Get the focus selector.
   *
   * If no focus selector is set, focus the view results.
   *
   * @return {String}
   */
  getFocus() {
    if (!_classPrivateFieldGet(_shouldFocus, this)) {
      return '';
    }
    return _classPrivateFieldGet(_focus, this) ? _classPrivateFieldGet(_focus, this) : '.view-content, .view-empty, [data-multiselect-dropdown-view-results]';
  }

  /**
   * Set the search text for a multiselect.
   *
   * @param {HTMLElement} multiselect
   * @param {String} value
   */
  setSearch(multiselect, value) {
    if (value) {
      _classPrivateFieldGet(_search, this).set(this.getId(multiselect), value);
    } else {
      _classPrivateFieldGet(_search, this).delete(this.getId(multiselect));
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
    const value = _classPrivateFieldGet(_search, this).get(this.getId(multiselect));
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
    return element.hasAttribute(_classPrivateFieldGet(_attribute, this));
  }

  /**
   * Check if a multiselect dropdown is open.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Boolean}
   */
  isOpen(multiselect) {
    return _classPrivateFieldGet(_opened, this).includes(this.getId(multiselect));
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
      throw new Error('Multiselect does not contain an HTMLDialogElement with the attribute data-multiselect-dropdown-dialog.');
    }
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
      _classPrivateFieldGet(_opened, this).push(this.getId(multiselect));
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
      throw new Error('Multiselect does not contain an HTMLDialogElement with the attribute data-multiselect-dropdown-dialog.');
    }
    dialog.close();
    multiselect.removeAttribute('data-multiselect-dropdown-open');
    const toggle = this.getToggle(multiselect);
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (toggle && document.activeElement === this.getList(multiselect)) {
      toggle.focus({
        preventScroll: true
      });
    }
    if (this.isOpen(multiselect)) {
      _classPrivateFieldGet(_opened, this).splice(_classPrivateFieldGet(_opened, this).indexOf(this.getId(multiselect)), 1);
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
    return multiselect.hasAttribute("".concat(_classPrivateFieldGet(_attribute, this), "-persist-open"));
  }

  /**
   * Check if a multiselect is marked as open on load.
   *
   * @param {HTMLElement} multiselect
   *
   * @return {Boolean}
   */
  isPersistedOnLoad(multiselect) {
    return _classPrivateFieldGet(_persistedOnLoad, this).includes(this.getId(multiselect));
  }

  /**
   * Open a multiselect on load.
   *
   * @param {HTMLElement} multiselect
   */
  persistOnLoad(multiselect) {
    if (!this.isPersistedOnLoad(multiselect)) {
      _classPrivateFieldGet(_persistedOnLoad, this).push(this.getId(multiselect));
    }
  }

  /**
   * Do not open a multiselect on load.
   *
   * @param {HTMLElement} multiselect
   */
  doNotPersistOnLoad(multiselect) {
    if (this.isPersistedOnLoad(multiselect)) {
      _classPrivateFieldGet(_persistedOnLoad, this).splice(_classPrivateFieldGet(_persistedOnLoad, this).indexOf(this.getId(multiselect)), 1);
      this.setSearch(multiselect, '');
    }
  }

  /**
   * Update the multiselect dropdown toggle label.
   *
   * @param {HTMLElement} multiselect
   */
  updateLabel(multiselect) {
    var _MultiselectDropdowns2, _MultiselectDropdowns3, _multiselect$getAttri;
    let attribute;
    const count = (_MultiselectDropdowns2 = (_MultiselectDropdowns3 = MultiselectDropdowns.getCheckboxesChecked(multiselect)) === null || _MultiselectDropdowns3 === void 0 ? void 0 : _MultiselectDropdowns3.length) !== null && _MultiselectDropdowns2 !== void 0 ? _MultiselectDropdowns2 : 0;
    switch (count) {
      case 0:
        attribute = "".concat(_classPrivateFieldGet(_attribute, this), "-label-none");
        break;
      case 1:
        attribute = "".concat(_classPrivateFieldGet(_attribute, this), "-label-single");
        break;
      case MultiselectDropdowns.getCheckboxes(multiselect).length:
        attribute = "".concat(_classPrivateFieldGet(_attribute, this), "-label-all");
        break;
      default:
        attribute = "".concat(_classPrivateFieldGet(_attribute, this), "-label-plural");
        break;
    }
    this.getToggle(multiselect).innerText = ((_multiselect$getAttri = multiselect.getAttribute(attribute)) !== null && _multiselect$getAttri !== void 0 ? _multiselect$getAttri : '').replace(/%d/, count.toString());
  }

  /**
   * Submit a multiselect dropdown.
   *
   * @param {HTMLElement} multiselect
   */
  submit(multiselect) {
    const submitButton = multiselect.closest('form').querySelector('[type="submit"]');
    if (submitButton) {
      submitButton.click();
    } else {
      multiselect.closest('form').requestSubmit();
    }
    _classPrivateFieldSet(_opened, this, []);
  }

  /**
   * Filter a multiselect's items.
   *
   * @param {HTMLElement} multiselect
   */
  filter(multiselect) {
    const searchInput = this.getSearchInput(multiselect);
    const input = searchInput.value.toLowerCase();
    const threshold = searchInput.getAttribute("".concat(_classPrivateFieldGet(_attribute, this), "-search-character-threshold"));
    this.setSearch(multiselect, searchInput.value);
    this.getListItems(multiselect).forEach(item => {
      if (input.length < threshold) {
        item.style.display = 'initial';
      } else {
        const match = item.querySelector('label').innerText.toLowerCase().indexOf(input) === -1;
        item.style.display = match ? 'none' : 'initial';
      }
    });
  }
}