<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'decimal' custom field type.
 *
 * @CustomFieldType(
 *   id = "decimal",
 *   label = @Translation("Number (decimal)"),
 *   description = @Translation("This field stores a number in the database in a fixed decimal format."),
 *   category = @Translation("Number"),
 *   default_widget = "decimal",
 *   default_formatter = "number_decimal"
 * )
 */
class DecimalType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'numeric',
      'precision' => $settings['precision'] ?? 10,
      'scale' => $settings['scale'] ?? 2,
      'unsigned' => $settings['unsigned'] ?? FALSE,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints['Regex']['pattern'] = '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i';
    if (isset($settings['min']) && $settings['min'] !== '') {
      $min = $settings['min'];
      $constraints['Range']['min'] = $min;
      $constraints['Range']['minMessage'] = $this->t('%name: the value may be no less than %min.', [
        '%name' => $settings['name'],
        '%min' => $min,
      ]);
    }
    if (isset($settings['max']) && $settings['max'] !== '') {
      $max = $settings['max'];
      $constraints['Range']['max'] = $max;
      $constraints['Range']['maxMessage'] = $this->t('%name: the value may be no greater than %max.', [
        '%name' => $settings['name'],
        '%max' => $max,
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): float {
    $widget_settings = $field->getWidgetSetting('settings');
    $precision = $field->getPrecision() ?: 10;
    $scale = $field->getScale() ?: 2;
    $default_min = $field->isUnsigned() ? 0 : -pow(10, ($precision - $scale)) + 1;
    $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
    $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : pow(10, ($precision - $scale)) - 1;

    // Get the number of decimal digits for the $max.
    $decimal_digits = static::getDecimalDigits($max);
    // Do the same for the min and keep the higher number of decimal
    // digits.
    $decimal_digits = max(static::getDecimalDigits($min), $decimal_digits);
    // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3.
    $scale = rand($decimal_digits, $scale);
    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);

    return static::truncateDecimal($random_decimal, $scale);
  }

}
