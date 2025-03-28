<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\FieldType\EmailType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Plugin implementation of the 'email' custom field widget.
 *
 * @FieldWidget(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   category = @Translation("General"),
 *   data_types = {
 *     "string",
 *     "email",
 *   }
 * )
 */
class EmailWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings');

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'email',
      '#maxlength' => EmailType::EMAIL_MAX_LENGTH,
      '#placeholder' => $settings['placeholder'] ?? NULL,
      '#size' => $settings['size'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];

    $element['settings']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element;
  }

}
