<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Functional;

use Drupal\Core\Config\Config;
use Drupal\multiselect_dropdown_bef\Plugin\better_exposed_filters\filter\MultiselectDropdownFilterWidget;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use Drupal\multiselect_dropdown\ModalType;
use Drupal\views\ViewEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the multiselect dropdown Better Exposed Filters filter widget.
 */
#[Group('multiselect_dropdown')]
#[CoversClass(MultiselectDropdownFilterWidget::class)]
#[CoversFunction('multiselect_dropdown_bef_preprocess_multiselect_dropdown')]
class MultiselectDropdownBefFilterWidgetTest extends BrowserTestBase {

  use MultiselectDropdownTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiselect_dropdown_bef_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Dropdowns.
   *
   * @var string[]
   */
  private static array $dropdowns = [
    'type',
    'field_list_text_value',
    'field_taxonomy_target_id',
  ];

  private string $path = '/multiselect-dropdown/view';

  /**
   * Test that the widget applies only with the correct exposed filter settings.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testApplies(): void {
    $path = '/admin/structure/views/nojs/display/multiselect_dropdown_bef_test/default/exposed_form_options';
    $this->setUpViewsUi();
    $this->drupalGet($path);
    $this->assertSession()->optionExists(
      'edit-exposed-form-options-bef-filter-type-configuration-plugin-id',
      'multiselect_dropdown',
    );
    $this->assertSession()->optionExists(
      'edit-exposed-form-options-bef-filter-field-taxonomy-target-id-configuration-plugin-id',
      'multiselect_dropdown',
    );

    $this->updateViewConfig('display.default.display_options.filters.type.expose.multiple', FALSE);
    $this->drupalGet($path);
    $this->assertSession()->optionNotExists(
      'edit-exposed-form-options-bef-filter-type-configuration-plugin-id',
      'multiselect_dropdown',
    );

    $this->updateViewConfig('display.default.display_options.filters.field_taxonomy_target_id.expose.multiple', FALSE);
    $this->drupalGet($path);
    $this->assertSession()->optionNotExists(
      'edit-exposed-form-options-bef-filter-field-taxonomy-target-id-configuration-plugin-id',
      'multiselect_dropdown',
    );

    $this->updateViewConfig('display.default.display_options.filters.field_taxonomy_target_id.expose.multiple', TRUE);
    $this->updateViewConfig('display.default.display_options.filters.field_taxonomy_target_id.type', 'textfield');
    $this->drupalGet($path);
    $this->assertSession()->optionNotExists(
      'edit-exposed-form-options-bef-filter-field-taxonomy-target-id-configuration-plugin-id',
      'multiselect_dropdown',
    );
  }

  /**
   * Test that configuration form options are available.
   */
  public function testFormOptionsPresent(): void {
    $this->setUpViewsUi();
    $page = $this->drupalGet('/admin/structure/views/nojs/display/multiselect_dropdown_bef_test/default/exposed_form_options');
    $options = [
      'label_aria',
      'label_none',
      'label_all',
      'label_single',
      'label_plural',
      'label_select_all',
      'label_select_none',
      'label_close',
      'label_submit',
      'label_clear',
      'search_title',
      'search_title_display',
      'search_placeholder',
      'search_character_threshold',
      'modal_type',
      'modal_breakpoint',
      'default_open',
      'persist_open',
    ];
    foreach ($options as $option) {
      self::assertStringContainsString("exposed_form_options[bef][filter][type][configuration][$option]", $page);
    }
  }

  /**
   * Test that the library is attached and the assets are present.
   */
  public function testAssetsLoad(): void {
    self::assertStringContainsString('multiselect_dropdown/js/dist/multiselect-dropdown-views.js', $this->loadPage());
  }

  /**
   * Test that all dropdowns are present.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  public function testDropdownsPresent(): void {
    $this->loadPage();
    $session = $this->assertSession();
    foreach ($this->dropdownSelectors() as $dropdown_selector) {
      $session->elementExists('css', $dropdown_selector);
      $session->elementAttributeExists('css', $dropdown_selector, self::$attributes['dropdown']);
      $element = $session->elementAttributeExists('css', $dropdown_selector, 'id');
      self::assertNotEmpty($element->getAttribute('id'));
    }
  }

  /**
   * Test that nested options generate correctly from taxonomy terms.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNestedOptions(): void {
    $this->loadPage();
    $session = $this->assertSession();

    $session->elementsCount(
      'css',
      $this->dropdownSelector('field_taxonomy_target_id', '[' . self::$attributes['depth'] . '="1"]'),
      1,
    );
    $session->elementsCount(
      'css',
      $this->dropdownSelector('field_taxonomy_target_id', '[' . self::$attributes['depth'] . '="0"]'),
      9,
    );
  }

  /**
   * Test that the aria-label generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testAriaLabel(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $toggle = $this->dropdownSelector('type', self::$selectors['toggle']);

    $session->elementAttributeContains('css', $toggle, 'aria-label', 'Toggle the list of items');
    $this->updateMultiselectDropdownConfig('type', 'label_aria', 'Test Aria');
    $this->loadPage();
    $session->elementAttributeContains('css', $toggle, 'aria-label', 'Test Aria');
  }

  /**
   * Test that the none label generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testLabelNone(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_none'], 'All Items');
    $this->updateMultiselectDropdownConfig('type', 'label_none', 'Test None');
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_none'], 'Test None');
  }

  /**
   * Test that the all label generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testLabelAll(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_all'], 'All Items');
    $this->updateMultiselectDropdownConfig('type', 'label_all', 'Test All');
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_all'], 'Test All');
  }

  /**
   * Test that the single label generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testLabelSingle(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_single'], '%d Item Selected');
    $this->updateMultiselectDropdownConfig('type', 'label_single', 'Test Single');
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_single'], 'Test Single');
  }

  /**
   * Test that the plural label generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testLabelPlural(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_plural'], '%d Items Selected');
    $this->updateMultiselectDropdownConfig('type', 'label_plural', 'Test Plural');
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_plural'], 'Test Plural');
  }

  /**
   * Test that the select all button generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSelectAll(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $all = $this->dropdownSelector('type', self::$selectors['all']);

    $session->elementNotExists('css', $all);
    $this->updateMultiselectDropdownConfig('type', 'label_select_all', 'Test Select All');
    $this->loadPage();
    $session->elementTextContains('css', $all, 'Test Select All');
  }

  /**
   * Test that the select none button generates correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSelectNone(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $none = $this->dropdownSelector('type', self::$selectors['none']);

    $session->elementNotExists('css', $none);
    $this->updateMultiselectDropdownConfig('type', 'label_select_none', 'Test Select None');
    $this->loadPage();
    $session->elementTextContains('css', $none, 'Test Select None');
  }

  /**
   * Test that the close button label generates correctly.
   */
  public function testClose(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $close = $this->dropdownSelector('type', self::$selectors['close']);

    $session->elementTextEquals('css', $close, '');
    $this->updateMultiselectDropdownConfig('type', 'label_close', 'Test Close');
    $this->loadPage();
    $session->elementTextEquals('css', $close, 'Test Close');
  }

