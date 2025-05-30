<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\FieldType\TelephoneType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'telephone' widget.
 */
#[CustomFieldWidget(
  id: 'telephone',
  label: new TranslatableMarkup('Telephone'),
  category: new TranslatableMarkup('General'),
  field_types: [
    'telephone',
  ],
)]
class TelephoneWidget extends TextWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings']['pattern'] = '';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $element['#type'] = 'tel';
    $element['#maxlength'] = TelephoneType::MAX_LENGTH;
    if (!empty($settings['pattern'])) {
      $format = $this->getTelephoneFormats()[$settings['pattern']];
      $element['#attributes']['pattern'] = $format['pattern'];
      $element['#description'] = $settings['description'] ?: $this->t('Enter a telephone number in the format: %format', ['%format' => $format['format']]);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    $element['settings']['pattern'] = [
      '#type' => 'select',
      '#title' => $this->t('Telephone format'),
      '#options' => $this->getTelephoneFormatOptions(),
      '#default_value' => $settings['pattern'],
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('A regex pattern to enforce input to a particular telephone format.'),
    ];

    return $element;
  }

  /**
   * Helper function to get telephone formats for various countries.
   *
   * @return array<string, array{label: string, format: string, regex: string, pattern: string}>
   *   An array of common telephone formats.
   */
  protected function getTelephoneFormats(): array {
    return [
      'AU' => [
        'label' => 'Australia',
        'format' => 'xx xxxx xxxx',
        'regex' => '/^[0-9]{2} [0-9]{4} [0-9]{4}$/',
        'pattern' => '[0-9]{2} [0-9]{4} [0-9]{4}',
      ],
      'BR' => [
        'label' => 'Brazil',
        'format' => '(xx) xxxx-xxxx',
        'regex' => '/^\([0-9]{2}\) [0-9]{4}-[0-9]{4}$/',
        'pattern' => '\([0-9]{2}\) [0-9]{4}-[0-9]{4}',
      ],
      'CA' => [
        'label' => 'Canada',
        'format' => 'xxx-xxx-xxxx',
        'regex' => '/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/',
        'pattern' => '[0-9]{3}-[0-9]{3}-[0-9]{4}',
      ],
      'CN' => [
        'label' => 'China',
        'format' => '0xx-xxxx-xxxx',
        'regex' => '/^0[0-9]{2}-[0-9]{4}-[0-9]{4}$/',
        'pattern' => '0[0-9]{2}-[0-9]{4}-[0-9]{4}',
      ],
      'FR' => [
        'label' => 'France',
        'format' => '0x xx xx xx xx',
        'regex' => '/^0[0-9] [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/',
        'pattern' => '0[0-9] [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}',
      ],
      'DE' => [
        'label' => 'Germany',
        'format' => '0xxx xxxxxxx',
        'regex' => '/^0[0-9]{3} [0-9]{7}$/',
        'pattern' => '0[0-9]{3} [0-9]{7}',
      ],
      'IN' => [
        'label' => 'India',
        'format' => 'xxxxx-xxxxx',
        'regex' => '/^[0-9]{5}-[0-9]{5}$/',
        'pattern' => '[0-9]{5}-[0-9]{5}',
      ],
      'JP' => [
        'label' => 'Japan',
        'format' => '0xx-xxx-xxxx',
        'regex' => '/^0[0-9]{2}-[0-9]{3}-[0-9]{4}$/',
        'pattern' => '0[0-9]{2}-[0-9]{3}-[0-9]{4}',
      ],
      'MX' => [
        'label' => 'Mexico',
        'format' => '01 (xxx) xxx-xxxx',
        'regex' => '/^01 \([0-9]{3}\) [0-9]{3}-[0-9]{4}$/',
        'pattern' => '01 \([0-9]{3}\) [0-9]{3}-[0-9]{4}',
      ],
      'ZA' => [
        'label' => 'South Africa',
        'format' => '0xx xxx xxxx',
        'regex' => '/^0[0-9]{2} [0-9]{3} [0-9]{4}$/',
        'pattern' => '0[0-9]{2} [0-9]{3} [0-9]{4}',
      ],
      'ES' => [
        'label' => 'Spain',
        'format' => '9xx xx xx xx',
        'regex' => '/^9[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/',
        'pattern' => '9[0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}',
      ],
      'GB' => [
        'label' => 'United Kingdom',
        'format' => 'xxxx-xxx-xxxx',
        'regex' => '/^[0-9]{4}-[0-9]{3}-[0-9]{4}$/',
        'pattern' => '[0-9]{4}-[0-9]{3}-[0-9]{4}',
      ],
      'US' => [
        'label' => 'United States',
        'format' => 'xxx-xxx-xxxx',
        'regex' => '/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/',
        'pattern' => '[0-9]{3}-[0-9]{3}-[0-9]{4}',
      ],
    ];
  }

  /**
   * Helper function to return telephone format options.
   *
   * @return array<string, mixed>
   *   An array of telephone format options.
   */
  protected function getTelephoneFormatOptions(): array {
    return array_map(function ($option) {
      return $this->t('@label: @format', [
        '@label' => $option['label'],
        '@format' => $option['format'],
      ]);
    }, $this->getTelephoneFormats());
  }

}
