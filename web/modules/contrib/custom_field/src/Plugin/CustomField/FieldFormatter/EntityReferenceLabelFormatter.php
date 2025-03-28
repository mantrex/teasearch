<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entity."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceLabelFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'link' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements['link'] = [
      '#title' => $this->t('Link label to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    return $elements;
  }

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

    $label = $value->label();
    $output_as_link = $this->getSetting('link');

    // If the link is to be displayed and the entity has a uri, display a
    // link.
    if ($output_as_link && !$value->isNew()) {
      try {
        $uri = $value->toUrl();
      }
      catch (UndefinedLinkTemplateException $e) {
        // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
        // and it means that the entity type doesn't have a link template nor
        // a valid "uri_callback", so don't bother trying to output a link for
        // the rest of the referenced entities.
        $output_as_link = FALSE;
      }
    }

    if ($output_as_link && isset($uri) && !$value->isNew()) {
      $build = [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $uri,
        '#options' => $uri->getOptions(),
        '#cache' => [
          'tags' => $value->getCacheTags(),
        ],
        '#entity' => $value,
      ];
    }
    else {
      $build = [
        '#plain_text' => $label,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
