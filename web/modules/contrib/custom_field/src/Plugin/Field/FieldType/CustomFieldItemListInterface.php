<?php

namespace Drupal\custom_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for entity reference lists of field items.
 */
interface CustomFieldItemListInterface extends FieldItemListInterface {

  /**
   * Gets the entities referenced by this field, preserving field item deltas.
   *
   * @return string[]
   *   An array of target types list of entity objects keyed by field item
   *   deltas.
   */
  public function referencedEntities(): array;

}
