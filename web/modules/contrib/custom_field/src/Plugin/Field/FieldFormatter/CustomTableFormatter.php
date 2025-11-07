<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'custom_table' formatter.
 */
#[FieldFormatter(
  id: 'custom_table',
  label: new TranslatableMarkup('Table'),
  description: new TranslatableMarkup('Formats the custom field items as html table.'),
  field_types: [
    'custom',
  ],
  weight: 2,
)]
class CustomTableFormatter extends BaseFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'hide_empty' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Hide columns with empty rows: @hide_empty', ['@hide_empty' => $this->getSetting('hide_empty') ? 'Yes' : 'No']);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide columns with empty rows'),
      '#default_value' => $this->getSetting('hide_empty'),
    ];
    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      // Remove non-applicable settings.
      unset($form['fields'][$name]['content']['formatter_settings']['label_display']);
      unset($form['fields'][$name]['content']['wrappers']['label_tag']);
      unset($form['fields'][$name]['content']['wrappers']['label_classes']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    if (!$items->isEmpty()) {
      $component = Html::cleanCssIdentifier($this->fieldDefinition->getName());
      $settings = $this->getSetting('fields') ?? [];
      $hide_empty = $this->getSetting('hide_empty') ?? FALSE;
      $custom_items = $this->sortFields($settings);

      // Track columns with non-empty values.
      $column_has_values = [];
      $header = [];
      $valid_columns = [];

      foreach ($custom_items as $name => $custom_item) {
        $setting = $settings[$name] ?? [];
        $is_hidden = isset($setting['format_type']) && $setting['format_type'] === 'hidden';
        if ($is_hidden) {
          continue;
        }
        $formatter_settings = $setting['formatter_settings'] ?? [];
        $field_label = $formatter_settings['field_label'] ?? NULL;
        $header[$name] = !empty($field_label) ? $field_label : $custom_item->getLabel();
        $column_has_values[$name] = FALSE;
        $valid_columns[$name] = TRUE;
      }

      // Initialize rows.
      $rows = [];

      foreach ($items as $delta => $item) {
        $row = [
          'class' => [$component . '__item'],
          'data' => [],
        ];
        $values = $this->getFormattedValues($item, $langcode);
        foreach ($custom_items as $name => $custom_item) {
          if (!isset($valid_columns[$name])) {
            continue;
          }
          $value = $values[$name] ?? NULL;
          $output = NULL;
          if ($value !== NULL) {
            $column_has_values[$name] = TRUE;
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
          $row['data'][$name] = [
            'data' => $output ?: '',
            'class' => [$component . '__' . Html::cleanCssIdentifier((string) $name)],
          ];
        }
        $rows[$delta] = $row;
      }

      // Filter headers and rows based on table_empty setting.
      $filtered_header = [];
      $filtered_rows = [];
      foreach ($valid_columns as $name => $is_valid) {
        if (!$hide_empty || $column_has_values[$name]) {
          $filtered_header[] = $header[$name];
          foreach ($rows as $delta => $row) {
            $filtered_rows[$delta]['class'] = $row['class'];
            $filtered_rows[$delta]['data'][] = $row['data'][$name];
          }
        }
      }

      if (!empty($filtered_header)) {
        $elements[0] = [
          '#theme' => 'table',
          '#header' => $filtered_header,
          '#attributes' => [
            'class' => [$component],
          ],
          '#rows' => $filtered_rows,
        ];
      }
    }

    return $elements;
  }

}
