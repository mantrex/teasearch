<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'hidden' formatter.
 *
 * @FieldFormatter(
 *   id = "hidden",
 *   label = @Translation("Hidden"),
 *   field_types = {
 *     "boolean",
 *     "string",
 *     "string_long",
 *     "uri",
 *     "email",
 *     "map",
 *     "map_string",
 *     "telephone",
 *     "uuid",
 *     "color",
 *     "integer",
 *     "float",
 *     "datetime",
 *     "file",
 *     "entity_reference",
 *     "image",
 *     "viewfield",
 *   }
 * )
 */
class HiddenFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    return NULL;
  }

}
