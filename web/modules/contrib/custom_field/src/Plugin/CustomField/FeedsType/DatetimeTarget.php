<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldFeedsType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'datetime' feeds type.
 */
#[CustomFieldFeedsType(
  id: 'datetime',
  label: new TranslatableMarkup('Datetime'),
  mark_unique: TRUE,
)]
class DatetimeTarget extends BaseTarget {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'timezone' => 'UTC',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(int $delta, array $configuration) {
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone handling'),
      '#options' => $this->getTimezoneOptions(),
      '#default_value' => $configuration['timezone'],
      '#description' => $this->t('This value will only be used if the timezone is missing.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $configuration): array {
    $options = $this->getTimezoneOptions();

    $summary[] = $this->t('Default timezone: %zone', [
      '%zone' => $options[$configuration['timezone']],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $datetime_type = $this->configuration['datetime_type'];
    $date = $this->convertDate((string) $value, $configuration['timezone']);

    if (isset($date) && !$date->hasErrors()) {
      $storage_format = $datetime_type === 'date' ? CustomFieldTypeInterface::DATE_STORAGE_FORMAT : CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT;
      return $date->format($storage_format, [
        'timezone' => CustomFieldTypeInterface::STORAGE_TIMEZONE,
      ]);
    }

    return NULL;
  }

  /**
   * Returns the timezone options.
   *
   * @return array
   *   A map of timezone options.
   */
  public function getTimezoneOptions() {
    return [
      '__SITE__' => $this->t('Site default'),
    ] + TimeZoneFormHelper::getOptionsList();
  }

  /**
   * Converts a value to Date object or null.
   *
   * @param string $value
   *   The date value to convert.
   * @param string $timezone
   *   The timezone configuration.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A datetime object or null, if there is no value or if the date value
   *   has errors.
   */
  protected function convertDate(string $value, string $timezone): mixed {
    $value = trim($value);

    // This is a year value.
    if (ctype_digit($value) && strlen($value) === 4) {
      $value = 'January ' . $value;
    }

    if (is_numeric($value)) {
      $date = DrupalDateTime::createFromTimestamp($value, $this->getTimezoneConfiguration($timezone));
    }

    elseif (strtotime($value)) {
      $date = new DrupalDateTime($value, $this->getTimezoneConfiguration($timezone));
    }

    if (isset($date) && !$date->hasErrors()) {
      return $date;
    }

    return NULL;
  }

  /**
   * Returns the timezone configuration.
   *
   * @param string $timezone
   *   The timezone setting from feeds configuration array.
   *
   * @return array|mixed|null
   *   The timezone configuration.
   */
  public function getTimezoneConfiguration(string $timezone) {
    $default_timezone = $this->configFactory->get('system.date')->get('timezone.default');
    return ($timezone == '__SITE__') ?
      $default_timezone : $timezone;
  }

}
