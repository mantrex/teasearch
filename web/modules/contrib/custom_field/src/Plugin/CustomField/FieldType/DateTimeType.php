<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'datetime' field type.
 */
#[CustomFieldType(
  id: 'datetime',
  label: new TranslatableMarkup('Date'),
  description: new TranslatableMarkup('A field containing a Date.'),
  category: new TranslatableMarkup('General'),
  default_widget: 'datetime_default',
  default_formatter: 'datetime_default',
)]
class DateTimeType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'varchar',
      'length' => 20,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    $datetime_type = $field->getDatetimeType();
    $timestamp = \Drupal::time()->getRequestTime() - mt_rand(0, 86400 * 365);
    if ($datetime_type == $field::DATETIME_TYPE_DATE) {
      $value = gmdate($field::DATE_STORAGE_FORMAT, $timestamp);
    }
    else {
      $value = gmdate($field::DATETIME_STORAGE_FORMAT, $timestamp);
    }

    return $value;
  }

  /**
   * Get the Unix timestamp from the stored datetime value.
   *
   * @return int|null
   *   The Unix timestamp, or NULL if the value is invalid.
   */
  public function getTimestamp(FieldItemInterface $item): ?int {
    $value = $this->value($item);

    // Ensure the value is not empty and is in the correct ISO 8601 format.
    if (!empty($value)) {
      try {
        $datetime = new \DateTime($value);
        return $datetime->getTimestamp();
      }
      catch (\Exception $e) {
        // Handle invalid datetime formats gracefully.
        return NULL;
      }
    }

    return NULL;
  }

}
