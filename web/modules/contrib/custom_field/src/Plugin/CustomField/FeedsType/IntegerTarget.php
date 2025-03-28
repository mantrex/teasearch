<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'integer' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   mark_unique = TRUE,
 * )
 */
class IntegerTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $value = is_string($value) ? trim($value) : $value;

    return is_numeric($value) ? (int) $value : NULL;
  }

}
