<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeType;
use Drupal\custom_field\Plugin\CustomField\FieldType\DateTimeTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin class for datetime custom field widgets.
 */
class DateTimeWidgetBase extends CustomFieldWidgetBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'timezone_enabled' => FALSE,
      'timezone_options' => [],
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $datetime_type = $field->getDatetimeType();
    $timezone_options = TimeZoneFormHelper::getOptionsListByRegion();

    if ($datetime_type === DateTimeType::DATETIME_TYPE_DATETIME) {
      $element['settings']['timezone_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable time zone selection'),
        '#description' => $this->t('Allows users to set a time zone for the date value.'),
        '#default_value' => $settings['timezone_enabled'],
      ];
      $element['settings']['timezone_options'] = [
        '#type' => 'select',
        '#multiple' => TRUE,
        '#title' => $this->t('Time zone options'),
        '#description' => $this->t('Select one or more time zones to display as options in the widget.<br />Hold down Ctrl (Windows) or Command (Mac) to select multiple.'),
        '#description_display' => 'before',
        '#options' => $timezone_options,
        '#default_value' => $settings['timezone_options'],
        '#states' => [
          'visible' => [
            ':input[name="settings[field_settings][' . $field->getName() . '][widget_settings][settings][timezone_enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $item = $items[$delta];
    $datetime_type = $field->getDatetimeType();
    $settings = $this->getSettings()['settings'] + static::defaultSettings()['settings'];

    $date = [
      '#type' => 'custom_field_datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
    ];

    if ($datetime_type == DateTimeType::DATETIME_TYPE_DATE) {
      // A date-only field should have no timezone conversion performed, so
      // use the same timezone as for storage.
      $date['#date_timezone'] = DateTimeTypeInterface::STORAGE_TIMEZONE;
    }

    if ($date_object = $item->{$field->getName() . '__date'} ?? NULL) {
      $date['#default_value'] = $this->createDefaultValue($date_object, $date['#date_timezone'], $datetime_type);
    }
    $element['value'] = $date + $element;
    if ($datetime_type === DateTimeType::DATETIME_TYPE_DATETIME && $settings['timezone_enabled']) {
      $timezone_options = !empty($settings['timezone_options']) ? array_combine($settings['timezone_options'], $settings['timezone_options']) : TimeZoneFormHelper::getOptionsListByRegion();
      $element['value']['#timezone_element'] = TRUE;
      $element['timezone'] = [
        '#type' => 'select',
        '#title' => $this->t('Time zone'),
        '#options' => $timezone_options,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $item->{$field->getName() . '__timezone'},
      ];
    }
    return $element;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   * @param string $datetime_type
   *   The type of date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   *
   * @throws \DateInvalidTimeZoneException
   */
  protected function createDefaultValue(DrupalDateTime $date, string $timezone, string $datetime_type): DrupalDateTime {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    $year = (int) $date->format('Y');
    $month = (int) $date->format('m');
    $day = (int) $date->format('d');
    $date->setTimezone(new \DateTimeZone($timezone));
    if ($datetime_type === DateTimeType::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
      // Reset the date to handle cases where the UTC offset is greater than
      // 12 hours.
      $date->setDate($year, $month, $day);
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): mixed {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    $datetime_type = $column['datetime_type'];
    $storage_format = $datetime_type === 'date' ? DateTimeTypeInterface::DATE_STORAGE_FORMAT : DateTimeTypeInterface::DATETIME_STORAGE_FORMAT;
    $timezone = new \DateTimeZone(DateTimeTypeInterface::STORAGE_TIMEZONE);

    if (empty($value['value'])) {
      return NULL;
    }

    if ($value['value'] instanceof DrupalDateTime) {
      $date = $value['value'];

      // Adjust the date for storage.
      $value['value'] = $date->setTimezone($timezone)->format($storage_format);
    }

    return $value;
  }

}
