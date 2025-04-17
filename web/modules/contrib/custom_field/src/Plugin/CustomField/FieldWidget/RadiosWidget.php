<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\ListWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'radios' widget.
 */
#[CustomFieldWidget(
  id: 'radios',
  label: new TranslatableMarkup('Radios'),
  category: new TranslatableMarkup('Lists'),
  field_types: [
    'string',
    'integer',
    'float',
  ],
)]
class RadiosWidget extends ListWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    // Add our widget type and additional properties and return.
    $element['#type'] = 'radios';
    if (!$settings['required']) {
      $options = $element['#options'];
      $options = ['' => $settings['empty_option']] + $options;
      $element['#options'] = $options;
    }

    return $element;
  }

}
