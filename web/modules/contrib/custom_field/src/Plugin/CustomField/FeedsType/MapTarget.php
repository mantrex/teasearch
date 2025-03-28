<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

/**
 * Plugin implementation of the 'map' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "map",
 *   label = @Translation("Serialized - Key/Value"),
 *   mark_unique = FALSE,
 * )
 */
class MapTarget extends BaseTarget {

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?array {
    $decoded = json_decode($value, TRUE);
    if (is_array($decoded) && !empty($decoded)) {
      return $decoded;
    }

    return NULL;
  }

}
