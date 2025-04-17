<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\NumberWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'integer' widget.
 */
#[CustomFieldWidget(
  id: 'integer',
  label: new TranslatableMarkup('Integer'),
  category: new TranslatableMarkup('Number'),
  field_types: [
    'integer',
  ],
)]
class IntegerWidget extends NumberWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings');

    $min_setting = $settings['min'] ?? NULL;
    // Make sure we force positive numbers when unsiqned.
    if ($field->isUnsigned() && (!is_numeric($min_setting) || $min_setting < 0)) {
      $element['#min'] = 0;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];

    // Prevent min negative numbers when storage is unsigned.
    if ($field->isUnsigned()) {
      $element['settings']['min']['#min'] = 0;
      $element['settings']['min']['#default_value'] = $settings['min'];
      $element['settings']['min']['#description'] = $this->t('The minimum value that should be allowed in this field.');
    }

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
