<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'map' custom field type.
 *
 * @CustomFieldType(
 *   id = "map",
 *   label = @Translation("Serialized - Key/Value"),
 *   description = @Translation("A field for storing a serialized array of values."),
 *   category = @Translation("Map"),
 *   default_widget = "map_key_value",
 *   default_formatter = "string",
 * )
 */
class MapType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'blob',
      'size' => 'big',
      'serialize' => TRUE,
      'description' => 'A serialized array of values.',
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): array {
    $random = new Random();
    $map_values = [];
    for ($i = 0; $i < 5; $i++) {
      $map_values[] = [
        'key' => $random->word(10),
        'value' => $random->word(mt_rand(10, 20)),
      ];
    }

    return $map_values;
  }

}
