<?php

namespace Drupal\custom_field\Plugin;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldWidgetManagerInterface {

  /**
   * Returns an array of widget types supported for a particular field.
   *
   * @param string $type
   *   The column type or plugin id of the field.
   *
   * @return array
   *   The array of widget type plugin ids.
   */
  public function getWidgetsForField(string $type): array;

}
