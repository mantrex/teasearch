<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference' feeds type.
 *
 * @CustomFieldFeedsType(
 *   id = "entity_reference",
 *   label = @Translation("Entity reference"),
 *   mark_unique = TRUE,
 * )
 */
class EntityReferenceTarget extends BaseTarget {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Feeds entity finder service.
   *
   * @var \Drupal\feeds\EntityFinderInterface
   */
  protected $entityFinder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityFinder = $container->get('feeds.entity_finder');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = [
      'reference_by' => $this->getLabelKey(),
      'autocreate' => FALSE,
      'autocreate_bundle' => FALSE,
    ];
    if (array_key_exists('feeds_item', $this->getPotentialFields())) {
      $config['feeds_item'] = FALSE;
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(int $delta, array $configuration) {
    $target_type = $this->configuration['target_type'];
    // Only reference content entities. Configuration entities will need
    // custom targets.
    $form = [];
    $options = $this->getPotentialFields();
    if ($this->entityTypeManager->getDefinition($target_type)->entityClassImplements(ContentEntityInterface::class)) {
      $name = $this->configuration['name'];
      $bundles = $this->getBundles();
      $form['reference_by'] = [
        '#type' => 'select',
        '#title' => $this->t('Reference by'),
        '#options' => $options,
        '#default_value' => $configuration['reference_by'],
      ];

      $feed_item_options = $this->getFeedsItemOptions();

      $form['feeds_item'] = [
        '#type' => 'select',
        '#title' => $this->t('Feed item'),
        '#options' => $feed_item_options,
        '#default_value' => $configuration['feeds_item'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name="mappings[' . $delta . '][settings][' . $name . '][reference_by]"]' => [
              'value' => 'feeds_item',
            ],
          ],
        ],
      ];
      $form['autocreate'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Autocreate entity'),
        '#default_value' => $configuration['autocreate'],
        '#states' => [
          'visible' => [
            ':input[name="mappings[' . $delta . '][settings][' . $name . '][reference_by]"]' => [
              'value' => $this->getLabelKey(),
            ],
          ],
        ],
      ];
      if (count($bundles) > 0) {
        // Check that recent field configuration changes haven't invalidated any
        // previous selection.
        if (!in_array($configuration['autocreate_bundle'], $bundles)) {
          $configuration['autocreate_bundle'] = reset($bundles);
        }

        $form['autocreate_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Bundle to autocreate'),
          '#options' => $bundles,
          '#default_value' => $configuration['autocreate_bundle'],
          '#states' => [
            'visible' => [
              ':input[name="mappings[' . $delta . '][settings][' . $name . '][autocreate]"]' => [
                ['checked' => TRUE, 'visible' => TRUE],
              ],
            ],
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $configuration): array {
    $summary = [];
    $options = $this->getPotentialFields();
    if ($configuration['reference_by'] && isset($options[$configuration['reference_by']])) {
      $summary[] = $this->t('Reference by: %message', [
        '%message' => $options[$configuration['reference_by']],
      ]);
      if ($configuration['reference_by'] == 'feeds_item') {
        $feed_item_options = $this->getFeedsItemOptions();
        $summary[] = $this->t('Feed item: %feed_item', [
          '%feed_item' => $feed_item_options[$configuration['feeds_item']],
        ]);
      }
    }
    else {
      $summary[] = [
        '#prefix' => '<div class="messages messages--warning">',
        '#markup' => $this->t('Please select a field to reference by.'),
        '#suffix' => '</div>',
      ];
    }

    if ($configuration['reference_by'] === $this->getLabelKey()) {
      $create = $configuration['autocreate'] ? $this->t('Yes') : $this->t('No');
      $summary[] = $this->t('Autocreate entities: %create', ['%create' => $create]);
      if ($configuration['autocreate'] && in_array($configuration['autocreate_bundle'], $this->getBundles())) {
        $summary[] = $this->t('Bundle for autocreated entities: %bundle', ['%bundle' => $configuration['autocreate_bundle']]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $name = $this->configuration['name'];
    if (strlen(trim($value)) === 0) {
      return NULL;
    }
    $field = $configuration[$name]['reference_by'];
    $target_ids = $this->findEntities($field, $configuration[$name], $value, $langcode);
    if (empty($target_ids)) {
      return NULL;
    }

    return reset($target_ids);
  }

  /**
   * Tries to lookup an existing entity.
   *
   * @param string $field
   *   The subfield to search in.
   * @param array $configuration
   *   The feeds configuration array for the custom field.
   * @param int|string $search
   *   The value to lookup.
   * @param string $langcode
   *   The feeds language code.
   *
   * @return int[]
   *   A list of entity ID's.
   */
  protected function findEntities(string $field, array $configuration, int|string $search, string $langcode) {
    $target_type = $this->configuration['target_type'];
    if ($field == 'feeds_item') {
      $field = 'feeds_item.' . $configuration['feeds_item'];
    }

    $target_ids = $this->entityFinder->findEntities($target_type, $field, $search, $this->getBundles());
    if (!empty($target_ids)) {
      return $target_ids;
    }

    if ($configuration['autocreate'] && $field === $this->getLabelKey()) {
      return [$this->createEntity($search, $configuration, $langcode)];
    }

    return [];
  }

  /**
   * Creates a new entity with the given label and saves it.
   *
   * @param string $label
   *   The label the new entity should get.
   * @param array $configuration
   *   The feeds configuration array.
   * @param string $feeds_langcode
   *   The feeds language code.
   *
   * @return int|string|false
   *   The ID of the new entity or false if the given label is empty.
   */
  protected function createEntity($label, array $configuration, string $feeds_langcode) {
    if (!is_string($label) || !strlen(trim($label))) {
      return FALSE;
    }

    $target_type = $this->configuration['target_type'];
    $bundles = $this->getBundles();
    $bundle = in_array($configuration['autocreate_bundle'], $bundles) ? $configuration['autocreate_bundle'] : reset($bundles);
    // Create values for the new entity.
    $values = [
      $this->getLabelKey() => $label,
      $this->getBundleKey() => $bundle,
    ];
    // Set language if the entity type supports it.
    if ($langcode = $this->getLangcodeKey()) {
      $values[$langcode] = $feeds_langcode;
    }

    $entity = $this->entityTypeManager->getStorage($target_type)->create($values);

    $entity->save();

    return $entity->id();
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @return string
   *   The bundle key of the entity type.
   */
  protected function getBundleKey(): string {
    $target_type = $this->configuration['target_type'];
    return $this->entityTypeManager->getDefinition($target_type)->getKey('bundle');
  }

  /**
   * Returns the entity type's label key.
   *
   * @return string
   *   The label key of the entity type.
   */
  protected function getLabelKey(): string {
    $target_type = $this->configuration['target_type'];
    return $this->entityTypeManager->getDefinition($target_type)->getKey('label');
  }

  /**
   * Returns the entity type's langcode key, if it has one.
   *
   * @return string|null
   *   The langcode key of the entity type.
   */
  protected function getLangcodeKey(): ?string {
    $target_type = $this->configuration['target_type'];
    $entity_type = $this->entityTypeManager->getDefinition($target_type);
    if ($entity_type->hasKey('langcode')) {
      return $entity_type->getKey('langcode');
    }

    return NULL;
  }

  /**
   * Callback for the potential field filter.
   *
   * Checks whether the provided field is available to be used as reference.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field
   *   The field to check.
   *
   * @return bool
   *   TRUE if the field can be used as reference otherwise FALSE.
   *
   * @see ::getPotentialFields()
   */
  protected function filterFieldTypes(FieldStorageDefinitionInterface $field): bool {
    if ($field instanceof DataDefinitionInterface && $field->isComputed()) {
      return FALSE;
    }

    switch ($field->getType()) {
      case 'integer':
      case 'string':
      case 'text_long':
      case 'path':
      case 'uuid':
      case 'feeds_item':
        return TRUE;

      default:
        return FALSE;
    }
  }

  /**
   * Returns a list of fields that may be used to reference by.
   *
   * @return array
   *   A list subfields of the entity reference field.
   */
  protected function getPotentialFields(): array {
    $target_type = $this->configuration['target_type'];
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($target_type);
    $field_definitions = array_filter($field_definitions, [
      $this,
      'filterFieldTypes',
    ]);
    $options = [];
    foreach ($field_definitions as $id => $definition) {
      $options[$id] = Html::escape($definition->getLabel());
    }

    return $options;
  }

  /**
   * Returns a list of bundles that may be referenced.
   *
   * If there are no target bundles configured on the entity reference field, an
   * empty array is returned.
   *
   * @return array
   *   Bundles that are allowed to be referenced.
   */
  protected function getBundles(): array {
    return $this->configuration['widget_settings']['settings']['handler_settings']['target_bundles'] ?? [];
  }

  /**
   * Returns options for feeds_item configuration.
   *
   * @return array
   *   Array of feeds item options.
   */
  public function getFeedsItemOptions() {
    return [
      'guid' => $this->t('Item GUID'),
    ];
  }

}
