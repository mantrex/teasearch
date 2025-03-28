<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'telephone' custom field type.
 *
 * @CustomFieldType(
 *   id = "telephone",
 *   label = @Translation("Telephone number"),
 *   description = @Translation("This field stores a telephone number in the database."),
 *   category = @Translation("General"),
 *   default_widget = "telephone",
 *   default_formatter = "telephone_link",
 * )
 */
class TelephoneType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'varchar',
      'length' => $settings['max_length'] ?? 256,
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
    $area_code = mt_rand(100, 999);
    $prefix = mt_rand(100, 999);
    $line_number = mt_rand(1000, 9999);

    return "$area_code-$prefix-$line_number";
  }

}
