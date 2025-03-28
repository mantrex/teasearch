<?php

namespace Drupal\custom_field\Plugin;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeManagerInterface {

  /**
   * Get custom field plugin items from an array of custom field settings.
   *
   * @param array $settings
   *   The array of Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   *   settings.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   The array of custom field plugin items to return.
   */
  public function getCustomFieldItems(array $settings): array;

  /**
   * Builds options for a select list based on field types.
   *
   * @return array
   *   An array of options suitable for a select list.
   */
  public function fieldTypeOptions(): array;

}
