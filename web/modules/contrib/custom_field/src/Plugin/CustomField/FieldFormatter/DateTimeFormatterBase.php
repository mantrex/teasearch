<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'DateTime custom field formatter' plugin implementations.
 */
abstract class DateTimeFormatterBase extends CustomFieldFormatterBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $dateFormatStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->dateFormatStorage = $container->get('entity_type.manager')->getStorage('date_format');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'timezone_override' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements['timezone_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone override'),
      '#description' => $this->t('The time zone selected here will always be used'),
      '#options' => TimeZoneFormHelper::getOptionsListByRegion(TRUE),
      '#default_value' => $this->getSetting('timezone_override'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, mixed $value): ?array {
    $datetime_type = $this->customFieldDefinition->getDatetimeType();

    /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
    $date = $this->getDate($value, $datetime_type);

    if ($date === NULL) {
      return NULL;
    }

    return $this->buildDateWithIsoAttribute($date, $datetime_type);
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

  /**
   * Creates a formatted date value as a string.
   *
   * @param \Drupal\Component\Datetime\DateTimePlus $date
   *   A DrupalDateTime object.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  abstract protected function formatDate(DateTimePlus $date): string;

  /**
   * Sets the proper time zone on a DrupalDateTime object for the current user.
   *
   * A DrupalDateTime object loaded from the database will have the UTC time
   * zone applied to it.  This method will apply the time zone for the current
   * user, based on system and user settings.
   *
   * @param \Drupal\Component\Datetime\DateTimePlus $date
   *   A DrupalDateTime object.
   * @param string $datetime_type
   *   The date type.
   */
  protected function setTimeZone(DateTimePlus $date, string $datetime_type): void {
    if ($datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
      // A date without time has no timezone conversion.
      $timezone = CustomFieldTypeInterface::STORAGE_TIMEZONE;
    }
    else {
      $timezone = date_default_timezone_get();
    }
    $date->setTimezone(timezone_open($timezone));
  }

  /**
   * Creates a render array from a date object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   * @param string $datetime_type
   *   The date type.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  protected function buildDate(DrupalDateTime $date, string $datetime_type): array {
    $this->setTimeZone($date, $datetime_type);

    return [
      '#markup' => $this->formatDate($date),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];
  }

  /**
   * Creates a render array from a date object with ISO date attribute.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   * @param string $datetime_type
   *   The date type.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  protected function buildDateWithIsoAttribute(DrupalDateTime $date, string $datetime_type): array {
    // Create the ISO date in Universal Time.
    $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

    $this->setTimeZone($date, $datetime_type);

    return [
      '#theme' => 'time',
      '#text' => $this->formatDate($date),
      '#attributes' => [
        'datetime' => $iso_date,
      ],
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];
  }

}
