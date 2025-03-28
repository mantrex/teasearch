<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\EntityReferenceOptionsWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'entity_reference_radios' custom field widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_radios",
 *   label = @Translation("Radios"),
 *   category = @Translation("Reference"),
 *   data_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceRadiosWidget extends EntityReferenceOptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'handler_settings' => [],
        'empty_option' => 'N/A',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    // Prevent default value form rendering unset options.
    if (!isset($element['#options'])) {
      return [];
    }
    $options = $element['#options'];
    $flattened_options = [];

    // Flatten the options array and preserve keys.
    foreach ($options as $group => $option_set) {
      if (is_array($option_set)) {
        $flattened_options += $option_set;
      }
      else {
        $flattened_options[$group] = $option_set;
      }
    }

    // Sanitize the options to prevent XSS vulnerabilities.
    $safe_options = array_map('Drupal\Component\Utility\Html::escape', $flattened_options);

    // Add an empty option if the field is not required.
    if (!$settings['required']) {
      $safe_options = ['' => $settings['empty_option']] + $safe_options;
    }

    $element['#type'] = 'radios';
    $element['#options'] = $safe_options;

    return ['target_id' => $element];
  }

}
