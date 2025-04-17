<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\NumberWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'decimal' widget.
 */
#[CustomFieldWidget(
  id: 'decimal',
  label: new TranslatableMarkup('Decimal'),
  category: new TranslatableMarkup('Number'),
  field_types: [
    'decimal',
  ],
)]
class DecimalWidget extends NumberWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $element['#step'] = pow(0.1, $field->getScale());

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $scale = $field->getScale();

    $element['settings']['min']['#step'] = pow(0.1, $scale);
    $element['settings']['max']['#step'] = pow(0.1, $scale);

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
