<?php

namespace Drupal\custom_field;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Random;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * The CustomFieldGenerateData class.
 */
class CustomFieldGenerateData implements CustomFieldGenerateDataInterface {

  /**
   * The interface for generating UUIDs.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * Constructs a new CustomFieldGenerateData object.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The interface for generating UUIDs.
   * @param \Drupal\Component\Datetime\TimeInterface $time_service
   *   The datetime.time service.
   */
  public function __construct(UuidInterface $uuid_service, TimeInterface $time_service) {
    $this->uuid = $uuid_service;
    $this->timeService = $time_service;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFieldData(array $columns, array $field_settings = []): array {
    $random = new Random();
    $items = [];
    foreach ($columns as $column_name => $column) {
      $widget_settings = $field_settings[$column_name]['widget_settings']['settings'] ?? [];
      switch ($column['type']) {
        case 'string':
          if (!empty($widget_settings['allowed_values'])) {
            $value = self::getRandomOptions($widget_settings['allowed_values']);
          }
          else {
            $max_length = isset($widget_settings['maxlength']) && is_numeric($widget_settings['maxlength']) ? $widget_settings['maxlength'] : $column['max_length'];
            $length = min($max_length, 20);
            $value = $random->word(mt_rand(1, $length));
          }
          break;

        case 'string_long':
          $value = $random->paragraphs(4);
          break;

        case 'integer':
          if (!empty($widget_settings['allowed_values'])) {
            $value = self::getRandomOptions($widget_settings['allowed_values']);
          }
          else {
            $default_min = $column['unsigned'] ? 0 : -1000;
            $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
            $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : 1000;
            $value = mt_rand($min, $max);
          }
          break;

        case 'decimal':
          $precision = $column['precision'] ?: 10;
          $scale = $column['scale'] ?: 2;
          $default_min = $column['unsigned'] ? 0 : -pow(10, ($precision - $scale)) + 1;
          $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
          $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : pow(10, ($precision - $scale)) - 1;

          // Get the number of decimal digits for the $max.
          $decimal_digits = self::getDecimalDigits($max);
          // Do the same for the min and keep the higher number of decimal
          // digits.
          $decimal_digits = max(self::getDecimalDigits($min), $decimal_digits);
          // If $min = 1.234 and $max = 1.33 then $decimal_digits = 3.
          $scale = rand($decimal_digits, $scale);
          // @see "Example #1 Calculate a random floating-point number" in
          // http://php.net/manual/function.mt-getrandmax.php
          $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
          $value = self::truncateDecimal($random_decimal, $scale);
          break;

        case 'float':
          $precision = rand(10, 32);
          $scale = rand(0, 2);
          $default_min = $column['unsigned'] ? 0 : -pow(10, ($precision - $scale)) + 1;
          $min = isset($widget_settings['min']) && is_numeric($widget_settings['min']) ? $widget_settings['min'] : $default_min;
          $max = isset($widget_settings['max']) && is_numeric($widget_settings['max']) ? $widget_settings['max'] : pow(10, ($precision - $scale)) - 1;
          $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
          $value = self::truncateDecimal($random_decimal, $scale);
          break;

        case 'email':
          $value = $random->word(10) . '@example.com';
          break;

        case 'telephone':
          $area_code = mt_rand(100, 999);
          $prefix = mt_rand(100, 999);
          $line_number = mt_rand(1000, 9999);
          $value = "$area_code-$prefix-$line_number";
          break;

        case 'uri':
          $link_type = $widget_settings['link_type'] ?? NULL;
          if ($link_type & CustomFieldTypeInterface::LINK_EXTERNAL) {
            $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
            $domain_length = mt_rand(7, 15);
            $protocol = mt_rand(0, 1) ? 'https' : 'http';
            $www = mt_rand(0, 1) ? 'www.' : '';
            $domain = $random->word($domain_length);
            $tld = $tlds[mt_rand(0, (count($tlds) - 1))];
            $value = "$protocol://$www$domain.$tld";
          }
          else {
            $value = 'base:' . $random->name(mt_rand(1, 64));
          }
          break;

        case 'boolean':
          $value = mt_rand(0, 1);
          break;

        case 'uuid':
          $value = $this->uuid->generate();
          break;

        case 'color':
          $value = $this->generateRandomHexCode();
          break;

        case 'map':
          $map_values = [];
          for ($i = 0; $i < 5; $i++) {
            $map_values[] = [
              'key' => $random->word(10),
              'value' => $random->word(mt_rand(10, 20)),
            ];
          }
          $value = $map_values;
          break;

        case 'map_string':
          $map_values = [];
          for ($i = 0; $i < 5; $i++) {
            $map_values[] = $random->word(mt_rand(10, 20));
          }
          $value = $map_values;
          break;

        case 'datetime':
          $datetime_type = $column['datetime_type'];
          $timestamp = $this->timeService->getRequestTime() - mt_rand(0, 86400 * 365);
          if ($datetime_type == CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
            $value = gmdate(CustomFieldTypeInterface::DATE_STORAGE_FORMAT, $timestamp);
          }
          else {
            $value = gmdate(CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT, $timestamp);
          }
          break;

        default:
          $value = NULL;
      }

      $items[$column_name] = $value;
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function generateSampleFormData(FieldDefinitionInterface $field, $deltas = NULL): array {
    $field_name = $field->getName();
    if ($deltas === NULL) {
      $deltas = [0];
    }

    $form_values = [];

    // Generate data for the field.
    $field_settings = $field->getSetting('field_settings');
    $columns = $field->getSetting('columns');

    foreach ($deltas as $delta) {
      $random_values = self::generateFieldData($columns, $field_settings);

      // UUID's can't be unset through the GUI.
      unset($random_values['uuid_test']);

      // @todo Hardening: floating point calculation can randomly fail.
      $random_values['decimal_test'] = '0.50';
      $random_values['float_test'] = '10.775';
      // Cast integer to string.
      $random_values['integer_test'] = (string) $random_values['integer_test'];

      // @todo Hardening: we need to treat maps specially due to ajax.
      unset($random_values['map_test']);
      unset($random_values['map_string_test']);

      // @todo Hardening: why do color fields not set using ::submitForm?
      unset($random_values['color_test']);

      // @todo Hardening: figure out why an array fails as datetime value.
      unset($random_values['datetime_test']);

      // @todo Hardening: Add support for entity reference.
      unset($random_values['entity_reference_test']);

      // @todo Hardening: Add support for file.
      unset($random_values['file_test']);

      // @todo Hardening: Add support for image.
      unset($random_values['image_test']);

      // @todo Hardening: Add support for viewfield.
      unset($random_values['viewfield_test']);

      $keys = array_map(static function ($key) use ($field_name, $delta) {
        return "{$field_name}[$delta][$key]";
      }, array_keys($random_values));

      $form_values[] = array_combine($keys, $random_values);
    }
    return array_merge(['title[0][value]' => 'Test'], ...$form_values);
  }

  /**
   * Helper method to get the number of decimal digits out of a decimal number.
   *
   * @param int $decimal
   *   The number to calculate the number of decimals digits from.
   *
   * @return int
   *   The number of decimal digits.
   */
  protected static function getDecimalDigits(int $decimal): int {
    $digits = 0;
    while ($decimal - round($decimal)) {
      $decimal *= 10;
      $digits++;
    }
    return $digits;
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   */
  protected static function truncateDecimal(float $decimal, int $num): float {
    return floor($decimal * pow(10, $num)) / pow(10, $num);
  }

  /**
   * Helper method to generate random hexadecimal color codes.
   *
   * @return string
   *   The generated hexadecimal code.
   */
  protected static function generateRandomHexCode(): string {
    $characters = '0123456789ABCDEF';
    $hexCode = '';

    for ($i = 0; $i < 6; $i++) {
      $hexCode .= $characters[rand(0, strlen($characters) - 1)];
    }

    return '#' . $hexCode;
  }

  /**
   * Helper method to flatten an array of allowed values and randomize.
   *
   * @param array $allowed_values
   *   An array of allowed values.
   *
   * @return int|string
   *   A random key from allowed values array.
   */
  protected static function getRandomOptions(array $allowed_values): int|string {
    $randoms = [];
    foreach ($allowed_values as $value) {
      $randoms[$value['key']] = $value['value'];
    }

    return array_rand($randoms, 1);
  }

}
