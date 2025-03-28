<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Plugin implementation of the 'checkbox' custom field widget.
 *
 * @FieldWidget(
 *   id = "checkbox",
 *   label = @Translation("Checkbox"),
 *   category = @Translation("General"),
 *   data_types = {
 *     "boolean",
 *   }
 * )
 */
class CheckboxWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $item = $items[$delta];

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'checkbox',
      '#default_value' => !empty($item->{$field->getName()}),
    ] + $element;
  }

}
