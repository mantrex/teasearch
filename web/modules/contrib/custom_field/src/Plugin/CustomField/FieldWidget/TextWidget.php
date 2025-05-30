<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Plugin implementation of the 'text' widget.
 */
#[CustomFieldWidget(
  id: 'text',
  label: new TranslatableMarkup('Text'),
  category: new TranslatableMarkup('Text'),
  field_types: [
    'string',
  ],
)]
class TextWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'size' => 60,
      'placeholder' => '',
      'maxlength' => '',
      'maxlength_js' => FALSE,
      'prefix' => '',
      'suffix' => '',
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $default_maxlength = $field->getMaxLength();
    if (is_numeric($settings['maxlength']) && $settings['maxlength'] < $field->getMaxLength()) {
      $default_maxlength = $settings['maxlength'];
    }

    // Add our widget type and additional properties and return.
    if (is_numeric($settings['maxlength'])) {
      $element['#attributes']['data-maxlength'] = $settings['maxlength'];
    }
    if (isset($settings['maxlength_js']) && $settings['maxlength_js']) {
      $element['#maxlength_js'] = TRUE;
    }

    // Add prefix and suffix.
    if (isset($settings['prefix'])) {
      $element['#field_prefix'] = FieldFilteredMarkup::create($settings['prefix']);
    }
    if (isset($settings['suffix'])) {
      $element['#field_suffix'] = FieldFilteredMarkup::create($settings['suffix']);
    }

    return [
      '#type' => 'textfield',
      '#maxlength' => $default_maxlength,
      '#placeholder' => $settings['placeholder'] ?? NULL,
      '#size' => $settings['size'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $default_maxlength = $field->getMaxLength();
    if (is_numeric($settings['maxlength']) && $settings['maxlength'] < $field->getMaxLength()) {
      $default_maxlength = $settings['maxlength'];
    }
    $element['settings']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#description' => $this->t('The maximum amount of characters in the field'),
      '#default_value' => $default_maxlength,
      '#min' => 1,
      '#max' => $field->getMaxLength(),
      '#required' => TRUE,
    ];
    // Add additional setting if maxlength module is enabled.
    if ($this->moduleHandler->moduleExists('maxlength')) {
      $element['settings']['maxlength_js'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show max length character count'),
        '#default_value' => $settings['maxlength_js'],
      ];
    }

    $element['settings']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none."),
    ];

    $element['settings']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none."),
    ];

    return $element;
  }

}
