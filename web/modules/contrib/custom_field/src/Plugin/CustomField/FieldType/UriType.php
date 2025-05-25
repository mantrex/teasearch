<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'uri' field type.
 */
#[CustomFieldType(
  id: 'uri',
  label: new TranslatableMarkup('URI'),
  description: new TranslatableMarkup('A field containing a URI.'),
  category: new TranslatableMarkup('Link'),
  default_widget: 'url',
  default_formatter: 'uri_link',
  constraints: [
    "CustomFieldLinkAccess" => [],
    "CustomFieldLinkExternalProtocols" => [],
    "CustomFieldLinkType" => [],
    "CustomFieldLinkNotExistingInternal" => [],
  ]
)]
class UriType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'type' => 'varchar',
      'length' => 2048,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_link')
      ->setLabel(new TranslatableMarkup('@label', ['@label' => $name]))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): array {
    $random = new Random();
    $widget_settings = $field->getWidgetSetting('settings');
    $link_type = $widget_settings['link_type'] ?? NULL;
    if ($link_type & $field::LINK_EXTERNAL) {
      $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
      $domain_length = mt_rand(7, 15);

      $value['uri'] = 'https://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (count($tlds) - 1))];
    }
    else {
      $value['uri'] = 'base:' . $random->name(mt_rand(1, 64));
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    /** @var array<string, mixed> $definition */
    $definition = $this->pluginDefinition;
    return $definition['constraints'];
  }

}
