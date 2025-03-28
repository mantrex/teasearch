<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'uuid' custom field widget.
 *
 * Simple uuid custom field widget. This doesn't actually render as an editable
 * widget on the form. Rather it sets a UUID on the field when the custom field
 * is first created to give a unique identifier to the custom field item.
 *
 * The main purpose of this field is to be able to identify a specific
 * custom field item without having to rely on any of the exposed fields which
 * could change at any given time (i.e. content is updated, or delta is changed
 * with a manual reorder).
 *
 * @FieldWidget(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   never_check_empty = TRUE,
 *   category = @Translation("General"),
 *   data_types = {
 *     "uuid",
 *   }
 * )
 */
class UuidWidget extends CustomFieldWidgetBase {

  /**
   * The Uuid service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->uuidService = $container->get('uuid');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    // We're not calling the parent widget method here since we don't want to
    // actually render this widget.
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    $field_name = $field->getName();
    $element = [
      '#type' => 'value',
      '#value' => NULL,
    ];
    if (!$is_config_form) {
      $element['#value'] = !empty($items[$delta]->{$field_name}) ? $items[$delta]->{$field_name} : $this->uuidService->generate();
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element['description'] = [
      '#markup' => '<em>This will set a UUID on the custom field item the first time it is created and can be used as a unique identifier for the item in your custom code. This is the main use for this field type.</em>',
    ];

    return $element;
  }

}
