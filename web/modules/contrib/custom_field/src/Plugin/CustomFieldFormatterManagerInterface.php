<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for custom field formatter plugins.
 */
interface CustomFieldFormatterManagerInterface {

  /**
   * Return the available formatter plugins as an array keyed by plugin_id.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $custom_field
   *   The custom field associated with the formatter.
   *
   * @return array
   *   The array of formatter options.
   */
  public function getOptions(CustomFieldTypeInterface $custom_field): array;

  /**
   * Return the input path structure in formatter settings form for states api.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field_name
   *   The field name of the parent custom field.
   * @param string $property
   *   The property name of the custom field.
   * @param bool $is_views_subfield
   *   A boolean to indicate if the settings form is an individual views
   *   subfield.
   *
   * @return string
   *   The input path.
   */
  public function getInputPathForStatesApi(FormStateInterface $form_state, string $field_name, string $property, bool $is_views_subfield): string;

  /**
   * Return the value keys in formatter settings form for format_type selection.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $field_name
   *   The field name of the parent custom field.
   * @param string $property
   *   The property name of the custom field.
   *
   * @return array
   *   An array of value keys.
   */
  public function getFormatterValueKeys(FormStateInterface $form_state, string $field_name, string $property): array;

}
