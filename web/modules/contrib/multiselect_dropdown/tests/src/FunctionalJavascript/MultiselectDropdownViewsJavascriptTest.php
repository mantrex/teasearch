<?php

declare(strict_types=1);

namespace Drupal\Tests\multiselect_dropdown\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\multiselect_dropdown\Traits\MultiselectDropdownTestTrait;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test multiselect dropdown form elements Views JavaScript.
 */
#[Group('multiselect_dropdown')]
#[CoversFunction('multiselect_dropdown_bef_preprocess_multiselect_dropdown')]
final class MultiselectDropdownViewsJavascriptTest extends WebDriverTestBase {

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
  protected $defaultTheme = 'multiselect_dropdown_test_theme';

  private string $path = '/multiselect-dropdown/view-ajax';

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The test theme includes starterkit's views-view.html.twig template, so
    // enable starterkit.
    $this->container->get('theme_installer')->install(['starterkit_theme']);
  }

  /**
   * Test that the aria-label of list elements has the altered text.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testListAriaLabelAltered(): void {
    $this->loadPage();
    $session = $this->assertSession();
    $wrapper = $this->dropdownSelector('type', self::$selectors['wrapper']);
    $session->elementAttributeContains('css', $wrapper, 'aria-label', 'shift');
  }

  /**
   * Test that dropdowns persist across AJAX submissions correctly.
   *
   * Tests Drupal.behaviors.multiselectDropdownLoadOpen implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testDialogPersistence(): void {
    // Test dropdowns which should persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');
    $toggle = $this->dropdownSelector('type', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('type', self::$selectors['dialog']);
    $search = $this->dropdownSelector('type', self::$selectors['search_input']);

    $session->elementExists('css', $toggle)->click();
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');
    $session->elementExists('css', $search)->setValue('Search');

    $session->elementExists('css', self::$selectors['view_submit'])->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $session->waitForElement('css', $dropdown);
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');
    self::assertSame('Search', $session->elementExists('css', $search)->getValue());

    // Test dropdowns which should not persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('field_taxonomy_target_id');
    $toggle = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['dialog']);
    $search = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['search_input']);

    $session->elementExists('css', $toggle)->click();
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');
    $session->elementExists('css', $search)->setValue('Search');

    $session->elementExists('css', self::$selectors['view_submit'])->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $session->waitForElement('css', $dropdown);
    $session->elementAttributeNotExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'false');
    $session->elementAttributeNotExists('css', $dialog, 'open');
    self::assertSame('', $session->elementExists('css', $search)->getValue());

    // Test that checkboxes persist dropdowns when autosubmit is enabled.
    $this->container
      ->get('config.factory')
      ->getEditable('views.view.multiselect_dropdown_bef_test')
      ->set('display.default.display_options.exposed_form.options.bef.general.autosubmit', TRUE)
      ->set('display.default.display_options.exposed_form.options.bef.filter.field_taxonomy_target_id.persist_open', TRUE)
      ->save();
    $this->loadPage();
    $session = $this->assertSession();
    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $session->waitForElement('css', $dropdown);
    $session->elementAttributeExists('css', $dropdown, self::$attributes['open']);
    $session->elementAttributeContains('css', $toggle, 'aria-expanded', 'true');
    $session->elementAttributeExists('css', $dialog, 'open');
  }

  /**
   * Test submitting from a checkbox focuses the correct elements on load.
   *
   * Tests Drupal.behaviors.multiselectDropdownSubmitCheckboxes implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testCheckboxSubmit(): void {
    // Test dropdowns which should persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');
    $toggle = $this->dropdownSelector('type', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('type', self::$selectors['dialog']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(1, $checkboxes);
    $checkboxes[1]->check();
    $checkboxes[1]->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-empty")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $checkboxes[0]->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('[data-drupal-selector=\"{$checkboxes[0]->getAttribute('data-drupal-selector')}\"]')");
    // Resolves an issue where the dialog element may be stale intermittently.
    sleep(1);
    $session->elementAttributeExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle);
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    // Test dropdowns which should not persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('field_taxonomy_target_id');
    $toggle = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['dialog']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(9, $checkboxes);
    $checkboxes[9]->check();
    $checkboxes[9]->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-empty")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $checkboxes[0]->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('[data-drupal-selector=\"{$checkboxes[0]->getAttribute('data-drupal-selector')}\"]')");
    // Resolves an issue where the dialog element may be stale intermittently.
    sleep(1);
    $session->elementAttributeExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle);
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(1, $checkboxes);
    $checkboxes[1]->check();
    $checkboxes[1]->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');
  }

  /**
   * Test submitting the dialog with enter focuses the correct elements on load.
   *
   * Tests Drupal.behaviors.multiselectDropdownSubmitButton implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testSubmit(): void {
    // Test dropdowns which should persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');
    $toggle = $this->dropdownSelector('type', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('type', self::$selectors['dialog']);
    $submit = $this->dropdownSelector('type', self::$selectors['submit']);
    $wrapper = $this->dropdownSelector('type', self::$selectors['wrapper']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(1, $checkboxes);
    $checkboxes[1]->check();
    $session->elementExists('css', $submit)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-empty")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $submit)->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('$wrapper')");
    $session->elementAttributeExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle);
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $submit)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    // Test dropdowns which should not persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('field_taxonomy_target_id');
    $toggle = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['dialog']);
    $submit = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['submit']);
    $wrapper = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['wrapper']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(9, $checkboxes);
    $checkboxes[9]->check();
    $session->elementExists('css', $submit)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-empty")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $submit)->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('$wrapper')");
    $session->elementAttributeExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle);
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $submit)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');
  }

  /**
   * Test clearing the dialog with enter focuses the correct elements on load.
   *
   * Tests Drupal.behaviors.multiselectDropdownClearButtonEnter implementation.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testClearEnter(): void {
    // Test dropdowns which should persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('type');
    $toggle = $this->dropdownSelector('type', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('type', self::$selectors['dialog']);
    $clear = $this->dropdownSelector('type', self::$selectors['clear']);
    $wrapper = $this->dropdownSelector('type', self::$selectors['wrapper']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $clear)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'type');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $clear)->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('$wrapper')");
    $session->elementAttributeExists('css', $dialog, 'open');

    // Test dropdowns which should not persist.
    $this->loadPage();
    $session = $this->assertSession();
    $dropdown = $this->dropdownSelector('field_taxonomy_target_id');
    $toggle = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['toggle']);
    $dialog = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['dialog']);
    $clear = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['clear']);
    $wrapper = $this->dropdownSelector('field_taxonomy_target_id', self::$selectors['wrapper']);

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $clear)->keyDown(13, 'shift');
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition('document.activeElement === document.querySelector(".view-content")');
    $session->elementAttributeNotExists('css', $dialog, 'open');

    $session->elementExists('css', $toggle)->click();
    $checkboxes = $this->checkboxes($session, 'field_taxonomy_target_id');
    self::assertArrayHasKey(0, $checkboxes);
    $checkboxes[0]->check();
    $session->elementExists('css', $clear)->keyDown(13);
    $session->waitForElement('css', $dropdown);
    $this->assertJsCondition("document.activeElement === document.querySelector('$wrapper')");
    $session->elementAttributeExists('css', $dialog, 'open');
  }

}
