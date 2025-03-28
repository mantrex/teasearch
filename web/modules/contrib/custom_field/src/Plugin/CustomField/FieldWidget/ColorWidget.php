<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Plugin implementation of the 'color' custom field widget.
 *
 * @FieldWidget(
 *   id = "color",
 *   label = @Translation("Color"),
 *   category = @Translation("General"),
 *   data_types = {
 *     "color",
 *   }
 * )
 */
class ColorWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'color',
      '#maxlength' => 7,
      '#size' => 7,
    ] + $element;
  }

}
