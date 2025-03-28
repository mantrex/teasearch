<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'decimal' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   mark_unique = TRUE,
 * )
 */
class DecimalTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): mixed {
    $value = parent::prepareValue($value, $configuration, $langcode);

    return is_numeric($value) ? $value : NULL;
  }

}
