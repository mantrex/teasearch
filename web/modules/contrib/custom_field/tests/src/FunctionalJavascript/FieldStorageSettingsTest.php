<?php

namespace Drupal\Tests\custom_field\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Field storage settings form tests for custom field.
 *
 * @group custom_field
 */
class FieldStorageSettingsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field_viewfield',
    'custom_field_test',
    'user',
    'system',
    'field',
    'field_ui',
    'text',
    'node',
    'path',
    'views',
  ];

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
   * The field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The bundle type.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The field array path.
   *
   * @var string
   */
  protected $parentPath;

  /**
   * URL to field's storage configuration form.
   *
   * @var string
   */
  protected $fieldStorageConfigUrl;

  /**
   * The custom field generate data service.
   *
   * @var \Drupal\custom_field\CustomFieldGenerateDataInterface
   */
  protected $customFieldDataGenerator;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldName = 'field_test';
    $this->bundle = 'custom_field_entity_test';
    $this->fieldStorageConfigUrl = '/admin/structure/types/manage/' . $this->bundle . '/fields/node.' . $this->bundle . '.' . $this->fieldName;
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->customFieldDataGenerator = $this->container->get('custom_field.generate_data');

    $this->fields = $this->entityFieldManager
      ->getFieldDefinitions('node', $this->bundle);

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->parentPath = 'field_storage[subform][settings][items]';
    // Create node bundle for tests.
    $type = NodeType::create(['name' => 'Article', 'type' => 'article']);
    $type->save();
  }

  /**
   * Tests the settings form with stored configuration.
   */
  public function testFormSettings() {
    $field = $this->fields[$this->fieldName];
    $this->drupalGet($this->fieldStorageConfigUrl);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $columns = $field->getSetting('columns');

    // Verify the clone settings field exists.
    $assert_session->elementExists('css', '[name="field_storage[subform][settings][clone]"]');

    // Iterate over each column stored in config to test form element.
    foreach ($columns as $name => $column) {
      $type = $column['type'];
      $max_length = $page->findField($this->parentPath . '[' . $name . '][max_length]');
      $max_length_message = 'The max length field is visible';
      $size = $page->findField($this->parentPath . '[' . $name . '][size]');
      $size_message = 'The size field is visible';
      $precision = $page->findField($this->parentPath . '[' . $name . '][precision]');
      $precision_message = 'The precision field is visible';
      $scale = $page->findField($this->parentPath . '[' . $name . '][scale]');
      $scale_message = 'The scale field is visible';
      $unsigned = $page->findField($this->parentPath . '[' . $name . '][unsigned]');
      $unsigned_message = 'The unsigned checkbox is visible';
      $datetime_type = $page->findField($this->parentPath . '[' . $name . '][datetime_type]');
      $datetime_type_message = 'The datetime type field is visible';

      // Verify the type field is present and required.
      $this->assertNotEmpty((bool) $this->xpath('//select[@name="' . $this->parentPath . '[' . $name . '][type]" and boolean(@required)]'), 'Type is shown as required.');
      // Verify the type field selected option matches the stored config value.
      $this->assertOptionSelected($this->parentPath . '[' . $name . '][type]', $type, 'The configured data type is selected.');

      // Perform special assertions based on column type.
      switch ($type) {
        case 'string':
        case 'telephone':
          $this->assertNotTrue($size->isVisible(), $size_message);
          $this->assertNotTrue($precision->isVisible(), $precision_message);
          $this->assertNotTrue($scale->isVisible(), $scale_message);
          $this->assertNotTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);
          $this->assertTrue($max_length->isVisible(), $max_length_message);
          $this->assertTrue($max_length->getValue() === $column['max_length'], 'The configured value equals the form value.');
          break;

        case 'integer':
        case 'float':
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);
          $this->assertTrue($size->isVisible(), $size_message);
          $this->assertOptionSelected($this->parentPath . '[' . $name . '][size]', $column['size'], 'The configured size is selected.');
          $this->assertTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertTrue($unsigned->isChecked() === (bool) $unsigned->getValue(), 'The unsigned field is checked if the value is true.');
          break;

        case 'decimal':
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);
          $this->assertTrue($precision->isVisible(), $precision_message);
          $this->assertTrue($scale->isVisible(), $scale_message);
          $this->assertTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertTrue($unsigned->isChecked() === (bool) $unsigned->getValue(), 'The unsigned field is checked if the value is true.');
          break;

        case 'datetime':
          $this->assertTrue($datetime_type->isVisible(), $datetime_type_message);
          $this->assertOptionSelected($this->parentPath . '[' . $name . '][datetime_type]', $column['datetime_type'], 'The configured datetime type is selected.');
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($size->isVisible(), $size_message);
          $this->assertNotTrue($precision->isVisible(), $precision_message);
          $this->assertNotTrue($scale->isVisible(), $scale_message);
          $this->assertNotTrue($unsigned->isVisible(), $unsigned_message);
          break;

        case 'entity_reference':
          $target_type = $page->findField($this->parentPath . '[' . $name . '][target_type]');
          $target_type_message = 'The target type field is visible';
          $this->assertTrue($target_type->isVisible(), $target_type_message);
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($size->isVisible(), $size_message);
          $this->assertNotTrue($precision->isVisible(), $precision_message);
          $this->assertNotTrue($scale->isVisible(), $scale_message);
          $this->assertNotTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);
          break;

        case 'file':
        case 'image':
          $uri_scheme = $page->findField($this->parentPath . '[' . $name . '][uri_scheme]');
          $uri_scheme_message = 'The uri scheme field is visible';
          $this->assertTrue($uri_scheme->isVisible(), $uri_scheme_message);
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($size->isVisible(), $size_message);
          $this->assertNotTrue($precision->isVisible(), $precision_message);
          $this->assertNotTrue($scale->isVisible(), $scale_message);
          $this->assertNotTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);

        default:
          $this->assertNotTrue($max_length->isVisible(), $max_length_message);
          $this->assertNotTrue($size->isVisible(), $size_message);
          $this->assertNotTrue($precision->isVisible(), $precision_message);
          $this->assertNotTrue($scale->isVisible(), $scale_message);
          $this->assertNotTrue($unsigned->isVisible(), $unsigned_message);
          $this->assertNotTrue($datetime_type->isVisible(), $datetime_type_message);
          break;
      }
    }
  }

  /**
   * Tests the add/remove columns buttons with stored configuration.
   */
  public function testAddRemoveColumns() {
    $this->drupalGet($this->fieldStorageConfigUrl);
    $field = $this->fields[$this->fieldName];
    $columns = $field->getSetting('columns');
    $page = $this->getSession()->getPage();
    // Remove elements in descending order until getting to the last one.
    foreach ($columns as $i => $column) {
      $button_id = 'remove_' . $i;
      $this->assertSession()->waitForElementVisible('css', '#custom-field-storage-wrapper');
      $this->assertSession()->waitForElementVisible('css', $button_id);
      $remove_button = $this->getSession()->getPage()->findButton($button_id);
      if ($remove_button) {
        $this->getSession()->getPage()->findButton($button_id)->click();
      }
      $this->assertSession()->waitForElementVisible('css', '#custom-field-storage-wrapper');
      $this->assertSession()->buttonNotExists($button_id);
    }
    // Click the Add another button and verify the new element exists.
    $page->findButton('Add sub-field')->click();
    $this->assertSession()->waitForElementVisible('css', '#field-combined');
  }

  /**
   * Tests cloning field settings from another field.
   */
  public function testCloneSettings() {
    $field_copy = $this->fields[$this->fieldName];
    $field_copy_columns = $field_copy->getSetting('columns');

    // Create a generic custom field for validation.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_custom_generic',
      'entity_type' => 'node',
      'type' => 'custom',
    ]);
    $field_storage->save();

    $field_instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Generic custom field',
    ]);
    $field_instance->save();
    $article_config_url = '/admin/structure/types/manage/article/fields/node.article.field_custom_generic';

    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load('node.article.default');

    if (!$this->formDisplay) {
      EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ])->save();
      $this->formDisplay = EntityFormDisplay::load('node.article.default');
    }
    $this->formDisplay->setComponent('field_custom_generic', [
      'type' => 'custom_stacked',
    ])->save();

    $this->drupalGet($article_config_url);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Verify the clone settings field exists.
    $assert_session->elementExists('css', '[name="field_storage[subform][settings][clone]"]');
    $clone_field = $page->findField('field_storage[subform][settings][clone]');
    $option_value = 'node.' . $this->bundle . '.' . $this->fieldName;
    $clone_field->setValue($option_value);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContainsOnce('The selected custom field field settings will be cloned. Any existing settings for this field will be overwritten. Field widget and formatter settings will not be cloned.');
    // Save the form.
    $page->findButton('Save settings')->click();
    $this->drupalGet($article_config_url);
    $field = FieldConfig::loadByName('node', 'article', 'field_custom_generic');
    $columns = $field->getSetting('columns');
    $this->assertSame($field_copy_columns, $columns, 'The cloned columns match the source columns.');
  }

  /**
   * Tests the settings form with existing data.
   */
  public function testFormExistingData() {
    $field = $this->fields[$this->fieldName];
    $this->drupalGet('/node/add/custom_field_entity_test');
    $assert_session = $this->assertSession();
    // Fill out the single cardinality field.
    $generator = $this->customFieldDataGenerator;
    $form_values = $generator->generateSampleFormData($field);
    $this->submitForm($form_values, 'Save');

    // Ensure the values were properly persisted.
    $this->drupalGet('/node/1/edit');

    foreach ($form_values as $key => $expected) {
      $actual = $assert_session->waitForField($key)->getValue();
      static::assertEquals($expected, $actual);
    }

    // Load the settings page now to evaluate existing data.
    $this->drupalGet($this->fieldStorageConfigUrl);
    // Verify the clone settings field no longer exists.
    $assert_session->elementNotExists('css', '[name="field_storage[subform][settings][clone]"]');
    // Verify the add another button is hidden.
    $assert_session->elementNotExists('css', '#edit-field-storage-subform-settings-actions-add');
  }

  /**
   * Asserts that a select field has a selected option.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  protected function assertOptionSelected($id, $option, $message = '') {
    $elements = $this->xpath('//select[@name=:id]//option[@value=:option]', [
      ':id' => $id,
      ':option' => $option,
    ]);
    foreach ($elements as $element) {
      $this->assertNotEmpty($element->isSelected(), $message ? $message : new FormattableMarkup('Option @option for field @id is selected.', [
        '@option' => $option,
        '@id' => $id,
      ]));
    }
  }

}
