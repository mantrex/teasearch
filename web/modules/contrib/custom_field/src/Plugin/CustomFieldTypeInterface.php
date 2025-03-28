<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeInterface extends PluginInspectionInterface {

  /**
   * Specifies whether the field supports only internal URLs.
   */
  const LINK_INTERNAL = 0x01;

  /**
   * Specifies whether the field supports only external URLs.
   */
  const LINK_EXTERNAL = 0x10;

  /**
   * Specifies whether the field supports both internal and external URLs.
   */
  const LINK_GENERIC = 0x11;

  /**
   * Value for the 'datetime_type' setting: store only a date.
   */
  const DATETIME_TYPE_DATE = 'date';

  /**
   * Value for the 'datetime_type' setting: store a date and time.
   */
  const DATETIME_TYPE_DATETIME = 'datetime';

  /**
   * Defines the timezone that dates should be stored in.
   */
  const STORAGE_TIMEZONE = 'UTC';

  /**
   * Defines the format that date and time should be stored in.
   */
  const DATETIME_STORAGE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Defines the format that dates should be stored in.
   */
  const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * Defines the widget settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultWidgetSettings(): array;

  /**
   * Render the stored value of the custom field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   A field.
   *
   * @return mixed
   *   The value.
   */
  public function value(FieldItemInterface $item): mixed;

  /**
   * The default formatter plugin type.
   *
   * @return string
   *   The machine name of the formatter plugin.
   */
  public function getDefaultFormatter(): string;

  /**
   * The default widget plugin type.
   *
   * @return string
   *   The machine name of the widget plugin.
   */
  public function getDefaultWidget(): string;

  /**
   * The label for the custom field item.
   *
   * @return string
   *   The label.
   */
  public function getLabel(): string;

  /**
   * The machine name of the custom field item.
   *
   * @return string
   *   The machine name.
   */
  public function getName(): string;

  /**
   * The maxLength value for the custom field item.
   *
   * @return int
   *   The maxLength value.
   */
  public function getMaxLength(): int;

  /**
   * The dataType value for the custom field item.
   *
   * @return string
   *   The dataType value.
   */
  public function getDataType(): string;

  /**
   * The unsigned value from the custom field item.
   *
   * @return bool
   *   The boolean value for unsigned.
   */
  public function isUnsigned(): bool;

  /**
   * The scale value from the custom field item.
   *
   * @return int
   *   The scale value of the column.
   */
  public function getScale(): int;

  /**
   * The precision value from the custom field item.
   *
   * @return int
   *   The precision value of the column.
   */
  public function getPrecision(): int;

  /**
   * The datetime_type value from the custom field item.
   *
   * @return string
   *   The datetime_type value of the column.
   */
  public function getDatetimeType(): string;

  /**
   * The target_type value from the custom field item.
   *
   * @return string
   *   The target_type value of the column.
   */
  public function getTargetType(): string;

  /**
   * Gets a widget setting by name.
   *
   * @param string $name
   *   The name of the widget setting to get.
   *
   * @return mixed
   *   The widget setting to return.
   */
  public function getWidgetSetting(string $name): mixed;

  /**
   * Gets a fields configuration.
   *
   * @return array
   *   The configuration array.
   */
  public function getConfiguration(): array;

  /**
   * Should the field item be included in the empty check?
   *
   * @return bool
   *   TRUE if the field item should be included, otherwise FALSE.
   */
  public function checkEmpty(): bool;

  /**
   * Returns an array of schema properties.
   *
   * @param array $settings
   *   Optional settings passed to the schema() function.
   *
   * @return array
   *   An array of schema properties for the field type.
   */
  public static function schema(array $settings): array;

  /**
   * Returns an array of property definitions.
   *
   * @param array $settings
   *   Optional settings passed to the propertyDefinitions() function.
   *
   * @return mixed
   *   The DataDefinition of properties for the field type.
   */
  public static function propertyDefinitions(array $settings): mixed;

  /**
   * Generates placeholder field values.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $field
   *   An instance of the custom field.
   * @param string $target_entity_type
   *   The entity type of the field this custom field is attached to.
   *
   * @return mixed
   *   A sample value for the custom field.
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed;

  /**
   * Returns an array of constraints.
   *
   * @param array $settings
   *   An array of settings passed to the getConstraints() function.
   *
   * @return array
   *   Array of constraints.
   */
  public function getConstraints(array $settings): array;

  /**
   * Returns an array of calculated dependencies.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $item
   *   The custom field type interface.
   * @param array $default_value
   *   A default value array for the field.
   *
   * @return array
   *   An array of dependencies.
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array;

  /**
   * Returns an array of calculated storage dependencies.
   *
   * @param array $settings
   *   An array of settings for the stored column.
   *
   * @return array
   *   An array of storage dependencies.
   */
  public static function calculateStorageDependencies(array $settings): array;

  /**
   * Returns an array of widget settings to change when dependency is removed.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $item
   *   The custom field type interface.
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are, for example, entity, module and theme.
   *
   * @return array
   *   An array of settings that changed.
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array;

  /**
   * Returns Url object for a field.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   A field.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  public function getUrl(FieldItemInterface $item);

  /**
   * Determines if a link is external.
   *
   * @return bool
   *   TRUE if the link is external, FALSE otherwise.
   */
  public function isExternal(FieldItemInterface $item);

  /**
   * Returns if the field type can be added.
   *
   * @return bool
   *   TRUE if the formatter can be used, FALSE otherwise.
   */
  public static function isApplicable(): bool;

}
