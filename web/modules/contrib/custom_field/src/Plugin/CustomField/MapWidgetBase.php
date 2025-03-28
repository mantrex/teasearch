<?php

namespace Drupal\custom_field\Plugin\CustomField;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;

/**
 * Base plugin class for map custom field widgets.
 */
class MapWidgetBase extends CustomFieldWidgetBase {

  /**
   * Default new item.
   *
   * @return string|int|array
   *   The default value for new items.
   */
  protected static function newItem(): string|int|array {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'table_empty' => '',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $element['#type'] = 'item';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $element['settings']['table_empty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty text'),
      '#description' => $this->t('The text to display when no items have been added.'),
      '#default_value' => $settings['table_empty'],
    ];

    return $element;
  }

  /**
   * Submit handler for the "add item" button.
   */
  public static function addItem(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $wrapper_id = $trigger['#ajax']['wrapper'];
    $items = $form_state->get($wrapper_id);
    $items[] = static::newItem();
    $form_state->set($wrapper_id, $items);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove item" button.
   */
  public static function removeItem(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $key = $trigger['#attributes']['data-key'];
    $wrapper_id = $trigger['#ajax']['wrapper'];
    $items = $form_state->get($wrapper_id);
    // Only unset if the key exists.
    if (isset($items[$key])) {
      unset($items[$key]);
    }
    $form_state->set($wrapper_id, $items);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    // Get the triggering element's wrapper ID.
    $trigger = $form_state->getTriggeringElement();
    $wrapper_id = $trigger['#ajax']['wrapper'];

    // Get the current parent array for this widget.
    $parents = $trigger['#array_parents'];
    $length = -1;
    if (isset($trigger['#attributes']['data-key'])) {
      $length = -3;
    }
    $sliced_parents = array_slice($parents, 0, $length, TRUE);

    // Get the updated element from the form structure.
    $updated_element = NestedArray::getValue($form, $sliced_parents)['data'];

    // Create an AjaxResponse.
    $response = new AjaxResponse();
    // Add a ReplaceCommand to replace the content inside the widget's wrapper.
    $response->addCommand(new ReplaceCommand('#' . $wrapper_id, $updated_element));

    return $response;
  }

}
