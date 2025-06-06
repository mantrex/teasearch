<?php

namespace Drupal\mfd;

/**
 * Field manager for MFD fields.
 */
class MfdFieldManager implements MfdFieldManagerInterface {

  /**
   * The id of the Multilingual Form Display Type.
   */
  const MULTILINGUAL_FORM_DISPLAY = "multilingual_form_display";

  /**
   * Has mfd field method.
   *
   * This is a service that determines if a form has a Multilingual Form Display
   * initialized and returns a boolean to that effect.
   */
  public function hasMfdField($entity): bool {
    $field_def = $entity->getFieldDefinitions();
    // Takes the field definitions and loops through to find the mfd field.
    foreach ($field_def as $field) {
      if ($field->getType() == self::MULTILINGUAL_FORM_DISPLAY) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
