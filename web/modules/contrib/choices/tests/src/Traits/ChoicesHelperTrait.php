<?php

namespace Drupal\Tests\choices\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * A helper trait for the choices module.
 */
trait ChoicesHelperTrait {

  /**
   * Create a select field on the article content type.
   */
  public function createSelectOnArticle(string $fieldName, string $fieldType, int $cardinality, array $allowedValues, string $widgetType = 'options_select') {
    FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => $fieldType,
      'cardinality' => $cardinality,
      'settings' => [
        'allowed_values' => $allowedValues,
      ],
    ])->save();
    // Create field instance:
    FieldConfig::create([
      'label' => $fieldName,
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [],
    ])->save();
    // Set form display:
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => $widgetType,
      ])
      ->save();
    // Set view display:
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => 'list_default',
      ])
      ->save();
  }

}
