<?php

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Tests the custom field type.
 *
 * @group custom_field
 */
class CustomFieldItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'custom_field_viewfield',
    'custom_field_test',
    'field',
    'node',
    'path',
    'path_alias',
    'system',
    'user',
    'file',
    'image',
    'views',
  ];

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

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
   * The CustomFieldTypeManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The CustomFieldWidgetManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager
   */
  protected $customFieldWidgetManager;

  /**
   * The CustomFieldFormatterManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager
   */
  protected $customFieldFormatterManager;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityType;

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
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
    $this->installConfig([
      'system',
      'custom_field_test',
      'node',
      'field',
      'user',
      'file',
      'image',
      'views',
    ]);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);

    // Get the services required for testing.
    $this->customFieldTypeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->customFieldWidgetManager = $this->container->get('plugin.manager.custom_field_widget');
    $this->customFieldFormatterManager = $this->container->get('plugin.manager.custom_field_formatter');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->entityType = 'node';
    $this->bundle = 'custom_field_entity_test';
    $this->fieldName = 'field_test';
    $this->fields = $this->entityFieldManager->getFieldDefinitions('node', 'custom_field_entity_test');
    $this->field = $this->fields[$this->fieldName];
    $this->fieldStorage = FieldStorageConfig::loadByName($this->entityType, $this->fieldName);
  }

  /**
   * Tests using entity fields of the custom field type.
   */
  public function testCustomFieldItem(): void {
    $random = new Random();
    $expected = [
      'uuid' => [
        'widget' => [
          'id' => 'uuid',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\UuidWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'string' => [
        'widget' => [
          'id' => 'text',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TextWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'map' => [
        'widget' => [
          'id' => 'map_key_value',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\MapKeyValueWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'map_string' => [
        'widget' => [
          'id' => 'map_text',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\MapTextWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'color' => [
        'widget' => [
          'id' => 'color',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\ColorWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'float' => [
        'widget' => [
          'id' => 'float',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\FloatWidget',
        ],
        'formatter' => [
          'id' => 'number_decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DecimalFormatter',
        ],
      ],
      'integer' => [
        'widget' => [
          'id' => 'integer',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\IntegerWidget',
        ],
        'formatter' => [
          'id' => 'number_integer',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\IntegerFormatter',
        ],
      ],
      'string_long' => [
        'widget' => [
          'id' => 'textarea',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TextareaWidget',
        ],
        'formatter' => [
          'id' => 'text_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\TextDefaultFormatter',
        ],
      ],
      'uri' => [
        'widget' => [
          'id' => 'url',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\UrlWidget',
        ],
        'formatter' => [
          'id' => 'uri_link',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\UriLinkFormatter',
        ],
      ],
      'boolean' => [
        'widget' => [
          'id' => 'checkbox',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\CheckboxWidget',
        ],
        'formatter' => [
          'id' => 'boolean',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\BooleanFormatter',
        ],
      ],
      'email' => [
        'widget' => [
          'id' => 'email',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\EmailWidget',
        ],
        'formatter' => [
          'id' => 'email_mailto',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\MailToFormatter',
        ],
      ],
      'decimal' => [
        'widget' => [
          'id' => 'decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\DecimalWidget',
        ],
        'formatter' => [
          'id' => 'number_decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DecimalFormatter',
        ],
      ],
      'telephone' => [
        'widget' => [
          'id' => 'telephone',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TelephoneWidget',
        ],
        'formatter' => [
          'id' => 'telephone_link',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\TelephoneLinkFormatter',
        ],
      ],
      'datetime' => [
        'widget' => [
          'id' => 'datetime_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\DateTimeDefaultWidget',
        ],
        'formatter' => [
          'id' => 'datetime_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DateTimeDefaultFormatter',
        ],
      ],
      'entity_reference' => [
        'widget' => [
          'id' => 'entity_reference_autocomplete',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\EntityReferenceAutocompleteWidget',
        ],
        'formatter' => [
          'id' => 'entity_reference_label',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\EntityReferenceLabelFormatter',
        ],
      ],
      'file' => [
        'widget' => [
          'id' => 'file_generic',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\FileWidget',
        ],
        'formatter' => [
          'id' => 'file_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\GenericFileFormatter',
        ],
      ],
      'image' => [
        'widget' => [
          'id' => 'image_image',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\ImageWidget',
        ],
        'formatter' => [
          'id' => 'image',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\ImageFormatter',
        ],
      ],
      'link' => [
        'widget' => [
          'id' => 'link_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\LinkWidget',
        ],
        'formatter' => [
          'id' => 'link',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\LinkFormatter',
        ],
      ],
      'viewfield' => [
        'widget' => [
          'id' => 'viewfield_select',
          'class' => 'Drupal\custom_field_viewfield\Plugin\CustomField\FieldWidget\ViewfieldSelectWidget',
        ],
        'formatter' => [
          'id' => 'viewfield_default',
          'class' => 'Drupal\custom_field_viewfield\Plugin\CustomField\FieldFormatter\ViewfieldDefaultFormatter',
        ],
      ],
      'time' => [
        'widget' => [
          'id' => 'time_widget',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TimeWidget',
        ],
        'formatter' => [
          'id' => 'time',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\TimeFormatter',
        ],
      ],
    ];

    // Perform assertions to verify that the storage was added successfully.
    $this->assertNotNull($this->fieldStorage, 'The field storage configuration exists.');
    $settings = $this->field->getSettings();
    $custom_items = $this->customFieldTypeManager->getCustomFieldItems($settings);
    foreach ($custom_items as $custom_item) {
      $default_widget = $custom_item->getDefaultWidget();
      $default_formatter = $custom_item->getDefaultFormatter();
      $type = $custom_item->getDataType();

      // Assert the expected default widget id for the field type plugin.
      $this->assertEquals($default_widget, $expected[$type]['widget']['id'], 'The default widget id is equal to the expected widget id.');

      // Assert the expected default widget class for the field type plugin.
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager $widget_plugin */
      $widget_plugin = $this->customFieldWidgetManager->createInstance($default_widget);
      $this->assertEquals(get_class($widget_plugin), $expected[$type]['widget']['class'], 'The default widget class is equal to the expected widget class.');

      // Assert the expected default formatter id for the field type plugin.
      $this->assertEquals($default_formatter, $expected[$type]['formatter']['id'], 'The default formatter is equal to the expected formatter.');

      // Assert the expected default formatter class for the field type plugin.
      /** @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager $formatter_plugin */
      $formatter_plugin = $this->customFieldFormatterManager->createInstance($default_formatter);
      $this->assertEquals(get_class($formatter_plugin), $expected[$type]['formatter']['class'], 'The default formatter class is equal to the expected formatter class.');
    }

    // Create an entity.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => $this->bundle,
    ]);
    $string_long = $random->paragraphs(4);
    $float = 3.14;
    $email = 'test@example.com';
    $telephone = '+0123456789';
    $uri_external = 'https://www.drupal.com';
    $link_title = 'Drupal';
    $boolean = '1';
    $color = '#000000';
    $datetime = '2014-01-01T20:00:00';
    $map = [
      [
        'key' => 'Key1',
        'value' => 'Value1',
      ],
      [
        'key' => 'Key2',
        'value' => 'Value2',
      ],
    ];
    $map_string = ['text1', 'text2', 'text3', 'text4'];
    $time = 37980;
    // Test string constraints.
    $entity->{$this->fieldName}->string_test = $this->randomString(256);
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'String exceeding length causes validation error');
    $string = $this->randomString(255);
    $entity->{$this->fieldName}->string_test = $string;

    // Test integer constraints.
    $integer_max = 2147483647;
    $integer_min = -2147483648;
    $entity->{$this->fieldName}->integer_test = $integer_max + 1;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'The integer value exceeds max.');
    $entity->{$this->fieldName}->integer_test = $integer_min - 1;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'The integer value is below min.');
    $integer = rand(0, 10);
    $entity->{$this->fieldName}->integer_test = $integer;

    // Test decimal constraints.
    $entity->{$this->fieldName}->decimal_test = '20-40';
    $this->assertCount(1, $violations, 'Wrong decimal value causes validation error');
    $decimal = 31.30;
    $link_title_field = 'link_test__title';
    $entity->{$this->fieldName}->decimal_test = $decimal;
    $entity->{$this->fieldName}->float_test = $float;
    $entity->{$this->fieldName}->email_test = $email;
    $entity->{$this->fieldName}->telephone_test = $telephone;
    $entity->{$this->fieldName}->uri_test = $uri_external;
    $entity->{$this->fieldName}->boolean_test = $boolean;
    $entity->{$this->fieldName}->color_test = $color;
    $entity->{$this->fieldName}->string_long_test = $string_long;
    $entity->{$this->fieldName}->map_test = $map;
    $entity->{$this->fieldName}->map_string_test = $map_string;
    $entity->{$this->fieldName}->datetime_test = $datetime;
    $entity->{$this->fieldName}->time_test = $time;
    $entity->{$this->fieldName}->link_test = $uri_external;
    $entity->{$this->fieldName}->{$link_title_field} = $link_title;
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = Node::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->{$this->fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $entity->{$this->fieldName}[0]);
    $this->assertEquals($string, $entity->{$this->fieldName}->string_test);
    $this->assertEquals($string, $entity->{$this->fieldName}[0]->string_test);
    $this->assertEquals(strlen($string_long), strlen($entity->{$this->fieldName}->string_long_test));
    $this->assertEquals(strlen($string_long), strlen($entity->{$this->fieldName}[0]->string_long_test));
    $this->assertEquals($integer, $entity->{$this->fieldName}->integer_test);
    $this->assertEquals($integer, $entity->{$this->fieldName}[0]->integer_test);
    $this->assertEquals((float) $decimal, $entity->{$this->fieldName}->decimal_test);
    $this->assertEquals((float) $decimal, $entity->{$this->fieldName}[0]->decimal_test);
    $this->assertEquals($float, $entity->{$this->fieldName}->float_test);
    $this->assertEquals($float, $entity->{$this->fieldName}[0]->float_test);
    $this->assertEquals($email, $entity->{$this->fieldName}->email_test);
    $this->assertEquals($email, $entity->{$this->fieldName}[0]->email_test);
    $this->assertEquals($telephone, $entity->{$this->fieldName}->telephone_test);
    $this->assertEquals($telephone, $entity->{$this->fieldName}[0]->telephone_test);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}->uri_test);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}[0]->uri_test);
    $this->assertEquals($boolean, $entity->{$this->fieldName}->boolean_test);
    $this->assertEquals($boolean, $entity->{$this->fieldName}[0]->boolean_test);
    $this->assertEquals($color, $entity->{$this->fieldName}->color_test);
    $this->assertEquals($color, $entity->{$this->fieldName}[0]->color_test);
    $this->assertEquals($map, $entity->{$this->fieldName}->map_test);
    $this->assertEquals($map, $entity->{$this->fieldName}[0]->map_test);
    $this->assertEquals($map_string, $entity->{$this->fieldName}->map_string_test);
    $this->assertEquals($map_string, $entity->{$this->fieldName}[0]->map_string_test);
    $this->assertEquals($datetime, $entity->{$this->fieldName}->datetime_test);
    $this->assertEquals($datetime, $entity->{$this->fieldName}[0]->datetime_test);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime_test']->getDateTime()->getTimeZone()->getName());
    $this->assertEquals($time, $entity->{$this->fieldName}->time_test);
    $this->assertEquals($time, $entity->{$this->fieldName}[0]->time_test);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}->link_test);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}[0]->link_test);
    $this->assertEquals($link_title, $entity->{$this->fieldName}->{$link_title_field});
    $this->assertEquals($link_title, $entity->{$this->fieldName}[0]->{$link_title_field});

    // Verify changing the field values.
    $new_string = $this->randomString(255);
    $new_string_long = $random->paragraphs(6);
    $new_integer = rand(11, 20);
    $new_float = rand(1001, 2000) / 100;
    $new_decimal = 18.20;
    $new_email = 'test2@example.com';
    $new_telephone = '+41' . rand(1000000, 9999999);
    $new_uri_external = 'https://www.drupal.org';
    $new_link_title = 'Drupal secure';
    $new_boolean = 0;
    $new_color = '#FFFFFF';
    $new_datetime = '2016-11-04T00:21:00';
    $new_map = [
      [
        'key' => 'New Key1',
        'value' => 'New Value1',
      ],
      [
        'key' => 'New Key2',
        'value' => 'New Value2',
      ],
      [
        'key' => 'New Key3',
        'value' => 'New Value3',
      ],
    ];
    $new_map_string = ['new text1', 'new text2', 'new text3'];
    $new_time = 56160;
    $entity->{$this->fieldName}->string_test = $new_string;
    $this->assertEquals($new_string, $entity->{$this->fieldName}->string_test);
    $entity->{$this->fieldName}->integer_test = $new_integer;
    $this->assertEquals($new_integer, $entity->{$this->fieldName}->integer_test);
    $entity->{$this->fieldName}->decimal_test = $new_decimal;
    $this->assertEquals($new_decimal, $entity->{$this->fieldName}->decimal_test);
    $entity->{$this->fieldName}->float_test = $new_float;
    $this->assertEquals($new_float, $entity->{$this->fieldName}->float_test);
    $entity->{$this->fieldName}->email_test = $new_email;
    $this->assertEquals($new_email, $entity->{$this->fieldName}->email_test);
    $entity->{$this->fieldName}->telephone_test = $new_telephone;
    $this->assertEquals($new_telephone, $entity->{$this->fieldName}->telephone_test);
    $entity->{$this->fieldName}->uri_test = $new_uri_external;
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}->uri_test);
    $entity->{$this->fieldName}->boolean_test = $new_boolean;
    $this->assertEquals($new_boolean, $entity->{$this->fieldName}->boolean_test);
    $entity->{$this->fieldName}->color_test = $new_color;
    $this->assertEquals($new_color, $entity->{$this->fieldName}->color_test);
    $entity->{$this->fieldName}->string_long_test = $new_string_long;
    $this->assertEquals(strlen($new_string_long), strlen($entity->{$this->fieldName}[0]->string_long_test));
    $entity->{$this->fieldName}->map_test = $new_map;
    $this->assertEquals($new_map, $entity->{$this->fieldName}[0]->map_test);
    $entity->{$this->fieldName}->map_string_test = $new_map_string;
    $this->assertEquals($new_map_string, $entity->{$this->fieldName}[0]->map_string_test);
    $entity->{$this->fieldName}->datetime_test = $new_datetime;
    $this->assertEquals($new_datetime, $entity->{$this->fieldName}[0]->datetime_test);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime_test']->getDateTime()->getTimeZone()->getName());
    $entity->{$this->fieldName}->time_test = $new_time;
    $this->assertEquals($new_time, $entity->{$this->fieldName}[0]->time_test);
    $entity->{$this->fieldName}->link_test = $new_uri_external;
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}[0]->link_test);
    $entity->{$this->fieldName}->{$link_title_field} = $new_link_title;
    $this->assertEquals($new_link_title, $entity->{$this->fieldName}[0]->{$link_title_field});

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = Node::load($id);
    $this->assertEquals($new_string, $entity->{$this->fieldName}->string_test);
    $this->assertEquals($new_integer, $entity->{$this->fieldName}->integer_test);
    $this->assertEquals($new_decimal, $entity->{$this->fieldName}->decimal_test);
    $this->assertEquals($new_float, $entity->{$this->fieldName}->float_test);
    $this->assertEquals($new_email, $entity->{$this->fieldName}->email_test);
    $this->assertEquals($new_telephone, $entity->{$this->fieldName}->telephone_test);
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}->uri_test);
    $this->assertEquals($new_boolean, $entity->{$this->fieldName}->boolean_test);
    $this->assertEquals($new_color, $entity->{$this->fieldName}->color_test);
    $this->assertEquals(strlen($new_string_long), strlen($entity->{$this->fieldName}[0]->string_long_test));
    $this->assertEquals($new_map, $entity->{$this->fieldName}[0]->map_test);
    $this->assertEquals($new_map_string, $entity->{$this->fieldName}[0]->map_string_test);
    $this->assertEquals($new_datetime, $entity->{$this->fieldName}[0]->datetime_test);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime_test']->getDateTime()->getTimeZone()->getName());
    $this->assertEquals($new_time, $entity->{$this->fieldName}[0]->time_test);
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}[0]->link_test);
    $this->assertEquals($new_link_title, $entity->{$this->fieldName}[0]->{$link_title_field});

    // Test sample item generation.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => $this->bundle,
    ]);
    $entity->{$this->fieldName}->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests using the datetime_type of 'date'.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testDateOnly(): void {
    $columns = $this->fieldStorage->getSetting('columns');
    $columns['datetime_test']['datetime_type'] = 'date';
    $this->fieldStorage->setSetting('columns', $columns);
    $this->fieldStorage->save();

    // Create an entity.
    $entity = Node::create([
      'title' => 'Test node title',
      'type' => $this->bundle,
    ]);
    $date = '2014-01-01';
    $entity->{$this->fieldName}->datetime_test = $date;
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = Node::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->{$this->fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $entity->{$this->fieldName}[0]);
    $this->assertEquals($date, $entity->{$this->fieldName}->datetime_test);
    $this->assertEquals($date, $entity->{$this->fieldName}[0]->datetime_test);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime_test']->getDateTime()->getTimeZone()->getName());
    /** @var \Drupal\Core\Datetime\DrupalDateTime $date_object */
    $date_object = $entity->{$this->fieldName}[0]->getProperties()['datetime_test']->getDateTime();
    $this->assertEquals('00:00:00', $date_object->format('H:i:s'));
    $date_object->setDefaultDateTime();
    $this->assertEquals('12:00:00', $date_object->format('H:i:s'));
  }

}
