<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Base plugin class for numeric custom field widgets.
 */
class NumberWidgetBase extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
      'placeholder' => '',
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    // Number form element type.
    $element['#type'] = 'number';
    $element['#step'] = 'any';
    if (!empty($settings['placeholder'])) {
      $element['#placeholder'] = $settings['placeholder'];
    }

    // Add min/max if set.
    if (isset($settings['min']) && is_numeric($settings['min'])) {
      $element['#min'] = $settings['min'];
    }
    if (isset($settings['max']) && is_numeric($settings['max'])) {
      $element['#max'] = $settings['max'];
    }

    // Add prefix and suffix.
    if (isset($settings['prefix'])) {
      $prefixes = explode('|', $settings['prefix']);
      $element['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if (isset($settings['suffix'])) {
      $suffixes = explode('|', $settings['suffix']);
      $element['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $unsigned = $field->isUnsigned();

    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $element['settings']['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum'),
      '#default_value' => $settings['min'],
      '#min' => $unsigned ? 0 : NULL,
      '#description' => $this->t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#prefix' => '<div class="customfield-settings-inline">',
    ];

    $element['settings']['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum'),
      '#default_value' => $settings['max'],
      '#min' => $unsigned ? 0 : NULL,
      '#description' => $this->t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#suffix' => '</div>',
    ];

    $element['settings']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    $element['settings']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    return $element;
  }

}
