<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'string_long' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "string_long",
 *   label = @Translation("String long"),
 *   mark_unique = TRUE,
 * )
 */
class StringLongTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $value = parent::prepareValue($value, $configuration, $langcode);

    return !empty($value) ? $value : NULL;
  }

}
