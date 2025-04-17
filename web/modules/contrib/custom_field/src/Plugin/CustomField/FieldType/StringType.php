<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'string' field type.
 */
#[CustomFieldType(
  id: 'string',
  label: new TranslatableMarkup('Text (plain)'),
  description: new TranslatableMarkup('A field containing a plain string value.'),
  category: new TranslatableMarkup('Text'),
  default_widget: 'text',
  default_formatter: 'string',
)]
class StringType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'varchar',
      'length' => $settings['max_length'] ?? 255,
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
    $constraints = [];
    if ($max_length = $settings['max_length']) {
      $constraints['Length'] = [
        'max' => $max_length,
        'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
          '%name' => $settings['name'],
          '@max' => $max_length,
        ]),
      ];
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    $widget_settings = $field->getWidgetSetting('settings');
    if (!empty($widget_settings['allowed_values'])) {
      return static::getRandomOptions($widget_settings['allowed_values']);
    }
    $random = new Random();
    $max_length = isset($widget_settings['maxlength']) && is_numeric($widget_settings['maxlength']) ? $widget_settings['maxlength'] : $field->getMaxLength();

    // When the maximum length is less than 15 generate a random word using the
    // maximum length.
    if ($max_length <= 15) {
      return ucfirst($random->word($max_length));
    }

    // The minimum length is either 10% of the maximum length, or 15 characters
    // long, whichever is greater.
    $min_length = max(ceil($max_length * 0.10), 15);

    // Reduce the max length to allow us to add a period.
    $max_length -= 1;

    // The random value is generated multiple times to create a slight
    // preference towards values that are closer to the minimum length of the
    // string. For values larger than 255 (which is the default maximum value),
    // the bias towards minimum length is increased. This is because the default
    // maximum length of 255 is often used for fields that include shorter
    // values (i.e. title).
    $length = mt_rand($min_length, mt_rand($min_length, $max_length >= 255 ? mt_rand($min_length, $max_length) : $max_length));

    $string = $random->sentences(1);
    while (mb_strlen($string) < $length) {
      $string .= " {$random->sentences(1)}";
    }

    if (mb_strlen($string) > $max_length) {
      $string = substr($string, 0, $length);
      $string = substr($string, 0, strrpos($string, ' '));
    }

    $string = rtrim($string, ' .');

    // Ensure that the string ends with a full stop if there are multiple
    // sentences.
    return $string . (str_contains($string, '.') ? '.' : '');
  }

}
