<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'float' custom field type.
 *
 * @CustomFieldType(
 *   id = "float",
 *   label = @Translation("Number (float)"),
 *   description = @Translation("This field stores a number in the database in a floating point format."),
 *   category = @Translation("Number"),
 *   default_widget = "float",
 *   default_formatter = "number_decimal"
 * )
 */
class FloatType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'float',
      'unsigned' => $settings['unsigned'] ?? FALSE,
      'size' => $settings['size'] ?? 'normal',
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): float {
    $widget_settings = $field->getWidgetSetting('settings');
    $precision = rand(10, 32);
    $scale = rand(0, 2);
    $default_min = $field->isUnsigned() ? 0 : -pow(10, ($precision - $scale)) + 1;
    $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
    $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : pow(10, ($precision - $scale)) - 1;
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);

    return static::truncateDecimal($random_decimal, $scale);
  }

}
