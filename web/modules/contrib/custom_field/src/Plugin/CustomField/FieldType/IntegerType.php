<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'integer' field type.
 */
#[CustomFieldType(
  id: 'integer',
  label: new TranslatableMarkup('Number (integer)'),
  description: new TranslatableMarkup('This field stores a number in the database as an integer.'),
  category: new TranslatableMarkup('Number'),
  default_widget: 'integer',
  default_formatter: 'number_integer',
)]
class IntegerType extends NumericTypeBase {

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
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string|int {
    $widget_settings = $field->getWidgetSetting('settings');
    if (!empty($widget_settings['allowed_values'])) {
      return self::getRandomOptions($widget_settings['allowed_values']);
    }
    $default_min = $field->isUnsigned() ? 0 : -1000;
    $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
    $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : 1000;

    return mt_rand($min, $max);
  }

}
