<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Drupal\custom_field\Time;

/**
 * Plugin implementation of the 'time_widget' custom field widget.
 */
#[CustomFieldWidget(
  id: 'time_widget',
  label: new TranslatableMarkup('Time'),
  category: new TranslatableMarkup('Time'),
  field_types: [
    'time',
  ],
)]
class TimeWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'seconds_enabled' => FALSE,
      'seconds_step' => 5,
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $field_name = $field->getName();

    $element['settings']['seconds_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add seconds parameter to input widget'),
      '#default_value' => $settings['seconds_enabled'],
    ];
    $element['settings']['seconds_step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step to change seconds'),
      '#open' => TRUE,
      '#default_value' => $settings['seconds_step'],
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $field_name . '][widget_settings][settings][seconds_enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    $item = $items[$delta];
    $time = $item->{$field->getName()} ?? NULL;

    // Determine if we're showing seconds in the widget.
    $show_seconds = (bool) $settings['seconds_enabled'];
    $additional = [
      '#type' => 'time_cf',
      '#default_value' => Time::createFromTimestamp($time)?->formatForWidget($show_seconds),
    ];

    // We need this to be a correct Time also.
    $element['#default_value'] = Time::createFromTimestamp($element['#default_value'])?->formatForWidget($show_seconds);

    // Add the step attribute if we're showing seconds in the widget.
    if ($show_seconds) {
      $additional['#attributes']['step'] = $settings['seconds_step'];
    }
    // Set a property to determine the format in TimeElement::preRenderTime().
    $additional['#show_seconds'] = $show_seconds;

    return $element + $additional;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): string {
    // We're still doing this, I guess, in case a browser rendered the input
    // element as text instead of the HTML Time element.
    // @todo Is this really still necessary? The Time element has full browser
    // support.
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/time#browser_compatibility
    return trim((string) $value);
  }

}
