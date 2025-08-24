<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\Traits;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverWebAssert;
use Drupal\Tests\WebAssert;

/**
 * Shared properties and methods for testing multiselect dropdowns.
 */
trait MultiselectDropdownTestTrait {

  /**
   * Attributes.
   *
   * @var array<string, string>
   */
  private static array $attributes = [
    'dropdown' => 'data-multiselect-dropdown',
    'label_none' => 'data-multiselect-dropdown-label-none',
    'label_all' => 'data-multiselect-dropdown-label-all',
    'label_single' => 'data-multiselect-dropdown-label-single',
    'label_plural' => 'data-multiselect-dropdown-label-plural',
    'search_character_threshold' => 'data-multiselect-dropdown-search-character-threshold',
    'breakpoint' => 'data-multiselect-dropdown-breakpoint',
    'open' => 'data-multiselect-dropdown-open',
    'depth' => 'data-multiselect-dropdown-depth',
  ];

  /**
   * Selectors.
   *
   * @var array<string, string>
   */
  private static array $selectors = [
    'dropdown' => '[data-multiselect-dropdown]',
    'toggle' => '[data-multiselect-dropdown-toggle]',
    'dialog' => '[data-multiselect-dropdown-dialog]',
    'close' => '[data-multiselect-dropdown-dialog-close]',
    'all' => '[data-multiselect-dropdown-select-all]',
    'none' => '[data-multiselect-dropdown-select-none]',
    'wrapper' => '[data-multiselect-dropdown-wrapper]',
    'list' => '[data-multiselect-dropdown-list]',
    'submit' => '[data-multiselect-dropdown-submit]',
    'clear' => '[data-multiselect-dropdown-clear]',
    'search_label' => '[for*="-search"]',
    'search_input' => '[data-multiselect-dropdown-search]',
    'form_submit' => '[data-drupal-selector="multiselect-dropdown-test"] [data-drupal-selector="edit-submit"]',
    'view_submit' => '[data-drupal-selector="views-exposed-form-multiselect-dropdown-bef-test-test-ajax"] [data-drupal-selector="edit-submit-multiselect-dropdown-bef-test"]',
  ];

  /**
   * Load the form test page.
   *
   * @param array<string, string|string[]> $parameters
   */
  private function loadPage(array $parameters = []): string {
    return $this->drupalGet(
      $this->path,
      $parameters ? ['query' => $parameters] : [],
    );
  }

  /**
   * Generate a dropdown CSS selector.
   */
  private function dropdownSelector(string $id, string $child = ''): string {
    return "[data-drupal-selector=\"$id\"] $child";
  }

  /**
   * Generate CSS selectors for all dropdowns.
   *
   * @return string[]
   */
  private function dropdownSelectors(): array {
    return array_map(fn(string $id) => $this->dropdownSelector($id), self::$dropdowns);
  }

  /**
   * Get a checkboxes of a dropdown.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  private function checkboxes(
    WebDriverWebAssert|WebAssert $session,
    string $dropdown,
  ): array {
    return $session
      ->elementExists('css', 'form')
      ->findAll('css', $this->dropdownSelector($dropdown, '[type="checkbox"]'));
  }

  /**
   * Get the number of checked checkboxes.
   *
   * @param \Behat\Mink\Element\NodeElement[] $checkboxes
   */
  private function checkedCount(array $checkboxes): int {
    return \count(array_filter($checkboxes, fn(NodeElement $checkbox) => $checkbox->isChecked()));
  }

}
