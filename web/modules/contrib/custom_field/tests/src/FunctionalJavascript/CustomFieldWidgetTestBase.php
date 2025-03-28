<?php

namespace Drupal\Tests\custom_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for testing custom field widget plugins.
 *
 * Test cases provided in this class apply to all widget plugins.
 */
abstract class CustomFieldWidgetTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_test',
    'user',
    'system',
    'field',
    'field_ui',
    'text',
    'node',
    'path',
  ];

  /**
   * The field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The custom field generate data service.
   *
   * @var \Drupal\custom_field\CustomFieldGenerateDataInterface
   */
  protected $customFieldDataGenerator;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The custom fields on the test entity bundle.
   *
   * @var array|\Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fields = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->customFieldDataGenerator = $this->container->get('custom_field.generate_data');

    $this->fields = $this->entityFieldManager
      ->getFieldDefinitions('node', 'custom_field_entity_test');

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
  }

  /**
   * Test case for a single cardinality field.
   *
   * This method sets various field data and ensures that subsequent visits
   * to the node edit form displays the correct data in the correct places.
   */
  public function testWidgets() {
    $assert = $this->assertSession();
    $this->drupalGet('/node/add/custom_field_entity_test');
    $generator = $this->customFieldDataGenerator;

    // Fill out the single cardinality field.
    $form_values = $generator->generateSampleFormData($this->fields['field_test']);

    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');

    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }

    // Fill out the multiple cardinality field.
    $form_values = $generator->generateSampleFormData(
      $this->fields['field_test_multiple'],
      [0, 1, 2]
    );
    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }

    // Fill out the unlimited cardinality field (and add another several times).
    $page = $this->getSession()->getPage();
    for ($i = 0; $i < 4; ++$i) {
      $page->pressButton('Add another item');
      $assert->assertWaitOnAjaxRequest();
    }
    $form_values = $generator->generateSampleFormData(
      $this->fields['field_test_unlimited'],
      [0, 1, 2, 3, 4]
    );
    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');
    foreach ($form_values as $key => $expected) {
      $actual = $assert->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }
  }

}
