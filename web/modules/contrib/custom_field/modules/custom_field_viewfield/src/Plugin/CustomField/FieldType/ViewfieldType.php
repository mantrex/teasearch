<?php

namespace Drupal\custom_field_viewfield\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomField\FieldType\EntityReference;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'boolean' custom field type.
 *
 * @CustomFieldType(
 *   id = "viewfield",
 *   label = @Translation("Viewfield"),
 *   description = @Translation("Defines a entity reference field type to display a view."),
 *   category = @Translation("Reference"),
 *   default_widget = "viewfield_select",
 *   default_formatter = "viewfield_default",
 * )
 */
class ViewfieldType extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    $columns = parent::schema($settings);
    ['name' => $name] = $settings;

    $display_id = $name . self::SEPARATOR . 'display';
    $arguments = $name . self::SEPARATOR . 'arguments';
    $items_to_display = $name . self::SEPARATOR . 'items';

    $columns[$name]['description'] = 'The ID of the view';
    $columns[$display_id] = [
      'description' => 'The ID of the view display.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    $columns[$arguments] = [
      'description' => 'Arguments to be passed to the display.',
      'type' => 'varchar',
      'length' => 255,
    ];
    $columns[$items_to_display] = [
      'description' => 'Items to display.',
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    $properties = parent::propertyDefinitions($settings);
    ['name' => $name] = $settings;

    $display_id = $name . self::SEPARATOR . 'display';
    $arguments = $name . self::SEPARATOR . 'arguments';
    $items_to_display = $name . self::SEPARATOR . 'items';

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_viewfield')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $name]))
      ->setSetting('target_type', 'view')
      ->setRequired(FALSE);

    $properties[$display_id] = DataDefinition::create('string')
      ->setLabel(t('Display ID'))
      ->setDescription(t('The view display ID'));

    $properties[$arguments] = DataDefinition::create('string')
      ->setLabel(t('Arguments'))
      ->setDescription(t('An optional comma-delimited list of arguments for the display'));

    $properties[$items_to_display] = DataDefinition::create('integer')
      ->setLabel(t('Items to display'))
      ->setDescription(t('Override the number of displayed items.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array {
    $dependencies = [];
    $entity_type_manager = \Drupal::entityTypeManager();
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $allowed_views = $widget_settings['allowed_views'] ?? [];
    foreach ($allowed_views as $view_name => $displays) {
      /** @var \Drupal\views\Entity\View $view */
      if ($view = $entity_type_manager->getStorage('view')->load($view_name)) {
        $filtered_displays = array_filter($displays);
        if (!empty($filtered_displays)) {
          $dependency_key = $view->getConfigDependencyKey();
          $dependencies[$dependency_key][] = $view->getConfigDependencyName();
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(array $settings): array {
    $dependencies = [];
    $entity_type_manager = \Drupal::entityTypeManager();
    $target_entity_type = $entity_type_manager->getDefinition($settings['target_type']);
    $dependencies['module'][] = $target_entity_type->getProvider();

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $views_changed = FALSE;
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $allowed_views = $widget_settings['allowed_views'] ?? [];
    $changed_settings = [];
    foreach ($allowed_views as $view_name => $displays) {
      /** @var \Drupal\views\Entity\View $view */
      if ($view = $entity_type_manager->getStorage('view')->load($view_name)) {
        $dependency_key = $view->getConfigDependencyKey();
        $dependency_name = $view->getConfigDependencyName();
        if (isset($dependencies[$dependency_key][$dependency_name])) {
          unset($allowed_views[$view_name]);
          $views_changed = TRUE;
        }
      }
    }
    if ($views_changed) {
      $widget_settings['allowed_views'] = $allowed_views;
      $changed_settings = $widget_settings;
    }

    return $changed_settings;
  }

}
