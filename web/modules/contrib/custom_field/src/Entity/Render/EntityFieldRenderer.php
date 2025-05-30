<?php

declare(strict_types=1);

namespace Drupal\custom_field\Entity\Render;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\custom_field\Plugin\views\field\CustomField;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Entity\Render\RendererBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Renders entity fields.
 *
 * This is used to build render arrays for all entity field values of a view
 * result set sharing the same relationship. An entity translation renderer is
 * used internally to handle entity language properly.
 */
final class EntityFieldRenderer extends RendererBase {

  use EntityTranslationRenderTrait;
  use DependencySerializationTrait;

  /**
   * Constructs an EntityFieldRenderer object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view whose fields are being rendered.
   * @param string|null $relationship
   *   The relationship to be handled.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(
    ViewExecutable $view,
    private readonly ?string $relationship,
    LanguageManagerInterface $language_manager,
    EntityTypeInterface $entity_type,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityRepositoryInterface $entityRepository,
  ) {
    parent::__construct($view, $language_manager, $entity_type);

    // Initialize, because it is not set as nullable in the parent class.
    // This allows for the proper is not empty check in render() below.
    $this->build = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return $this->getEntityTranslationRenderer()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): string {
    return $this->entityType->id();
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

  /**
   * Get the entity repository.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  protected function getEntityRepository(): EntityRepositoryInterface {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager(): LanguageManagerInterface {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView(): ViewExecutable {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL): void {
    $this->getEntityTranslationRenderer()->query($query, $relationship);
  }

  /**
   * Renders entity field data.
   *
   * @param \Drupal\views\ResultRow $row
   *   A single row of the query result.
   * @param \Drupal\custom_field\Plugin\views\field\CustomField|null $field
   *   (optional) A field to be rendered.
   *
   * @return array<int, mixed>
   *   A renderable array for the entity data contained in the result row.
   */
  public function render(ResultRow $row, ?CustomField $field = NULL): array {
    // The method is called for each field in each result row. In order to
    // leverage multiple-entity building of formatter output, we build the
    // render arrays for all fields in all rows on the first call.
    if ($this->build !== []) {
      $this->build = $this->buildFields($this->view->result);
    }

    if (!is_null($field)) {
      $field_id = $field->options['id'];
      // Pick the render array for the row / field we are being asked to render,
      // and remove it from $this->build to free memory as we progress.
      if (isset($this->build[$row->index][$field_id])) {
        $build = $this->build[$row->index][$field_id];
        unset($this->build[$row->index][$field_id]);
      }
      elseif (isset($this->build[$row->index])) {
        // In the uncommon case where a field gets rendered several times
        // (typically through direct Views API calls), the pre-computed render
        // array was removed by the unset() above. We have to manually rebuild
        // the render array for the row.
        $build = $this->buildFields([$row])[$row->index][$field_id];
      }
      else {
        // In case the relationship is optional, there might not be any fields
        // to render for this row.
        $build = [];
      }
    }
    // Same logic as above, in the case where we are being called for a whole
    // row.
    elseif (isset($this->build[$row->index])) {
      $build = $this->build[$row->index];
      unset($this->build[$row->index]);
    }
    else {
      $build = $this->buildFields([$row])[$row->index];
    }

    return $build;
  }

  /**
   * Builds the render arrays for all fields of all result rows.
   *
   * The output is built using EntityViewDisplay objects to leverage
   * multiple-entity building and ensure a common code path with regular entity
   * view.
   * - Each relationship is handled by a separate EntityFieldRenderer instance,
   *   since it operates on its own set of entities. This also ensures different
   *   entity types are handled separately, as they imply different
   *   relationships.
   * - Within each relationship, the fields to render are arranged in unique
   *   sets containing each field at most once (an EntityViewDisplay can
   *   only process a field once with given display options, but a View can
   *   contain the same field several times with different display options).
   * - For each set of fields, entities are processed by bundle, so that
   *   formatters can operate on the proper field definition for the bundle.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   An array of all ResultRow objects returned from the query.
   *
   * @return array<int, mixed>
   *   A renderable array for the fields handled by this renderer.
   *
   * @see \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected function buildFields(array $values): array {
    $build = [];

    if ($values !== [] && ($field_ids = $this->getRenderableFieldIds())) {
      $entity_type_id = $this->getEntityTypeId();

      // Collect the entities for the relationship, fetch the right translation,
      // and group by bundle. For each result row, the corresponding entity can
      // be obtained from any of the fields handlers, so we arbitrarily use the
      // first one.
      $entities_by_bundles = [];
      $field = $this->view->field[current($field_ids)];
      foreach ($values as $result_row) {
        if ($entity = $field->getEntity($result_row)) {
          $relationship = $field->options['relationship'] ?? 'none';
          $entities_by_bundles[$entity->bundle()][$result_row->index] = $this->getEntityTranslationByRelationship($entity, $result_row, $relationship);
        }
      }

      // Determine unique sets of fields that can be processed by the same
      // display. Fields that appear several times in the View open additional
      // "overflow" displays.
      $display_sets = [];
      foreach ($field_ids as $field_id) {
        $field = $this->view->field[$field_id];
        $property_name = $field->definition['property'];
        $index = 0;
        while (isset($display_sets[$index]['field_names'][$property_name])) {
          $index++;
        }
        $display_sets[$index]['field_names'][$property_name] = $field;
        $display_sets[$index]['field_ids'][$field_id] = $field;
      }

      // For each set of fields, build the output by bundle.
      foreach ($display_sets as $display_fields) {
        foreach ($entities_by_bundles as $bundle => $bundle_entities) {
          /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $bundle_entities */
          // Create the display, and configure the field display options.
          $display = EntityViewDisplay::create([
            'targetEntityType' => $entity_type_id,
            'bundle' => $bundle,
            'status' => TRUE,
          ]);
          foreach ($display_fields['field_ids'] as $field) {
            $display->setComponent($field->definition['field_name'], [
              'type' => $field->options['type'],
              'settings' => $field->options['settings'],
            ]);
          }
          // Let the display build the render array for the entities.
          $display_build = $display->buildMultiple($bundle_entities);
          // Collect the field render arrays and index them using our internal
          // row indexes and field IDs.
          foreach ($display_build as $row_index => $entity_build) {
            foreach ($display_fields['field_ids'] as $field_id => $field) {
              $build[$row_index][$field_id] = !empty($entity_build[$field->definition['field_name']]) ? $entity_build[$field->definition['field_name']] : [];
            }
          }
        }
      }
    }

    return $build;
  }

  /**
   * Returns a list of names of entity fields to be rendered.
   *
   * @return string[]
   *   An associative array of views fields.
   */
  protected function getRenderableFieldIds(): array {
    $field_ids = [];
    foreach ($this->view->field as $field_id => $field) {
      if ($field instanceof CustomField && $field->relationship == $this->relationship) {
        $field_ids[] = $field_id;
      }
    }
    return $field_ids;
  }

}
