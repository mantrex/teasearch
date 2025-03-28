<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'map_string' custom field type.
 *
 * @CustomFieldType(
 *   id = "map_string",
 *   label = @Translation("Serialized - Text (plain)"),
 *   description = @Translation("A field for storing a serialized array of strings."),
 *   category = @Translation("Map"),
 *   default_widget = "map_text",
 *   default_formatter = "string",
 * )
 */
class MapStringType extends MapType {

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): array {
    $random = new Random();
    $map_values = [];
    for ($i = 0; $i < 5; $i++) {
      $map_values[] = $random->word(mt_rand(10, 20));
    }

    return $map_values;
  }

}
