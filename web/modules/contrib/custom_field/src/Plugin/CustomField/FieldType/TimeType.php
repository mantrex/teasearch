<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Time;

/**
 * Plugin implementation of the 'time' custom field type.
 */
#[CustomFieldType(
  id: 'time',
  label: new TranslatableMarkup('Time'),
  description: new TranslatableMarkup('A field containing a Time.'),
  category: new TranslatableMarkup('General'),
  default_widget: 'time_widget',
  default_formatter: 'time',
  constraints: [
    'CustomFieldTime' => [],
  ]
)]
class TimeType extends CustomFieldTypeBase {

  use TypedDataTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      $settings['name'] => [
        'type' => 'int',
        'unsigned' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {

    $properties = [];
    $properties[$settings['name']] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@name', ['@name' => $settings['name']]))
      ->setDescription(new TranslatableMarkup('Seconds passed through midnight'))
      ->setSetting('unsigned', TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    /** @var array<string, mixed> $definition */
    $definition = $this->pluginDefinition;
    return $definition['constraints'];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): string {
    // Generate random hours (1–12), minutes (0–59).
    $hours = rand(0, 23);
    $minutes = rand(0, 59);

    // Format with leading zeros.
    $time = sprintf('%02d:%02d:00', $hours, $minutes);

    return (string) Time::createFromHtml5Format($time)->getTimestamp();
  }

}
