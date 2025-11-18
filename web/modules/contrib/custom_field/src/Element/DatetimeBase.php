<?php

namespace Drupal\custom_field\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for custom field date elements.
 */
abstract class DatetimeBase extends Datetime {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $info['#theme_wrappers'] = [];
    $info['#theme'] = NULL;
    $info['#attached'] = [
      'library' => [
        'custom_field/custom-field-datetime',
      ],
    ];
    $info['#timezone_element'] = FALSE;

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#date_timezone' => date_default_timezone_get()];
    if ($input !== FALSE) {
      if ($element['#date_date_element'] === 'datetime-local' && !empty($input['date'])) {
        // With a datetime-local input, the date value is always normalized to
        // the format Y-m-d\TH:i.
        // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
        // 'html_datetime' is not a valid format to pass to
        // DrupalDateTime::createFromFormat()
        [$date_input, $time_input] = explode('T', $input['date']);
        $date_format = DateFormat::load('html_date')->getPattern();
        $time_format = DateFormat::load('html_time')->getPattern();
      }
      else {
        $date_format = $element['#date_date_format'] != 'none' ? static::getHtml5DateFormat($element) : '';
        $time_format = $element['#date_time_element'] != 'none' ? static::getHtml5TimeFormat($element) : '';
        if ($input instanceof DrupalDateTime) {
          $values = [
            'date' => $input->format($date_format),
            'time' => $input->format($time_format),
          ];
          $input = $values;
        }

        $date_input = $element['#date_date_element'] != 'none' && !empty($input['date']) ? $input['date'] : '';
        $time_input = $element['#date_time_element'] != 'none' && !empty($input['time']) ? $input['time'] : '';
      }

      // Seconds will be omitted in a post in case there's no entry.
      if (!empty($time_input) && strlen($time_input) == 5) {
        $time_input .= ':00';
      }

      try {
        $date_time_format = trim($date_format . ' ' . $time_format);
        $date_time_input = trim($date_input . ' ' . $time_input);
        $date = DrupalDateTime::createFromFormat($date_time_format, $date_time_input, $element['#date_timezone']);
      }
      catch (\Exception) {
        $date = NULL;
      }
      $input = [
        'date'   => $date_input,
        'time'   => $time_input,
        'object' => $date,
      ];
    }
    else {
      $date = $element['#default_value'] ?? NULL;
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $date->setTimezone(new \DateTimeZone($element['#date_timezone']));
        $input = [
          'date'   => $date->format($element['#date_date_format']),
          'time'   => $date->format($element['#date_time_format']),
          'object' => $date,
        ];
      }
      else {
        $input = [
          'date'   => '',
          'time'   => '',
          'object' => NULL,
        ];
      }
    }

    return $input;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getHtml5DateFormat($element) {
    switch ($element['#date_date_element']) {
      case 'date':
        return DateFormat::load('html_date')->getPattern();

      case 'datetime':
        return DateFormat::load('html_datetime')->getPattern();

      case 'datetime-local':
        return 'Y-m-d\TH:i';

      default:
        return $element['#date_date_format'];
    }
  }

}
