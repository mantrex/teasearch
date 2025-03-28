<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_table' formatter.
 *
 * Formats the custom field items as html table.
 *
 * @FieldFormatter(
 *   id = "custom_table",
 *   label = @Translation("Table"),
 *   weight = 2,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomTableFormatter extends BaseFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      // Remove non-applicable settings.
      unset($form['fields'][$name]['formatter_settings']['label_display']);
      unset($form['fields'][$name]['wrappers']['label_tag']);
      unset($form['fields'][$name]['wrappers']['label_classes']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $settings = $this->getSettings();
    if (!$items->isEmpty()) {
      $component = Html::cleanCssIdentifier($this->fieldDefinition->get('field_name'));
      $custom_items = $this->getCustomFieldItems();
      $header = [];
      foreach ($custom_items as $name => $custom_item) {
        $is_hidden = $settings['fields'][$name]['format_type'] === 'hidden' ?? FALSE;
        if ($is_hidden) {
          continue;
        }
        $field_label = $settings['fields'][$name]['formatter_settings']['field_label'] ?? NULL;
        $header[] = !empty($field_label) ? $field_label : $custom_item->getLabel();
      }

      // Jam the whole table in the first row since we're rendering the main
      // field items as table rows.
      $elements[0] = [
        '#theme' => 'table',
        '#header' => $header,
        '#attributes' => [
          'class' => [$component],
        ],
        '#rows' => [],
      ];

      // Build the table rows and columns.
      foreach ($items as $delta => $item) {
        $elements[0]['#rows'][$delta]['class'][] = $component . '__item';
        $values = $this->getFormattedValues($item, $langcode);
        foreach ($custom_items as $name => $custom_item) {
          $is_hidden = $settings['fields'][$name]['format_type'] === 'hidden' ?? FALSE;
          if ($is_hidden) {
            continue;
          }
          $value = $values[$name] ?? NULL;
          $output = NULL;
          if ($value !== NULL) {
            $output = [
              '#theme' => 'custom_field_item',
              '#field_name' => $name,
              '#name' => $value['name'],
              '#value' => $value['value']['#markup'],
              '#label' => $value['label'],
              '#label_display' => 'hidden',
              '#type' => $value['type'],
              '#wrappers' => $value['wrappers'],
              '#entity_type' => $value['entity_type'],
              '#lang_code' => $langcode,
            ];
          }
          $elements[0]['#rows'][$delta]['data'][$name] = [
            'data' => $output,
            'class' => [$component . '__' . Html::cleanCssIdentifier($name)],
          ];
        }
      }
    }

    return $elements;
  }

}
