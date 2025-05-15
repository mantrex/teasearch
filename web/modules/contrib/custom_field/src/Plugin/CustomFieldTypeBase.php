<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Base class for CustomField Type plugins.
 */
abstract class CustomFieldTypeBase extends PluginBase implements CustomFieldTypeInterface {

  use StringTranslationTrait;

  /**
   * The custom field separator for extended properties.
   *
   * @var
   */
  const SEPARATOR = '__';

  /**
   * The name of the custom field item.
   *
   * @var string
   */
  protected $name = 'value';

  /**
   * The data type of the custom field item.
   *
   * @var string
   */
  protected $dataType = '';

  /**
   * The max length of the custom field item database column.
   *
   * @var int
   */
  protected $maxLength = 255;

  /**
   * A boolean to determine if a custom field type of integer is unsigned.
   *
   * @var bool
   */
  protected $unsigned = FALSE;

  /**
   * An array of widget settings.
   *
   * @var array
   */
  protected $widgetSettings = [];

  /**
   * Should this field item be included in the empty check?
   *
   * @var bool
   */
  protected $checkEmpty = FALSE;

  /**
   * Returns the 'scale' field storage value.
   *
   * @var int|mixed
   */
  protected $scale;

  /**
   * Returns the 'datetime_type' field storage value.
   *
   * @var string
   */
  protected $datetimeType;

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'label' => '',
      'translatable' => FALSE,
      'settings' => [
        'description' => '',
        'description_display' => 'after',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * Construct a CustomFieldType plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Initialize properties based on configuration.
    $this->name = $this->configuration['name'] ?? 'value';
    $this->maxLength = $this->configuration['max_length'] ?? 255;
    $this->unsigned = $this->configuration['unsigned'] ?? FALSE;
    $this->widgetSettings = $this->configuration['widget_settings'] ?? [];
    $this->dataType = $this->configuration['data_type'] ?? '';
    $this->checkEmpty = $this->configuration['check_empty'] ?? FALSE;
    $this->scale = $this->configuration['scale'] ?? 2;
    $this->datetimeType = $this->configuration['datetime_type'] ?? static::DATETIME_TYPE_DATETIME;

    // We want to default the label to the column name, so we do that before the
    // merge and only if it's unset since a value of '' may be what the user
    // wants for no label.
    if (!isset($this->widgetSettings['label'])) {
      $this->widgetSettings['label'] = ucfirst(str_replace(['-', '_'], ' ', $this->name));
    }

    // Merge defaults.
    $this->widgetSettings = $this->widgetSettings + self::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function value(FieldItemInterface $item): mixed {
    return $item->{$this->name};
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormatter(): string {
    return $this->getPluginDefinition()['default_formatter'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultWidget(): string {
    return $this->getPluginDefinition()['default_widget'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->widgetSettings['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxlength(): int {
    return $this->maxLength;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType(): string {
    return $this->dataType;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnsigned(): bool {
    return $this->unsigned;
  }

  /**
   * {@inheritdoc}
   */
  public function getScale(): int {
    return $this->scale;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrecision(): int {
    return $this->configuration['precision'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDatetimeType(): string {
    return $this->datetimeType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(): string {
    return $this->configuration['target_type'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSetting(string $name): mixed {
    return $this->widgetSettings[$name] ?? static::defaultWidgetSettings()[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEmpty(): bool {
    return $this->checkEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => $settings['max_length'] ?? 255,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(array $settings): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(FieldItemInterface $item) {
    return Url::fromUri($item->{$this->name});
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal(FieldItemInterface $item) {
    return $this->getUrl($item)->isExternal();
  }

  /**
   * Helper method to flatten an array of allowed values and randomize.
   *
   * @param array $allowed_values
   *   An array of allowed values.
   *
   * @return int|string
   *   A random key from allowed values array.
   */
  protected static function getRandomOptions(array $allowed_values): int|string {
    $randoms = [];
    foreach ($allowed_values as $value) {
      $randoms[$value['key']] = $value['value'];
    }

    return array_rand($randoms, 1);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(): bool {
    return TRUE;
  }

}
