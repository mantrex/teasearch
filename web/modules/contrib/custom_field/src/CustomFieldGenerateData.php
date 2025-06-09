<?php

declare(strict_types=1);

namespace Drupal\custom_field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;

/**
 * The CustomFieldGenerateData class.
 */
final class CustomFieldGenerateData implements CustomFieldGenerateDataInterface {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  private readonly CustomFieldTypeManagerInterface $customFieldTypeManager;

  /**
   * Constructs a new CustomFieldGenerateData object.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_type_manager
   *   The custom field type manager.
   */
  public function __construct(CustomFieldTypeManagerInterface $custom_field_type_manager) {
    $this->customFieldTypeManager = $custom_field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFieldData(array $settings, string $target_entity_type): array {
    $items = [];
    $custom_items = $this->customFieldTypeManager->getCustomFieldItems($settings);
    foreach ($custom_items as $name => $custom_item) {
      $items[$name] = $custom_item->generateSampleValue($custom_item, $target_entity_type);
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleFormData(FieldDefinitionInterface $field, ?array $deltas = NULL): array {
    $field_name = $field->getName();
    if ($deltas === NULL) {
      $deltas = [0];
    }

    // Generate data for the field.
    $settings = $field->getSettings();
    $target_entity_type = $field->getTargetEntityTypeId();

    $form_values = [
      'title[0][value]' => 'Test',
    ];
    foreach ($deltas as $delta) {
      $random_values = self::generateFieldData($settings, $target_entity_type);

      // UUID's can't be unset through the GUI.
      unset($random_values['uuid_test']);

      // @todo Hardening: floating point calculation can randomly fail.
      $random_values['decimal_test'] = '0.50';
      $random_values['float_test'] = '10.775';
      // Cast integer to string.
      $random_values['integer_test'] = (string) $random_values['integer_test'];
      // Set a valid time string.
      $random_values['time_test'] = Time::createFromTimestamp($random_values['time_test'])->format('h:iA');

      // @todo Hardening: we need to treat maps specially due to ajax.
      unset($random_values['map_test']);
      unset($random_values['map_string_test']);

      // @todo Hardening: why do color fields not set using ::submitForm?
      unset($random_values['color_test']);

      // @todo Hardening: figure out why an array fails as datetime value.
      unset($random_values['datetime_test']);

      // @todo Hardening: Add support for entity reference.
      unset($random_values['entity_reference_test']);

      // @todo Hardening: Add support for file.
      unset($random_values['file_test']);

      // @todo Hardening: Add support for image.
      unset($random_values['image_test']);

      // @todo Hardening: Add support for viewfield.
      unset($random_values['viewfield_test']);

      foreach ($random_values as $subfield => $value) {
        $element_key = "{$field_name}[$delta][$subfield]";

        // Handle nested fields for 'uri' and 'link' types.
        if (in_array($subfield, ['uri_test', 'link_test'])) {
          $form_values["{$element_key}[uri]"] = $value['uri'];
          if (isset($value['title'])) {
            $form_values["{$element_key}[title]"] = $value['title'] ?: 'Test title';
          }
        }
        else {
          // Handle flat subfields (e.g., string_test).
          $form_values[$element_key] = $value;
        }
      }
    }

    return $form_values;
  }

}
