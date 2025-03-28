<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "string_long",
 *   }
 * )
 */
class TextDefaultFormatter extends CustomFieldFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    $formatted = $this->getFieldWidgetSetting('formatted') ?? FALSE;
    if ($formatted) {
      // The ProcessedText element already handles cache context & tag bubbling.
      // @see \Drupal\filter\Element\ProcessedText::preRenderText()
      $build = [
        '#type' => 'processed_text',
        '#text' => $value,
        '#format' => $this->getFieldWidgetSetting('default_format'),
        '#langcode' => $item->getLangcode(),
      ];
      $value = $build;
    }

    return $value;
  }

}
