<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\custom_field\TagManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base formatter for custom_field.
 */
abstract class BaseFormatter extends FormatterBase implements BaseFormatterInterface {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $customFieldManager;

  /**
   * The custom field formatter manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFormatterManagerInterface
   */
  protected CustomFieldFormatterManagerInterface $customFieldFormatterManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The tag manager service.
   *
   * @var \Drupal\custom_field\TagManagerInterface
   */
  protected TagManagerInterface $tagManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->customFieldManager = $container->get('plugin.manager.custom_field_type');
    $instance->customFieldFormatterManager = $container->get('plugin.manager.custom_field_formatter');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->tagManager = $container->get('custom_field.tag_manager');
    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'fields' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Field settings'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      $settings = $this->getSetting('fields')[$name] ?? [];
      $formatter_settings = $settings['formatter_settings'] ?? [];
      $wrapper_settings = $settings['wrappers'] ?? [];
      $type = $custom_item->getPluginId();
      $formatter_options = $this->customFieldFormatterManager->getOptions($custom_item);
      $default_format = $custom_item->getDefaultFormatter();
      if (isset($settings['format_type']) && isset($formatter_options[$settings['format_type']])) {
        $default_format = $settings['format_type'];
      }
      $values = $form_state->getValues();
      $value_keys = $this->customFieldFormatterManager->getFormatterValueKeys($form_state, $field_name, (string) $name);
      $format_type = NestedArray::getValue($values, $value_keys) ?? $default_format;

      $visibility_path = $this->customFieldFormatterManager->getInputPathForStatesApi($form_state, $field_name, (string) $name);
      $root_visibility_path = $visibility_path;
      // Strip the last [formatter_settings] to get root path.
      if (str_ends_with($visibility_path, '[formatter_settings]')) {
        $root_visibility_path = substr($visibility_path, 0, -strlen('[formatter_settings]'));
      }
      $form['#visibility_path'] = $visibility_path;
      $wrapper_id = 'field-' . $field_name . '-' . $name . '-ajax';
      $form['fields'][$name] = [
        '#type' => 'details',
        '#title' => $this->t('@label (@type)', [
          '@label' => $custom_item->getLabel(),
          '@type' => $custom_item->getDataType(),
        ]),
      ];

      if (!empty($formatter_options)) {
        $form['fields'][$name]['format_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Format type'),
          '#options' => $formatter_options,
          '#default_value' => $format_type,
          '#ajax' => [
            'callback' => [$this, 'actionCallback'],
            'wrapper' => $wrapper_id,
            'method' => 'replace',
          ],
        ];
        $form['fields'][$name]['formatter_settings'] = [
          '#type' => 'container',
          '#prefix' => '<div id="' . $wrapper_id . '">',
          '#suffix' => '</div>',
        ];
        $formatter = [];
        $options = $this->customFieldFormatterManager->createOptionsForInstance($custom_item, $format_type, $formatter_settings, $this->viewMode);

        // Get the formatter settings form.
        $format = $this->customFieldFormatterManager->getInstance($options);
        if (!is_null($format)) {
          $formatter = $format->settingsForm($form, $form_state);
        }
        $form['fields'][$name]['formatter_settings'] += $formatter;