  /**
   * Test that the submit button generates correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSubmit(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $submit = $this->dropdownSelector('field_list_text_value', self::$selectors['submit']);

    $session->elementNotExists('css', $submit);
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'label_submit', 'Test Submit');
    $this->loadPage();
    $session->elementTextEquals('css', $submit, 'Test Submit');
  }

  /**
   * Test that the clear button generates correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testClear(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $clear = $this->dropdownSelector('field_list_text_value', self::$selectors['clear']);

    $session->elementNotExists('css', $clear);
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'label_clear', 'Test Clear');
    $this->loadPage();
    $session->elementTextEquals('css', $clear, 'Test Clear');
  }

  /**
   * Test that the search input generates correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearch(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $search_label = $this->dropdownSelector('field_list_text_value', self::$selectors['search_label']);
    $search_input = $this->dropdownSelector('field_list_text_value', self::$selectors['search_input']);

    $session->elementNotExists('css', $search_label);
    $session->elementNotExists('css', $search_input);
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'search_title', 'Test Search Label');
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'search_title_display', 'before');
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'search_placeholder', 'Test Search Placeholder');
    $this->updateMultiselectDropdownConfig('field_list_text_value', 'search_character_threshold', 5);
    $this->loadPage();
    $session->elementTextContains('css', $search_label, 'Test Search Label');
    $session->elementAttributeContains('css', $search_input, 'placeholder', 'Test Search Placeholder');
    $session->elementAttributeContains('css', $search_input, self::$attributes['search_character_threshold'], '5');
  }

  /**
   * Test that an invalid breakpoint value throws an exception.
   */
  public function testModalType(): void {
    $this->updateMultiselectDropdownConfig('type', 'modal_type', 'invalid');
    $page = $this->loadPage();
    self::assertStringContainsString('ValueError', $page);
    self::assertStringContainsString(
      htmlentities('Value "invalid" of "#modal_breakpoint" is not allowed in multiselect dropdown "edit-type".'),
      $page,
    );
  }

  /**
   * Test that breakpoints generate correctly.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testModalTypeAndBreakpoint(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['breakpoint'], '768');

    $this->updateMultiselectDropdownConfig('type', 'modal_breakpoint', '512');
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['breakpoint'], '512');

    $this->updateMultiselectDropdownConfig('type', 'modal_type', ModalType::Dialog->value);
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['breakpoint'], ModalType::Dialog->value);

    $this->updateMultiselectDropdownConfig('type', 'modal_type', ModalType::Modal->value);
    $this->loadPage();
    $session->elementAttributeContains('css', $dropdown, self::$attributes['breakpoint'], ModalType::Modal->value);
  }

  /**
   * Test that dropdowns can display opened on load.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testOpen(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');

    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $this->updateMultiselectDropdownConfig('type', 'default_open', TRUE);
    $this->loadPage();
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
  }

  /**
   * Test that uninstalling the test module removes test entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testUninstall(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');

    self::assertInstanceOf(ViewEntityInterface::class, $entity_type_manager->getStorage('view')->load('multiselect_dropdown_bef_test'));

    $this->container->get('module_installer')->uninstall(['multiselect_dropdown_bef_test']);
    self::assertNull($entity_type_manager->getStorage('view')->load('multiselect_dropdown_bef_test'));
  }

  /**
   * Get the config of the multiselect dropdown test view.
   */
  private function getViewConfig(): Config {
    return $this->container
      ->get('config.factory')
      ->getEditable('views.view.multiselect_dropdown_bef_test');
  }

  /**
   * Update the configuration of the multiselect dropdown test view.
   */
  private function updateViewConfig(string $key, mixed $value): void {
    $this->getViewConfig()->set($key, $value)->save();
  }

  /**
   * Update the multiselect dropdown configuration of the test view filter.
   */
  private function updateMultiselectDropdownConfig(
    string $filter,
    string $key,
    mixed $value,
    string $display_id = 'default',
  ): void {
    $this->updateViewConfig("display.$display_id.display_options.exposed_form.options.bef.filter.$filter.$key", $value);
  }

  /**
   * Set up the views UI module and a user so tests can access view settings.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setUpViewsUi(): void {
    $this->container->get('module_installer')->install(['views_ui']);
    if ($user = $this->createUser(['administer views'])) {
      $this->drupalLogin($user);
    }
  }

}
