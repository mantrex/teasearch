<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Base widget definition for custom field type.
 */
abstract class CustomWidgetBase extends WidgetBase {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldManager;

  /**
   * The custom field widget manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager
   */
  protected $customFieldWidgetManager;

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
    $instance->customFieldManager = $container->get('plugin.manager.custom_field_type');
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
    return $this->customFieldManager->getCustomFieldItems($this->fieldDefinition->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $columns = $this->getFieldSetting('columns');
    $field_settings = $this->getFieldSetting('field_settings');
    foreach ($values as &$value) {
      foreach ($value as $name => $field_value) {
        if (isset($columns[$name])) {
          $type = $columns[$name]['type'];
          /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
          $plugin = $this->customFieldManager->createInstance($type);
          $widget_type = $field_settings[$name]['type'] ?? $plugin->getPluginDefinition()['default_widget'];
          $widget_plugin = $this->customFieldWidgetManager->createInstance($widget_type);
          if (method_exists($widget_plugin, 'massageFormValue')) {
            $value[$name] = $widget_plugin->massageFormValue($field_value, $columns[$name]);
          }
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
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
    $field_settings = $this->getFieldSetting('field_settings');
    $columns = $this->getFieldSetting('columns');
    if (!empty($element[$field_name])) {
      $definition = $this->customFieldManager->createInstance($columns[$field_name]['type'])->getPluginDefinition();
      $widget_type = $field_settings[$field_name]['type'] ?? $definition['default_widget'];
      $widget_plugin = $this->customFieldWidgetManager->createInstance($widget_type);
      if (method_exists($widget_plugin, 'errorElement')) {
        return $widget_plugin->errorElement($element, $error, $form, $form_state);
      }
    }
    return isset($error->arrayPropertyPath[0]) ? $element[$error->arrayPropertyPath[0]] : $element;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $field_settings = $this->getFieldSetting('field_settings');
    if (!empty($field_settings)) {
      foreach ($field_settings as $field_setting) {
        $widget_settings = $field_setting['widget_settings'] ?? [];
        if (empty($widget_settings)) {
          continue;
        }
        try {
          /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $plugin */
          $plugin = $this->customFieldWidgetManager->createInstance($field_setting['type']);
          $plugin_dependencies = $plugin->calculateWidgetDependencies($widget_settings);
          $dependencies = array_merge($dependencies, $plugin_dependencies);
        }
        catch (PluginException $e) {
          // No dependencies applicable if we somehow have invalid plugin.
        }
      }
    }

    return $dependencies;
  }

}
