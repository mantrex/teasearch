<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'entity_reference' custom field type.
 *
 * @CustomFieldType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   description = @Translation("A field containing an entity reference."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 * )
 */
class EntityReference extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name, 'target_type' => $target_type] = $settings;

    try {
      $target_type_info = \Drupal::entityTypeManager()
        ->getDefinition($target_type);
    }
    catch (PluginNotFoundException $e) {
      throw new FieldException(sprintf("Field '%s' references a target entity type '%s' which does not exist.",
        $name,
        $target_type
      ));
    }
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $properties */
    $properties = static::propertyDefinitions($settings);
    if ($target_type_info->entityClassImplements(FieldableEntityInterface::class) && $properties[$name]->getSetting('data_type') === 'integer') {
      $columns[$name] = [
        'type' => 'int',
        'description' => 'The ID of the target entity.',
        'unsigned' => TRUE,
      ];
    }
    else {
      $columns[$name] = [
        'type' => 'varchar_ascii',
        'description' => 'The ID of the target entity.',
        // If the target entities act as bundles for another entity type,
        // their IDs should not exceed the maximum length for bundles.
        'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
      ];
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): mixed {
    ['name' => $name, 'target_type' => $target_type] = $settings;
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);

    // If the target entity type doesn't have an ID key, we cannot determine
    // the target_id data type.
    if (!$target_type_info->hasKey('id')) {
      throw new FieldException('Entity type "' . $target_type_info->id() . '" has no ID key and cannot be targeted by entity reference field "' . $name . '"');
    }

    $target_id_data_type = 'string';
    if ($target_type_info->entityClassImplements(FieldableEntityInterface::class)) {
      $id_definition = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($target_type)[$target_type_info->getKey('id')];
      if ($id_definition->getType() === 'integer') {
        $target_id_data_type = 'integer';
      }
    }

    $target_id_definition = CustomFieldDataDefinition::create('custom_field_entity_reference')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $name]))
      ->setSetting('data_type', $target_id_data_type)
      ->setSetting('target_type', $target_type)
      ->setRequired(FALSE);

    if ($target_id_data_type === 'integer') {
      $target_id_definition->setSetting('unsigned', TRUE);
    }

    $properties[$name] = $target_id_definition;
    $properties[$name . self::SEPARATOR . 'entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      ->setComputed(TRUE)
      ->setSettings(['target_id' => $name, 'target_type' => $target_type])
      ->setClass('\Drupal\custom_field\Plugin\CustomField\EntityReferenceComputed')
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($target_type))
      ->addConstraint('EntityType', $target_type);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $configuration = $item->getConfiguration();
    $target_entity_type = $entity_type_manager->getDefinition($configuration['target_type']);
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $target_bundles = $widget_settings['handler_settings']['target_bundles'] ?? [];
    $dependencies = [];
    $field_name = $item->getName();
    // Depend on default values entity types configurations.
    if (!empty($default_value)) {
      foreach ($default_value as $value) {
        if (isset($value[$field_name])) {
          $entity = $entity_type_manager->getStorage($configuration['target_type'])->load($value[$field_name]);
          if ($entity) {
            $dependencies[$entity->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
          }
        }
      }
    }
    // Depend on target bundle configurations. Dependencies for 'target_bundles'
    // also covers the 'auto_create_bundle' setting, if any, because its value
    // is included in the 'target_bundles' list.
    if (!empty($target_bundles)) {
      if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
        if ($storage = $entity_type_manager->getStorage($bundle_entity_type_id)) {
          foreach ($storage->loadMultiple($target_bundles) as $bundle) {
            $dependencies[$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
          }
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(array $settings): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $dependencies = [];
    $target_entity_type = $entity_type_manager->getDefinition($settings['target_type']);
    $dependencies['module'][] = $target_entity_type->getProvider();

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $bundles_changed = FALSE;
    $configuration = $item->getConfiguration();
    $target_entity_type = $entity_type_manager->getDefinition($configuration['target_type']);
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $handler_settings = $widget_settings['handler_settings'] ?? [];
    $changed_settings = [];

    if (!empty($handler_settings['target_bundles'])) {
      if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
        if ($storage = $entity_type_manager->getStorage($bundle_entity_type_id)) {
          foreach ($storage->loadMultiple($handler_settings['target_bundles']) as $bundle) {
            if (isset($dependencies[$bundle->getConfigDependencyKey()][$bundle->getConfigDependencyName()])) {
              unset($handler_settings['target_bundles'][$bundle->id()]);

              // If this bundle is also used in the 'auto_create_bundle'
              // setting, disable the auto-creation feature completely.
              $auto_create_bundle = !empty($handler_settings['auto_create_bundle']) ? $handler_settings['auto_create_bundle'] : FALSE;
              if ($auto_create_bundle && $auto_create_bundle == $bundle->id()) {
                $handler_settings['auto_create'] = FALSE;
                $handler_settings['auto_create_bundle'] = NULL;
              }

              $bundles_changed = TRUE;
            }
          }
        }
      }
    }
    if ($bundles_changed) {
      $widget_settings['handler_settings'] = $handler_settings;
      $changed_settings = $widget_settings;
    }

    return $changed_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed {
    $widget_settings = $field->getWidgetSetting('settings');
    $handler_settings = $widget_settings['handler_settings'] ?? [];

    // If the field hasn't been configured yet, return early.
    if (empty($handler_settings)) {
      return NULL;
    }

    $target_type = $field->getTargetType();
    // An associative array keyed by the reference type, target type, and
    // bundle.
    static $recursion_tracker = [];

    $manager = \Drupal::service('plugin.manager.entity_reference_selection');

    // Instead of calling $manager->getSelectionHandler($field_definition)
    // replicate the behavior to be able to override the sorting settings.
    $options = [
      'target_type' => $target_type,
      'handler' => $widget_settings['handler'],
      'entity' => NULL,
    ] + $handler_settings;

    $entity_type = \Drupal::entityTypeManager()->getDefinition($options['target_type']);
    $options['sort'] = [
      'field' => $entity_type->getKey('id'),
      'direction' => 'DESC',
    ];

    $selection_handler = $manager->getInstance($options);

    // Select a random number of references between the last 50 referenceable
    // entities created.
    if ($referenceable = $selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 50)) {
      $group = array_rand($referenceable);

      return array_rand($referenceable[$group]);
    }

    // Attempt to create a sample entity, avoiding recursion.
    $entity_storage = \Drupal::entityTypeManager()->getStorage($options['target_type']);
    if ($entity_storage instanceof ContentEntityStorageInterface) {
      $bundle = static::getRandomBundle($entity_type, $options);

      // Track the generated entity by reference type, target type, and bundle.
      $key = $target_entity_type . ':' . $options['target_type'] . ':' . $bundle;

      // If entity generation was attempted but did not finish, do not continue.
      if (isset($recursion_tracker[$key])) {
        return [];
      }

      // Mark this as an attempt at generation.
      $recursion_tracker[$key] = TRUE;

      // Mark the sample entity as being a preview.
      $entity = $entity_storage->createWithSampleValues($bundle, [
        'in_preview' => TRUE,
      ]);

      // Remove the indicator once the entity is successfully generated.
      unset($recursion_tracker[$key]);
      return ['entity' => $entity];
    }

    return NULL;
  }

  /**
   * Gets a bundle for a given entity type and selection options.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param array $selection_settings
   *   An array of selection settings.
   *
   * @return string|null
   *   Either the bundle string, or NULL if there is no bundle.
   */
  protected static function getRandomBundle(EntityTypeInterface $entity_type, array $selection_settings) {
    if ($entity_type->getKey('bundle')) {
      if (!empty($selection_settings['target_bundles'])) {
        $bundle_ids = $selection_settings['target_bundles'];
      }
      else {
        $bundle_ids = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type->id());
      }
      return array_rand($bundle_ids);
    }
  }

}
