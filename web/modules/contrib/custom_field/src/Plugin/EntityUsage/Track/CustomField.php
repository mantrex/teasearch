<?php

namespace Drupal\custom_field\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\entity_usage\EntityUsageTrackMultipleLoadInterface;

/**
 * Tracks usage of entities related in custom fields.
 *
 * @EntityUsageTrack(
 *   id = "custom_field",
 *   label = @Translation("Custom Field"),
 *   description = @Translation("Tracks relationships created with 'Custom Field' sub-fields (entity_reference, image, file, viewfield)."),
 *   field_types = {"custom"},
 *   source_entity_class = "Drupal\Core\Entity\FieldableEntityInterface",
 * )
 */
class CustomField extends EntityUsageTrackBase implements EntityUsageTrackMultipleLoadInterface {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item): array {
    return $this->doGetTargetEntities($item->getParent(), $item);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntitiesFromField(FieldItemListInterface $field): array {
    return $this->doGetTargetEntities($field);
  }

  /**
   * Retrieve the target entity(ies) from a field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to get the target entity(ies) from.
   * @param \Drupal\Core\Field\FieldItemInterface|null $field_item
   *   (optional) The field item to get the target entity(ies) from.
   *
   * @return string[]
   *   An indexed array of strings where each target entity type and ID are
   *   concatenated with a "|" character. Will return an empty array if no
   *   target entity could be retrieved from the received field item value.
   */
  private function doGetTargetEntities(FieldItemListInterface $field, ?FieldItemInterface $field_item = NULL): array {
    $entity_ids = [];
    if ($field_item instanceof FieldItemInterface) {
      $iterable = [$field_item];
    }
    else {
      $iterable = &$field;
    }

    $columns = $field->getSetting('columns');
    $reference_subfields = [];
    $referenceable_types = [
      'entity_reference',
      'viewfield',
      'file',
      'image',
    ];
    foreach ($columns as $name => $column) {
      if (!isset($column['target_type'])) {
        continue;
      }
      $target_type = $column['target_type'];
      if (in_array($column['type'], $referenceable_types) && $this->isEntityTypeTracked($target_type)) {
        $reference_subfields[$name] = $column;
      }
    }
    if (empty($reference_subfields)) {
      return [];
    }
    foreach ($iterable as $item) {
      /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
      foreach ($reference_subfields as $name => $subfield) {
        $target_type = $subfield['target_type'];
        $target_id = $item->get($name)->getValue();
        if (!empty($target_id)) {
          $entity_ids[$target_type][] = $target_id;
        }
      }
    }

    $return = [];
    foreach ($entity_ids as $target_type_id => $entity_id_values) {
      $return = array_merge($return, $this->checkAndPrepareEntityIds($target_type_id, $entity_id_values, 'id'));
    }

    return $return;
  }

}
