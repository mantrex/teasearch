<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'entity_reference_entity_id' formatter.
 */
#[FieldFormatter(
  id: 'entity_reference_entity_id',
  label: new TranslatableMarkup('Entity ID'),
  description: new TranslatableMarkup('Display the ID of the referenced entity.'),
  field_types: [
    'entity_reference',
  ],
)]
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
