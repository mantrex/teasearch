<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'float' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "float",
 *   label = @Translation("Float"),
 *   mark_unique = FALSE,
 * )
 */
class FloatTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): mixed {
    $value = parent::prepareValue($value, $configuration, $langcode);

    return is_numeric($value) ? $value : NULL;
  }

}
