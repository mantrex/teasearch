<?php

/**
 * @file
 * Provide Views data for the Custom field module.
 *
 * @ingroup views_module_handlers
 */

declare(strict_types=1);

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 */
function custom_field_field_views_data(FieldStorageConfigInterface $field_storage): array {
  $data = views_field_default_views_data($field_storage);

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $entity_type = $entity_type_manager->getDefinition($entity_type_id);
  $entity_type_storage = $entity_type_manager->getStorage($entity_type_id);
  assert($entity_type_storage instanceof SqlEntityStorageInterface);
  $table_mapping = $entity_type_storage->getTableMapping();
  assert($table_mapping instanceof DefaultTableMapping);

  $custom_field_type_manager = \Drupal::service('plugin.manager.custom_field_type');
  assert($custom_field_type_manager instanceof CustomFieldTypeManagerInterface);
  $custom_field_formatter_manager = \Drupal::service('plugin.manager.custom_field_formatter');
  assert($custom_field_formatter_manager instanceof CustomFieldFormatterManagerInterface);

  $field_name = $field_storage->getName();
  $custom_fields = $custom_field_type_manager->getCustomFieldItems($field_storage->getSettings());
  $types_with_relationships = [
    'file',
    'image',
    'entity_reference',
  ];

  foreach ($data as $table_name => $table_data) {
    foreach ($custom_fields as $name => $custom_field) {

      $default_formatter = $custom_field->getDefaultFormatter();
      $instance_options = $custom_field_formatter_manager->createOptionsForInstance($custom_field, $default_formatter, [], 'default');
      $instance = $custom_field_formatter_manager->getInstance($instance_options);
      assert($instance instanceof CustomFieldFormatterInterface);

      // Build the list of additional fields to add to queries.
      $subfield = $field_name . '_' . $name;
      $add_fields = ['delta', 'langcode', 'bundle'];
      $extra_columns = [];
      if ($custom_field->getDataType() === 'image') {
        $image_cols = ['alt', 'title', 'width', 'height'];
        foreach ($image_cols as $col) {
          $add_fields[] = $subfield . '__' . $col;
          $extra_columns[$col] = t('@column', ['@column' => $col]);
        }
      }
      elseif ($custom_field->getDataType() === 'link') {
        $add_fields[] = $subfield . '__title';
        $extra_columns['title'] = t('title');
      }
      $field = [
        'id' => 'custom_field',
        'field_name' => $field_name,
        'property' => $name,
        'entity_type' => $entity_type_id,
        'default_formatter' => $default_formatter,
        'default_formatter_settings' => $instance::defaultSettings(),
        'additional fields' => $add_fields,
        'extra columns' => $extra_columns,
      ];
      $filter = [
        'id' => 'standard',
      ];
      $sort = [
        'id' => 'standard',
      ];
      $argument = [
        'id' => 'standard',
      ];

      $type = $custom_field->getDataType();
      switch ($type) {
        case 'boolean':
          $filter['id'] = 'boolean';
          break;

        case 'string':
        case 'string_long':
        case 'telephone':
        case 'email':
        case 'color':
          $filter['id'] = 'string';
          $argument['id'] = 'string';
          break;

        case 'datetime':
          $datetime_type = $custom_field->getDatetimeType();
          $argument = [
            'id' => 'custom_field_datetime',
            'datetime_type' => $custom_field->getDatetimeType(),
          ];
          $arguments = [
            // Argument type => help text.
            'year' => t('Date in the form of YYYY.'),
            'month' => t('Date in the form of MM (01 - 12).'),
            'day' => t('Date in the form of DD (01 - 31).'),
            'week' => t('Date in the form of WW (01 - 53).'),
            'year_month' => t('Date in the form of YYYYMM.'),
            'full_date' => t('Date in the form of CCYYMMDD.'),
          ];
          $filter['id'] = 'custom_field_datetime';
          $filter['datetime_type'] = $datetime_type;
          foreach ($arguments as $argument_type => $help_text) {
            $data[$table_name][$subfield . '_' . $argument_type] = [
              'title' => t('@label@column (@argument)', [
                '@label' => $field_storage->getLabel(),
                '@column' => ':' . $name,
                '@argument' => $argument_type,
              ]),
              'help' => $help_text,
              'argument' => [
                'field' => $subfield,
                'id' => 'custom_field_datetime_' . $argument_type,
                'entity_type' => $entity_type_id,
                'field_name' => $field_name,
                'datetime_type' => $datetime_type,
              ],
              'group' => $entity_type->getLabel(),
            ];
          }
          $sort = [
            'id' => 'custom_field_datetime',
            'datetime_type' => $datetime_type,
          ];
          break;

        case 'integer':
        case 'float':
        case 'decimal':
        case 'file':
        case 'image':
          $filter['id'] = 'numeric';
          $argument['id'] = 'numeric';
          break;

        case 'map':
          $filter = [];
          $sort = [];
          $argument = [];
          break;

        case 'entity_reference':
          $data_type = $field_storage->getPropertyDefinition($name)->getSetting('data_type');
          $id_type = $data_type === 'integer' ? 'numeric' : 'string';
          $filter['id'] = $id_type;
          $sort['id'] = $id_type;
          $argument['id'] = $id_type;
          break;
      }
      $data[$table_name][$subfield]['field'] = $field;
      $data[$table_name][$subfield]['filter'] = $filter;
      $data[$table_name][$subfield]['sort'] = $sort;
      $data[$table_name][$subfield]['argument'] = $argument;

      // Build views relationships.
      if (in_array($type, $types_with_relationships)) {
        $target_entity_type_id = $custom_field->getTargetType();
        $target_entity_type = $entity_type_manager->getDefinition($target_entity_type_id);
        $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
        if ($target_entity_type instanceof ContentEntityTypeInterface) {
          // Provide a relationship for the entity type with the entity
          // reference field.
          $args = [
            '@label' => $target_entity_type->getLabel(),
            '@field_name' => $field_name . '_' . $name,
          ];

          $data[$table_name][$subfield]['relationship'] = [
            'title' => t('@label referenced from @field_name', $args),
            'label' => t('@field_name: @label', $args),
            'group' => $entity_type->getLabel(),
            'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
            'id' => 'standard',
            'base' => $target_base_table,
            'entity type' => $target_entity_type_id,
            'base field' => $target_entity_type->getKey('id'),
            'relationship field' => $subfield,
          ];

          // Provide a reverse relationship for the entity type that is
          // referenced by the field.
          $args['@entity'] = $entity_type->getLabel();
          $args['@label'] = $target_entity_type->getSingularLabel();
          $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $subfield;
          $data[$target_base_table][$pseudo_field_name]['relationship'] = [
            'title' => t('@entity using @field_name', $args),
            'label' => t('@field_name', ['@field_name' => $field_name]),
            'group' => $target_entity_type->getLabel(),
            'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
            'id' => 'entity_reverse',
            'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
            'entity_type' => $entity_type_id,
            'base field' => $entity_type->getKey('id'),
            'field_name' => $field_name,
            'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
            'field field' => $subfield,
            'join_extra' => [
              [
                'field' => 'deleted',
                'value' => 0,
                'numeric' => TRUE,
              ],
            ],
          ];
        }
        // Provide an argument plugin that has a meaningful titleQuery()
        // implementation getting the entity label.
        $data[$table_name][$subfield]['argument']['id'] = 'entity_target_id';
        $data[$table_name][$subfield]['argument']['target_entity_type_id'] = $target_entity_type_id;
      }
    }
  }

  return $data;
}
