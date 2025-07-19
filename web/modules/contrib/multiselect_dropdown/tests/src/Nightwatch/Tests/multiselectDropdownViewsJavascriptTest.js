/**
 * @file
 * Multiselect dropdown Nightwatch tests.
 */
const path = require('path');

module.exports = {
  '@tags': ['multiselect_dropdown'],

  before: (browser) => {
    browser.drupalInstall({
      setupFile: path.normalize(`${__dirname}/../MultiselectDropdownInstallTestScript.php`),
      installProfile: 'minimal',
    });
  },

  after: (browser) => {
    browser.drupalUninstall();
  },

  /**
   * Test that submitting the form with enter does not open a multiselect.
   */
  'Pressing enter on a form element submits the form': (browser) => {
    const searchButton = element('[data-drupal-selector="edit-title"]');
    browser
      .drupalRelativeURL('/multiselect-dropdown/view-ajax')
      .waitForElementVisible(searchButton);
    browser.updateValue(searchButton, ['Unused', browser.Keys.ENTER]);
    browser.waitForElementVisible(searchButton);
    browser.assert.elementCount('.field-content', 1);
  },

  /**
   * Test that the search input correctly filters the item list.
   */
  'Search input filters correctly': (browser) => {
    const searchInput = element('[data-drupal-selector="colors"] [data-multiselect-dropdown-search]');
    const visible = '[data-drupal-selector="colors"] .multiselect-dropdown__item[style*="display: initial"], [data-drupal-selector="colors"] .multiselect-dropdown__item:not([style])';
    const hidden = '[data-drupal-selector="colors"] .multiselect-dropdown__item[style*="display: none"]';

    const toggle = element('[data-drupal-selector="colors"] [data-multiselect-dropdown-toggle]');
    browser
      .drupalRelativeURL('/multiselect-dropdown/form')
      .waitForElementVisible(toggle);
    toggle.click();

    browser.assert.elementCount(visible, 7);
    browser.assert.elementCount(hidden, 0);

    browser.updateValue(searchInput, 'r');
    browser.assert.elementCount(visible, 7);
    browser.assert.elementCount(hidden, 0);

    browser.updateValue(searchInput, 'Re');
    browser.assert.elementCount(visible, 7);
    browser.assert.elementCount(hidden, 0);

    browser.updateValue(searchInput, 'rEd');
    browser.assert.elementCount(visible, 2);
    browser.assert.elementCount(hidden, 5);

    browser.updateValue(searchInput, 'd-O');
    browser.assert.elementCount(visible, 1);
    browser.assert.elementCount(hidden, 6);

    browser.updateValue(searchInput, 'gReEN');
    browser.assert.elementCount(visible, 1);
    browser.assert.elementCount(hidden, 6);

    browser.updateValue(searchInput, 'white');
    browser.assert.elementCount(visible, 0);
    browser.assert.elementCount(hidden, 7);
  },
};
