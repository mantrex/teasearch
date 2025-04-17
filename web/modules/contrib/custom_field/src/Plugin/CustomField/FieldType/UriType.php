<?php

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
  category: new TranslatableMarkup('General'),
  default_widget: 'url',
  default_formatter: 'uri_link',
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

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_uri')
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
    $link_type = $widget_settings['link_type'] ?? NULL;
    if ($link_type & $field::LINK_EXTERNAL) {
      $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
      $domain_length = mt_rand(7, 15);
      $protocol = mt_rand(0, 1) ? 'https' : 'http';
      $www = mt_rand(0, 1) ? 'www.' : '';
      $domain = $random->word($domain_length);
      $tld = $tlds[mt_rand(0, (count($tlds) - 1))];
      $value = "$protocol://$www$domain.$tld";
    }
    else {
      $value = 'base:' . $random->name(mt_rand(1, 64));
    }

    return $value;
  }

}
