<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Plugin implementation of the 'textarea' widget.
 */
#[CustomFieldWidget(
  id: 'textarea',
  label: new TranslatableMarkup('Text area (multiple rows)'),
  category: new TranslatableMarkup('Text'),
  field_types: [
    'string_long',
  ],
)]
class TextareaWidget extends CustomFieldWidgetBase {

  /**
   * The field type.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface
   */
  protected CustomFieldTypeInterface $field;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'rows' => 5,
      'placeholder' => '',
      'maxlength' => '',
      'maxlength_js' => FALSE,
      'formatted' => FALSE,
      'default_format' => '',
      'format' => [
        'guidelines' => TRUE,
        'help' => TRUE,
      ],
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    // Add our widget type and additional properties and return.
    $type = isset($settings['formatted']) && $settings['formatted'] ? 'text_format' : 'textarea';

    if (isset($settings['formatted']) && $settings['formatted'] && !empty($settings['default_format'])) {
      $this->field = $field;
      $element['#format'] = $settings['default_format'];
      $element['#allowed_formats'] = [$settings['default_format']];
      $element['#after_build'][] = [$this, 'callUnsetFilters'];
    }

    if (isset($settings['maxlength'])) {
      $element['#attributes']['data-maxlength'] = $settings['maxlength'];
    }
    if (isset($settings['maxlength_js']) && $settings['maxlength_js']) {
      $element['#maxlength_js'] = TRUE;
    }

    return [
      '#type' => $type,
      '#rows' => $settings['rows'] ?? 5,
      '#size' => NULL,
      '#placeholder' => $settings['placeholder'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $formats = filter_formats();
    $format_options = [];
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    foreach ($formats as $key => $format) {
      $format_options[$key] = $format->get('name');
    }

    $element['settings']['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#description' => $this->t('Text editors (like CKEditor) may override this setting.'),
      '#default_value' => $settings['rows'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['formatted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable wysiwyg'),
      '#default_value' => $settings['formatted'],
    ];
    $element['settings']['default_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Default format'),
      '#options' => $format_options,
      '#default_value' => $settings['default_format'],
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $field->getName() . '][widget_settings][settings][formatted]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['format'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Format settings'),
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $field->getName() . '][widget_settings][settings][formatted]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['format']['guidelines'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show format guidelines'),
      '#default_value' => $settings['format']['guidelines'],
    ];
    $element['settings']['format']['help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show format help'),
      '#default_value' => $settings['format']['help'],
    ];
    $element['settings']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#description' => $this->t('The maximum amount of characters in the field'),
      '#default_value' => is_numeric($settings['maxlength']) ? $settings['maxlength'] : NULL,
      '#min' => 1,
    ];
    // Add additional setting if maxlength module is enabled.
    if ($this->moduleHandler->moduleExists('maxlength')) {
      $element['settings']['maxlength_js'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Show max length character count'),
        '#default_value' => $settings['maxlength_js'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): mixed {
    // If text field is formatted, the value is an array.
    if (is_array($value)) {
      $value = $value['value'];
    }
    if (trim($value) === '') {
      return NULL;
    }

    return $value;
  }

  /**
   * Closure function to pass arguments to unsetFilters().
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The field settings.
   */
  public function callUnsetFilters(array $element, FormStateInterface $form_state): array {
    $settings = $this->field->getWidgetSetting('settings');
    return static::unsetFilters($element, $form_state, $settings);
  }

  /**
   * Helper function to modify filter settings output.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array<string, mixed> $settings
   *   The field settings.
   *
   * @return array<string, mixed>
   *   The modified form element.
   */
  public static function unsetFilters(array $element, FormStateInterface $formState, array $settings): array {
    $hide_guidelines = FALSE;
    $hide_help = FALSE;
    if (!$settings['format']['guidelines']) {
      $hide_guidelines = TRUE;
      unset($element['format']['guidelines']);
    }
    if (!$settings['format']['help']) {
      $hide_help = TRUE;
      unset($element['format']['help']);
    }
    if ($hide_guidelines && $hide_help) {
      unset($element['format']['#theme_wrappers']);
    }

    return $element;
  }

}
