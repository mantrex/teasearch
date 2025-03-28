<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\NumberWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'float' custom field widget.
 *
 * @FieldWidget(
 *   id = "float",
 *   label = @Translation("Float"),
 *   category = @Translation("Number"),
 *   data_types = {
 *     "float",
 *   },
 * )
 */
class FloatWidget extends NumberWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);

    // Add our widget type and additional properties and return.
    return [
      '#step' => 'any',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);

    $element['settings']['min']['#scale'] = 'any';
    $element['settings']['max']['#scale'] = 'any';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): mixed {
    if (!is_numeric($value)) {
      return NULL;
    }

    return $value;
  }

}
