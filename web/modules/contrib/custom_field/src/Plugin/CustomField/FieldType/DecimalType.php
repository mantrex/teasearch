<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'decimal' field type.
 */
#[CustomFieldType(
  id: "decimal",
  label: new TranslatableMarkup("Number (decimal)"),
  description: [
    new TranslatableMarkup("Ideal for exact counts and measures (prices, temperatures, distances, volumes, etc.)"),
    new TranslatableMarkup("Stores a number in the database in a fixed decimal format"),
    new TranslatableMarkup("For example, 12.34 km or € when used for further detailed calculations (such as summing many of these)"),
  ],
  category: new TranslatableMarkup("Number"),
  default_widget: "decimal",
  default_formatter: "number_decimal",
)]
class DecimalType extends NumericTypeBase {

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

    $properties[$name] = DataDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = parent::getConstraints($settings);
    $constraints['Regex']['pattern'] = '/^[+-]?((\d+(\.\d*)?)|(\.\d+))$/i';

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): float {
    $widget_settings = $field->getWidgetSetting('settings');
    $precision = $field->getPrecision() ?: 10;
    $scale = $field->getScale() ?: 2;
    $margin = $precision - $scale;
    // Hack precision into valid value that can be stored.
    if ($precision >= $margin) {
      $precision = $margin + 2;
    }

    $default_min = $field->isUnsigned() ? 0 : -pow(10, ($precision - $scale)) + 1;
    $default_max = pow(10, ($precision - $scale)) - 1;
    $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
    $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : $default_max;

    // Get the number of decimal digits for the $max.
    $decimal_digits = self::getDecimalDigits($max);
    // Do the same for the min and keep the higher number of decimal
    // digits.
    $decimal_digits = max(self::getDecimalDigits($min), $decimal_digits);

    // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3.
    $scale = rand($decimal_digits, $scale);

    // Generate random decimal and truncate to scale.
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);

    return self::truncateDecimal($random_decimal, $scale);
  }

}
