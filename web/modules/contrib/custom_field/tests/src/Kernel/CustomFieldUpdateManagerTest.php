<?php

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Test the CustomFieldUpdateManager service.
 *
 * @group custom_field
 */
class CustomFieldUpdateManagerTest extends KernelTestBase {

  use UserCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field',
    'node',
    'views',
    'custom_field',
    'custom_field_viewfield',
    'custom_field_test',
    'user',
    'path',
    'file',
    'image',
  ];

  /**
   * The CustomFieldUpdateManager service.
   *
   * @var \Drupal\custom_field\CustomFieldUpdateManager
   */
  protected $customFieldUpdateManager;

  /**
   * The CustomFieldTypeManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The entity definition update manager service.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user object.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $currentUser;

  /**
   * The custom field data generator.
   *
   * @var \Drupal\custom_field\CustomFieldGenerateDataInterface
   */
  protected $customFieldGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['custom_field', 'custom_field_test']);

    $bundle = 'custom_field_entity_test';
    // Create and log in a test user with necessary permissions.
    $permissions = [
      'create ' . $bundle . ' content',
      'edit own ' . $bundle . ' content',
      // Add more permissions if needed.
    ];
    $this->currentUser = $this->createUser($permissions, 'test_user');

    // Get the services required for testing.
    $this->customFieldUpdateManager = $this->container->get('custom_field.update_manager');
    $this->customFieldTypeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityTypeBundleInfo = $this->container->get('entity_type.bundle.info');
    $this->database = $this->container->get('database');
    $this->customFieldGenerator = $this->container->get('custom_field.generate_data');
  }

  /**
   * Test the addColumn method.
   */
  public function testAddColumn(): void {
    // Define the entity type and field names from the provided configuration.
    $entityTypeId = 'node';
    $bundle = 'custom_field_entity_test';
    $fieldName = 'field_test';

    // Define the new property (column) name and data type.
    $newProperty = 'new_property';
    $dataType = 'string';
    $options = ['length' => 'test', 'not null' => FALSE, 'default' => NULL];

    // Create a new node entity.
    $fieldStorageConfig = FieldStorageConfig::loadByName($entityTypeId, $fieldName);
    $columns = $fieldStorageConfig->getSetting('columns');
    $node = $this->createNode([
      'type' => $bundle,
      'title' => 'Test Node',
      $fieldName => $this->customFieldGenerator->generateFieldData($columns),
      'langcode' => 'en',
    ]);
    $node->save();

    // Verify entity has been created properly.
    $id = $node->id();
    $node = Node::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $node->{$fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $node->{$fieldName}[0]);

    // Call the addColumn method.
    $this->customFieldUpdateManager->addColumn($entityTypeId, $fieldName, $newProperty, $dataType, $options);

    // Perform assertions to verify that the column was added successfully.
    $fieldStorageConfig = FieldStorageConfig::loadByName($entityTypeId, $fieldName);
    $this->assertNotNull($fieldStorageConfig, 'The field storage configuration exists.');
    $this->assertEquals('custom', $fieldStorageConfig->getType(), 'The field storage type is "custom".');

    // Verify new column has been added to the field storage configuration.
    $columns = $fieldStorageConfig->getSetting('columns');
    $this->assertArrayHasKey($newProperty, $columns, 'The new property is added to the columns settings.');
    $this->assertEquals($dataType, $columns[$newProperty]['type'], 'The new property has the correct data type.');

    // Verify no data loss resulted in adding the column.
    $node->save();
    $node = Node::load($id);
    $field_value = $node->get($fieldName)->getValue();
    // If restoreData() is commented out, this should fail. Why is it not?
    $this->assertNotEmpty($field_value, 'The field value is not empty.');

    // Call the addColumn method with a non-existent field.
    $nonExistentField = 'non_existent_field';
    try {
      $this->customFieldUpdateManager->addColumn($entityTypeId, $nonExistentField, $newProperty, $dataType, $options);
      $this->fail('An exception should have been thrown for a non-existent field.');
    }
    catch (\Exception $e) {
      // Assert that the exception message contains the field name.
      $this->assertStringContainsString($nonExistentField, $e->getMessage(), 'Exception message contains the non-existent field name.');
    }
    // Assert that the column was not added.
    $fieldStorageConfig = FieldStorageConfig::loadByName($entityTypeId, 'non_existent_field');
    $this->assertNull($fieldStorageConfig, 'The field storage configuration does not exist.');
  }

  /**
   * Test the removeColumn method.
   */
  public function testRemoveColumn(): void {
    // Define the entity type and field names from the provided configuration.
    $entityTypeId = 'node';
    $fieldName = 'field_test';
    $bundle = 'custom_field_entity_test';

    // Perform assertions to verify that the column was added successfully.
    $fieldStorageConfig = FieldStorageConfig::loadByName($entityTypeId, $fieldName);
    $this->assertNotNull($fieldStorageConfig, 'The field storage configuration exists.');
    $this->assertEquals('custom', $fieldStorageConfig->getType(), 'The field storage type is "custom".');
    // Create a node.
    $columns = $fieldStorageConfig->getSetting('columns');
    // Some types have extra columns that alter test.
    unset($columns['image_test']);
    unset($columns['viewfield_test']);
    $fieldStorageConfig->setSetting('columns', $columns)->save();
    $node = $this->createNode([
      'type' => $bundle,
      'title' => 'Test Node',
      $fieldName => $this->customFieldGenerator->generateFieldData($columns),
      'langcode' => 'en',
    ]);
    $node->save();

    // Verify entity has been created properly.
    $id = $node->id();
    $node = Node::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $node->{$fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $node->{$fieldName}[0]);

    // Iterate through each column in the field settings and remove it.
    $fieldSettings = $fieldStorageConfig->getSetting('columns');
    // Remove last item from array to test not removing all columns.
    $last = [array_key_last($fieldSettings) => array_pop($fieldSettings)];
    $lastColumn = key($last);
    foreach ($fieldSettings as $columnName => $columnSettings) {
      // Call the removeColumn method.
      $this->customFieldUpdateManager->removeColumn($entityTypeId, $fieldName, $columnName);
      // Perform assertions to verify that the column was removed successfully.
      $fieldStorageConfig = FieldStorageConfig::loadByName($entityTypeId, $fieldName);
      $this->assertNotNull($fieldStorageConfig, 'The field storage configuration still exists.');
      $columns = $fieldStorageConfig->getSetting('columns');
      $this->assertArrayNotHasKey($columnName, $columns, 'The column "' . $columnName . '" is removed from the columns settings.');
    }

    // Verify no data loss resulted in removing the column.
    $node->save();
    $node = Node::load($id);
    $field_value = $node->get($fieldName)->getValue();
    // If restoreData() is commented out, this should fail. Why is it not?
    $this->assertNotEmpty($field_value, 'The field value is not empty.');

    // Reload fieldSettings and verify the new count.
    $fieldSettings = $fieldStorageConfig->getSetting('columns');
    $this->assertCount(1, $fieldSettings, 'The field settings count is 1.');

    // Try to remove the last column.
    try {
      $this->customFieldUpdateManager->removeColumn($entityTypeId, $fieldName, $lastColumn);
      $this->fail('An exception should have been thrown for removing the only field.');
    }
    catch (\Exception $e) {
      // Assert that the exception message contains the field name.
      $this->assertStringContainsString($lastColumn, $e->getMessage(), 'Exception message contains the last field name.');
    }

    // Verify the last remaining item still exists.
    $columns = $fieldStorageConfig->getSetting('columns');
    $this->assertArrayHasKey($lastColumn, $columns, 'The column "' . $lastColumn . '" still exists in the columns settings.');
  }

}
