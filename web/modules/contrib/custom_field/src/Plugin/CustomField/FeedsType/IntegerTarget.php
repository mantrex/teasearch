<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldFeedsType;

/**
 * Plugin implementation of the 'integer' feeds type.
 */
#[CustomFieldFeedsType(
  id: 'integer',
  label: new TranslatableMarkup('Integer'),
  mark_unique: TRUE,
)]
class IntegerTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $value = is_string($value) ? trim($value) : $value;

    return is_numeric($value) ? (int) $value : NULL;
  }

}
