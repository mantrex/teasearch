<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\MapWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'map_text' widget.
 */
#[CustomFieldWidget(
  id: 'map_text',
  label: new TranslatableMarkup('Map: Text'),
  category: new TranslatableMarkup('Map'),
  field_types: [
    'map_string',
  ],
)]
class MapTextWidget extends MapWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $element['#element_validate'] = [[static::class, 'validateArrayValues']];
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $field_name = $items->getFieldDefinition()->getName();
    $custom_field_name = $field->getName();
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    $field_parents = [
      $field_name,
      $delta,
      $custom_field_name,
    ];
    if ($is_config_form) {
      array_unshift($field_parents, 'default_value_input');
    }

    $wrapper_id = $field_name . $delta . $custom_field_name;
    $element['#attached'] = [
      'library' => ['custom_field/customfield-admin'],
    ];

    if (!$form_state->has($wrapper_id)) {
      $default_value = $element['#default_value'] ?? [];
      $form_state->set($wrapper_id, $default_value);
    }

    $items = $form_state->get($wrapper_id);

    $element['data'] = [
      '#type' => 'table',
      '#empty' => $settings['table_empty'] ?? NULL,
      '#attributes' => [
        'class' => ['customfield-map-table'],
      ],
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#wrapper_id' => $wrapper_id,
    ];
    foreach ($items as $key => $value) {
      $element['data'][$key]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#title_display' => 'invisible',
        '#default_value' => $value,
        '#required' => TRUE,
      ];
      $element['data'][$key]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [[static::class, 'removeItem']],
        '#name' => 'remove_' . $wrapper_id . $key,
        '#attributes' => ['data-key' => $key],
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [$field_parents],
      ];
    }
    $element['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add item'),
      '#submit' => [[static::class, 'addItem']],
      '#name' => 'add_' . $wrapper_id,
      '#ajax' => [
        'callback' => [$this, 'actionCallback'],
        'wrapper' => $wrapper_id,
      ],
      '#limit_validation_errors' => [$field_parents],
    ];

    return $element;
  }

  /**
   * The #element_validate callback for map field array values.
   *
   * @param array<string, mixed> $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateArrayValues(array $element, FormStateInterface $form_state): void {
    $wrapper_id = $element['data']['#wrapper_id'];
    $values = $element['data']['#value'] ?? NULL;
    $filtered_values = [];
    $has_errors = FALSE;
    if (is_array($values)) {
      foreach ($values as $key => $value) {
        if (!is_array($value)) {
          continue;
        }
        $filtered_value = $value['value'] ? trim($value['value']) : '';
        // Make sure each value is unique.
        if (in_array($filtered_value, $filtered_values)) {
          $has_errors = TRUE;
          break;
        }
        else {
          $filtered_values[$key] = $filtered_value;
        }
      }
    }
    if ($has_errors) {
      $form_state->setError($element, t('All values must be unique.'));
    }
    else {
      $form_state->set($wrapper_id, $filtered_values);
      $form_state->setValueForElement($element, $filtered_values);
    }
  }

}
