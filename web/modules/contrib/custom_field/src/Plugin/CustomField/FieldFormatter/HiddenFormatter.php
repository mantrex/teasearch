<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'hidden' formatter.
 */
#[FieldFormatter(
  id: 'hidden',
  label: new TranslatableMarkup('Hidden'),
  field_types: [
    'boolean',
    'color',
    'datetime',
    'file',
    'float',
    'email',
    'entity_reference',
    'image',
    'integer',
    'link',
    'map',
    'map_string',
    'string',
    'string_long',
    'telephone',
    'time',
    'uri',
    'uuid',
    'viewfield',
  ],
)]
class HiddenFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, mixed $value): mixed {
    return NULL;
  }

}
