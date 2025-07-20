<?php

namespace Drupal\Tests\choices\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\choices\Traits\ChoicesHelperTrait;

/**
 * Tests the choices field widget javascript functionalities.
 *
 * @group choices
 */
class ChoicesWidgetFunctionalJsTest extends WebDriverTestBase {
  use ChoicesHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'field_ui',
    'options',
    'test_page_test',
    'choices',
  ];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
    // Disable the global choices setting:
    $this->config('choices.settings')->set('enable_globally', FALSE)->save();
    // Enable CDN, because we can not require npm/bower-assets via the
    // external automated test bot on Drupal.org:
    $this->config('choices.settings')->set('use_cdn', TRUE)->save();
    // Programmatically create a content type with a select field and generate
    // an instance:
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    // Create select field:
    $this->createSelectOnArticle('test_global_select', 'list_string', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [
      'test' => 'Test',
      'test2' => 'Test2',
    ], 'choices_widget');
  }

  /**
   * Test to see if the choices library is loaded on a field.
   */
  public function testLibraryLoaded() {
    $session = $this->assertSession();
    // Go to the front page and check, that the javascript is not loaded, as
    // there is no select:
    $this->drupalGet('<front>');
    $session->elementNotExists('css', 'script[src*="choices.min.js"]');
    $session->elementNotExists('css', 'link[href*="choices.min.css"]');
    // Go to article creation page and see if the library is loaded there:
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
  }

  /**
   * Test to see if the select is modified by choices inside a list text field.
   */
  public function testChoicesAppliedOnFieldSelectListText() {
    $session = $this->assertSession();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper.field--type-list-string.field--widget-choices-widget');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices', 'data-type', 'select-multiple');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select', 'multiple', 'multiple');
    // Set cardinality to 1:
    \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.test_global_select')->set('cardinality', 1)->save();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices', 'data-type', 'select-one');
  }

  /**
   * Test to see if the select is modified by choices inside a list float field.
   */
  public function testChoicesAppliedOnFieldSelectListFloat() {
    $session = $this->assertSession();
    // Create list_float field on article:
    $this->createSelectOnArticle('test_global_select_float', 'list_float', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [
      '1.5' => 'Test',
      '2.0' => 'Test2',
    ], 'choices_widget');
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-float-wrapper.field--type-list-float.field--widget-choices-widget');
    $session->elementExists('css', 'div#edit-test-global-select-float-wrapper > div.form-item > div.choices select#edit-test-global-select-float');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-float-wrapper > div.form-item > div.choices', 'data-type', 'select-multiple');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-float-wrapper > div.form-item > div.choices select#edit-test-global-select-float', 'multiple', 'multiple');
    // Set cardinality to 1:
    \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.test_global_select_float')->set('cardinality', 1)->save();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-float-wrapper > div.form-item > div.choices select#edit-test-global-select-float');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-float-wrapper > div.form-item > div.choices', 'data-type', 'select-one');
  }

  /**
   * Tests if the select is modified by choices inside a inetger list field.
   */
  public function testChoicesAppliedOnFieldSelectListInteger() {
    $session = $this->assertSession();
    // Create list_integer field on article:
    $this->createSelectOnArticle('test_global_select_integer', 'list_integer', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [
      '1' => 'Test',
      '2' => 'Test2',
    ], 'choices_widget');
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-integer-wrapper.field--type-list-integer.field--widget-choices-widget');
    $session->elementExists('css', 'div#edit-test-global-select-integer-wrapper > div.form-item > div.choices select#edit-test-global-select-integer');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-integer-wrapper > div.form-item > div.choices', 'data-type', 'select-multiple');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-integer-wrapper > div.form-item > div.choices select#edit-test-global-select-integer', 'multiple', 'multiple');
    // Set cardinality to 1:
    \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.test_global_select_integer')->set('cardinality', 1)->save();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-integer-wrapper > div.form-item > div.choices select#edit-test-global-select-integer');
    $session->elementAttributeContains('css', 'div#edit-test-global-select-integer-wrapper > div.form-item > div.choices', 'data-type', 'select-one');
  }

  /**
   * Test that choices configuration options apply.
   */
  public function testChoicesConfigurationOptionsApply() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to config page and set a json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{
      "classNames": {
        "containerOuter": "test-choices"
      }
      }');
    $page->pressButton('Update');
    $page->pressButton('edit-submit');
    $session->pageTextContains('Your settings have been saved.');
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.test-choices select#edit-test-global-select');
  }

  /**
   * Tests if faulty configuration options throw a validation error.
   */
  public function testFaultyConfigurationOptionsDoNotApply() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to config page and set a json setting with a missing semicolon:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{
      "classNames": {
        "containerOuter": "test-choices
      }
      }');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set a 0 as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '0');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set "blank" as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', 'blank');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set "'" as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', "'");
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '"' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '"');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '[]' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '[]');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '{' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '}' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '}');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $session->pageTextContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '');
    $page->pressButton('Update');
    // Save the config and see, if it applies successfully:
    $page->pressButton('edit-submit');
    $session->pageTextNotContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '{}' as json setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{}');
    $page->pressButton('Update');
    // Save the config and see, if it applies successfully:
    $page->pressButton('edit-submit');
    $session->pageTextNotContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');
  }

}
