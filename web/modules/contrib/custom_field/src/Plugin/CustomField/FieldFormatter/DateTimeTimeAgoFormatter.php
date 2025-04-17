<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'datetime_time_ago' formatter.
 */
#[FieldFormatter(
  id: 'datetime_time_ago',
  label: new TranslatableMarkup('Time ago'),
  field_types: [
    'datetime',
  ],
)]
class DateTimeTimeAgoFormatter extends TimestampAgoFormatter {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    $datetime_type = $this->customFieldDefinition->getDatetimeType();

    /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
    $date = $this->getDate($value, $datetime_type);

    if ($date === NULL) {
      return NULL;
    }

    return $this->formatDate($date);
  }

  /**
   * Formats a date/time as a time interval.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date/time object.
   *
   * @return array
   *   The formatted date/time string using the past or future format setting.
   */
  protected function formatDate(DrupalDateTime $date): array {
    return parent::formatTimestamp($date->getTimestamp());
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
  protected function getDate(string $value, string $datetime_type): ?DrupalDateTime {
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

}
