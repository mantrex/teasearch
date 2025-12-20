<?php

namespace Drupal\teasearch_filter\Helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Helper class for dynamic search across all fields.
 */
class SearchHelper
{

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a SearchHelper object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
    $this->logger = $logger_factory->get('teasearch_filter');
  }

  /**
   * Get all searchable fields for a given entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type (node, user, etc).
   * @param string $bundle
   *   The bundle (content type).
   *
   * @return array
   *   Array with keys: 'text', 'taxonomy', 'entity_reference', 'numeric'.
   */
  public function getAllSearchableFields($entity_type, $bundle)
  {
    $searchable_fields = [
      'text' => [],
      'taxonomy' => [],
      'entity_reference' => [],
      'numeric' => [],
    ];

    try {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

      foreach ($field_definitions as $field_name => $field_definition) {
        // Skip base fields that are not searchable
        if ($this->shouldSkipField($field_name)) {
          continue;
        }

        $field_type = $field_definition->getType();

        // Categorize by field type
        if ($this->isTextField($field_type)) {
          $searchable_fields['text'][] = $field_name;
        } elseif ($field_type === 'entity_reference') {
          // Check if it's taxonomy or generic entity reference
          $target_type = $field_definition->getSetting('target_type');
          if ($target_type === 'taxonomy_term') {
            $searchable_fields['taxonomy'][] = $field_name;
          } else {
            $searchable_fields['entity_reference'][] = $field_name;
          }
        } elseif ($this->isNumericField($field_type)) {
          $searchable_fields['numeric'][] = $field_name;
        }
      }
    } catch (\Exception $e) {
      $this->logger->error('Error getting searchable fields: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $searchable_fields;
  }

  /**
   * Build a dynamic search query for an entity type.
   *
   * @param string $entity_type
   *   The entity type (node, user).
   * @param string $bundle
   *   The bundle/content type.
   * @param string $keyword
   *   The search keyword.
   * @param array $conditions
   *   Additional WHERE conditions (optional).
   *
   * @return array
   *   Array of entity IDs.
   */
  public function buildDynamicSearchQuery($entity_type, $bundle, $keyword, array $conditions = [])
  {
    if (empty($keyword)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);

    // Add bundle condition
    if ($entity_type === 'node') {
      $query->condition('type', $bundle);
    }

    // Apply additional WHERE conditions
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    // Get searchable fields
    $searchable_fields = $this->getAllSearchableFields($entity_type, $bundle);

    // Create OR group for all searchable conditions
    $or_group = $query->orConditionGroup();

    // Add text field conditions
    $this->addTextFieldConditions($or_group, $searchable_fields['text'], $keyword);

    // Add taxonomy field conditions
    $this->addTaxonomyFieldConditions($or_group, $searchable_fields['taxonomy'], $keyword, $entity_type);

    // Add entity reference conditions
    $this->addEntityReferenceConditions($or_group, $searchable_fields['entity_reference'], $keyword, $entity_type);

    // Add numeric field conditions (exact match)
    $this->addNumericFieldConditions($or_group, $searchable_fields['numeric'], $keyword);

    $query->condition($or_group);

    // Execute query
    try {
      $ids = $query->sort('created', 'DESC')
        ->range(0, 1000) // Safety limit
        ->execute();

      return $ids;
    } catch (\Exception $e) {
      $this->logger->error('Search query error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Add text field conditions to query group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $group
   *   The condition group.
   * @param array $fields
   *   Array of text field names.
   * @param string $keyword
   *   The search keyword.
   */
  protected function addTextFieldConditions($group, array $fields, $keyword)
  {
    foreach ($fields as $field_name) {
      try {
        // Handle both simple string fields and complex text fields
        if (strpos($field_name, 'field_') === 0 || $field_name === 'body') {
          // Custom fields - use .value
          $group->condition("{$field_name}.value", "%{$keyword}%", 'LIKE');
        } elseif (in_array($field_name, ['title', 'name'])) {
          // Base searchable fields
          $group->condition($field_name, "%{$keyword}%", 'LIKE');
        }
        // Skip other base fields that are not searchable
      } catch (\Exception $e) {
        // Log but continue - don't let one bad field break the whole search
        $this->logger->warning('Error searching in field @field: @message', [
          '@field' => $field_name,
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Add taxonomy field conditions to query group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $group
   *   The condition group.
   * @param array $fields
   *   Array of taxonomy field names.
   * @param string $keyword
   *   The search keyword.
   * @param string $entity_type
   *   The entity type.
   */
  protected function addTaxonomyFieldConditions($group, array $fields, $keyword, $entity_type)
  {
    if (empty($fields)) {
      return;
    }

    // Get taxonomy term IDs that match the keyword
    $matching_term_ids = $this->searchTaxonomyTerms($keyword);

    if (empty($matching_term_ids)) {
      return;
    }

    // Add condition for each taxonomy field
    foreach ($fields as $field_name) {
      $group->condition("{$field_name}.target_id", $matching_term_ids, 'IN');
    }
  }

  /**
   * Add entity reference field conditions to query group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $group
   *   The condition group.
   * @param array $fields
   *   Array of entity reference field names.
   * @param string $keyword
   *   The search keyword.
   * @param string $entity_type
   *   The entity type.
   */
  protected function addEntityReferenceConditions($group, array $fields, $keyword, $entity_type)
  {
    if (empty($fields)) {
      return;
    }

    // For each entity reference field, search in referenced entities
    foreach ($fields as $field_name) {
      $matching_entity_ids = $this->searchReferencedEntities($field_name, $keyword, $entity_type);

      if (!empty($matching_entity_ids)) {
        $group->condition("{$field_name}.target_id", $matching_entity_ids, 'IN');
      }
    }
  }

  /**
   * Add numeric field conditions to query group.
   *
   * @param \Drupal\Core\Entity\Query\ConditionInterface $group
   *   The condition group.
   * @param array $fields
   *   Array of numeric field names.
   * @param string $keyword
   *   The search keyword.
   */
  protected function addNumericFieldConditions($group, array $fields, $keyword)
  {
    // Only search if keyword is numeric
    if (!is_numeric($keyword)) {
      return;
    }

    foreach ($fields as $field_name) {
      $group->condition("{$field_name}.value", $keyword);
    }
  }

  /**
   * Search taxonomy terms by name.
   *
   * @param string $keyword
   *   The search keyword.
   *
   * @return array
   *   Array of term IDs.
   */
  protected function searchTaxonomyTerms($keyword)
  {
    try {
      $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
        ->accessCheck(TRUE)
        ->condition('name', "%{$keyword}%", 'LIKE')
        ->range(0, 100); // Limit results

      return $query->execute();
    } catch (\Exception $e) {
      $this->logger->error('Taxonomy search error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Search in referenced entities.
   *
   * @param string $field_name
   *   The entity reference field name.
   * @param string $keyword
   *   The search keyword.
   * @param string $source_entity_type
   *   The source entity type.
   *
   * @return array
   *   Array of matching entity IDs.
   */
  protected function searchReferencedEntities($field_name, $keyword, $source_entity_type)
  {
    // This is a simplified version - in reality you'd need to determine
    // the target entity type from field configuration
    try {
      // Search in nodes (most common case)
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('title', "%{$keyword}%", 'LIKE')
        ->range(0, 50);

      return $query->execute();
    } catch (\Exception $e) {
      $this->logger->error('Entity reference search error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Check if field should be skipped.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if should skip.
   */
  protected function shouldSkipField($field_name)
  {
    $skip_fields = [
      'uuid',
      'vid',
      'langcode',
      'revision_timestamp',
      'revision_uid',
      'revision_log',
      'status',
      'created',
      'changed',
      'promote',
      'sticky',
      'default_langcode',
      'revision_default',
      'revision_translation_affected',
      'content_translation_source',
      'content_translation_outdated',
      'content_translation_uid',
      'content_translation_created',
      'content_translation_changed',
      'menu_link',  // Menu link reference - causes query errors
      'path',       // Path alias
      'uid',        // User ID reference
      'type',       // Bundle field
      'nid',        // Node ID
    ];

    return in_array($field_name, $skip_fields);
  }

  /**
   * Check if field type is text-based.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return bool
   *   TRUE if text field.
   */
  protected function isTextField($field_type)
  {
    $text_types = [
      'string',
      'string_long',
      'text',
      'text_long',
      'text_with_summary',
    ];

    return in_array($field_type, $text_types);
  }

  /**
   * Check if field type is numeric.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return bool
   *   TRUE if numeric field.
   */
  protected function isNumericField($field_type)
  {
    $numeric_types = [
      'integer',
      'decimal',
      'float',
      'list_integer',
      'list_float',
    ];

    return in_array($field_type, $numeric_types);
  }
}