<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test multiselect dropdown form elements JavaScript polyfill.
 */
#[Group('multiselect_dropdown')]
final class MultiselectDropdownJavascriptPolyfillTest extends WebDriverTestBase {

  use MultiselectDropdownTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiselect_dropdown_polyfill',
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
    'open',
  ];

  /**
   * Test that the toggle button sets the correct attributes.
   *
   * Tests Drupal.behaviors.multiselectDropdownToggle implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testToggle(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('colors');
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('colors', self::$selectors['dialog']);

    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');
  }

}
