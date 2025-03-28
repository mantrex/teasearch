<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'email' custom field type.
 *
 * @CustomFieldType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("A field containing an e-mail value."),
 *   category = @Translation("General"),
 *   default_widget = "email",
 *   default_formatter = "email_mailto",
 * )
 */
class EmailType extends CustomFieldTypeBase {

  public const EMAIL_MAX_LENGTH = 254;

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'varchar',
      'length' => self::EMAIL_MAX_LENGTH,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('email')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints['Length'] = [
      'max' => self::EMAIL_MAX_LENGTH,
      'maxMessage' => $this->t('%name: the email address can not be longer than @max characters.', [
        '%name' => $settings['name'],
        '@max' => self::EMAIL_MAX_LENGTH,
      ]),
    ];

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    $random = new Random();

    return $random->name() . '@example.com';
  }

}
