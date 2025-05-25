<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Base widget definition for custom field type.
 */
abstract class CustomWidgetBase extends WidgetBase {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $customFieldTypeManager;

  /**
   * The custom field widget manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldWidgetManagerInterface
   */
  protected CustomFieldWidgetManagerInterface $customFieldWidgetManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'label' => TRUE,
      'wrapper' => 'div',
      'open' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->customFieldTypeManager = $container->get('plugin.manager.custom_field_type');
    $instance->customFieldWidgetManager = $container->get('plugin.manager.custom_field_widget');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $definition = $this->fieldDefinition;

    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;

    $elements['label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show field label?'),
      '#default_value' => $this->getSetting('label'),
    ];
    $elements['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper'),
      '#default_value' => $this->getSetting('wrapper'),
      '#options' => [
        'div' => $this->t('Default'),
        'fieldset' => $this->t('Fieldset'),
        'details' => $this->t('Details'),
      ],
      '#states' => [
        'visible' => [
          'input[name="fields[' . $definition->getName() . '][settings_edit_form][settings][label]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show open by default?'),
      '#default_value' => $this->getSetting('open'),
      '#states' => [
        'visible' => [
          'input[name="fields[' . $definition->getName() . '][settings_edit_form][settings][label]"]' => ['checked' => TRUE],
          0 => 'AND',
          'select[name="fields[' . $definition->getName() . '][settings_edit_form][settings][wrapper]"]' => ['value' => 'details'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = $this->t('Show field label?: @label', ['@label' => $this->getSetting('label') ? 'Yes' : 'No']);
    $summary[] = $this->t('Wrapper: @wrapper', ['@wrapper' => $this->getSetting('wrapper')]);
    if ($this->getSetting('wrapper') === 'details') {
      $summary[] = $this->t('Open: @open', ['@open' => $this->getSetting('open') ? 'Yes' : 'No']);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['#attached']['library'][] = 'custom_field/custom-field-widget';
    if ($this->getSetting('label')) {
      switch ($this->getSetting('wrapper')) {
        case 'fieldset':
          $element['#type'] = 'fieldset';
          break;

        case 'details':
          $element['#type'] = 'details';
          $element['#open'] = $this->getSetting('open');
          break;

        default:
          $element['#type'] = 'item';
      }
    }

    return $element;
  }

  /**
   * Get the field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition.
   */
  public function getFieldStorageDefinition(): FieldStorageDefinitionInterface {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * Get the custom field items for this field.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   An array of custom field items.
   */
  public function getCustomFieldItems(): array {
    return $this->customFieldTypeManager->getCustomFieldItems($this->fieldDefinition->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $columns = $this->getFieldSetting('columns');
    $custom_items = $this->getCustomFieldItems();
    foreach ($values as &$value) {
      foreach ($value as $name => $field_value) {
        if (isset($custom_items[$name])) {
          $custom_item = $custom_items[$name];
          try {
            $widget_plugin = $this->customFieldWidgetManager->createInstance($custom_item->getWidgetPluginId());
            if (method_exists($widget_plugin, 'massageFormValue')) {
              $value[$name] = $widget_plugin->massageFormValue($field_value, $columns[$name]);
            }
          }
          catch (PluginException $e) {
            continue;
          }
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state): array {
    $parents = $form['#parents'];
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $processed_flag = "custom_field_{$field_name}_processed";
    if (!empty($parents)) {
      $id_suffix = implode('_', $parents);
      $processed_flag .= "_{$id_suffix}";
    }

    // If we're using unlimited cardinality we don't display one empty item.
    // Form validation will kick in if left empty which essentially means
    // people won't be able to submit without filling required fields for
    // another value.
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && count($items) > 0 && !$form_state->get($processed_flag)) {
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      if (empty($field_state['array_parents'])) {
        --$field_state['items_count'];
        static::setWidgetState($parents, $field_name, $form_state, $field_state);

        // Set a flag on the form denoting that we've already removed the empty
        // item that is usually appended to the end on fresh form loads.
        $form_state->set($processed_flag, TRUE);
      }
    }

    return parent::formMultipleElements($items, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    $path = explode('.', $error->getPropertyPath());
    $field_name = end($path);
    $custom_items = $this->getCustomFieldItems();
    if (!empty($element[$field_name]) && isset($custom_items[$field_name])) {
      $custom_item = $custom_items[$field_name];
      try {
        $widget_plugin = $this->customFieldWidgetManager->createInstance($custom_item->getWidgetPluginId());
        if (method_exists($widget_plugin, 'errorElement')) {
          return $widget_plugin->errorElement($element, $error, $form, $form_state);
        }
      }
      catch (PluginException $e) {
        // Plugin not found.
      }
    }
    return isset($error->arrayPropertyPath[0]) ? $element[$error->arrayPropertyPath[0]] : $element;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $custom_items = $this->getCustomFieldItems();
    foreach ($custom_items as $custom_item) {
      $widget_settings = $custom_item->getWidgetSettings() ?? [];
      if (empty($widget_settings)) {
        continue;
      }
      try {
        /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $plugin */
        $plugin = $this->customFieldWidgetManager->createInstance($custom_item->getWidgetPluginId());
        $plugin_dependencies = $plugin->calculateWidgetDependencies($widget_settings);
        $dependencies = array_merge($dependencies, $plugin_dependencies);
      }
      catch (PluginException $e) {
        // No dependencies applicable if we somehow have invalid plugin.
      }
    }

    return $dependencies;
  }

  /**
   * Reports field-level validation errors against actual form elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\custom_field\Plugin\Field\FieldType\CustomItem> $items
   *   The field values.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   A list of constraint violations to flag.
   * @param array<string, mixed> $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state): void {
    $custom_items = $this->getCustomFieldItems();
    foreach ($custom_items as $custom_item) {
      try {
        /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $plugin */
        $plugin = $this->customFieldWidgetManager->createInstance($custom_item->getWidgetPluginId());
        if (method_exists($plugin, 'flagErrors')) {
          $plugin->flagErrors($items, $violations, $form, $form_state);
        }
      }
      catch (PluginException $e) {
        // No errors applicable if we somehow have invalid plugin.
      }
    }

    parent::flagErrors($items, $violations, $form, $form_state);
  }

}
