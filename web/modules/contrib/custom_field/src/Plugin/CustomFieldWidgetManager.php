<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Attribute\CustomFieldWidget;

/**
 * Provides the custom field widget plugin manager.
 */
class CustomFieldWidgetManager extends DefaultPluginManager implements CustomFieldWidgetManagerInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new CustomFieldWidgetManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/CustomField/FieldWidget',
      $namespaces,
      $module_handler,
      'Drupal\custom_field\Plugin\CustomFieldWidgetInterface',
      CustomFieldWidget::class,
      'Drupal\Core\Field\Annotation\FieldWidget'
    );

    $this->alterInfo('custom_field_widget_info');
    $this->setCacheBackend($cache_backend, 'custom_field_widget_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Ensure that every field type has a category.
    if (empty($definition['category'])) {
      $definition['category'] = $this->t('General');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetsForField(string $type): array {
    $definitions = $this->getDefinitions();
    $widgets = [];
    foreach ($definitions as $definition) {
      if (in_array($type, $definition['field_types'])) {
        $widgets[] = $definition['id'];
      }
    }

    return $widgets;
  }

}
