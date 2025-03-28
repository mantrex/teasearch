<?php

declare(strict_types=1);

namespace Drupal\custom_field_graphql\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomFieldItemListInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemsInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\FileItem;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\ImageItem;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\TextItem;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "custom",
 * )
 */
class CustomFieldItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface, FieldProducerItemsInterface {

  use FieldProducerTrait;

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->customFieldManager = $container->get('plugin.manager.custom_field_type');
    $instance->typedDataManager = $container->get('typed_data_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItems(FieldItemListInterface $field, FieldContext $context): array {
    assert($field instanceof CustomFieldItemListInterface);
    $referenced_entities = $field->referencedEntities();
    $results = [];

    foreach ($field as $delta => $item) {
      $entities = $referenced_entities[$delta] ?? [];
      // Set the loaded entity references to context.
      $context->setContextValue('entities', $entities);
      $results[] = $this->resolveFieldItem($item, $context);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $settings = $item->getFieldDefinition()->getSettings();
    $custom_items = $this->customFieldManager->getCustomFieldItems($settings);
    $fields = [];
    // Get all loaded entity references from context.
    $entities = $context->getContextValue('entities');

    foreach ($custom_items as $name => $custom_item) {
      $reference = $entities[$name] ?? NULL;
      // Pass the widget settings down as context.
      $context->setContextValue('settings', $settings['field_settings'][$name]['widget_settings']['settings'] ?? []);
      $context->setContextValue('property_name', $name);
      $fields[$name] = $this->getSubField($name, $item, $context, $reference) ?: NULL;
    }

    return $fields;
  }

  /**
   * Get the subfield value for a subfield.
   *
   * @param string $subfield
   *   The name of the subfield. (first, second)
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The field context.
   * @param \Drupal\Core\Entity\EntityInterface|null $reference
   *   The referenced entity if applicable.
   *
   * @return mixed
   *   The value of the subfield.
   */
  protected function getSubField(string $subfield, FieldItemInterface $item, FieldContext $context, ?EntityInterface $reference = NULL) {
    $settings = $item->getFieldDefinition()->getSettings();
    $custom_items = $this->customFieldManager->getCustomFieldItems($settings);
    $custom_item = $custom_items[$subfield];
    $value = $item->{$subfield};
    // If the value is empty, go no further.
    if ($value === NULL || $value === "") {
      return NULL;
    }

    // Attempt to load the plugin for the field type.
    $plugin = $this->getSubfieldPlugin($subfield);

    if (!$plugin) {
      return $value;
    }

    // Check if it has a resolver we can hijack.
    $class = new \ReflectionClass($plugin['class']);

    if (!$class->implementsInterface(FieldProducerItemInterface::class)) {
      return $value;
    }

    // Create an instance of the graphql plugin.
    $instance = $this->gqlFieldTypeManager->createInstance($plugin['id'], []);

    // Clone the current item into a new object for safety.
    $clone = clone $item;

    // Generically set the value. Relies on magic method __set().
    $clone->value = $value;

    // Snowflake items.
    if ($instance instanceof FileItem) {
      $clone->entity = $reference;
    }

    elseif ($instance instanceof ImageItem) {
      /** @var \Drupal\custom_field\Plugin\DataType\CustomFieldImage $image_instance */
      $image_instance = $this->typedDataManager->getPropertyInstance($item, $custom_item->getName());
      $clone->entity = $reference;
      $clone->alt = $image_instance->getAlt();
      $clone->title = $image_instance->getTitle();
      $clone->width = $image_instance->getWidth();
      $clone->height = $image_instance->getHeight();
    }

    elseif ($instance instanceof CustomFieldEntityReference) {
      $clone->entity = $reference;
    }

    elseif ($instance instanceof CustomFieldLink) {
      $clone->uri = $value;
    }

    elseif ($instance instanceof TextItem) {
      $widget_settings = $custom_item->getWidgetSetting('settings');
      $format = $widget_settings['default_format'] ?? filter_fallback_format();
      $clone->format = $format;
      $clone->processed = check_markup($value, $format);
    }

    // Call the plugin resolver on the sub field.
    return $instance->resolveFieldItem($clone, $context);
  }

  /**
   * {@inheritdoc}
   *
   * Override the type resolution for this field item.
   */
  public function getTypeSdl(): string {
    $type = u('Custom');
    $definition = $this->getFieldDefinition();
    $entity_type = $definition->getTargetEntityTypeId();
    $bundle = $definition->getTargetBundle();
    $type = $type->append(u($definition->getName())
      ->camel()
      ->title()
      ->toString());
    $type = $type->append(u($entity_type)->camel()->title()->toString());
    $type = $type->append(u($bundle)->camel()->title()->toString());

    return $type->toString();
  }

  /**
   * Get the subfield type for a subfield.
   *
   * @param string $subfield
   *   The subfield to get the type for. Eg first, second.
   *
   * @return string
   *   The SDL type of the subfield.
   */
  public function getSubfieldTypeSdl(string $subfield): string {
    $plugin = $this->getSubfieldPlugin($subfield);
    if ($plugin['id'] === 'custom_field_entity_reference') {
      return $this->getUnionTypeSdl($subfield);
    }
    return $plugin['type_sdl'] ?? 'String';
  }

  /**
   * Get the data definition type from DoubleField.
   *
   * @param string $subfield
   *   The subfield to get the plugin for. Eg first, second.
   *
   * @return array|null
   *   The plugin definition or NULL if not found.
   */
  protected function getSubfieldPlugin(string $subfield): ?array {
    $storage = $this->getFieldDefinition()->getFieldStorageDefinition();
    $settings = $storage->getSettings();

    // Use the stored data type.
    $type = $settings['columns'][$subfield]['type'];

    // Coerce them back into our schema supported type.
    switch ($type) {

      case 'uri':
        $type = 'custom_field_link';
        break;

      case 'string_long':
        $type = 'text';
        break;

      case 'map':
      case 'map_string':
        $type = 'custom_field_map';
        break;

      case 'entity_reference':
        $type = 'custom_field_entity_reference';
        break;

      case 'viewfield':
        $type = 'custom_field_viewfield';
        break;
    }

    return $this->gqlFieldTypeManager->getDefinition($type, FALSE);
  }

  /**
   * The GraphQL union type for this field (non generic).
   *
   * @param string $subfield
   *   The name of the subfield.
   *
   * @return string
   *   Type in format of {Entity}Union
   */
  public function getUnionTypeSdl(string $subfield): string {
    $settings = $this->getFieldDefinition()->getSettings();
    $custom_items = $this->customFieldManager->getCustomFieldItems($settings);
    $custom_item = $custom_items[$subfield];
    $target_type_id = $custom_item->getTargetType();

    if (!$target_type_id) {
      return 'UnsupportedType';
    }

    // Entity type not defined.
    if (!$entity_type = $this->entityTypeManager->getDefinition($target_type_id, FALSE)) {
      return 'UnsupportedType';
    }

    // Entity type plugin not defined.
    if (!$plugin_instance = $this->gqlEntityTypeManager->getPluginInstance($target_type_id)) {
      return 'UnsupportedType';
    }

    // No enabled plugin bundles.
    if (empty($plugin_instance->getBundles())) {
      return 'UnsupportedType';
    }

    // No bundle types on this entity.
    // No union required. Return normal name.
    if (!$entity_type->getBundleEntityType()) {
      return $plugin_instance->getTypeSdl();
    }

    return u($plugin_instance->getTypeSdl())
      ->camel()
      ->title()
      ->append('Union')
      ->toString();
  }

}
