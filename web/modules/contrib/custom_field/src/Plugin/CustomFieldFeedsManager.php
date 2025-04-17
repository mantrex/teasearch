<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Attribute\CustomFieldFeedsType;

/**
 * Provides the custom field feeds plugin manager.
 */
class CustomFieldFeedsManager extends DefaultPluginManager implements CustomFieldFeedsManagerInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new CustomFieldFeedsManager object.
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
      'Plugin/CustomField/FeedsType',
      $namespaces,
      $module_handler,
      'Drupal\custom_field\Plugin\CustomFieldFeedsTypeInterface',
      CustomFieldFeedsType::class,
      'Drupal\custom_field\Annotation\CustomFieldFeedsType'
    );

    $this->alterInfo('custom_field_feeds_info');
    $this->setCacheBackend($cache_backend, 'custom_field_feeds_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedsTargets(array $settings): array {
    $items = [];
    $field_settings = $settings['field_settings'] ?? [];

    foreach ($settings['columns'] as $name => $column) {
      $settings = $field_settings[$name] ?? [];
      $type = $column['type'];

      try {
        $items[$name] = $this->createInstance($type, [
          'name' => $column['name'],
          'max_length' => (int) $column['max_length'],
          'unsigned' => $column['unsigned'],
          'precision' => (int) $column['precision'],
          'scale' => (int) $column['scale'],
          'size' => $column['size'] ?? 'normal',
          'target_type' => $column['target_type'] ?? NULL,
          'datetime_type' => $column['datetime_type'] ?? 'datetime',
          'uri_scheme' => $column['uri_scheme'] ?? NULL,
          'widget_settings' => $settings['widget_settings'] ?? [],
        ]);
      }
      catch (PluginException $e) {
        // Should we log the error?
      }
    }

    return $items;
  }

}
