<?php

namespace Drupal\custom_field\Plugin;

/**
 * Defines an interface for custom field feeds plugins.
 */
interface CustomFieldFeedsManagerInterface {

  /**
   * Get custom field feeds plugin items from an array of custom field settings.
   *
   * @param array $settings
   *   The array of Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   *   settings.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldFeedsTypeInterface[]
   *   The array of custom field feeds plugin items to return.
   */
  public function getFeedsTargets(array $settings): array;

}
