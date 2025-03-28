<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the custom field formatter plugin manager.
 */
class CustomFieldFormatterManager extends DefaultPluginManager implements CustomFieldFormatterManagerInterface {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * Constructs a new CustomFieldFormatterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_type_manager
   *   The custom field type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, CustomFieldTypeManagerInterface $custom_field_type_manager) {
    parent::__construct(
      'Plugin/CustomField/FieldFormatter',
      $namespaces,
      $module_handler,
      'Drupal\custom_field\Plugin\CustomFieldFormatterInterface',
      'Drupal\Core\Field\Annotation\FieldFormatter'
    );

    $this->setCacheBackend($cache_backend, 'custom_field_formatter_plugins');
    $this->alterInfo('custom_field_formatter_info');
    $this->customFieldTypeManager = $custom_field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // @todo This is copied from \Drupal\Core\Plugin\Factory\ContainerFactory.
    //   Find a way to restore sanity to
    //   \Drupal\Core\Field\FormatterBase::__construct().
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($plugin_id, $plugin_definition, $configuration['custom_field_definition'], $configuration['settings'], $configuration['view_mode'], $configuration['third_party_settings']);
  }

  /**
   * Gets an instance of a formatter plugin.
   *
   * @param array $options
   *   An array of options to build the plugin.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldFormatterInterface|false
   *   A formatter object or FALSE when plugin is not found.
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $custom_field_definition */
    $custom_field_definition = $options['custom_field_definition'];
    $field_type = $custom_field_definition->getDataType();

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] == TRUE) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    // Switch back to default formatter if either:
    // - the configuration does not specify a formatter class
    // - the field type is not allowed for the formatter
    // - the formatter is not applicable to the field definition.
    $definition = $this->getDefinition($configuration['type'], FALSE);
    if (!isset($definition['class']) || !in_array($field_type, $definition['field_types']) || !$definition['class']::isApplicable($custom_field_definition)) {
      // Grabe the default formatter for the field type.
      $field_type_definition = $this->customFieldTypeManager->getDefinition($field_type);
      if (empty($field_type_definition['default_formatter'])) {
        return FALSE;
      }
      $plugin_id = $field_type_definition['default_formatter'];
    }

    $configuration += [
      'custom_field_definition' => $custom_field_definition,
      'view_mode' => $options['view_mode'] ?? 'default',
    ];
    return $this->createInstance($plugin_id, $configuration) ?? FALSE;
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $configuration
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += [
      'settings' => [],
      'third_party_settings' => [],
    ];

    // If no formatter is specified, use the default formatter.
    if (!isset($configuration['type'])) {
      $field_type = $this->customFieldTypeManager->getDefinition($field_type);
      $configuration['type'] = $field_type['default_formatter'];
    }
    // Filter out unknown settings, and fill in defaults for missing settings.
    $default_settings = $this->getDefaultSettings($configuration['type']);
    $configuration['settings'] = \array_intersect_key($configuration['settings'], $default_settings) + $default_settings;

    return $configuration;
  }

  /**
   * Returns the default settings of a custom_field formatter.
   *
   * @param string $type
   *   A custom_field formatter type name.
   *
   * @return array
   *   The formatter type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultSettings();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(CustomFieldTypeInterface $custom_field): array {
    $options = [];
    $field_type = $custom_field->getPluginId();
    foreach ($this->getDefinitions() as $id => $definition) {
      $plugin_class = DefaultFactory::getPluginClass($id, $definition);
      $is_applicable = $plugin_class::isApplicable($custom_field);
      if (!in_array($field_type, $definition['field_types']) || !$is_applicable) {
        continue;
      }
      $options[$id] = $definition['label'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputPathForStatesApi(FormStateInterface $form_state, string $field_name, string $property, bool $is_views_subfield = FALSE): string {
    $form_id = $form_state->getFormObject()->getFormId();
    $is_views_form = $form_id === 'views_ui_config_item_form';
    $is_block_form = $form_id === 'block_form';
    $is_layout_builder_form = $form_id === 'layout_builder_add_block' || $form_id === 'layout_builder_update_block';
    if ($is_views_form) {
      return $is_views_subfield ? 'options[settings]' : "options[settings][fields][$property][formatter_settings]";
    }
    elseif ($is_block_form) {
      return "settings[formatter_settings][fields][$property][formatter_settings]";
    }
    elseif ($is_layout_builder_form) {
      return "settings[formatter][settings][fields][$property][formatter_settings]";
    }
    return "fields[$field_name][settings_edit_form][settings][fields][$property][formatter_settings]";
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatterValueKeys(FormStateInterface $form_state, string $field_name, string $property): array {
    $form_id = $form_state->getFormObject()->getFormId();
    $value_keys = [
      'fields',
      $field_name,
      'settings_edit_form',
      'settings',
      'fields',
      $property,
      'format_type',
    ];

    switch ($form_id) {
      case 'views_ui_config_item_form':
        $value_keys = [
          'options',
          'settings',
          'fields',
          $property,
          'format_type',
        ];
        break;

      case 'block_form':
        $value_keys = [
          'settings',
          'formatter_settings',
          'fields',
          $property,
          'format_type',
        ];
        break;

      case 'layout_builder_add_block':
      case 'layout_builder_update_block':
        $value_keys = [
          'settings',
          'formatter',
          'settings',
          'fields',
          $property,
          'format_type',
        ];
        break;

      default:
        break;
    }

    return $value_keys;
  }

}
