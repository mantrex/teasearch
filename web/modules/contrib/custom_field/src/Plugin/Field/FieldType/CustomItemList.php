<?php

namespace Drupal\custom_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;

/**
 * Represents a configurable entity custom field.
 */
class CustomItemList extends FieldItemList implements CustomFieldItemListInterface {

  /**
   * {@inheritdoc}
   */
  public function referencedEntities(): array {
    if ($this->isEmpty()) {
      return [];
    }
    // Collect the IDs of existing entities to load.
    $ids = [];
    $entities = [];
    $settings = $this->getSettings();
    $custom_fields = $this->getCustomFieldManager()->getCustomFieldItems($settings);
    foreach ($this->list as $item) {
      foreach ($custom_fields as $custom_field) {
        if ($target_type = $custom_field->getTargetType()) {
          $id = $item->{$custom_field->getName()};
          if (!empty($id)) {
            $ids[$target_type][] = $id;
          }
        }
      }
    }
    if ($ids) {
      foreach ($ids as $target_type => $entity_ids) {
        $target_entities[$target_type] = \Drupal::entityTypeManager()->getStorage($target_type)->loadMultiple($entity_ids);
      }
    }
    foreach ($this->list as $delta => $item) {
      foreach ($custom_fields as $custom_field) {
        if ($target_type = $custom_field->getTargetType()) {
          $id = $item->{$custom_field->getName()};
          if (!empty($id) && isset($target_entities[$target_type][$id])) {
            $entities[$delta][$custom_field->getName()] = $target_entities[$target_type][$id];
          }
        }
      }
    }

    return $entities;
  }

  /**
   * Gets all files referenced by this field.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array of all file objects.
   */
  public function referencedFiles(): array {
    if ($this->isEmpty()) {
      return [];
    }
    // Collect the IDs of existing files to load.
    $ids = [];
    $settings = $this->getSettings();
    $custom_fields = $this->getCustomFieldManager()->getCustomFieldItems($settings);
    foreach ($this->list as $item) {
      foreach ($custom_fields as $custom_field) {
        $data_type = $custom_field->getPluginId();
        if (in_array($data_type, ['file', 'image'])) {
          $id = $item->get($custom_field->getName())->getValue();
          if (!empty($id)) {
            $ids[] = $id;
          }
        }
      }
    }
    if (!empty($ids)) {
      $files = \Drupal::entityTypeManager()->getStorage('file')->loadMultiple($ids);
      return $files;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $entity = $this->getEntity();

    if (!$update) {
      // Add a new usage for newly uploaded files.
      /** @var \Drupal\File\FileInterface $file */
      foreach ($this->referencedFiles() as $file) {
        \Drupal::service('file.usage')->add($file, 'custom_field', $entity->getEntityTypeId(), $entity->id());
      }
    }
    else {
      // Get current target file entities and file IDs.
      $files = $this->referencedFiles();
      $ids = [];

      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $ids[] = $file->id();
      }

      // On new revisions, all files are considered to be a new usage and no
      // deletion of previous file usages are necessary.
      if (!empty($entity->original) && $entity->getRevisionId() != $entity->original->getRevisionId()) {
        foreach ($files as $file) {
          \Drupal::service('file.usage')->add($file, 'custom_field', $entity->getEntityTypeId(), $entity->id());
        }
        return;
      }

      // Get the file IDs attached to the field before this update.
      $field_name = $this->getFieldDefinition()->getName();
      $original_ids = [];
      $langcode = $this->getLangcode();
      $original = $entity->original;
      if ($original->hasTranslation($langcode)) {
        $original_items = $original->getTranslation($langcode)->{$field_name};
        $settings = $this->getSettings();
        $custom_fields = $this->getCustomFieldManager()->getCustomFieldItems($settings);
        foreach ($original_items as $item) {
          foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->getPluginId(), ['file', 'image'])) {
              $original_ids[] = $item->{$custom_field->getName()};
            }
          }
        }
      }

      // Decrement file usage by 1 for files that were removed from the field.
      $removed_ids = array_filter(array_diff($original_ids, $ids));
      $removed_files = \Drupal::entityTypeManager()->getStorage('file')->loadMultiple($removed_ids);
      foreach ($removed_files as $file) {
        \Drupal::service('file.usage')->delete($file, 'custom_field', $entity->getEntityTypeId(), $entity->id());
      }

      // Add new usage entries for newly added files.
      foreach ($files as $file) {
        if (!in_array($file->id(), $original_ids)) {
          \Drupal::service('file.usage')->add($file, 'custom_field', $entity->getEntityTypeId(), $entity->id());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $entity = $this->getEntity();

    // If a translation is deleted only decrement the file usage by one. If the
    // default translation is deleted remove all file usages within this entity.
    $count = $entity->isDefaultTranslation() ? 0 : 1;
    foreach ($this->referencedFiles() as $file) {
      \Drupal::service('file.usage')->delete($file, 'custom_field', $entity->getEntityTypeId(), $entity->id(), $count);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision() {
    parent::deleteRevision();
    $entity = $this->getEntity();

    // Decrement the file usage by 1.
    foreach ($this->referencedFiles() as $file) {
      \Drupal::service('file.usage')->delete($file, 'custom_field', $entity->getEntityTypeId(), $entity->id());
    }
  }

  /**
   * Get the custom field_type manager plugin.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   *   Returns the 'custom' field type plugin manager.
   */
  public function getCustomFieldManager(): CustomFieldTypeManagerInterface {
    return \Drupal::service('plugin.manager.custom_field_type');
  }

}
