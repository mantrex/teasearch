<?php

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * The "custom_field_viewfield" data type.
 *
 * This data type provides a way to process entity and additional metadata as
 * part of values.
 *
 * @DataType(
 *   id = "custom_field_viewfield",
 *   label = @Translation("Viewfield"),
 *   definition_class = "\Drupal\custom_field\TypedData\CustomFieldDataDefinition"
 * )
 */
class CustomFieldViewfield extends TypedData implements PrimitiveInterface {

  /**
   * The data value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The entity object or null.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * The views display id.
   *
   * @var string
   */
  protected $displayId;

  /**
   * The view arguments.
   *
   * @var string
   */
  protected $arguments;

  /**
   * The items_to_display value.
   *
   * @var int
   */
  protected $itemsToDisplay;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->value = $parent->{$this->getName()};
    $this->displayId = $parent->get($this->getName() . '__display')->getValue();
    $this->arguments = $parent->get($this->getName() . '__arguments')->getValue();
    $this->itemsToDisplay = $parent->get($this->getName() . '__items')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $display_id = $value['display_id'] ?? NULL;
    $arguments = $value['arguments'] ?? NULL;
    $items_to_display = $value['items_to_display'] ?? NULL;
    if (!empty($display_id)) {
      $this->setDisplayId($display_id);
    }
    if (!empty($arguments)) {
      $this->setArguments($arguments);
    }
    if (!empty($items_to_display)) {
      $this->setItemsToDisplay($items_to_display);
    }
    $entity = $value['entity'] ?? NULL;
    if ($entity instanceof EntityInterface) {
      if ($entity->isNew()) {
        try {
          $entity->save();
        }
        catch (EntityStorageException $exception) {
          $entity = NULL;
        }
      }
      $this->entity = $entity;
      $value = $entity->id();
    }
    $this->value = $value['target_id'] ?? $value;
  }

  /**
   * Helper function to load an entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object or null.
   */
  public function getEntity() {
    if (empty($this->entity) && !empty($this->value)) {
      $target_type = $this->getDataDefinition()->getSetting('target_type');
      $storage = \Drupal::entityTypeManager()->getStorage($target_type);
      $this->entity = $storage->load($this->getValue());
    }

    return $this->entity;
  }

  /**
   * Sets the alt text value.
   *
   * @param string $display_id
   *   The display id value to set.
   */
  protected function setDisplayId(string $display_id): void {
    $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'display', $display_id);
    $this->displayId = $display_id;
  }

  /**
   * Sets the title text value.
   *
   * @param string $arguments
   *   The arguments value to set.
   */
  protected function setArguments(string $arguments): void {
    $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'arguments', $arguments);
    $this->arguments = $arguments;
  }

  /**
   * Sets the title text value.
   *
   * @param int $items
   *   The items to display value to set.
   */
  protected function setItemsToDisplay(int $items): void {
    $this->getParent()->set($this->getName() . CustomItem::SEPARATOR . 'items', $items);
    $this->itemsToDisplay = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = $this->value;
    return $value['target_id'] ?? $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getValue();
  }

}
