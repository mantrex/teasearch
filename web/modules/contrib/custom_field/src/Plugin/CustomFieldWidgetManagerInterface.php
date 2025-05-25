<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldWidgetManagerInterface extends PluginManagerInterface {

  /**
   * Returns an array of widget types supported for a particular field.
   *
   * @param string $type
   *   The column type or plugin id of the field.
   *
   * @return string[]
   *   The array of widget type plugin ids.
   */
  public function getWidgetsForField(string $type): array;

}
