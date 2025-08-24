<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test multiselect dropdown form elements JavaScript.
 */
#[Group('multiselect_dropdown')]
final class MultiselectDropdownJavascriptTest extends WebDriverTestBase {

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

  /**
   * Test dropdown label generation.
   *
   * Tests Drupal.behaviors.multiselectDropdownToggleLabel implementation.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testToggleLabel(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $checkboxes = $this->checkboxes($session, 'colors');
    $session->elementExists('css', $toggle)->click();

    $session->elementTextEquals('css', $toggle, '0 Colors Selected');

    $checkboxes[0]->check();
    $session->elementTextEquals('css', $toggle, '1 Color Selected');

    $checkboxes[1]->check();
    $session->elementTextEquals('css', $toggle, '2 Colors Selected');

    array_walk($checkboxes, fn(NodeElement $checkbox) => $checkbox->check());
    $session->elementTextEquals('css', $toggle, 'All Colors Selected');

    array_walk($checkboxes, fn(NodeElement $checkbox) => $checkbox->uncheck());
    $session->elementTextEquals('css', $toggle, '0 Colors Selected');
  }

  /**
   * Test that the close button sets the closed attributes.
   *
   * Tests Drupal.behaviors.multiselectDropdownDialogClose implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testDialogClose(): void {
    // The dropdown label shows on mobile only.
    $this->getSession()->resizeWindow(400, 800);
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('colors');
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('colors', self::$selectors['dialog']);
    $close = $this->dropdownSelector('colors', self::$selectors['close']);

    $session->elementExists('css', $toggle)->click();
    $session->elementExists('css', $close)->click();
    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeNotExists('css', $dialog, 'open');
  }

  /**
   * Test that pressing the escape key sets the closed attributes.
   *
   * Tests Drupal.behaviors.multiselectDropdownEscapeClose implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testEscapeClose(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('colors');
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('colors', self::$selectors['dialog']);

    $session->elementExists('css', $toggle)->click();
    $this->getSession()->executeScript("e = new Event('keydown'); e.key = 'Escape'; window.dispatchEvent(e);");
    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeNotExists('css', $dialog, 'open');
  }

  /**
   * Test that clicking outside a multiselect sets the closed attributes.
   *
   * Tests Drupal.behaviors.multiselectDropdownClickClose implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testClickClose(): void {
    $this->loadPage();
    $session = $this->assertSession();
    // Do not use the first multiselect because the dialog will cover other
    // elements which need to be clickable.
    $dropdown = $this->dropdownSelector('continents');
    $toggle = $this->dropdownSelector('continents', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('continents', self::$selectors['dialog']);

    $session->elementExists('css', $toggle)->click();
    $session->elementExists('css', $dialog)->click();
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');

    $session->elementExists('css', $this->dropdownSelector('colors', self::$selectors['toggle']))->click();
    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeNotExists('css', $dialog, 'open');
  }

  /**
   * Test the clear button behavior.
   *
   * Tests Drupal.behaviors.multiselectDropdownClearButton implementation.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testClear(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $clear = $this->dropdownSelector('colors', self::$selectors['clear']);
    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'colors');

    array_walk($checkboxes, fn(NodeElement $checkbox) => $checkbox->check());
    $session->elementTextEquals('css', $toggle, 'All Colors Selected');
    $session->elementExists('css', $clear)->click();

    $session->waitForElement('css', $toggle);
    $session->elementTextEquals('css', $toggle, '0 Colors Selected');
  }

  /**
   * Test the submit button behavior.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testSubmit(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $submit = $this->dropdownSelector('colors', self::$selectors['submit']);
    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'colors');

    array_walk($checkboxes, fn(NodeElement $checkbox) => $checkbox->check());
    $session->elementTextEquals('css', $toggle, 'All Colors Selected');
    $session->elementExists('css', $submit)->click();

    $session->waitForElement('css', $toggle);
    $session->elementTextEquals('css', $toggle, 'All Colors Selected');
  }

  /**
   * Test the select all/none toggle buttons.
   *
   * Tests Drupal.behaviors.multiselectDropdownSelectAll and
   * Drupal.behaviors.multiselectDropdownSelectNone implementations.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testAllNoneToggle(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $toggle = $this->dropdownSelector('colors', self::$selectors['toggle']);
    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'colors');
    self::assertArrayHasKey(0, $checkboxes);
    $all = $session->elementExists(
      'css',
      $this->dropdownSelector('colors', self::$selectors['all']),
    );
    $none = $session->elementExists(
      'css',
      $this->dropdownSelector('colors', self::$selectors['none']),
    );

    self::assertSame(0, $this->checkedCount($checkboxes));

    $checkboxes[0]->check();
    $all->click();
    self::assertSame(count($checkboxes), $this->checkedCount($checkboxes));
    // Checking to ensure that the label generation cannot go out of bounds.
    $session->elementTextEquals('css', $toggle, 'All Colors Selected');

    $checkboxes[0]->uncheck();
    $none->click();
    self::assertSame(0, $this->checkedCount($checkboxes));
    // Checking to ensure that the label generation cannot go out of bounds.
    $session->elementTextEquals('css', $toggle, '0 Colors Selected');
  }

}
