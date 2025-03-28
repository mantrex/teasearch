<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'string' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "string",
 *   label = @Translation("String"),
 *   mark_unique = TRUE,
 * )
 */
class StringTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $value = parent::prepareValue($value, $configuration, $langcode);

    return !empty($value) ? $value : NULL;
  }

}
