<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for custom field base formatter.
 */
interface BaseFormatterInterface {

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   The textual output generated.
   */
  public function viewValue(FieldItemInterface $item, string $langcode): array;

}
