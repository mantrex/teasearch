<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_stacked' widget.
 *
 * @FieldWidget(
 *   id = "custom_stacked",
 *   label = @Translation("Stacked"),
 *   weight = 2,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomStackedWidget extends CustomWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Account for unsaved fields in field config default values form.
    if (!empty($form_state->get('current_settings'))) {
      $current_settings = $form_state->get('current_settings');
      $field_settings = $current_settings['field_settings'];
      $custom_items = $this->customFieldManager->getCustomFieldItems($current_settings);
    }
    else {
      $field_settings = $this->getFieldSetting('field_settings');
      $custom_items = $this->getCustomFieldItems();
    }

    foreach ($custom_items as $name => $custom_item) {
      $type = $field_settings[$name]['type'] ?? $custom_item->getDefaultWidget();
      if (!in_array($type, $this->customFieldWidgetManager->getWidgetsForField($custom_item->getPluginId()))) {
        $type = $custom_item->getDefaultWidget();
      }
      $widget_plugin = $this->customFieldWidgetManager->createInstance($type, ['settings' => $field_settings[$name]['widget_settings'] ?? []]);
      $element[$name] = $widget_plugin->widget($items, $delta, $element, $form, $form_state, $custom_item);
    }

    return $element;
  }

}
