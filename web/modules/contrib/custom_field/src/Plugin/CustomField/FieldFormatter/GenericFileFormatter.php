<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'file_default' formatter.
 *
 * @FieldFormatter(
 *   id = "file_default",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class GenericFileFormatter extends EntityReferenceFormatterBase {

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
      '#theme' => 'file_link',
      '#file' => $value,
      '#description' => NULL,
      '#cache' => [
        'tags' => $value->getCacheTags(),
      ],
    ];
  }

}
