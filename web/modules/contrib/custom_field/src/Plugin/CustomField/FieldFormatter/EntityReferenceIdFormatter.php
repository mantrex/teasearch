<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_id",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entity."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceIdFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {

    if (!$value instanceof EntityInterface) {
      return NULL;
    }

    $access = $this->checkAccess($value);

    if (!$access->isAllowed()) {
      return NULL;
    }

    return [
      '#plain_text' => $value->id(),
      '#cache' => [
        'tags' => $value->getCacheTags(),
      ],
    ];
  }

}
