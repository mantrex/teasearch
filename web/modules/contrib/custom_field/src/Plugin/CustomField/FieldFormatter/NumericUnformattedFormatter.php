<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'number_unformatted' formatter.
 *
 * @FieldFormatter(
 *   id = "number_unformatted",
 *   label = @Translation("Unformatted"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float",
 *   }
 * )
 */
class NumericUnformattedFormatter extends DecimalFormatter {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    return $value;
  }

}
