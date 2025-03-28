<?php

namespace Drupal\custom_field;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines an interface for custom field data generation.
 */
interface CustomFieldGenerateDataInterface {

  /**
   * Generates field data for custom field.
   *
   * @param array $columns
   *   Array of field columns from the field storage settings.
   * @param array $field_settings
   *   Optional array of field widget settings.
   *
   * @return array
   *   Array of key/value pairs to populate custom field.
   */
  public function generateFieldData(array $columns, array $field_settings = []): array;

  /**
   * Generates random form data that is ready to save.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field definition.
   * @param null $deltas
   *   An array of deltas to generate form data for.
   *
   * @return array|string[]
   *   An associative array of form data.
   */
  public function generateSampleFormData(FieldDefinitionInterface $field, $deltas = NULL): array;

}
