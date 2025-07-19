<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\multiselect_dropdown\Plugin\Field\FieldWidget\MultiselectDropdownWidget;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the multiselect dropdown field widget configuration form.
 */
#[Group('multiselect_dropdown')]
#[CoversClass(MultiselectDropdownWidget::class)]
final class MultiselectDropdownWidgetJavascriptTest extends WebDriverTestBase {

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
   * Test that configuration form options are available.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFormOptionsPresent(): void {
    $this->drupalGet('/admin/structure/types/manage/node/form-display');
    $session = $this->assertSession();
    $this->submitForm([], 'field_taxonomy_settings_edit');
    $session->waitForElement('css', '[name="fields[field_taxonomy][settings_edit_form][settings][label_aria]"]');
    $options = [
      'label_aria',
      'label_none',
      'label_all',
      'label_single',
      'label_plural',
      'label_select_all',
      'label_select_none',
      'search_title',
      'search_title_display',
      'search_placeholder',
      'search_character_threshold',
    ];
    $session->elementsCount('css', '[name^="fields[field_taxonomy][settings_edit_form][settings]"]', \count($options));
    foreach ($options as $option) {
      $session->elementExists('css', "[name='fields[field_taxonomy][settings_edit_form][settings][$option]']");
    }
  }

  /**
   * Test that all search field options show when the label input has a value.
   *
   * Tests Drupal.behaviors.multiselectDropdownSearch implementation.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\DriverException
   */
  public function testSearchInputLabelStates(): void {
    $this->drupalGet('/admin/structure/types/manage/node/form-display');
    $session = $this->assertSession();
    $this->submitForm([], 'field_taxonomy_settings_edit');
    $search_label = $session->waitForElement('css', '[name="fields[field_taxonomy][settings_edit_form][settings][search_title]"]');
    self::assertNotNull($search_label);

    $options = [
      'search_title_display',
      'search_placeholder',
      'search_character_threshold',
    ];
    foreach ($options as $option) {
      $parent = $session
        ->elementExists('css', "[name='fields[field_taxonomy][settings_edit_form][settings][$option]']")
        ->getParent();
      self::assertSame('display: none;', $parent->getAttribute('style'));
    }

    $search_label->setValue('Search');
    foreach ($options as $option) {
      $parent = $session
        ->elementExists('css', "[name='fields[field_taxonomy][settings_edit_form][settings][$option]']")
        ->getParent();
      self::assertSame('', $parent->getAttribute('style'));
    }
  }

}
