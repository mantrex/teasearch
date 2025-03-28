<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomField\EntityReferenceOptionsWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'entity_reference_select' custom field widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_select",
 *   label = @Translation("Select"),
 *   category = @Translation("Reference"),
 *   data_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceSelectWidget extends EntityReferenceOptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'handler_settings' => [],
        'empty_option' => '- Select -',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];

    $element += [
      '#empty_option' => $settings['empty_option'],
    ];

    return ['target_id' => $element];
  }

}
