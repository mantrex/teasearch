<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'link' field type.
 */
#[CustomFieldType(
  id: 'link',
  label: new TranslatableMarkup('Link'),
  description: new TranslatableMarkup('Stores a URL string, optional varchar link text, and optional blob of attributes to assemble a link.'),
  category: new TranslatableMarkup('Link'),
  default_widget: 'link_default',
  default_formatter: 'link',
  constraints: [
    "CustomFieldLinkAccess" => [],
    "CustomFieldLinkExternalProtocols" => [],
    "CustomFieldLinkType" => [],
    "CustomFieldLinkNotExistingInternal" => [],
  ]
)]
class LinkType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $title = $name . self::SEPARATOR . 'title';
    $options = $name . self::SEPARATOR . 'options';
    $columns[$name] = [
      'description' => 'The URI of the link.',
      'type' => 'varchar',
      'length' => 2048,
    ];
    $columns[$title] = [
      'description' => 'The link text.',
      'type' => 'varchar',
      'length' => 255,
    ];
    $columns[$options] = [
      'description' => 'Serialized array of options for the link.',
      'type' => 'blob',
      'size' => 'big',
      'serialize' => TRUE,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name] = $settings;

    $title = $name . self::SEPARATOR . 'title';
    $options = $name . self::SEPARATOR . 'options';

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_link')
      ->setLabel(new TranslatableMarkup('@label URI', ['@label' => $name]))
      ->setRequired(FALSE)
      ->setSetting('field_type', 'link');

    $properties[$title] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label link text', ['@label' => $name]))
      ->setInternal(TRUE);

    $properties[$options] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('@label options', ['@label' => $name]))
      ->setInternal(TRUE);

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

      switch ($widget_settings['title']) {
        case DRUPAL_DISABLED:
          $value['title'] = '';
          break;

        case DRUPAL_REQUIRED:
          $value['title'] = $random->sentences(4);
          break;

        case DRUPAL_OPTIONAL:
          // In case of optional title, randomize its generation.
          $value['title'] = mt_rand(0, 1) ? $random->sentences(4) : '';
          break;
      }
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
