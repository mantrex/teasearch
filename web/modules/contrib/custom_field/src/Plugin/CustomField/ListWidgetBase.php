<?php

namespace Drupal\custom_field\Plugin\CustomField;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Base plugin class for list custom field widgets.
 */
class ListWidgetBase extends CustomFieldWidgetBase {

  /**
   * The data type of this field in the table.
   *
   * @var string
   */
  protected static $storageType = '';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'allowed_values' => [
          [
            'key' => NULL,
            'value' => '',
          ],
        ],
        'empty_option' => '- Select -',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    $options = [];
    if (!empty($settings['allowed_values'])) {
      foreach ($settings['allowed_values'] as $option) {
        $options[$option['key']] = $option['value'];
      }
    }

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'select',
      '#options' => $options,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $name = $field->getName();

    static::$storageType = $field->getDataType();

    $allowed_values = $form_state->getValue([
      'settings',
      'field_settings',
      $name,
      'widget_settings',
      'settings',
      'allowed_values',
    ]) ?? $settings['allowed_values'];

    if ($form_state->isRebuilding()) {
      $trigger = $form_state->getTriggeringElement();
      if ($trigger['#name'] == 'add_row:' . $name) {
        $allowed_values[] = [
          'key' => NULL,
          'value' => '',
        ];
        $form_state->set('add', NULL);
      }
      if ($form_state->get('remove')) {
        $remove = $form_state->get('remove');
        if ($remove['name'] === $name) {
          unset($allowed_values[$remove['key']]);
          $form_state->set('remove', NULL);
        }
      }
    }

    $options_wrapper_id = 'options-wrapper-' . $name;
    $element['#prefix'] = '<div id="' . $options_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['settings']['empty_option'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty option'),
      '#description' => $this->t('Option to show when field is not required.'),
      '#default_value' => $settings['empty_option'],
      '#required' => TRUE,
    ];

    $element['settings']['allowed_values'] = [
      '#type' => 'table',
      '#caption' => $this->t('<strong>Allowed values list</strong>'),
      '#header' => [
        $this->t('Value'),
        $this->t('Label'),
        '',
      ],
      '#element_validate' => [[static::class, 'validateAllowedValues']],
    ];
    $allowed_values_count = count($allowed_values);
    foreach ($allowed_values as $key => $value) {
      $key_properties = [
        '#title' => $this->t('Value'),
        '#title_display' => 'invisible',
        '#default_value' => $value['key'],
        '#required' => TRUE,
      ];
      // Change the field type based on how data is stored.
      $data_type = $field->getDataType();
      switch ($data_type) {
        case 'integer':
        case 'float':
          if ($field->isUnsigned()) {
            $key_properties['#min'] = 0;
          }
          if ($data_type == 'float') {
            $key_properties['#step'] = 'any';
          }
          $element['settings']['allowed_values'][$key]['key'] = [
            '#type' => 'number',
          ] + $key_properties;
          break;

        default:
          $element['settings']['allowed_values'][$key]['key'] = [
            '#type' => 'textfield',
          ] + $key_properties;
      }
      $element['settings']['allowed_values'][$key]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#title_display' => 'invisible',
        '#default_value' => $value['value'],
        '#required' => TRUE,
      ];
      $element['settings']['allowed_values'][$key]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [get_class($this) . '::removeSubmit'],
        '#name' => 'remove:' . $name . '_' . $key,
        '#delta' => $key,
        '#disabled' => $allowed_values_count <= 1,
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $options_wrapper_id,
        ],
      ];
    }
    $element['settings']['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add option'),
      '#submit' => [get_class($this) . '::addSubmit'],
      '#name' => 'add_row:' . $name,
      '#ajax' => [
        'callback' => [$this, 'actionCallback'],
        'wrapper' => $options_wrapper_id,
      ],
    ];

    return $element;
  }

  /**
   * The #element_validate callback for select field allowed values.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues(array $element, FormStateInterface $form_state): void {
    $values = $element['#value'];

    if (is_array($values)) {
      // Check that keys are valid for the field type.
      $unique_keys = [];
      foreach ($values as $value) {
        // Make sure each key is unique.
        if (!in_array($value['key'], $unique_keys)) {
          $unique_keys[] = $value['key'];
        }
        else {
          $form_state->setError($element, t('Allowed value key must be unique.'));
          break;
        }

        switch (static::$storageType) {
          case 'integer':
          case 'float':
            if (!is_numeric($value['key'])) {
              $form_state->setError($element, t('Allowed value key must be numeric.'));
              break;
            }
            break;
        }
      }
      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state): array {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $sliced_parents = array_slice($parents, 0, 4, TRUE);

    return NestedArray::getValue($form, $sliced_parents);
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->set('add', $form_state->getTriggeringElement()['#name']);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    $form_state->set(
      'remove', ['name' => $parents[2], 'key' => $trigger['#delta']]
    );
    $form_state->setRebuild();
  }

}
