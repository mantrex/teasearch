<?php

namespace Drupal\custom_field\Plugin\DataType;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Render\FilteredMarkup;

/**
 * The "custom_field_string_long" data type.
 *
 * The "custom_field_string_long" data type provides a mechanism to return the
 * processed value for "string_long" custom_field types that we can normalize
 * for jsonapi.
 */
#[DataType(
  id: 'custom_field_string_long',
  label: new TranslatableMarkup('String long'),
  definition_class: CustomFieldDataDefinition::class,
)]
class CustomFieldStringLong extends StringData implements CacheableDependencyInterface, CustomFieldStringLongInterface {

  /**
   * Cached processed text.
   *
   * @var \Drupal\filter\FilterProcessResult|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public function getProcessed(): mixed {
    $format = $this->getFormat();
    if (!$format) {
      return $this->getValue();
    }

    if ($this->processed !== NULL) {
      return FilteredMarkup::create($this->processed->getProcessedText());
    }

    $item = $this->getParent();
    $build = [
      '#type' => 'processed_text',
      '#text' => $this->getValue(),
      '#format' => $format,
      '#filter_types_to_skip' => [],
      '#langcode' => $item->getLangcode(),
    ];
    // Capture the cacheability metadata associated with the processed text.
    $processed_text = $this->getRenderer()->renderPlain($build);
    $this->processed = FilterProcessResult::createFromRenderArray($build)->setProcessedText((string) $processed_text);

    return FilteredMarkup::create($this->processed->getProcessedText());
  }

  /**
   * A helper function to return the format type from widget setting.
   *
   * @return mixed|null
   *   The specified format from widget setting, otherwise NULL.
   */
  protected function getFormat() {
    $parent = $this->getParent();
    $format = NULL;
    if ($parent instanceof CustomItem) {
      $field_settings = $parent->getFieldDefinition()->getSetting('field_settings')[$this->name];
      $settings = $field_settings ? $field_settings['widget_settings']['settings'] : [];
      $format = $settings['formatted'] ? $settings['default_format'] : NULL;
    }
    return $format;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $this->getValue();
    return $this->processed->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $this->getValue();
    return $this->processed->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $this->getValue();
    return $this->processed->getCacheMaxAge();
  }

  /**
   * Returns the renderer service.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer service.
   */
  protected function getRenderer() {
    return \Drupal::service('renderer');
  }

}
