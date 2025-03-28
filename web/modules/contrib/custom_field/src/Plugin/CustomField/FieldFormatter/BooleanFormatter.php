<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'boolean' formatter.
 *
 * @FieldFormatter(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   field_types = {
 *     "boolean",
 *   }
 * )
 */
class BooleanFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = [];

    // Fall back to field settings by default.
    $settings['format'] = 'yes-no';
    $settings['format_custom_false'] = '';
    $settings['format_custom_true'] = '';

    return $settings;
  }

  /**
   * Gets the available format options.
   *
   * @return array|string
   *   A list of output formats. Each entry is keyed by the machine name of the
   *   format. The value is an array, of which the first item is the result for
   *   boolean TRUE, the second is for boolean FALSE. The value can be also an
   *   array, but this is just the case for the custom format.
   */
  protected function getOutputFormats(): array|string {
    return [
      'yes-no' => [$this->t('Yes'), $this->t('No')],
      'true-false' => [$this->t('True'), $this->t('False')],
      'on-off' => [$this->t('On'), $this->t('Off')],
      'enabled-disabled' => [$this->t('Enabled'), $this->t('Disabled')],
      'boolean' => [1, 0],
      'unicode-yes-no' => ['✔', '✖'],
      'custom' => $this->t('Custom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $formats = [];
    foreach ($this->getOutputFormats() as $format_name => $format) {
      if (is_array($format)) {
        if ($format_name == 'default') {
          $formats[$format_name] = $this->t('Field settings (@on_label / @off_label)', [
            '@on_label' => $format[0],
            '@off_label' => $format[1],
          ]);
        }
        else {
          $formats[$format_name] = $this->t('@on_label / @off_label', [
            '@on_label' => $format[0],
            '@off_label' => $format[1],
          ]);
        }
      }
      else {
        $formats[$format_name] = $format;
      }
    }

    $visible = $form['#visibility_path'];
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $formats,
    ];
    $elements['format_custom_true'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for TRUE'),
      '#default_value' => $this->getSetting('format_custom_true'),
      '#states' => [
        'visible' => [
          'select[name="' . $visible . '[format]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $elements['format_custom_false'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom output for FALSE'),
      '#default_value' => $this->getSetting('format_custom_false'),
      '#states' => [
        'visible' => [
          'select[name="' . $visible . '[format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    $formats = $this->getOutputFormats();
    $format = $this->getSetting('format');

    if ($format == 'custom') {
      $output = $value ? $this->getSetting('format_custom_true') : $this->getSetting('format_custom_false');
    }
    else {
      $output = $value ? $formats[$format][0] : $formats[$format][1];
    }

    return $output;
  }

}
