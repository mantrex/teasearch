<?php

namespace Drupal\Tests\select2\FunctionalJavascript\Form;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\entity_test\Entity\EntityTestMulRevPub;

/**
 * Tests the select2 element.
 *
 * @group select2
 */
class ElementTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['select2_form_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests select2 optgroups.
   */
  public function testOptgroups(): void {
    $page = $this->getSession()->getPage();
    $this->drupalGet('/select2-optgroup-form');

    $this->click('.form-item-select2-optgroups .select2-selection.select2-selection--single');

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.select2-results__group'));

    $this->assertSession()->elementTextContains('css', '.select2-results__group', 'Baba');
    $this->assertSession()->elementTextContains('css', 'ul.select2-results__options li.select2-results__option ul.select2-results__options--nested li.select2-results__option', 'Nana');

    $page->find('css', '.select2-search__field')->setValue('Nana');
    $page->find('css', '.select2-results__option--highlighted')->click();
    $page->pressButton('Submit');
    $json = json_decode($this->getSession()->getPage()->getText(), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertEquals(3, $json['select2_optgroups']);
  }

  /**
   * Test that in-between ajax calls are not creating new entities.
   */
  public function testAjaxCallbacksInBetween(): void {

    $page = $this->getSession()->getPage();
    $this->drupalGet('/select2-ajax-form');

    $this->click('.form-item-select2-ajax .select2-selection.select2-selection--multiple');
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.select2-search__field'));

    $page->find('css', '.select2-search__field')->setValue('New value');
    $page->find('css', '.select2-results__option--highlighted')->click();
    $page->pressButton('Call ajax');

    $this->assertEmpty(EntityTestMulRevPub::loadMultiple());
  }

  /**
   * Test loading of claro theme style.
   */
  public function testClaroTheme(): void {
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')
      ->set('default', 'claro')
      ->set('admin', 'claro')
      ->save();

    $this->drupalGet('/select2-optgroup-form');

    $this->assertSession()->elementExists('css', '.select2-container--claro');

    $select2_js = $this->xpath("//script[contains(@src, '" . $this->getModulePath('select2') . "/js/select2.js')]");
    $this->assertEquals(1, count($select2_js));
    $select2_js = $this->xpath("//script[contains(@src, 'select2/dist/js/select2.min.js')]");
    $this->assertEquals(1, count($select2_js));
  }

  /**
   * Tests select2 autocomplete.
   */
  public function testAutocomplete(): void {
    EntityTestMulRevPub::create(['name' => 'foo'])->save();
    EntityTestMulRevPub::create(['name' => 'bar'])->save();
    EntityTestMulRevPub::create(['name' => 'gaga'])->save();

    $page = $this->getSession()->getPage();
    $this->drupalGet('/select2-autocomplete-form');

    $this->click('.form-item-select2-autocomplete .select2-selection.select2-selection--single');

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.select2-search__field'));
    $page->find('css', '.select2-search__field')->setValue('gaga');

    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', '.select2-results__option--highlighted'));
    $page->find('css', '.select2-results__option--highlighted')->click();

    $page->pressButton('Submit');
    $json = json_decode($this->getSession()->getPage()->getText(), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->assertEquals([['target_id' => 3]], $json['select2_autocomplete']);
  }

}
