<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_view",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Render the referenced entity."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceEntityFormatter extends EntityReferenceFormatterBase {

  /**
   * The number of times this formatter allows rendering the same entity.
   *
   * @var int
   */
  const RECURSIVE_RENDER_LIMIT = 20;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * An array of counters for the recursive rendering protection.
   *
   * Each counter takes into account all the relevant information about the
   * field and the referenced entity that is being rendered.
   *
   * @var array
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter::viewElements()
   */
  protected static $recursiveRenderDepth = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityDisplayRepository = $container->get('entity_display.repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'view_mode' => 'default',
      'link' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $target_type = $this->customFieldDefinition->getTargetType();
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($target_type),
      '#title' => $this->t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
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

    $view_mode = $this->getSetting('view_mode');
    $view_builder = $this->entityTypeManager->getViewBuilder($value->getEntityTypeId());

    return $view_builder->view($value, $view_mode, $value->language()->getId());
  }

}
