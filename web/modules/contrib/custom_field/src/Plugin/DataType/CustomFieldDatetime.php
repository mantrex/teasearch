<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * The "custom_field_datetime" data type.
 */
#[DataType(
  id: 'custom_field_datetime',
  label: new TranslatableMarkup('Datetime'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldDatetime extends CustomFieldDataTypeBase implements DateTimeInterface {

  /**
   * The link title value.
   *
   * @var string|null
   */
  protected ?string $timezone = NULL;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?FieldItemInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->value = $parent->{$this->getName()};
    $timezone = $parent->get($this->getName() . CustomItem::SEPARATOR . 'timezone')->getValue();
    if ($timezone) {
      $this->timezone = (string) $timezone;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDateTime(): ?DrupalDateTime {
    if ($this->value) {
      if (is_array($this->value)) {
        // Data of this type must always be stored in UTC.
        $datetime = DrupalDateTime::createFromArray($this->value, 'UTC');
      }
      else {
        // Data of this type must always be stored in UTC.
        $datetime = new DrupalDateTime($this->value, 'UTC');
      }
      return $datetime;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, bool $notify = TRUE): void {
    $this->value = $dateTime->format('c');
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    $parent = $this->getParent();
    if (isset($value) && !is_array($value)) {
      $value = ['value' => $value];
    }
    if (isset($value['timezone'])) {
      $parent->set($this->getName() . CustomItem::SEPARATOR . 'timezone', $value['timezone']);
      $this->timezone = (string) $value['timezone'];
    }
    parent::setValue($value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (is_array($this->value)) {
      $this->value = $this->value['value'];
    }
    return $this->value;
  }

  /**
   * Gets the stored timezone.
   *
   * @return string|null
   *   The stored timezone.
   */
  public function getTimezone(): ?string {
    return $this->timezone;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return (string) $this->getValue();
  }

}
