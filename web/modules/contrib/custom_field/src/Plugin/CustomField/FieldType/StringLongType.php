<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'string_long' custom field type.
 *
 * @CustomFieldType(
 *   id = "string_long",
 *   label = @Translation("Text (long)"),
 *   description = @Translation("A field containing a long string value."),
 *   category = @Translation("Text"),
 *   default_widget = "textarea",
 *   default_formatter = "text_default",
 * )
 */
class StringLongType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'text',
      'size' => 'big',
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_string_long')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    $random = new Random();
    $widget_settings = $field->getWidgetSetting('settings');
    $max_length = $widget_settings['maxlength'] ?? NULL;

    if (empty($max_length)) {
      $value = $random->paragraphs();
    }
    else {
      $max = ceil($max_length / 3);
      $value = substr($random->sentences(mt_rand(1, $max), FALSE), 0, $max_length);
    }

    return $value;
  }

}
