<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Config\Config;
use Drupal\multiselect_dropdown\Plugin\Field\FieldWidget\MultiselectDropdownWidget;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the multiselect dropdown field widget.
 */
#[Group('multiselect_dropdown')]
#[CoversClass(MultiselectDropdownWidget::class)]
final class MultiselectDropdownWidgetTest extends BrowserTestBase {

  use MultiselectDropdownTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'multiselect_dropdown_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  private string $path = '/node/add/node';

  /**
   * Dropdowns.
   *
   * @var string[]
   */
  private static array $dropdowns = [
    'field_taxonomy',
  ];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    if ($user = $this->createUser([], NULL, TRUE)) {
      $this->drupalLogin($user);
    }
  }

  /**
   * Test that the library is attached and the assets are present.
   */
  public function testAssetsLoad(): void {
    $html = $this->loadPage();
    self::assertStringContainsString('multiselect_dropdown/css/multiselect-dropdown-field-widget.css', $html);
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
   * Test that fields with a cardinality of one do not allow multiselects.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testDropdownRequiresMultiples(): void {
    $this->drupalGet('/admin/structure/types/manage/node/form-display');
    $session = $this->assertSession();

    $taxonomy = $session->selectExists('fields[field_list_text][type]');
    self::assertNull($taxonomy->find('css', '[value="multiselect_dropdown"]'));

    $taxonomy = $session->selectExists('fields[field_taxonomy][type]');
    self::assertInstanceOf(
      NodeElement::class,
      $taxonomy->find('css', '[value="multiselect_dropdown"]'),
    );
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
      $this->dropdownSelector('field_taxonomy', '[' . self::$attributes['depth'] . '="1"]'),
      1,
    );
    $session->elementsCount(
      'css',
      $this->dropdownSelector('field_taxonomy', '[' . self::$attributes['depth'] . '="0"]'),
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
    $toggle = $this->dropdownSelector('field_taxonomy', self::$selectors['toggle']);

    $session->elementAttributeContains('css', $toggle, 'aria-label', 'Toggle the list of items');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_aria', 'Test Aria');
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
    $dropdown = $this->dropdownSelector('field_taxonomy');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_none'], 'No Items Selected');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_none', 'Test None');
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
    $dropdown = $this->dropdownSelector('field_taxonomy');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_all'], 'All Items');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_all', 'Test All');
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
    $dropdown = $this->dropdownSelector('field_taxonomy');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_single'], '%d Item Selected');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_single', 'Test Single');
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
    $dropdown = $this->dropdownSelector('field_taxonomy');

    $session->elementAttributeContains('css', $dropdown, self::$attributes['label_plural'], '%d Items Selected');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_plural', 'Test Plural');
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
    $all = $this->dropdownSelector('field_taxonomy', self::$selectors['all']);

    $session->elementNotExists('css', $all);
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_select_all', 'Test Select All');
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
    $none = $this->dropdownSelector('field_taxonomy', self::$selectors['none']);

    $session->elementNotExists('css', $none);
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'label_select_none', 'Test Select None');
    $this->loadPage();
    $session->elementTextContains('css', $none, 'Test Select None');
  }

  /**
   * Test that the search input generates correctly.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearch(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $search_label = $this->dropdownSelector('field_taxonomy', self::$selectors['search_label']);
    $search_input = $this->dropdownSelector('field_taxonomy', self::$selectors['search_input']);

    $session->elementNotExists('css', $search_label);
    $session->elementNotExists('css', $search_input);
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'search_title', 'Test Search Label');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'search_title_display', 'before');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'search_placeholder', 'Test Search Placeholder');
    $this->updateMultiselectDropdownConfig('field_taxonomy', 'search_character_threshold', 5);
    $this->loadPage();
    $session->elementTextContains('css', $search_label, 'Test Search Label');
    $session->elementAttributeContains('css', $search_input, 'placeholder', 'Test Search Placeholder');
    $session->elementAttributeContains('css', $search_input, self::$attributes['search_character_threshold'], '5');
  }

  /**
   * Test that the modal type is a dialog.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testModalType(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $modal_type = $this->dropdownSelector('field_taxonomy');

    $session->elementAttributeContains('css', $modal_type, self::$attributes['breakpoint'], 'dialog');
  }

  /**
   * Get the config of node default view.
   */
  private function getNodeFormConfig(
    string $bundle = 'node',
    string $form_mode = 'default',
  ): Config {
    return $this->container
      ->get('config.factory')
      ->getEditable("core.entity_form_display.node.$bundle.$form_mode");
  }

  /**
   * Update the configuration of the node default view.
   */
  private function updateNodeFormConfig(string $key, mixed $value): void {
    $this->getNodeFormConfig()->set($key, $value)->save();
  }

  /**
   * Update the multiselect dropdown configuration of the test view filter.
   */
  private function updateMultiselectDropdownConfig(
    string $field,
    string $key,
    mixed $value,
  ): void {
    $this->updateNodeFormConfig("content.$field.settings.$key", $value);
  }

}
