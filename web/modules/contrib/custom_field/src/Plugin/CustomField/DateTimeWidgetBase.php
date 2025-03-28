<?php

namespace Drupal\custom_field\Plugin\CustomField;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
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
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $item = $items[$delta];
    $datetime_type = $field->getDatetimeType();

    $date = [
      '#type' => 'datetime',
      '#default_value' => NULL,
      '#date_increment' => 1,
      '#date_timezone' => date_default_timezone_get(),
    ];

    if ($datetime_type == CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
      // A date-only field should have no timezone conversion performed, so
      // use the same timezone as for storage.
      $date['#date_timezone'] = CustomFieldTypeInterface::STORAGE_TIMEZONE;
    }

    if ($value = $item->{$field->getName()}) {
      $date_object = $this->getDate($value, $datetime_type);
      if ($date_object !== NULL) {
        $date['#default_value'] = $this->createDefaultValue($date_object, $date['#date_timezone'], $datetime_type);
      }
    }

    return $date + $element;
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
   */
  protected function createDefaultValue($date, string $timezone, string $datetime_type) {
    // The date was created and verified during field_load(), so it is safe to
    // use without further inspection.
    $year = $date->format('Y');
    $month = $date->format('m');
    $day = $date->format('d');
    $date->setTimezone(new \DateTimeZone($timezone));
    if ($datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
      $date->setDefaultDateTime();
      // Reset the date to handle cases where the UTC offset is greater than
      // 12 hours.
      $date->setDate($year, $month, $day);
    }
    return $date;
  }

  /**
   * Helper function to convert stored value to date object.
   *
   * @param string $value
   *   The storage value as string.
   * @param string $datetime_type
   *   The date type.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Return a date object or null.
   */
  private function getDate(string $value, string $datetime_type) {
    $storage_format = $datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE ? CustomFieldTypeInterface::DATE_STORAGE_FORMAT : CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT;
    $date_object = NULL;
    try {
      $date = DrupalDateTime::createFromFormat($storage_format, $value, CustomFieldTypeInterface::STORAGE_TIMEZONE);
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $date_object = $date;
        // If the format did not include an explicit time portion, then the
        // time will be set from the current time instead. For consistency, we
        // set the time to 12:00:00 UTC for date-only fields. This is used so
        // that the local date portion is the same, across nearly all time
        // zones.
        // @see \Drupal\Component\Datetime\DateTimePlus::setDefaultDateTime()
        // @see http://php.net/manual/datetime.createfromformat.php
        if ($datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
          $date_object->setDefaultDateTime();
        }
      }
    }
    catch (\Exception $e) {
      // @todo Handle this.
    }
    return $date_object;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): mixed {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    $datetime_type = $column['datetime_type'];
    $storage_format = $datetime_type === 'date' ? CustomFieldTypeInterface::DATE_STORAGE_FORMAT : CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT;
    $storage_timezone = new \DateTimeZone(CustomFieldTypeInterface::STORAGE_TIMEZONE);

    if ($value === '') {
      return NULL;
    }

    if ($value instanceof DrupalDateTime) {
      $date = $value;

      // Adjust the date for storage.
      $value = $date->setTimezone($storage_timezone)->format($storage_format);
    }

    return $value;
  }

}
