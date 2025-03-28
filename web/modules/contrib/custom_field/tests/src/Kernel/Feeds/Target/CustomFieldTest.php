<?php

namespace Drupal\Tests\custom_field\Kernel\Feeds\Target;

use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Tests for mapping to custom_field fields.
 *
 * @group custom_field
 */
class CustomFieldTest extends FeedsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'file',
    'node',
    'custom_field',
    'custom_field_viewfield',
    'custom_field_test',
    'feeds',
    'system',
    'user',
    'image',
    'views',
  ];

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The CustomFieldTypeManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The custom field feeds manager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFeedsManagerInterface
   */
  protected $feedsManager;

  /**
   * The entity type for testing.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * The field name for testing.
   *
   * @var string
   */
  protected string $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Define the entity type and field names from the provided configuration.
    $this->entityTypeId = 'node';
    $bundle = 'custom_field_entity_test';
    $this->fieldName = 'field_test';

    $this->installConfig([
      'custom_field_test',
      'file',
    ]);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['custom_field', 'custom_field_test']);

    // Get the services required for testing.
    $this->customFieldTypeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->feedsManager = $this->container->get('plugin.manager.custom_field_feeds');
    $fieldStorageConfig = FieldStorageConfig::loadByName($this->entityTypeId, $this->fieldName);
    $columns = $fieldStorageConfig->getSetting('columns');

    // Create and configure feed type.
    $sources = [
      'title' => 'title',
    ];

    $mappings = [
      [
        'target' => 'title',
        'map' => ['value' => 'title'],
        'settings' => [
          'language' => NULL,
        ],
      ],
      [
        'target' => 'feeds_item',
        'map' => [
          'url' => '',
          'guid' => 'guid',
        ],
      ],
    ];

    $custom_field_map = [
      'target' => $this->fieldName,
      'map' => [],
    ];
    foreach ($columns as $name => $column) {
      $sources[$name] = $name;
      $custom_field_map['map'][$name] = $name;
    }

    $mappings[] = $custom_field_map;

    $this->feedType = $this->createFeedTypeForCsv(
      $sources,
      [
        'mappings' => $mappings,
        'processor_configuration' => [
          'authorize' => FALSE,
          'values' => [
            'type' => $bundle,
          ],
        ],
      ],
    );
  }

  /**
   * Basic test loading a CSV file.
   */
  public function test() {
    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(3);
    $expected_values = [
      1 => [
        'string_test' => 'String 1',
        'string_long_test' => 'Long string 1',
        'integer_test' => '42',
        'decimal_test' => '3.14',
        'float_test' => '2.718',
        'email_test' => 'test@example.com',
        'telephone_test' => '+1234567890',
        'uri_test' => 'http://www.example.com',
        'boolean_test' => '1',
        'color_test' => '#FFA500',
        'map_test' => [
          [
            'key' => 'key1',
            'value' => 'value1',
          ],
          [
            'key' => 'key2',
            'value' => 'value2',
          ],
        ],
        'map_string_test' => [
          'value1',
          'value2',
          'value3',
        ],
        'datetime_test' => '2023-01-01T00:00:00',
      ],
      2 => [
        'string_test' => 'String 2',
        'string_long_test' => 'Long string 2',
        'integer_test' => NULL,
        'decimal_test' => '-1.62',
        'float_test' => '0.5778',
        'email_test' => NULL,
        'telephone_test' => '-9876543210',
        'uri_test' => 'internal:/',
        'boolean_test' => '1',
        'color_test' => NULL,
        'map_test' => NULL,
        'map_string_test' => NULL,
        'datetime_test' => '2009-09-03T00:12:00',
      ],
      3 => [
        'string_test' => 'String 3',
        'string_long_test' => NULL,
        'integer_test' => '1234',
        'decimal_test' => '1.62',
        'float_test' => '0.577',
        'email_test' => NULL,
        'telephone_test' => NULL,
        'uri_test' => 'route:<nolink>',
        'boolean_test' => '1',
        'color_test' => '#FFFFFF',
        'map_test' => NULL,
        'map_string_test' => NULL,
        'datetime_test' => '2018-02-09T00:00:00',
      ],
    ];
    foreach ($expected_values as $nid => $data) {
      $node = Node::load($nid);
      $field_values = $node->get($this->fieldName)->first()->getValue();
      $this->assertNotEmpty($field_values, 'The field value is not empty');
      foreach ($data as $name => $data_value) {
        $this->assertSame($data_value, $field_values[$name], 'The expected value is the same as the saved value.');
      }
    }
    // Check if mappings can be unique.
    $unique_types = [
      'string_test',
      'string_long_test',
      'integer_test',
      'decimal_test',
      'email_test',
      'uri_test',
      'telephone_test',
    ];
    $unique_count = count($unique_types);
    $mappings = $this->feedType->getMappings();
    $mappings[1]['unique'] = $unique_types;
    $this->feedType->setMappings($mappings);
    $this->feedType->save();
    $updated_mappings = $this->feedType->getMappings();
    $this->assertCount($unique_count, $updated_mappings[1]['unique'], 'The count of expected unique types is accurate.');
  }

  /**
   * Overrides the absolute directory path of the Feeds module.
   *
   * @return string
   *   The absolute path to the custom_field module.
   */
  protected function absolutePath(): string {
    return $this->absolute() . '/' . $this->getModulePath('custom_field');
  }

}
