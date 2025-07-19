<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\multiselect_dropdown\Element\MultiselectDropdown;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use Drupal\multiselect_dropdown\ModalType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test multiselect dropdown form element without JavaScript.
 */
#[Group('multiselect_dropdown')]
#[CoversClass(MultiselectDropdown::class)]
#[CoversFunction('multiselect_dropdown_theme')]
#[CoversFunction('template_preprocess_multiselect_dropdown')]
#[CoversFunction('multiselect_dropdown_test_uninstall')]
final class MultiselectDropdownTest extends BrowserTestBase {

  use MultiselectDropdownTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiselect_dropdown_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  private string $path = '/multiselect-dropdown/form';

  /**
   * Dropdowns.
   *
   * @var string[]
   */
  private static array $dropdowns = [
    'colors',
    'continents',
    'nested',
    'open',
  ];

  /**
   * Test that the library is attached and the assets are present.
   */
  public function testAssetsLoad(): void {
    $html = $this->loadPage();
    self::assertStringContainsString('multiselect_dropdown/css/multiselect-dropdown.css', $html);
    self::assertStringContainsString('multiselect_dropdown/js/dist/multiselect-dropdowns.js', $html);
    self::assertStringContainsString('multiselect_dropdown/js/dist/multiselect-dropdown.js', $html);

    $this->container->get('module_installer')->install([
      'multiselect_dropdown_polyfill',
    ]);
    $html = $this->loadPage();
    self::assertStringContainsString('multiselect_dropdown_polyfill/css/multiselect-dropdown-polyfill.css', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/node_modules/dialog-polyfill/dist/dialog-polyfill.css', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/js/dist/multiselect-dropdown-polyfill.js', $html);
    self::assertStringContainsString('multiselect_dropdown_polyfill/node_modules/dialog-polyfill/dist/dialog-polyfill.esm.js', $html);
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
   * Test that dropdown options generate correctly.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testOptions(): void {
    $this->loadPage();
    $session = $this->assertSession();

    $color_selector = $this->dropdownSelector('colors');
    $color_dropdown = $session->elementExists('css', $color_selector);
    self::assertCount(7, $color_dropdown->findAll('css', 'ul [name]'));
    $colors = [
      'red',
      'redorange',
      'orange',
      'yellow',
      'green',
      'blue',
      'purple',
    ];
    foreach ($colors as $color) {
      self::assertTrue($color_dropdown->has('css', "[name=\"colors[$color]\"]"));
    }
  }

  /**
   * Test that dropdown options nest correctly.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testNestedOptions(): void {
    $this->loadPage();
    $session = $this->assertSession();

    $nested_selector = $this->dropdownSelector('nested');
    $nested_dropdown = $session->elementExists('css', $nested_selector);
    $nested = [
      'A' => 0,
      '1' => 1,
      'a' => 2,
      'i' => 3,
      'ii' => 3,
      'iii' => 3,
      'b' => 2,
      '2' => 1,
      'c' => 2,
      '3' => 1,
      'B' => 0,
      '4' => 1,
      'd' => 2,
      'iv' => 3,
      'v' => 3,
      'e' => 2,
      '5' => 1,
      'C' => 0,
      '6' => 1,
      'f' => 2,
      'vi' => 3,
      '7' => 1,
    ];
    foreach ($nested as $name => $expected_depth) {
      self::assertTrue($nested_dropdown->has('css', "[name=\"nested[$name]\"]"));
      $option = $nested_dropdown->find('css', "[name=\"nested[$name]\"]");
      self::assertInstanceOf(NodeElement::class, $option);
      $depth = $option->getAttribute(self::$attributes['depth']);
      self::assertEquals($expected_depth, $depth);

      $ul_parents = 0;
      $parent = $option->getParent();
      while (!$parent->hasAttribute('data-multiselect-dropdown-list')) {
        if ($parent->getTagName() === 'ul') {
          $ul_parents++;
        }
        $parent = $parent->getParent();
      }
      self::assertSame($expected_depth, $ul_parents);
    }
  }

  /**
   * Test that an invalid breakpoint value throws an exception.
   */
  public function testBreakpoints(): void {
    $page = $this->drupalGet('/multiselect-dropdown/breakpoint');
    self::assertStringContainsString('ValueError', $page);
    self::assertStringContainsString(
      htmlentities('Value "breakpoint" of "#modal_breakpoint" is not allowed in multiselect dropdown "edit-breakpoint".'),
      $page,
    );
  }

  /**
   * Test that attributes generate correctly.
   *
   * @param string $dropdown_id
   * @param array<string, string|null> $attributes
   *
   * @dataProvider attributeDataProvider
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  #[DataProvider('attributeDataProvider')]
  public function testAttributes(string $dropdown_id, array $attributes): void {
    $this->loadPage();
    $session = $this->assertSession();
    $selector = $this->dropdownSelector($dropdown_id);
    foreach ($attributes as $attribute => $value) {
      if (\is_null($value)) {
        $session->elementAttributeNotExists('css', $selector, $attribute);
      }
      else {
        $session->elementAttributeContains('css', $selector, $attribute, $value);
      }
    }
  }

  /**
   * Test that labels generate correctly.
   *
   * @param string $dropdown_id
   * @param array<string, string|null> $labels
   *
   * @dataProvider buttonLabelDataProvider
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  #[DataProvider('buttonLabelDataProvider')]
  public function testButtonLabels(string $dropdown_id, array $labels): void {
    $this->loadPage();
    $session = $this->assertSession();
    foreach ($labels as $selector => $label) {
      $selector = $this->dropdownSelector($dropdown_id, $selector);
      if (\is_null($label)) {
        $session->elementNotExists('css', $selector);
      }
      else {
        $session->elementTextEquals('css', $selector, $label);
      }
    }
  }

  /**
   * Test that interactive elements have the needed ARIA elements.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  public function testInteractiveElementsHaveAria(): void {
    $this->loadPage();
    $session = $this->assertSession();

    $has_aria_label = [
      'toggle',
      'close',
      'all',
      'none',
      'wrapper',
      'submit',
      'clear',
      'search_input',
    ];
    foreach ($has_aria_label as $aria_label) {
      $session->elementAttributeExists(
        'css',
        $this->dropdownSelector('colors', self::$selectors[$aria_label]),
        'aria-label',
      );
    }

    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeContains('css', $toggle, 'type', 'button');
    $controls = $session
      ->elementExists('css', $toggle)
      ->getAttribute('aria-controls');
    $session->elementExists('css', "[id=\"$controls\"]");

    $wrapper = $this->dropdownSelector('colors', self::$selectors['wrapper']);
    $session->elementAttributeContains('css', $wrapper, 'tabindex', '0');

    $session->elementAttributeContains(
      'css',
      $this->dropdownSelector('open', self::$selectors['toggle']),
      'aria-expanded',
      'true',
    );
  }

  /**
   * Test that toggle labels on page load generate correctly.
   */
  public function testToggleLabel(): void {
    $this->loadPage();
    $this->assertSession()->elementTextEquals(
      'css',
      $this->dropdownSelector('colors', self::$selectors['toggle']),
      '0 Colors Selected',
    );

    $this->loadPage(['colors' => ['red']]);
    $this->assertSession()->elementTextEquals(
      'css',
      $this->dropdownSelector('colors', self::$selectors['toggle']),
      '1 Color Selected',
    );

    $this->loadPage(['colors' => ['red', 'orange']]);
    $this->assertSession()->elementTextEquals(
      'css',
      $this->dropdownSelector('colors', self::$selectors['toggle']),
      '2 Colors Selected',
    );

    $this->loadPage(['colors' => ['red', 'redorange', 'orange', 'yellow', 'green', 'blue', 'purple']]);
    $this->assertSession()->elementTextEquals(
      'css',
      $this->dropdownSelector('colors', self::$selectors['toggle']),
      'All Colors Selected',
    );
  }

  /**
   * Test that uninstalling the test module removes test entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testUninstall(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');

    self::assertCount(2, $entity_type_manager->getStorage('node_type')->loadMultiple());
    self::assertCount(1, $entity_type_manager->getStorage('taxonomy_vocabulary')->loadMultiple());
    self::assertCount(10, $entity_type_manager->getStorage('node')->loadMultiple());
    self::assertCount(10, $entity_type_manager->getStorage('taxonomy_term')->loadMultiple());

    $this->container->get('module_installer')->uninstall(['multiselect_dropdown_test']);
    self::assertCount(0, $entity_type_manager->getStorage('node_type')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('taxonomy_vocabulary')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('node')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('taxonomy_term')->loadMultiple());
  }

  /**
   * Data provider for ::testAttributes().
   *
   * @return array<string, array{dropdown_id: string, attributes: array<string, string|null>}>
   */
  public static function attributeDataProvider(): array {
    $data = [];

    $data['colors'] = [
      'dropdown_id' => 'colors',
      'attributes' => [
        self::$attributes['label_none'] => '%d Colors Selected',
        self::$attributes['label_all'] => 'All Colors Selected',
        self::$attributes['label_single'] => '%d Color Selected',
        self::$attributes['label_plural'] => '%d Colors Selected',
        self::$attributes['breakpoint'] => '512',
        self::$attributes['open'] => NULL,
      ],
    ];

    $data['open'] = [
      'dropdown_id' => 'open',
      'attributes' => [
        self::$attributes['label_none'] => 'No Items Selected',
        self::$attributes['label_all'] => 'All Items',
        self::$attributes['label_single'] => '%d Item Selected',
        self::$attributes['label_plural'] => '%d Items Selected',
        self::$attributes['breakpoint'] => ModalType::Modal->value,
        self::$attributes['open'] => '',
      ],
    ];

    return $data;
  }

  /**
   * Data provider for ::testButtonLabels().
   *
   * @return array<string, array{dropdown_id: string, labels: array<string, string|null>}>
   */
  public static function buttonLabelDataProvider(): array {
    $data = [];

    $data['colors'] = [
      'dropdown_id' => 'colors',
      'labels' => [
        self::$selectors['close'] => 'Close Colors',
        self::$selectors['all'] => 'All Colors',
        self::$selectors['none'] => 'No Colors',
        self::$selectors['submit'] => 'Submit Colors',
        self::$selectors['clear'] => 'Clear Colors',
      ],
    ];

    $data['continents'] = [
      'dropdown_id' => 'continents',
      'labels' => [
        self::$selectors['close'] => '',
        self::$selectors['all'] => NULL,
        self::$selectors['none'] => NULL,
        self::$selectors['submit'] => NULL,
        self::$selectors['clear'] => NULL,
      ],
    ];

    return $data;
  }

}
