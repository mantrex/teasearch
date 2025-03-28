<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\ListWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'select' custom field widget.
 *
 * @FieldWidget(
 *   id = "select",
 *   label = @Translation("Select list"),
 *   category = @Translation("Lists"),
 *   data_types = {
 *     "string",
 *     "integer",
 *     "float",
 *   },
 * )
 */
class SelectWidget extends ListWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'select',
      '#empty_option' => $settings['empty_option'],
    ] + $element;
  }

}
