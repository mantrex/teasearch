<?php

namespace Drupal\Tests\choices\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\choices\Traits\ChoicesHelperTrait;

/**
 * Tests the global choices functionality mixed with the choices field widget.
 *
 * @group choices
 */
class ChoicesMixedFunctionalJsTest extends WebDriverTestBase {
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
    // Enable the global choices setting:
    $this->config('choices.settings')->set('enable_globally', TRUE)->save();

    $this->config('choices.settings')->set('css_selector', 'select')->save();
    // Include on every page:
    $this->config('choices.settings')->set('include', 2)->save();
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
    // For enabling the global choices option, we need to flush all caches
    // first:
    drupal_flush_all_caches();
  }

  /**
   * Test if widget setting overwrite a deep option.
   */
  public function testWidgetSettingsOverwriteGlobalSettingsDeep() {
    $page = $this->getSession()->getPage();
    $session = $this->assertSession();
    $this->drupalGet('/node/add/article');
    // Set the global setting:
    $this->config('choices.settings')->set('configuration_options', '{
      "classNames": {
        "containerOuter": "choices global-choices"
      }
}')->save();
    // Set the widget setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{
      "classNames": {
        "containerOuter": "choices widget-choices"
      }
}');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    drupal_flush_all_caches();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // Check if the widget options applied:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices');
    // Check if the global setting is not applied anymore:
    $session->elementNotExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.global-choices');
    // Check that some choices default settings are also still present:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices > div.choices__inner > input.choices__input');
  }

  /**
   * Test if global deep configs merge correctly with deep widget configs.
   */
  public function testGlobalSettingsDeepMerge() {
    $page = $this->getSession()->getPage();
    $session = $this->assertSession();
    $this->drupalGet('/node/add/article');
    // Set the global setting:
    $this->config('choices.settings')->set('configuration_options', '{
      "classNames": {
        "containerOuter": "choices global-choices",
        "containerInner": "choices__inner global-inner"
      }
}')->save();
    // Set the widget setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{
      "classNames": {
        "containerOuter": "choices widget-choices"
      }
}');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    drupal_flush_all_caches();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // Check if the widget options applied and global options merged correctly:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices > div.choices__inner.global-inner > input.choices__input');

    // Check if the global setting is not applied anymore:
    $session->elementNotExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.global-choices');
    // Check that some choices default settings are also still present:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices > div.choices__inner.global-inner > input.choices__input');
  }

  /**
   * Test if widget deep configs merge correctly with global deep configs.
   */
  public function testWidgetSettingsDeepMerge() {
    $page = $this->getSession()->getPage();
    $session = $this->assertSession();
    $this->drupalGet('/node/add/article');
    // Set the global setting:
    $this->config('choices.settings')->set('configuration_options', '{
      "classNames": {
        "containerOuter": "choices global-choices"
      }
}')->save();
    // Set the widget setting:
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $page->pressButton('edit-fields-test-global-select-settings-edit');
    $session->waitForElementVisible('css', 'textarea[id*=edit-fields-test-global-select-settings-edit-form-settings-configuration-options]');
    $page->fillField('fields[test_global_select][settings_edit_form][settings][configuration_options]', '{
      "classNames": {
        "containerOuter": "choices widget-choices",
        "containerInner": "choices__inner widget-inner"
      }
}');
    $page->pressButton('Update');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    drupal_flush_all_caches();
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // Check if the widget options applied and global options merged correctly:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices');
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices > div.choices__inner.widget-inner > input.choices__input');

    // Check if the global setting is not applied anymore:
    $session->elementNotExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.global-choices');
    // Check that some choices default settings are also still present:
    $session->elementExists('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices.widget-choices > div.choices__inner.widget-inner > input.choices__input');
  }

}
