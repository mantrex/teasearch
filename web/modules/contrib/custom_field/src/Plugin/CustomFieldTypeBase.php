<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for CustomField Type plugins.
 */
abstract class CustomFieldTypeBase extends PluginBase implements CustomFieldTypeInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The custom field separator for extended properties.
   *
   * @var string
   */
  const SEPARATOR = '__';

  /**
   * The default max length for string fields.
   *
   * @var int
   */
  const MAX_LENGTH = 255;

  /**
   * The field settings.
   *
   * @var array<string, mixed>
   */
  protected array $settings;

  /**
   * The name of the custom field item.
   *
   * @var string
   */
  protected string $name = 'value';

  /**
   * An array of widget settings.
   *
   * @var array<string, mixed>
   */
  protected array $widgetSettings = [];

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
   *
   * @param string $plugin_id
   *   The plugin ID for the field type.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array<string, mixed> $settings
   *   The field settings.
   */
  public function __construct(string $plugin_id, mixed $plugin_definition, array $settings) {
    parent::__construct([], $plugin_id, $plugin_definition);
    // Initialize properties based on configuration.
    $this->settings = $settings;
    $this->name = $settings['name'] ?? 'value';
    $this->widgetSettings = $settings['widget_settings'] ?? [];

    // We want to default the label to the column name, so we do that before the
    // merge and only if it's unset since a value of '' may be what the user
    // wants for no label.
    if (!isset($this->widgetSettings['label'])) {
      $label = ucfirst(str_replace(['-', '_'], ' ', $this->name));
      $this->widgetSettings['label'] = $this->t('@label', ['@label' => $label]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($plugin_id, $plugin_definition, $configuration['settings'] ?? []);
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
    /** @var array<string, mixed> $definition */
    $definition = $this->getPluginDefinition();
    return $definition['default_formatter'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultWidget(): string {
    /** @var array<string, mixed> $definition */
    $definition = $this->getPluginDefinition();
    return $definition['default_widget'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetPluginId(): string {
    return $this->settings['widget_plugin'] ?? self::getDefaultWidget();
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
    return $this->settings['name'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxlength(): int {
    $length = !empty($this->settings['length']) ? (int) $this->settings['length'] : NULL;
    return $length ?? self::MAX_LENGTH;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType(): string {
    return $this->settings['type'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function isUnsigned(): bool {
    if (!isset($this->settings['unsigned'])) {
      return FALSE;
    }
    return (bool) $this->settings['unsigned'];
  }

  /**
   * {@inheritdoc}
   */
  public function getScale(): int {
    if (!isset($this->settings['scale']) || !is_numeric($this->settings['scale'])) {
      return 2;
    }
    return (int) $this->settings['scale'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrecision(): int {
    if (!isset($this->settings['precision']) || !is_numeric($this->settings['precision'])) {
      return 10;
    }
    return (int) $this->settings['precision'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDatetimeType(): string {
    return $this->settings['datetime_type'] ?? static::DATETIME_TYPE_DATETIME;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(): ?string {
    return $this->settings['target_type'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $setting): mixed {
    return $this->settings[$setting] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings(): array {
    return $this->widgetSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSetting(string $name): mixed {
    return $this->widgetSettings[$name] ?? (static::defaultWidgetSettings()[$name] ?? NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function checkEmpty(): bool {
    return (bool) $this->settings['check_empty'];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => $settings['length'] ?? 255,
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
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(FieldItemInterface $item): Url {
    return Url::fromUri($item->{$this->name});
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal(FieldItemInterface $item): bool {
    return $this->getUrl($item)->isExternal();
  }

  /**
   * Helper method to flatten an array of allowed values and randomize.
   *
   * @param array<array{key: int|string, value: string}> $allowed_values
   *   An array of allowed values.
   *
   * @return int|string
   *   A random key from allowed values array.
   */
  protected static function getRandomOptions(array $allowed_values): int|string {
    $randoms = [];
    foreach ($allowed_values as $value) {
      if (isset($value['key'], $value['value'])) {
        $randoms[$value['key']] = $value['value'];
      }
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