        $form['fields'][$name]['formatter_settings']['label_display'] = [
          '#type' => 'select',
          '#title' => $this->t('Label display'),
          '#options' => $this->fieldLabelOptions(),
          '#default_value' => $formatter_settings['label_display'] ?? 'above',
          '#weight' => 10,
          '#access' => $type !== 'boolean' && $format_type !== 'hidden',
        ];
        $form['fields'][$name]['formatter_settings']['field_label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Field label'),
          '#description' => $this->t('The label for viewing this field. Leave blank to use the default field label.'),
          '#default_value' => $formatter_settings['field_label'] ?? $custom_item->getLabel(),
          '#weight' => 11,
          '#maxlength' => 255,
          '#access' => $format_type !== 'hidden',
          '#states' => [
            'visible' => [
              ':input[name="' . $visibility_path . '[label_display]"]' => ['!value' => 'hidden'],
            ],
          ],
        ];
        // HTML wrapper settings.
        $tag_options = $this->tagManager->getTagOptions();

        $form['fields'][$name]['wrappers'] = [
          '#type' => 'details',
          '#title' => $this->t('Style settings'),
          '#states' => [
            'visible' => [
              ':input[name="' . $root_visibility_path . '[format_type]"]' => ['!value' => 'hidden'],
            ],
          ],
        ];
        $form['fields'][$name]['wrappers']['field_wrapper_tag'] = [
          '#type' => 'select',
          '#title' => $this->t('Field wrapper tag'),
          '#description' => $this->t('Choose the HTML element to wrap around this field and label.'),
          '#options' => $tag_options,
          '#empty_option' => $this->t('- Use default -'),
          '#default_value' => $wrapper_settings['field_wrapper_tag'] ?? '',
        ];
        $form['fields'][$name]['wrappers']['field_wrapper_classes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Field wrapper classes'),
          '#description' => $this->t('Enter additional classes, separated by space.'),
          '#default_value' => $wrapper_settings['field_wrapper_classes'] ?? '',
          '#states' => [
            'invisible' => [
              ':input[name="' . $root_visibility_path . '[wrappers][field_wrapper_tag]"]' => ['value' => 'none'],
            ],
          ],
        ];
        $form['fields'][$name]['wrappers']['field_tag'] = [
          '#type' => 'select',
          '#title' => $this->t('Field tag'),
          '#description' => $this->t('Choose the HTML element to wrap around this field.'),
          '#options' => $tag_options,
          '#empty_option' => $this->t('- Use default -'),
          '#default_value' => $wrapper_settings['field_tag'] ?? '',
        ];
        $form['fields'][$name]['wrappers']['field_classes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Field classes'),
          '#description' => $this->t('Enter additional classes, separated by space.'),
          '#default_value' => $wrapper_settings['field_classes'] ?? '',
          '#states' => [
            'invisible' => [
              ':input[name="' . $root_visibility_path . '[wrappers][field_tag]"]' => ['value' => 'none'],
            ],
          ],
        ];
        $form['fields'][$name]['wrappers']['label_tag'] = [
          '#type' => 'select',
          '#title' => $this->t('Label tag'),
          '#description' => $this->t('Choose the HTML element to wrap around this label.'),
          '#options' => $tag_options,
          '#empty_option' => $this->t('- Use default -'),
          '#default_value' => $wrapper_settings['label_tag'] ?? '',
          '#states' => [
            'visible' => [
              ':input[name="' . $visibility_path . '[label_display]"]' => ['!value' => 'hidden'],
            ],
          ],
        ];
        $form['fields'][$name]['wrappers']['label_classes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Label classes'),
          '#description' => $this->t('Enter additional classes, separated by space.'),
          '#default_value' => $wrapper_settings['label_classes'] ?? '',
          '#states' => [
            'visible' => [
              ':input[name="' . $visibility_path . '[label_display]"]' => ['!value' => 'hidden'],
            ],
            'invisible' => [
              ':input[name="' . $root_visibility_path . '[wrappers][label_tag]"]' => ['value' => 'none'],
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
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $custom_fields = $this->getCustomFieldItems();
    $settings = $this->getSetting('fields');
    foreach ($custom_fields as $id => $custom_field) {
      $formatter_options = $this->customFieldFormatterManager->getOptions($custom_field);
      $format_type = $custom_field->getDefaultFormatter();
      if (isset($settings[$id]['format_type']) && isset($formatter_options[$settings[$id]['format_type']])) {
        $format_type = $settings[$id]['format_type'];
      }
      try {
        $definition = $this->customFieldFormatterManager->getDefinition($format_type);
      }
      catch (\Exception $exception) {
        // Silent fail, for now.
        continue;
      }

      $field_label = $custom_field->getLabel();
      $format_label = $definition['label'];
      $formatted_summary = new FormattableMarkup(
        '<strong>@label</strong>: @format_label', [
          '@label' => $field_label,
          '@format_label' => $format_label,
        ]
      );
      $summary[] = $this->t('@summary', ['@summary' => $formatted_summary]);
    }

    return $summary;
  }

  /**
   * Ajax callback for changing format type.
   *
   * Selects and returns the fieldset with the names in it.
   *
   * @param array<string, mixed> $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The updated form element.
   */
  public function actionCallback(array $form, FormStateInterface $form_state): AjaxResponse {
    $trigger = $form_state->getTriggeringElement();
    $wrapper_id = $trigger['#ajax']['wrapper'];

    // Get the current parent array for this widget.
    $parents = $trigger['#array_parents'];
    $sliced_parents = array_slice($parents, 0, -1, TRUE);

    // Get the updated element from the form structure.
    $updated_element = NestedArray::getValue($form, $sliced_parents)['formatter_settings'];

    // Create an AjaxResponse.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#' . $wrapper_id, $updated_element));

    return $response;
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\custom_field\Plugin\Field\FieldType\CustomItem> $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array<int, mixed>
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item, $langcode);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\custom_field\Plugin\Field\FieldType\CustomItem>[] $entities_items
   *   An array with the field values from the multiple entities being rendered.
   */
  public function prepareView(array $entities_items): void {
    $ids = [];
    $custom_items = $this->getCustomFieldItems();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        foreach ($custom_items as $custom_item) {
          $target_type = $custom_item->getTargetType();
          $value = $item->{$custom_item->getName()};
          if (!empty($target_type) && !empty($value)) {
            $ids[$target_type][] = $value;
          }
        }
      }
    }
    if ($ids) {
      foreach ($ids as $target_type => $entity_ids) {
        try {
          $target_entities[$target_type] = $this->entityTypeManager->getStorage($target_type)->loadMultiple($entity_ids);
        }
        catch (\Exception $exception) {
          // Silent fail, for now.
        }
      }
    }
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        foreach ($custom_items as $custom_item) {
          $target_type = $custom_item->getTargetType();
          $value = $item->{$custom_item->getName()};
          if (!empty($target_type) && !empty($value)) {
            if (isset($target_entities[$target_type][$value])) {
              $item->{$custom_item->getName()} = ['entity' => $target_entities[$target_type][$value]];
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewValue(FieldItemInterface $item, string $langcode): array {
    $field_name = $this->fieldDefinition->getName();
    $output = [
      '#theme' => 'custom_field',
      '#field_name' => $field_name,
      '#items' => [],
    ];

    $values = $this->getFormattedValues($item, $langcode);

    foreach ($values as $value) {
      if ($value !== NULL && $value !== '') {
        $output['#items'][] = [
          '#theme' => 'custom_field_item',
          '#field_name' => $field_name,
          '#name' => $value['name'],
          '#value' => $value['value']['#markup'],
          '#label' => $value['label'],
          '#label_display' => $value['label_display'],
          '#type' => $value['type'],
          '#wrappers' => $value['wrappers'],
          '#entity_type' => $value['entity_type'],
          '#lang_code' => $langcode,
        ];
      }
    }

    return $output;
  }

  /**
   * Get the custom field items for this field.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   An array of custom field items.
   */
  public function getCustomFieldItems(): array {
    return $this->customFieldManager->getCustomFieldItems($this->fieldDefinition->getSettings());
  }

  /**
   * Returns an array of formatted custom field item values for a singe field.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param string $langcode
   *   The language code.
   *
   * @return array<string, mixed>
   *   An array of formatted values.
   */
  protected function getFormattedValues(FieldItemInterface $item, string $langcode): array {
    $settings = $this->getSetting('fields');
    $values = [];
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      $value = $custom_item->value($item);
      $data_type = $custom_item->getDataType();
      if ($value === '' || $value === NULL) {
        continue;
      }
      if ($data_type === 'viewfield') {
        $value = [
          'target_id' => $value,
          'display_id' => $item->{$name . '__display'},
          'arguments' => $item->{$name . '__arguments'},
          'items_to_display' => $item->{$name . '__items'},
        ];
      }
      elseif ($data_type === 'uri') {
        $value = [
          'uri' => $value,
        ];
      }
      elseif ($data_type === 'link') {
        $value = [
          'uri' => $value,
          'title' => $item->{$name . '__title'},
          'options' => $item->{$name . '__options'},
        ];
      }
      elseif (in_array($data_type, ['entity_reference', 'file', 'image'])) {
        $entity = $item->{$name . '__entity'};
        if (!$entity instanceof EntityInterface) {
          continue;
        }
        if ($entity instanceof TranslatableInterface) {
          $entity = $this->entityRepository->getTranslationFromContext($entity, $langcode);
        }
        $value = $entity;
      }

      $default_wrappers = [
        'field_wrapper_tag' => '',
        'field_wrapper_classes' => '',
        'field_tag' => '',
        'field_classes' => '',
        'label_tag' => '',
        'label_classes' => '',
      ];

      $wrappers = $settings[$name]['wrappers'] ?? $default_wrappers;
      $formatter_settings = [
        'format_type' => $settings[$name]['format_type'] ?? NULL,
        'formatter_settings' => $settings[$name]['formatter_settings'] ?? [],
        'wrappers' => array_merge($default_wrappers, $wrappers),
      ];

      $format_type = $custom_item->getDefaultFormatter();
      // Get the available formatter options for this field type.
      $formatter_options = $this->customFieldFormatterManager->getOptions($custom_item);
      if (!empty($formatter_settings['format_type']) && isset($formatter_options[$formatter_settings['format_type']])) {
        $format_type = $formatter_settings['format_type'];
      }

      $options = $this->customFieldFormatterManager->createOptionsForInstance($custom_item, $format_type, $formatter_settings['formatter_settings'], $this->viewMode);
      /** @var \Drupal\custom_field\Plugin\CustomFieldFormatterInterface $plugin */
      $plugin = $this->customFieldFormatterManager->getInstance($options);
      $value = $plugin->formatValue($item, $value);
      if ($value === '' || $value === NULL) {
        continue;
      }

      $formatter_settings['formatter_settings'] += $plugin::defaultSettings();
      $field_label = $formatter_settings['formatter_settings']['field_label'] ?? NULL;

      $markup = [
        'name' => $name,
        'value' => [
          '#markup' => $value,
        ],
        'label' => !empty($field_label) ? $field_label : $custom_item->getLabel(),
        'label_display' => $formatter_settings['formatter_settings']['label_display'] ?? 'above',
        'type' => $custom_item->getPluginId(),
        'wrappers' => $formatter_settings['wrappers'],
        'entity_type' => $entity_type,
      ];

      $values[$name] = $markup;
    }

    return $values;
  }

  /**
   * Returns an array of visibility options for custom field labels.
   *
   * Copied from Drupal\field_ui\Form\EntityViewDisplayEditForm (can't call
   * directly since it's protected)
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   An array of visibility options.
   */
  protected function fieldLabelOptions(): array {
    return [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually hidden') . ' -',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, string[]>
   *   An array of dependencies grouped by type (config, content, module,
   *   theme). For example:
   *   @code
   *   [
   *     'config' => ['user.role.anonymous', 'user.role.authenticated'],
   *     'content' => ['node:article:f0a189e6-55fb-47fb-8005-5bef81c44d6d'],
   *     'module' => ['node', 'user'],
   *     'theme' => ['claro'],
   *   ];
   *   @endcode
   */
  public function calculateDependencies(): array {
    $dependencies = parent::calculateDependencies();
    $fields = $this->getSetting('fields');
    if (!empty($fields)) {
      foreach ($fields as $field) {
        $formatter_settings = $field['formatter_settings'] ?? [];
        if (empty($formatter_settings)) {
          continue;
        }
        try {
          $plugin = $this->customFieldFormatterManager->createInstance($field['format_type']);
          assert($plugin instanceof CustomFieldFormatterInterface);
          $plugin_dependencies = $plugin->calculateFormatterDependencies($formatter_settings);
          $dependencies = \array_merge_recursive($dependencies, $plugin_dependencies);
        }
        catch (PluginException $e) {
          // No dependencies applicable if we somehow have invalid plugin.
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, string[]> $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *   Dependency types are 'config', 'content', 'module' and 'theme'.
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    $settings_changed = FALSE;
    $fields = $this->getSetting('fields');
    foreach ($fields as $name => $field) {
      if (!isset($field['formatter_settings'])) {
        continue;
      }

      try {
        $plugin = $this->customFieldFormatterManager->createInstance($field['format_type']);
        if ($plugin instanceof CustomFieldFormatterInterface) {
          $changed_settings = $plugin->onFormatterDependencyRemoval($dependencies, $field['formatter_settings']);
          if (!empty($changed_settings)) {
            $fields[$name]['formatter_settings'] = $changed_settings;
            $settings_changed = TRUE;
          }
        }
      }
      catch (\Exception $exception) {
        // Silent fail, for now.
      }
    }

    if ($settings_changed) {
      $this->setSetting('fields', $fields);
    }
    $changed |= $settings_changed;

    return (bool) $changed;
  }

}
