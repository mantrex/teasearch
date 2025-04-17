<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\DateTimeWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'datetime_datelist' widget.
 */
#[CustomFieldWidget(
  id: 'datetime_datelist',
  label: new TranslatableMarkup('Select list'),
  category: new TranslatableMarkup('Date'),
  field_types: [
    'datetime',
  ],
)]
class DateTimeDatelistWidget extends DateTimeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'increment' => '15',
        'date_order' => 'YMD',
        'time_type' => '24',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $datetime_type = $field->getDatetimeType();

    $element['settings']['date_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Date part order'),
      '#default_value' => $settings['date_order'],
      '#options' => [
        'MDY' => $this->t('Month/Day/Year'),
        'DMY' => $this->t('Day/Month/Year'),
        'YMD' => $this->t('Year/Month/Day'),
      ],
    ];

    if ($datetime_type == 'datetime') {
      $element['settings']['time_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Time type'),
        '#default_value' => $settings['time_type'],
        '#options' => [
          '24' => $this->t('24 hour time'),
          '12' => $this->t('12 hour time'),
        ],
      ];

      $element['settings']['increment'] = [
        '#type' => 'select',
        '#title' => $this->t('Time increments'),
        '#default_value' => $settings['increment'],
        '#options' => [
          1 => $this->t('1 minute'),
          5 => $this->t('5 minutes'),
          10 => $this->t('10 minutes'),
          15 => $this->t('15 minutes'),
          30 => $this->t('30 minutes'),
        ],
      ];
    }
    else {
      $element['settings']['time_type'] = [
        '#type' => 'hidden',
        '#value' => 'none',
      ];

      $element['settings']['increment'] = [
        '#type' => 'hidden',
        '#value' => $settings['increment'],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $datetime_type = $field->getDatetimeType();
    $date_order = $settings['date_order'];
    $time_type = $datetime_type == 'datetime' ? $settings['time_type'] : '';
    $increment = $datetime_type == 'datetime' ? $settings['increment'] : '';

    // Set up the date part order array.
    switch ($date_order) {
      case 'YMD':
        $date_part_order = ['year', 'month', 'day'];
        break;

      case 'MDY':
        $date_part_order = ['month', 'day', 'year'];
        break;

      case 'DMY':
        $date_part_order = ['day', 'month', 'year'];
        break;

      default:
        $date_part_order = [];
        break;
    }

    switch ($time_type) {
      case '24':
        $date_part_order = array_merge($date_part_order, ['hour', 'minute']);
        break;

      case '12':
        $date_part_order = array_merge($date_part_order, [
          'hour',
          'minute',
          'ampm',
        ]);
        break;

      case 'none':
        break;
    }

    // Wrap all the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $date = [
      '#type' => 'datelist',
      '#date_increment' => $increment,
      '#date_part_order' => $date_part_order,
    ];

    return $date + $element;
  }

}
