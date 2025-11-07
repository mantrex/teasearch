<?php

namespace Drupal\Tests\choices\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\choices\Form\ConfigForm;
use Drupal\Tests\choices\Traits\ChoicesHelperTrait;

/**
 * Tests the global choices javascript functionalities.
 *
 * @group choices
 */
class ChoicesGlobalFunctionalJsTest extends WebDriverTestBase {
  use ChoicesHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'field',
    'field_ui',
    'link',
    'menu_ui',
    'options',
    'test_page_test',
    'test_select_view',
    'choices',
    'automated_cron',
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
    ]);
    // For enabling the global choices option, we need to flush all caches
    // first:
    drupal_flush_all_caches();
  }

  /**
   * Test to see if the choices library is loaded.
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
    ]);
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
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
   * Tests if the select is modified by choices inside an integer list field.
   */
  public function testChoicesAppliedOnFieldSelectListInteger() {
    $session = $this->assertSession();
    // Create list_integer field on article:
    $this->createSelectOnArticle('test_global_select_integer', 'list_integer', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [
      '1' => 'Test',
      '2' => 'Test2',
    ]);
    $this->drupalGet('/node/add/article');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
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
   * Test to see if the select is modified by choices on an admin page.
   */
  public function testChoicesAppliedOnAdminSelect() {
    $session = $this->assertSession();
    $this->drupalGet('/admin/config/system/cron');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div.choices select#edit-interval');
    $session->elementAttributeContains('css', 'div.choices', 'data-type', 'select-one');
  }

  /**
   * Test to see if the select is modified by choices inside a view.
   */
  public function testChoicesAppliedOnViewSelect() {
    $session = $this->assertSession();
    $this->drupalGet('/test-select-view');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div.choices select#edit-type');
    $session->elementAttributeContains('css', 'div.choices', 'data-type', 'select-one');
  }

  /**
   * Test css selector newlines.
   *
   * Test if the css selector string is applied correctly if it contains
   * newlines.
   */
  public function testCssSelectorSupportsNewlines() {
    $session = $this->assertSession();
    // Create a second select field with single select:
    $this->createSelectOnArticle('test_ignored_select', 'list_string', 1, [
      'test' => 'Test',
      'test2' => 'Test2',
    ]);
    // Add css selectors which only target the global_select and are split
    // using a new line:
    $this->config('choices.settings')->set('css_selector', "select[multiple]\r\nselect#edit-test-global-select")->save();
    $this->drupalGet('/node/add/article');
    // See if js and css are present:
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // See if choices applies at all and only on the global select:
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select', 'class', 'choices__input');
    $session->elementNotExists('css', 'div#edit-test-ignored-select-wrapper > div.form-item > div.choices');
    $session->elementAttributeNotContains('css', 'select#edit-test-ignored-select', 'class', 'choices__input');

  }

  /**
   * Test css selector double spaces.
   *
   * Test if the css selector string is applied correctly if it contains
   * double spaces.
   */
  public function testCssSelectorSupportsDoubleSpace() {
    $session = $this->assertSession();
    // Create a second select field with single select:
    $this->createSelectOnArticle('test_ignored_select', 'list_string', 1, [
      'test' => 'Test',
      'test2' => 'Test2',
    ]);
    // Add css selectors which only target the global_select and are split
    // using a double space:
    $this->config('choices.settings')->set('css_selector', "select[multiple]  div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select")->save();
    $this->drupalGet('/node/add/article');
    // See if js and css are present:
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // See if choices applies at all and only on the global select:
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select', 'class', 'choices__input');
    $session->elementNotExists('css', 'div#edit-test-ignored-select-wrapper > div.form-item > div.choices');
    $session->elementAttributeNotContains('css', 'select#edit-test-ignored-select', 'class', 'choices__input');
  }

  /**
   * Test css selector double spaces.
   *
   * Test if the css selector string is applied correctly if it contains
   * double spaces.
   */
  public function testCssSelectorTrimmedCorrectly() {
    $session = $this->assertSession();
    // Add a selector with a space at the front and at the end which should be
    // trimmed away:
    $this->config('choices.settings')->set('css_selector', " select[multiple] ")->save();
    $this->drupalGet('/node/add/article');
    // See if js and css are present:
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // See if choices applies:
    $session->elementAttributeContains('css', 'div#edit-test-global-select-wrapper > div.form-item > div.choices select#edit-test-global-select', 'class', 'choices__input');
  }

  /**
   * Tests if choices can be applied explicitly on admin pages.
   */
  public function testChoicesOnlyOnAdminPages() {
    $session = $this->assertSession();
    $this->config('choices.settings')->set('include', ConfigForm::CHOICES_INCLUDE_ADMIN)->save();
    // See if choices does not apply on article view:
    $this->drupalGet('/test-select-view');
    // See if js and css are not present:
    $session->elementNotExists('css', 'script[src*="choices.min.js"]');
    $session->elementNotExists('css', 'link[href*="choices.min.css"]');
    // See if choices is not applied:
    $session->elementNotExists('css', 'div.choices');
    $session->elementNotExists('css', 'div.choices select#edit-type');

    // Test if choices applies on admin route:
    $this->drupalGet('/admin/config/system/cron');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'div.choices select#edit-interval');
    $session->elementAttributeContains('css', 'div.choices', 'data-type', 'select-one');
  }

  /**
   * Tests if choices can be applied explicitly on frontend pages.
   */
  public function testChoicesOnlyOnFrontendPages() {
    $session = $this->assertSession();
    $this->config('choices.settings')->set('include', ConfigForm::CHOICES_INCLUDE_NO_ADMIN)->save();
    // See if choices does not apply on article view:
    $this->drupalGet('/test-select-view');
    // See if js and css are not present:
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    // See if choices is not applied:
    $session->elementExists('css', 'div.choices select#edit-type');
    $session->elementAttributeContains('css', 'div.choices', 'data-type', 'select-one');

    // Test if choices applies on admin route:
    $this->drupalGet('/admin/config/system/cron');
    $session->elementNotExists('css', 'script[src*="choices.min.js"]');
    $session->elementNotExists('css', 'link[href*="choices.min.css"]');
    $session->elementNotExists('css', 'div.choices');
    $session->elementNotExists('css', 'div.choices select#edit-interval');
  }

  /**
   * Test that choices configuration options apply.
   */
  public function testChoicesConfigurationOptionsApply() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to config page and set a json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '{
      "classNames": {
        "containerOuter": "test-choices"
      }
      }');
    // Save the config and see, if it applies:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('The configuration options have been saved.'));
    $session->pageTextNotContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');
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
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '{
      "classNames": {
        "containerOuter": "test-choices
      }
      }');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set a 0 as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '0');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set "blank" as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', 'blank');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set "'" as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', "'");
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set '"' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '"');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set '[]' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '[]');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set '{' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '{');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set '}' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '}');
    // Save the config and see, if it fails to apply:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('You have to enter a correct JSON object definition or leave the field empty to use default settings.'));

    // Go to config page and set '' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '');
    // Save the config and see, if it applies successfully:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('The configuration options have been saved.'));
    $session->pageTextNotContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');

    // Go to config page and set '{}' as json setting:
    $this->drupalGet('/admin/config/user-interface/choices');
    $page->fillField('edit-configuration-options', '{}');
    // Save the config and see, if it applies successfully:
    $page->pressButton('edit-submit');
    $this->assertTrue($session->waitForText('The configuration options have been saved.'));
    $session->pageTextNotContains('You have to enter a correct JSON object definition or leave the field empty to use default settings.');
  }

  /**
   * Test choices isn't applied on manage form display table drag.
   *
   * Step 1
   *   Go to the manage form display page.
   * Step 2
   *   Check if table drag is being used.
   * Step 3
   *   Click show row weights.
   * Step 4
   *   Choices shouldn't apply to any selects in the table drag interface.
   */
  public function testChoicesNotAppliedOnAdminTableDragSelect() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/types/manage/article/form-display');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'script[src*="tabledrag"]');
    $session->elementExists('css', 'link[href*="tabledrag"]');
    $page->pressButton('Show row weights');
    $session->waitForElementVisible('css', 'field-weight');
    $session->elementExists('css', 'tr.draggable');
    $session->elementNotExists('css', 'div.choices');
  }

  /**
   * Test choices isn't applied on edit menu Administration table drag.
   *
   * Step 1
   *   Go to the Edit menu Administration page.
   * Step 2
   *   Check if table drag is being used.
   * Step 3
   *   Click show row weights.
   * Step 4
   *   Choices shouldn't apply to any selects in the table drag interface.
   */
  public function testChoicesNotAppliedOnMenuTableDragSelect() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/menu/manage/admin');
    $session->pageTextContains('Edit menu Administration');
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'script[src*="tabledrag"]');
    $session->elementExists('css', 'link[href*="tabledrag"]');
    $page->pressButton('Show row weights');
    $session->waitForElementVisible('css', 'menu-weight');
    $session->elementExists('css', 'tr.draggable');
    $session->elementNotExists('css', 'div.choices');
  }

  /**
   * Test choices isn't applied on views rearrange filters dialog table drag.
   *
   * Step 1
   *   Go to edit the content view.
   * Step 2
   *   Open the Filters section drop down widget.
   * Step 3
   *   Click Rearrange to open the Rearrange modal dialog.
   * Step 4
   *   Check if table drag is being used.
   * Step 5
   *   Click show row weights.
   * Step 6
   *   Choices shouldn't apply to selects in the modal table drag interface.
   */
  public function testChoicesNotAppliedOnViewsFiltersRearrangeTableDragSelect() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/views/view/content');

    // Check if on the content view.
    $session->pageTextContains('Content (Content)');

    // Make the dropdown button toggle visible.
    $this->getSession()->executeScript("jQuery('.filter .dropbutton-toggle span').toggleClass('visually-hidden');");

    // Check the toggle is now visible to access and press.
    $session->assertVisibleInViewport('css', '.filter .dropbutton-toggle span');

    // Press the toggle.
    $page->find('css', '.filter .dropbutton-toggle button')->press();

    // Click the rearrange link to open the modal and wait for it to be visible.
    $page->clickLink('views-rearrange-filter');
    $session->waitForElementVisible('css', '#drupal-modal');

    // Check if CSS and JS is present for Choices and table drag.
    $session->elementExists('css', 'script[src*="choices.min.js"]');
    $session->elementExists('css', 'link[href*="choices.min.css"]');
    $session->elementExists('css', 'script[src*="tabledrag"]');
    $session->elementExists('css', 'link[href*="tabledrag"]');

    // Check the modal is visible.
    $session->assertVisibleInViewport('css', '#drupal-modal');

    // Toggle the weight select input visibility.
    $page->find('css', '#drupal-modal .tabledrag-toggle-weight')->press();

    // Verify the input is visible.
    $session->waitForElementVisible('css', '#drupal-modal views-group-select');

    // Check if table drag exists.
    $session->elementExists('css', '#drupal-modal tr.draggable');

    // Check that choices isn't applied to the modal select input.
    $session->elementNotExists('css', '#drupal-modal tr.draggable div.choices');
  }

}
