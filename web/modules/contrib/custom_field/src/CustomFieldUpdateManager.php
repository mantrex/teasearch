<?php

namespace Drupal\custom_field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Provides the CustomFieldUpdateManager service.
 */
class CustomFieldUpdateManager implements CustomFieldUpdateManagerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;
  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The plugin manager for custom field types.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $customFieldTypeManager;

  /**
   * The installed entity definition repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected EntityLastInstalledSchemaRepositoryInterface $lastInstalledSchemaRepository;

  /**
   * The Key-Value Factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|\Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected KeyValueStoreInterface|KeyValueFactoryInterface $keyValue;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a new CustomFieldUpdateManager object.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_type_manager
   *   The plugin manager for custom field types.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository
   *   The installed entity definition repository.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The Key-Value Factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(
    EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    Connection $database,
    CustomFieldTypeManagerInterface $custom_field_type_manager,
    EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository,
    KeyValueFactoryInterface $key_value,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->database = $database;
    $this->customFieldTypeManager = $custom_field_type_manager;
    $this->lastInstalledSchemaRepository = $last_installed_schema_repository;
    $this->keyValue = $key_value->get('entity.storage_schema.sql');
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Adds a new column to the specified field storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $new_property
   *   The new property name (column name).
   * @param string $data_type
   *   The data type to add. Allowed values such as: "integer", "boolean" etc.
   * @param array $options
   *   An array of options to set for new column.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function addColumn(string $entity_type_id, string $field_name, string $new_property, string $data_type, array $options = []): void {
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id);

    // Return early if no storage definition.
    if (!$field_storage_definition) {
      $message = 'There is no field storage definition for field ' . $field_name . ' and entity type ' . $entity_type_id . '.';
      throw new \Exception($message);
    }

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $definitions = $this->customFieldTypeManager->getDefinitions();
    $column = $definitions[$data_type] ?? NULL;

    // If we don't have a matching data type, return early.
    if (!$column) {
      $allowed_data_types = array_keys($definitions);
      sort($allowed_data_types);
      $valid_types_string = "\n" . implode("\n", $allowed_data_types);
      throw new \InvalidArgumentException(sprintf("Field '%s' requires a valid data type. Valid data types are: %s",
        $new_property,
        $valid_types_string
      ));
    }

    // Validate options.
    $target_type = NULL;
    $date_time_type = NULL;
    $uri_scheme = NULL;
    switch ($data_type) {
      case 'string':
      case 'telephone':
        $max = $data_type === 'telephone' ? 256 : 255;
        if (isset($options['length']) && (!is_numeric($options['length']) || $options['length'] > $max)) {
          throw new \InvalidArgumentException(sprintf("Field '%s' requires a numeric 'length' <= %s characters.",
            $new_property,
            $max,
          ));
        }
        break;

      case 'integer':
      case 'float':
      case 'decimal':
        if (isset($options['unsigned']) && !is_bool($options['unsigned'])) {
          throw new \InvalidArgumentException(sprintf("Field '%s' requires a boolean 'unsigned' value.",
            $new_property,
          ));
        }
        if (in_array($data_type, ['integer', 'float']) && isset($options['size'])) {
          $valid_sizes = [
            'tiny',
            'small',
            'medium',
            'big',
            'normal',
          ];
          if (!in_array($options['size'], $valid_sizes)) {
            $valid_size_string = "\n" . implode("\n", $valid_sizes);
            throw new \InvalidArgumentException(sprintf("Field '%s' requires a valid 'size' value. Valid sizes are:%s",
              $new_property,
              $valid_size_string,
            ));
          }
        }
        if ($data_type === 'decimal') {
          if (isset($options['precision'])) {
            $precision = (int) $options['precision'];
            if ($precision < 10 || $precision > 32) {
              throw new \InvalidArgumentException(sprintf("Field '%s' requires a numeric 'precision' value between 10 and 32.",
                $new_property,
              ));
            }
            $options['precision'] = $precision;
          }
          if (isset($options['scale'])) {
            if (!is_numeric($options['scale']) || $options['scale'] > 10) {
              throw new \InvalidArgumentException(sprintf("Field '%s' requires a numeric 'scale' value <= 10.",
                $new_property,
              ));
            }
            // Cast to integer.
            $options['scale'] = (int) $options['scale'];
          }
        }
        break;

      case 'entity_reference':
        if (!isset($options['target_type'])) {
          $entity_types = $this->entityTypeManager->getDefinitions();
          $valid_entity_types = array_keys($entity_types);
          sort($valid_entity_types);
          $valid_types_string = "\n" . implode("\n", $valid_entity_types);
          throw new \InvalidArgumentException(sprintf("Field '%s' requires a 'target_type'. Valid target types are:%s",
            $new_property,
            $valid_types_string,
          ));
        }
        $target_type = $options['target_type'];
        break;

      case 'datetime':
        $date_time_types = ['date', 'datetime'];
        if (!isset($options['datetime_type']) || !in_array($options['datetime_type'], $date_time_types)) {
          $valid_types_string = "\n" . implode("\n", $date_time_types);
          throw new \InvalidArgumentException(sprintf("Field '%s' requires a 'datetime_type'. Valid datetime types are:%s",
            $new_property,
            $valid_types_string,
          ));
        }
        $date_time_type = $options['datetime_type'];
        break;

      case 'image':
      case 'file':
        $target_type = 'file';
        $options['target_type'] = 'file';
        $uri_scheme = $this->configFactory->get('system.file')->get('default_scheme');
        break;

      case 'viewfield':
        $target_type = 'view';
        $options['target_type'] = $target_type;
        if (!$this->moduleHandler->moduleExists('custom_field_viewfield')) {
          throw new \InvalidArgumentException(sprintf("Field '%s' requires the 'custom_field_viewfield' module to be enabled.",
            $new_property,
          ));
        }
        break;
    }
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
    $plugin = $this->customFieldTypeManager->createInstance($data_type);
    $options['name'] = $new_property;
    $custom_field_schema = $plugin->schema($options);
    $spec = current($custom_field_schema);
    $spec['not null'] = FALSE;
    $spec['default'] = NULL;

    // If the storage is SqlContentEntityStorage, update the database schema.
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping([
      $field_name => $field_storage_definition,
    ]);

    $table_names = $table_mapping->getDedicatedTableNames();
    $column_name = "{$field_storage_definition->getName()}_{$new_property}";
    $schema = $this->database->schema();

    $existing_data = [];
    $is_revisionable = $entity_type->isRevisionable() && $field_storage_definition->isRevisionable();
    foreach ($table_names as $table_name) {
      $field_exists = $schema->fieldExists($table_name, $column_name);
      $table_exists = $schema->tableExists($table_name);

      // Skip revision tables for non-revisionable entity types.
      if ($table_name === $entity_type_id . '_revision__' . $field_name && !$is_revisionable) {
        continue;
      }

      // Add the new column.
      if (!$field_exists && $table_exists) {
        $schema->addField($table_name, $column_name, $spec);
        // Get the old data.
        $existing_data[$table_name] = $this->database->select($table_name)
          ->fields($table_name)
          ->execute()
          ->fetchAll(\PDO::FETCH_ASSOC);
        // Wipe it.
        $this->database->truncate($table_name)->execute();
      }
      else {
        // Show message that field already exists.
        $message = 'The column ' . $column_name . ' already exists in table ' . $table_name . '.';
        throw new \Exception($message);
      }
    }

    // Load the installed field schema so that it can be updated.
    $schema_key = "$entity_type_id.field_schema_data.$field_name";
    $field_schema_data = $this->keyValue->get($schema_key);

    // Add the new column to the installed field schema.
    foreach ($field_schema_data as $table_name => $fieldSchema) {
      $field_schema_data[$table_name]['fields'][$column_name] = $spec;
    }

    // Save changes to the installed field schema.
    $this->keyValue->set($schema_key, $field_schema_data);

    // Tell Drupal we have handled column changes.
    $new_field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id);
    if ($new_field_storage_definition instanceof FieldStorageConfigInterface) {
      $new_field_storage_definition->setSetting('column_changes_handled', TRUE);
      $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($new_field_storage_definition);

      // Update cached entity definitions for entity types.
      if ($table_mapping->allowsSharedTableStorage($new_field_storage_definition)) {
        $definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
        $definitions[$field_name] = $new_field_storage_definition;
        $this->lastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
      }
    }

    // Update config.
    $field_storage_config = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    $columns = $field_storage_config->getSetting('columns');

    // These settings exist across all data types.
    $column_config = [
      'type' => $data_type,
      'name' => $new_property,
    ];

    // Add the conditional settings based on schema match from data type.
    $optional_config = array_filter([
      'length' => $spec['length'] ?? NULL,
      'unsigned' => $spec['unsigned'] ?? NULL,
      'precision' => $spec['precision'] ?? NULL,
      'scale' => $spec['scale'] ?? NULL,
      'size' => $spec['size'] ?? NULL,
      'datetime_type' => $date_time_type ?: NULL,
      'target_type' => $target_type ?: NULL,
      'uri_scheme' => $uri_scheme ?: NULL,
    ], fn($value) => $value !== NULL);

    $column_config = array_merge($column_config, $optional_config);
    $columns[$new_property] = $column_config;

    $field_storage_config->setSetting('columns', $columns);
    $field_storage_config->save();

    if (!empty($existing_data)) {
      $this->restoreData($table_names, $existing_data);
    }

  }

  /**
   * Removes a column from the specified field storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $property
   *   The name of the column to remove.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   * @throws \Exception
   */
  public function removeColumn(string $entity_type_id, string $field_name, string $property): void {
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id);

    // Return early if no storage definition.
    if (!$field_storage_definition) {
      $message = 'There is no field storage definition for field ' . $field_name . ' and entity type ' . $entity_type_id . '.';
      throw new \Exception($message);
    }

    $schema = $this->database->schema();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    assert($storage instanceof SqlContentEntityStorage);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping([
      $field_name => $field_storage_definition,
    ]);
    $table_names = $table_mapping->getDedicatedTableNames();
    $table = $table_mapping->getDedicatedDataTableName($field_storage_definition);
    $column_name = $table_mapping->getFieldColumnName($field_storage_definition, $property);
    $table_columns = $field_storage_definition->getColumns();

    // Return early if there's only one column or if $property doesn't exist.
    if (!isset($table_columns[$property])) {
      $message = $column_name . ' cannot be removed because it does not exist for ' . $field_name;
      throw new \Exception($message);
    }
    elseif (count($table_columns) <= 1) {
      $message = 'Removing column ' . $column_name . ' would leave no remaining columns. The custom field requires at least 1 column.';
      throw new \Exception($message);
    }

    // Load the installed field schema so that it can be updated.
    $schema_key = "$entity_type_id.field_schema_data.$field_name";
    $field_schema_data = $this->keyValue->get($schema_key);

    // Save changes to the installed field schema.
    $existing_data = [];
    $is_revisionable = $entity_type->isRevisionable() && $field_storage_definition->isRevisionable();
    if ($field_schema_data) {
      foreach ($table_names as $table_name) {
        $field_exists = $schema->fieldExists($table_name, $column_name);
        $table_exists = $schema->tableExists($table_name);

        // Skip revision tables for non-revisionable entity types.
        if ($table_name === $entity_type_id . '_revision__' . $field_name && !$is_revisionable) {
          continue;
        }

        // Remove the new column.
        if ($field_exists && $table_exists) {
          // Get the old data.
          $existing_data[$table_name] = $this->database->select($table_name)
            ->fields($table_name)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
          // Wipe it.
          $this->database->truncate($table_name)->execute();
          unset($field_schema_data[$table_name]['fields'][$column_name]);
        }
      }
      // Update schema definition in database.
      $this->keyValue->set($schema_key, $field_schema_data);

      // Tell Drupal we have handled column changes.
      if ($field_storage_definition instanceof FieldStorageConfigInterface) {
        $field_storage_definition->setSetting('column_changes_handled', TRUE);
      }
      $this->entityDefinitionUpdateManager->updateFieldStorageDefinition($field_storage_definition);
    }

    // Update the field storage config.
    $field_storage_config = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    // Remove the column from the field storage configuration.
    $columns = $field_storage_config->getSetting('columns');
    if (isset($columns[$property])) {
      unset($columns[$property]);
      $field_storage_config->setSetting('columns', $columns);
      $field_storage_config->save();
    }

    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
    foreach ($bundles as $bundle) {
      // Update the field config for each bundle.
      if ($field_config = FieldConfig::loadByName($entity_type_id, $bundle, $field_name)) {
        assert($field_config instanceof FieldConfigInterface);
        $settings = $field_config->getSettings();
        foreach ($settings as $setting_type => $setting) {
          if (is_array($setting) && isset($setting[$property])) {
            unset($settings[$setting_type][$property]);
            $field_config->setSettings($settings);
            $field_config->save();
          }
        }

        // Update entity form display configs.
        if ($displays = $this->entityTypeManager->getStorage('entity_form_display')->loadByProperties([
          'targetEntityType' => $field_config->getTargetEntityTypeId(),
          'bundle' => $field_config->getTargetBundle(),
        ])) {
          /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
          foreach ($displays as $display) {
            if ($component = $display->getComponent($field_name)) {
              // Check for settings to remove in the custom_flex plugin.
              if (isset($component['settings']['columns'][$property])) {
                unset($component['settings']['columns'][$property]);
                $display->setComponent($field_name, $component)->save();
              }
            }
          }
        }

        // Update entity view display configs.
        if ($displays = $this->entityTypeManager->getStorage('entity_view_display')->loadByProperties([
          'targetEntityType' => $field_config->getTargetEntityTypeId(),
          'bundle' => $field_config->getTargetBundle(),
        ])) {
          /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
          foreach ($displays as $display) {
            if ($component = $display->getComponent($field_name)) {
              if (isset($component['settings']['fields'][$property])) {
                unset($component['settings']['fields'][$property]);
                $display->setComponent($field_name, $component)->save();
              }
            }
          }
        }
      }
    }

    // Try to drop field data.
    $this->database->schema()->dropField($table, $column_name);

    // Restore the data after removing the column.
    if (!empty($existing_data)) {
      foreach ($existing_data as $table => $fields) {
        foreach ($fields as $key => $field) {
          unset($existing_data[$table][$key][$column_name]);
        }
      }
      $this->restoreData($table_names, $existing_data);
    }
  }

  /**
   * A batch wrapper function for restoring data.
   *
   * @param array $tables
   *   The array of table names to restore data for.
   * @param array $existing_data
   *   The existing data to be restored for each table.
   */
  private function restoreData(array $tables, array $existing_data): void {
    $batch_size = 50;
    $tables_count = count($tables);

    // Initialize the batch.
    $batch = [
      'title' => $this->t('Restoring data...'),
      'operations' => [],
      'init_message' => $this->formatPlural($tables_count,
        'Starting data restoration for 1 table...',
        'Starting data restoration for @count tables...',
        ['@count' => count($tables)]),
      'error_message' => $this->t('An error occurred during data restoration. Please check the logs for errors.'),
      'finished' => [$this, 'restoreDataBatchFinished'],
    ];

    // Add table names to the batch context.
    $batch['context']['tables'] = $tables;

    // Process each table separately and create a batch for each one.
    foreach ($tables as $table_name) {
      if (!empty($existing_data[$table_name])) {
        // Populate the 'operations' array with data processing tasks.
        $total_rows = count($existing_data[$table_name]);
        $chunks = array_chunk($existing_data[$table_name], $batch_size);
        $chunk_total = count($chunks);

        foreach ($chunks as $chunk_index => $chunk) {
          $batch['operations'][] = [
            [$this, 'restoreDataBatchCallback'],
            [$table_name, $chunk, $total_rows, $chunk_total, $chunk_index + 1],
          ];
        }
      }
    }
    if (!empty($batch['operations'])) {
      // Queue the batch for processing.
      batch_set($batch);
    }
  }

  /**
   * The batch processing callback function for restoring data.
   *
   * @param string $table_name
   *   The table to batch process.
   * @param array $data
   *   The array of data to insert into the table.
   * @param int $total_rows
   *   The total number of rows in the table being processed.
   * @param int $chunk_total
   *   The total number of chunks for the table being processed.
   * @param int $chunk_index
   *   The index of the current chunk being processed (1-based).
   * @param array|\ArrayAccess $context
   *   The context array.
   */
  public static function restoreDataBatchCallback(string $table_name, array $data, int $total_rows, int $chunk_total, int $chunk_index, mixed &$context): void {
    // Initialize 'progress' key if it does not exist.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }

    if (!isset($context['sandbox']['table'])) {
      $context['sandbox']['table'] = $table_name;
    }

    // Initialize 'processed_rows' key for the table if it does not exist.
    if (!isset($context['results'][$table_name]['processed_rows'])) {
      $context['results'][$table_name]['processed_rows'] = 0;
    }

    $fields = array_keys($data[0]);
    $insert_query = \Drupal::database()->insert($table_name)->fields($fields);

    // Process a batch of rows for the current chunk.
    $batch_size = 50;
    $start = $context['sandbox']['progress'];
    $total_rows_chunk = count($data);
    $rows_to_process = array_slice($data, $start, $batch_size);

    // Use batch insert to optimize insertion.
    foreach ($rows_to_process as $row) {
      $insert_query->values(array_values($row));
      $context['sandbox']['progress']++;
      $context['results'][$table_name]['processed_rows']++;
    }

    // Insert multiple rows in a single query using batch insert.
    $insert_query->execute();

    // Update the progress message to include the table information.
    $context['message'] = t('Processed @current out of @total. (Table: @table, Chunk: @chunk/@total_chunks)', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $total_rows,
      '@table' => $context['sandbox']['table'],
      '@chunk' => $chunk_index,
      '@total_chunks' => $chunk_total,
    ]);

    // Calculate the overall progress for the batch process.
    if ($total_rows_chunk > 0) {
      $context['finished'] = $context['sandbox']['progress'] / $total_rows_chunk;
    }
    else {
      unset($context['sandbox']['table']);
      $context['finished'] = 1;
    }

  }

  /**
   * The batch processing finished callback function.
   *
   * @param bool $success
   *   The end result status of the batching.
   * @param array $results
   *   The results array of the batching.
   */
  public static function restoreDataBatchFinished(bool $success, array $results): void {
    if ($success) {
      foreach ($results as $table_name => $table_results) {
        if (isset($table_results['processed_rows'])) {
          $total_rows = $table_results['processed_rows'];
          $message = t('Updated @total_rows rows in @table', [
            '@table' => $table_name,
            '@total_rows' => $total_rows,
          ]);
          \Drupal::messenger()->addMessage($message, 'status');
        }
      }
    }
    else {
      \Drupal::messenger()->addMessage(\Drupal::translation()->translate('Data restoration failed. Please check the logs for errors.'), 'error');
    }
  }

}
