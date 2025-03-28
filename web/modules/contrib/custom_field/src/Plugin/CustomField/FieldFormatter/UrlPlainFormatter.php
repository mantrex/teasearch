<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\file\FileInterface;

/**
 * Plugin implementation of the 'file_url_plain' formatter.
 *
 * @FieldFormatter(
 *   id = "file_url_plain",
 *   label = @Translation("URL to file"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class UrlPlainFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {

    if (!$value instanceof FileInterface) {
      return NULL;
    }

    $access = $this->checkAccess($value);
    if (!$access->isAllowed()) {
      return NULL;
    }

    $build = [
      '#markup' => $value->createFileUrl(),
      '#cache' => [
        'tags' => $value->getCacheTags(),
      ],
    ];

    return $build;
  }

}
