<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldFeedsType;

/**
 * Plugin implementation of the 'string' feeds type.
 */
#[CustomFieldFeedsType(
  id: 'string',
  label: new TranslatableMarkup('String'),
  mark_unique: TRUE,
)]
class StringTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue(mixed $value, array $configuration, string $langcode): ?string {
    $value = parent::prepareValue($value, $configuration, $langcode);

    return !empty($value) ? $value : NULL;
  }

}
