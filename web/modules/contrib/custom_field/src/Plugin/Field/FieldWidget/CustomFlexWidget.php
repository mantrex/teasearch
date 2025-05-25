<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;

/**
 * Plugin implementation of the 'custom_flex' widget.
 */
#[FieldWidget(
  id: 'custom_flex',
  label: new TranslatableMarkup('Flexbox'),
  field_types: [
    'custom',
  ],
  weight: 0,
)]
class CustomFlexWidget extends CustomWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'breakpoint' => '',
      'columns' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;
    $elements['#attached']['library'][] = 'custom_field/custom-field-flex';
    $elements['#attached']['library'][] = 'custom_field/custom-field-flex-admin';

    $elements['columns'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Column settings'),
      '#description' => $this->t('Select the number of columns for each form element. A value of <em>auto</em> will size the column based on the natural width of content within it.'),
      '#description_display' => 'before',
    ];

    $elements['columns']['prefix'] = [
      '#markup' => '<div class="custom-field-row custom-field-flex--widget-settings">',
    ];

    $columns = $this->getSettings()['columns'];
    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      $plugin_id = $custom_item->getPluginId();
      // The uuid widget type is a hidden field.
      if ($plugin_id == 'uuid') {
        continue;
      }
      $elements['columns'][$name] = [
        '#type' => 'select',
        '#title' => $custom_item->getLabel(),
        '#options' => $this->columnOptions(),
        '#wrapper_attributes' => [
          'class' => ['custom-field-col'],
        ],
        '#attributes' => [
          'class' => ['custom-field-col__field'],
        ],
      ];
      if (isset($columns[$name])) {
        $elements['columns'][$name]['#default_value'] = $columns[$name];
        $elements['columns'][$name]['#wrapper_attributes']['class'][] = 'custom-field-col-' . $columns[$name];
        $is_disabled = in_array($plugin_id, ['color_boxes', 'map_key_value']);
        if ($is_disabled) {
          $elements['columns'][$name]['#default_value'] = 12;
          $elements['columns'][$name]['#attributes'] = ['disabled' => TRUE];
          $elements['columns'][$name]['#description'] = $this->t('This widget type as configured requires full width.');
        }
      }
    }

    $elements['columns']['suffix'] = [
      '#markup' => '</div>',
    ];

    $elements['breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Stack items on:'),
      '#description' => $this->t('The device width in which the columns are set to full width and stack on top of one another.'),
      '#options' => $this->breakpointOptions(),
      '#default_value' => $this->getSetting('breakpoint'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $columns = 'Automatic';
    if (!empty($this->getSettings()['columns'])) {
      $columns = implode(' | ', $this->getSettings()['columns']);
    }
    $summary[] = $this->t('Column settings: @columns', ['@columns' => $columns]);
    $summary[] = $this->t('Stack on: @breakpoint', ['@breakpoint' => $this->breakpointOptions($this->getSetting('breakpoint'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#attached']['library'][] = 'custom_field/custom-field-flex';
    $classes = ['custom-field-row'];
    if ($this->getSetting('breakpoint')) {
      $classes[] = 'custom-field-flex--stack-' . $this->getSetting('breakpoint');
    }
    // Using markup since we can't nest values because the field api expects
    // subfields to be at the top-level.
    $element['wrapper_prefix']['#markup'] = '<div class="custom-field-row-wrapper"><div class="' . implode(' ', $classes) . '">';
    $columns = $this->getSettings()['columns'];

    // Account for unsaved fields in field config default values form.
    if (!empty($form_state->get('current_settings'))) {
      $current_settings = $form_state->get('current_settings');
      $field_settings = $current_settings['field_settings'];
      $custom_items = $this->customFieldTypeManager->getCustomFieldItems($current_settings);
    }
    else {
      $field_settings = $this->getFieldSetting('field_settings');
      $custom_items = $this->getCustomFieldItems();
    }

    foreach ($custom_items as $name => $custom_item) {
      $type = $field_settings[$name]['type'] ?? $custom_item->getDefaultWidget();
      if (!in_array($type, $this->customFieldWidgetManager->getWidgetsForField($custom_item->getPluginId()))) {
        $type = $custom_item->getDefaultWidget();
      }
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $widget_plugin */
      $widget_plugin = $this->customFieldWidgetManager->createInstance($type, ['settings' => $field_settings[$name]['widget_settings'] ?? []]);
      $widget_settings = $custom_item->getWidgetSetting('settings');
      $element[$name] = $widget_plugin->widget($items, $delta, $element, $form, $form_state, $custom_item);
      $attributes = $this->getAttributesKey($custom_item, $widget_settings, $type);
      $column_class = "custom-field-$name custom-field-col";
      if (isset($columns[$name])) {
        $column_class .= " custom-field-col-$columns[$name]";
      }

      if (isset($element[$name]['#type']) && $element[$name]['#type'] === 'managed_file') {
        $element[$name]['#column_class'] = $column_class;
        $element[$name]['#after_build'][] = [$this, 'callManagedFileAfterBuild'];
      }

      // Entity reference widgets need class on target_id element.
      $entity_reference_widgets = [
        'entity_reference_autocomplete',
        'entity_reference_radios',
        'entity_reference_select',
      ];

      if (in_array($type, $entity_reference_widgets)) {
        $element[$name]['target_id'][$attributes]['class'][] = $column_class;
      }
      $date_widgets = [
        'datetime_default',
      ];
      if ($custom_item->getDatetimeType() === 'date' && in_array($type, $date_widgets)) {
        $element[$name]['#column_class'] = $column_class;
        $element[$name]['#after_build'][] = [$this, 'callDateAfterBuild'];
      }
      else {
        $element[$name][$attributes]['class'][] = $column_class;
      }
    }

    $element['wrapper_suffix']['#markup'] = '</div></div>';

    return $element;
  }

  /**
   * Closure function to pass arguments to managedFileAfterBuild().
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The element array.
   */
  public function callManagedFileAfterBuild(array $element, FormStateInterface $form_state): array {
    $column = $element['#column_class'];
    return static::managedFileAfterBuild($element, $form_state, $column);
  }

  /**
   * Closure function to pass arguments to dateAfterBuild().
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The element array.
   */
  public function callDateAfterBuild(array $element, FormStateInterface $form_state): array {
    $column = $element['#column_class'];
    return static::dateAfterBuild($element, $form_state, $column);
  }

  /**
   * After build function to add class to file outer ajax wrapper div.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $column
   *   The column class.
   *
   * @return array<string, mixed>
   *   The modified form element.
   */
  public static function managedFileAfterBuild(array $element, FormStateInterface $form_state, string $column): array {
    if (preg_match('/id="([^"]*ajax-wrapper[^"]*)"/', $element['#prefix'], $matches)) {
      $id_attribute = $matches[0];
      // Check if the class attribute exists.
      if (str_contains($element['#prefix'], 'class="')) {
        // If class exists, append the new class.
        $element['#prefix'] = str_replace('class="', 'class="' . $column . ' ', $element['#prefix']);
      }
      else {
        // If no class attribute exists, insert one after the id attribute.
        $element['#prefix'] = str_replace($id_attribute, $id_attribute . ' class="' . $column . '"', $element['#prefix']);
      }
    }
    return $element;
  }

  /**
   * After build function to add class to date outer wrapper div.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $column
   *   The column class.
   *
   * @return array<string, mixed>
   *   The modified form element.
   */
  public static function dateAfterBuild(array $element, FormStateInterface $form_state, string $column): array {
    // Add an outer div with our class.
    $element['#prefix'] = '<div class="' . $column . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Determine which attributes to use based on the plugin type.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $custom_item
   *   The custom field item.
   * @param array<string, mixed> $widget_settings
   *   The widget settings for the custom field item.
   * @param string $type
   *   The widget type.
   *
   * @return string
   *   The attribute key string.
   */
  protected function getAttributesKey(CustomFieldTypeInterface $custom_item, array $widget_settings, string $type): string {
    $attribute_types = [
      'media_library_widget',
      'viewfield_select',
      'entity_reference_radios',
      'radios',
      'datetime_datelist',
      'datetime_default',
      'url',
      'link_default',
      'linkit_url',
      'linkit',
    ];

    if (in_array($type, $attribute_types)) {
      return '#attributes';
    }

    switch ($custom_item->getPluginId()) {
      case 'string_long':
        $formatted = $widget_settings['formatted'] ?? FALSE;
        return $formatted ? '#attributes' : '#wrapper_attributes';

      default:
        return '#wrapper_attributes';
    }
  }

  /**
   * Get the field storage definition.
   */
  public function getFieldStorageDefinition(): FieldStorageDefinitionInterface {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * The options for columns.
   *
   * @param string|null $option
   *   The option key.
   *
   * @return array<string|int, TranslatableMarkup>|string
   *   The options or option.
   */
  public function columnOptions(?string $option = NULL): array|string {
    $options = [
      'auto' => $this->t('Auto'),
      1 => $this->t('1 column'),
      2 => $this->t('2 columns'),
      3 => $this->t('3 columns'),
      4 => $this->t('4 columns'),
      5 => $this->t('5 columns'),
      6 => $this->t('6 columns'),
      7 => $this->t('7 columns'),
      8 => $this->t('8 columns'),
      9 => $this->t('9 columns'),
      10 => $this->t('10 columns'),
      11 => $this->t('11 columns'),
      12 => $this->t('12 columns'),
    ];
    // @todo Better move this logic out of this function, so this function can
    // have single return type.
    if (!is_null($option)) {
      return $options[$option] ?? '';
    }

    return $options;
  }

  /**
   * The options for breakpoints.
   *
   * @param string|null $option
   *   The option key.
   *
   * @return array<string, TranslatableMarkup>|string
   *   The options or option.
   */
  public function breakpointOptions(?string $option = NULL): array|string {
    $options = [
      '' => $this->t("Don't stack"),
      'medium' => $this->t('Medium (less than 769px)'),
      'small' => $this->t('Small (less than 601px)'),
    ];
    // @todo Better move this logic out of this function, so this function can
    // have single return type.
    if (!is_null($option)) {
      return $options[$option] ?? '';
    }

    return $options;
  }

}
