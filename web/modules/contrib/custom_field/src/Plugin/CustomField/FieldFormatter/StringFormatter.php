<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'string' formatter.
 *
 * Value renders as it is entered by the user.
 */
#[FieldFormatter(
  id: 'string',
  label: new TranslatableMarkup('Plain text'),
  field_types: [
    'color',
    'email',
    'map',
    'map_string',
    'string',
    'string_long',
    'telephone',
    'uri',
    'uuid',
  ],
)]
class StringFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'prefix_suffix' => FALSE,
      'key_label' => 'label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements['key_label'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display'),
      '#description' => $this->t('Select the output when values are restricted in field widget.'),
      '#options' => [
        'key' => $this->t('Key'),
        'label' => $this->t('Label'),
      ],
      '#default_value' => $this->getSetting('key_label'),
    ];
    $elements['prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display prefix/suffix'),
      '#default_value' => $this->getSetting('prefix_suffix'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, mixed $value): mixed {
    if ($value === '' || $value === NULL) {
      return NULL;
    }

    // Account for map data types.
    if (is_array($value)) {
      if (empty($value)) {
        return NULL;
      }

      // Format the JSON output with JSON_PRETTY_PRINT.
      $formatted_json = json_encode($value, JSON_PRETTY_PRINT);

      // Return as HTML content with preformatted styling.
      return [
        '#markup' => '<pre>' . htmlspecialchars((string) $formatted_json) . '</pre>',
        '#allowed_tags' => ['pre'],
      ];
    }

    $allowed_values = $this->getFieldWidgetSetting('allowed_values') ?? [];

    if (!empty($allowed_values) && $this->getSetting('key_label') == 'label') {
      $index = array_search($value, array_column($allowed_values, 'key'));
      $value = $index !== FALSE ? $allowed_values[$index]['value'] : $value;
    }
    elseif ($this->getSetting('prefix_suffix') ?? FALSE) {
      $prefix = $this->getFieldWidgetSetting('prefix') ?? '';
      $suffix = $this->getFieldWidgetSetting('suffix') ?? '';
      $value = $prefix . $value . $suffix;
    }

    return $value;
  }

}
