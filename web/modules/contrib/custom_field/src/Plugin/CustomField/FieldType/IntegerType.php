<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'integer' custom field type.
 *
 * @CustomFieldType(
 *   id = "integer",
 *   label = @Translation("Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer."),
 *   category = @Translation("Number"),
 *   default_widget = "integer",
 *   default_formatter = "number_integer",
 * )
 */
class IntegerType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'int',
      'size' => $settings['size'] ?? 'normal',
      'unsigned' => $settings['unsigned'] ?? FALSE,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = [];
    // To prevent a PDO exception from occurring, restrict values in the range
    // allowed by databases.
    $min = $this->getDefaultMinValue($settings);
    $max = $this->getDefaultMaxValue($settings);
    $constraints['Range']['min'] = $min;
    $constraints['Range']['max'] = $max;

    return $constraints;
  }

  /**
   * Helper method to get the min value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The minimum value allowed by database.
   */
  protected function getDefaultMinValue(array $settings): int|float {
    if ($settings['unsigned']) {
      return 0;
    }

    // Each value is - (2 ^ (8 * bytes - 1)).
    $size_map = [
      'normal' => -2147483648,
      'tiny' => -128,
      'small' => -32768,
      'medium' => -8388608,
      'big' => -9223372036854775808,
    ];
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
  }

  /**
   * Helper method to get the max value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The maximum value allowed by database.
   */
  protected function getDefaultMaxValue(array $settings): int|float {
    if ($settings['unsigned']) {
      // Each value is (2 ^ (8 * bytes) - 1).
      $size_map = [
        'normal' => 4294967295,
        'tiny' => 255,
        'small' => 65535,
        'medium' => 16777215,
        'big' => 18446744073709551615,
      ];
    }
    else {
      // Each value is (2 ^ (8 * bytes - 1) - 1).
      $size_map = [
        'normal' => 2147483647,
        'tiny' => 127,
        'small' => 32767,
        'medium' => 8388607,
        'big' => 9223372036854775807,
      ];
    }
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string|int {
    $widget_settings = $field->getWidgetSetting('settings');
    if (!empty($widget_settings['allowed_values'])) {
      return static::getRandomOptions($widget_settings['allowed_values']);
    }
    $default_min = $field->isUnsigned() ? 0 : -1000;
    $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
    $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : 1000;

    return mt_rand($min, $max);
  }

}
