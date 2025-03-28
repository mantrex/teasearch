<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'custom_formatter' formatter.
 *
 * Generic formatter, renders the items using the custom_field theme hook
 * implementation.
 *
 * @FieldFormatter(
 *   id = "custom_formatter",
 *   label = @Translation("Default"),
 *   weight = 0,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomFormatter extends BaseFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewValue(FieldItemInterface $item, string $langcode): array {
    $field_name = $this->fieldDefinition->get('field_name');
    $output = [
      '#theme' => 'custom_field',
      '#field_name' => $field_name,
      '#items' => [],
    ];

    $values = $this->getFormattedValues($item, $langcode);

    foreach ($values as $value) {
      $output['#items'][] = [
        '#theme' => 'custom_field_item',
        '#field_name' => $field_name,
        '#name' => $value['name'],
        '#value' => $value['value']['#markup'],
        '#label' => $value['label'],
        '#label_display' => $value['label_display'],
        '#type' => $value['type'],
        '#wrappers' => $value['wrappers'],
        '#entity_type' => $value['entity_type'],
        '#lang_code' => $langcode,
      ];
    }

    return $output;
  }

}
