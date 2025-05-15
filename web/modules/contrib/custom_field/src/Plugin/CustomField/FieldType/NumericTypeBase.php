<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Base class for numeric custom field types.
 */
class NumericTypeBase extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = [];
    // To prevent a PDO exception from occurring, restrict values in the range
    // allowed by databases.
    $type = $settings['type'];
    $min = $type !== 'float' ? $this->getDefaultMinValue($settings) : NULL;
    $max = $type !== 'float' ? $this->getDefaultMaxValue($settings) : NULL;

    // Handle range constraints.
    $min_set = isset($settings['min']) && $settings['min'] !== '';
    $max_set = isset($settings['max']) && $settings['max'] !== '';

    if ($min_set) {
      $min = $settings['min'];
    }
    if ($max_set) {
      $max = $settings['max'];
    }

    if ($min) {
      $constraints['Range']['min'] = $min;
    }
    if ($max) {
      $constraints['Range']['max'] = $max;
    }

    // If both min and max are set, use notInRangeMessage.
    if ($min_set && $max_set) {
      $constraints['Range']['notInRangeMessage'] = $this->t('%name: the value must be between %min and %max.', [
        '%name' => $settings['name'],
        '%min' => $min,
        '%max' => $max,
      ]);
    }
    // Only min is set.
    elseif ($min_set) {
      $constraints['Range']['minMessage'] = $this->t('%name: the value may be no less than %min.', [
        '%name' => $settings['name'],
        '%min' => $min,
      ]);
    }
    // Only max is set.
    elseif ($max_set) {
      $constraints['Range']['maxMessage'] = $this->t('%name: the value may be no greater than %max.', [
        '%name' => $settings['name'],
        '%max' => $max,
      ]);
    }

    return $constraints;
  }

  /**
   * Helper method to get the min value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The minimum value allowed by database.
   */
  protected static function getDefaultMinValue(array $settings): int|float {
    if ($settings['unsigned']) {
      return 0;
    }

    // Each value is - (2 ^ (8 * bytes - 1)).
    $size_map = [
      'normal' => -2147483648,
      'tiny' => -128,
      'small' => -32768,
      'medium' => -8388608,
      'big' => -9223372036854775808,
    ];
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
  }

  /**
   * Helper method to get the max value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The maximum value allowed by database.
   */
  protected static function getDefaultMaxValue(array $settings): int|float {
    if ($settings['unsigned']) {
      // Each value is (2 ^ (8 * bytes) - 1).
      $size_map = [
        'normal' => 4294967295,
        'tiny' => 255,
        'small' => 65535,
        'medium' => 16777215,
        'big' => 18446744073709551615,
      ];
    }
    else {
      // Each value is (2 ^ (8 * bytes - 1) - 1).
      $size_map = [
        'normal' => 2147483647,
        'tiny' => 127,
        'small' => 32767,
        'medium' => 8388607,
        'big' => 9223372036854775807,
      ];
    }
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
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
    $factor = pow(10, $num);
    return floor($decimal * $factor) / $factor;
  }

  /**
   * Helper method to get the number of decimal digits out of a decimal number.
   *
   * @param float|int $decimal
   *   The number to calculate the number of decimals digits from.
   *
   * @return int
   *   The number of decimal digits.
   */
  protected static function getDecimalDigits(float|int $decimal): int {
    $digits = 0;
    while ($decimal - round($decimal)) {
      $decimal *= 10;
      $digits++;
    }

    return $digits;
  }

}
