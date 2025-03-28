<?php

namespace Drupal\custom_field\Plugin\Field\FieldType;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Plugin implementation of the 'custom' field type.
 *
 * @FieldType(
 *   id = "custom",
 *   label = @Translation("Custom field"),
 *   description = @Translation("A field of fields stored in a single table."),
 *   default_widget = "custom_stacked",
 *   default_formatter = "custom_formatter",
 *   list_class = "\Drupal\custom_field\Plugin\Field\FieldType\CustomItemList",
 * )
 */
class CustomItem extends FieldItemBase {

  use StringTranslationTrait;

  /**
   * The custom field separator for extended properties.
   *
   * @var
   */
  const SEPARATOR = '__';

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings(): array {
    // Need to have at least one item by default because the table is created
    // before the user gets a chance to customize and will throw an Exception
    // if there isn't at least one column defined.
    return [
      'columns' => [
        'value' => [
          'name' => 'value',
          'max_length' => 255,
          'type' => 'string',
          'unsigned' => FALSE,
          'scale' => 2,
          'precision' => 10,
          'size' => 'normal',
          'datetime_type' => CustomFieldTypeInterface::DATETIME_TYPE_DATETIME,
          'uri_scheme' => \Drupal::config('system.file')->get('default_scheme'),
        ],
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $columns = [];
    foreach ($field_definition->getSetting('columns') as $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      $field_schema = $plugin->schema($item);
      $columns += $field_schema;
    }

    $schema['columns'] = $columns;

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $properties = [];

    foreach ($field_definition->getSetting('columns') as $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      $definitions = $plugin->propertyDefinitions($item);
      if (is_array($definitions)) {
        $properties += $definitions;
      }
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition): array {
    $settings = $field_definition->getSettings();
    $field_manager = \Drupal::service('plugin.manager.custom_field_type');
    $custom_items = $field_manager->getCustomFieldItems($settings);
    $target_entity_type = $field_definition->getTargetEntityTypeId();
    $values = [];
    foreach ($custom_items as $name => $custom_item) {
      assert($custom_item instanceof CustomFieldTypeInterface);
      $values[$name] = $custom_item->generateSampleValue($custom_item, $target_entity_type);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(): array {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $field_constraints = [];
    $field_settings = $this->getSetting('field_settings');
    foreach ($this->getSetting('columns') as $id => $item) {
      $plugin = $plugin_service->createInstance($item['type']);
      if (method_exists($plugin, 'getConstraints')) {
        $widget_settings = $field_settings[$id]['widget_settings']['settings'] ?? [];
        $settings = $item;
        if (isset($widget_settings['min'])) {
          $settings['min'] = $widget_settings['min'];
        }
        if (isset($widget_settings['max'])) {
          $settings['max'] = $widget_settings['max'];
        }
        $field_constraints[$item['name']] = $plugin->getConstraints($settings);
      }
    }
    $constraints[] = $constraint_manager->create('ComplexData', $field_constraints);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    $field_definition = $this->getFieldDefinition();
    $field_name = $field_definition->getName();
    $custom_items = $this->getCustomFieldManager()->getCustomFieldItems($field_definition->getSettings());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $is_default_translation = FALSE;
    $has_translations = FALSE;
    $original_entity = $entity;

    if (!$entity->isNew() && $entity->isTranslatable() && $field_definition->isTranslatable()) {
      $is_default_translation = $entity->isDefaultTranslation();
      $has_translations = count($entity->getTranslationLanguages()) > 1;
      $original_entity = $has_translations ? $entity->getUntranslated() : $entity;
    }

    // Get the fields from the original or current entity based on whether it
    // has translations.
    /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomFieldItemListInterface[] $originalFields */
    $original_fields = $original_entity->get($field_name);
    foreach ($original_fields as $delta => $original_field) {
      $current_field = $entity->get($field_name)->get($delta);
      foreach ($custom_items as $name => $custom_item) {
        $field_type = $custom_item->getDataType();
        $is_subfield_translatable = $custom_item->getWidgetSetting('translatable') ?? FALSE;

        // The synchronization logic only applies if the entity supports
        // translations and we're not in the default language.
        if ($has_translations && !$is_default_translation && !$is_subfield_translatable) {
          // Fetch the value from the default language for this delta.
          $default_value = $original_field->{$name};
          if (!empty($default_value)) {
            $current_field->{$name} = $default_value;
            // Set extra default language properties for image.
            if ($field_type === 'image') {
              $current_field->{$name} = $default_value;
              $alt = $original_field->{$name . self::SEPARATOR . 'alt'};
              $title = $original_field->{$name . self::SEPARATOR . 'title'};
              $width = $original_field->{$name . self::SEPARATOR . 'width'};
              $height = $original_field->{$name . self::SEPARATOR . 'height'};
              $current_field->{$name . self::SEPARATOR . 'alt'} = $alt;
              $current_field->{$name . self::SEPARATOR . 'title'} = $title;
              $current_field->{$name . self::SEPARATOR . 'width'} = $width;
              $current_field->{$name . self::SEPARATOR . 'height'} = $height;
            }
          }
        }

        // Set subfield value to current applicable translation value.
        $subfield_value = $current_field->{$name};

        // Existing field type handling logic, which should work for all cases:
        switch ($field_type) {
          case 'color':
            $color = is_string($subfield_value) ? trim($subfield_value) : '';

            if (str_starts_with($color, '#')) {
              $color = substr($color, 1);
            }

            // Make sure we have a valid hexadecimal color.
            $current_field->{$name} = strlen($color) === 6 ? '#' . strtoupper($color) : NULL;
            break;

          case 'map':
          case 'map_string':
            if (!is_array($subfield_value) || empty($subfield_value)) {
              $current_field->{$name} = NULL;
            }
            else {
              $current_field->{$name} = array_values($subfield_value);
            }
            break;

          case 'image':
            if (!empty($subfield_value)) {
              $width = $current_field->get($name . self::SEPARATOR . 'width')->getValue();
              $height = $current_field->get($name . self::SEPARATOR . 'height')->getValue();
              if (empty($width) || empty($height)) {
                $file = \Drupal::entityTypeManager()
                  ->getStorage('file')
                  ->load($subfield_value);
                if ($file) {
                  $image = \Drupal::service('image.factory')->get($file->getFileUri());
                  if ($image->isValid()) {
                    $current_field->{$name . self::SEPARATOR . 'width'} = $image->getWidth();
                    $current_field->{$name . self::SEPARATOR . 'height'} = $image->getHeight();
                  }
                }
              }
            }
            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data): array {
    assert($form_state instanceof SubformStateInterface);
    $form_state = $form_state->getCompleteFormState();
    $default_settings = self::defaultStorageSettings()['columns']['value'];
    $wrapper_id = 'custom-field-storage-wrapper';
    $parents = ['field_storage', 'subform', 'settings'];
    $storage = $form_state->getStorage();
    $settings = $this->getSettings();
    $current_settings = $form_state->get('current_settings');
    $field_name = $this->getFieldDefinition()->getName();
    // Calculate a safe max column length to coincide with SQL column limit.
    $max_name_length = 64 - strlen($field_name) - 12;
    if (empty($current_settings)) {
      $form_state->set('current_settings', $this->getSettings());
    }

    if ($form_state->isRebuilding()) {
      $settings['items'] = $form_state->getValue([...$parents, 'items']) ?? $current_settings['columns'];
      $field_settings = $form_state->getValue(['settings', 'field_settings']) ?? $current_settings['field_settings'];
      $current_columns = $current_settings['columns'];
      $columns = $settings['items'];
      foreach ($settings['items'] as $name => $item) {
        unset($item['remove']);
        if ($name != $item['name']) {
          $settings['items'][$item['name']] = $item;
          $columns[$item['name']] = $item;
          unset($settings['items'][$name]);
          unset($columns[$name]);
          if (isset($field_settings[$name])) {
            unset($field_settings[$name]);
          }
        }
        elseif (isset($current_columns[$name])) {
          $diffs = array_diff($item, $current_columns[$name]);
          if (!empty($diffs)) {
            $columns[$name] = $item;
            if (isset($field_settings[$name])) {
              unset($field_settings[$name]);
            }
          }
          if ($item['type'] !== $current_columns[$name]['type']) {
            if (isset($field_settings[$name])) {
              unset($field_settings[$name]);
            }
            if ($item['type'] === 'entity_reference') {
              $settings['items'][$name]['target_type'] = NULL;
              // Force the selection of target type.
              unset($columns[$name]);
            }
            elseif (in_array($item['type'], ['file', 'image'])) {
              $settings['items'][$name]['target_type'] = 'file';
            }
            elseif ($item['type'] === 'viewfield') {
              $settings['items'][$name]['target_type'] = 'view';
            }
          }
        }
      }
      $form_state->set('current_settings', [
        'columns' => $columns,
        'field_settings' => $field_settings,
      ]);
    }
    else {
      $settings['items'] = $settings['columns'];
    }

    $element = [
      '#tree' => TRUE,
      'columns' => [
        '#type' => 'value',
        '#value' => $settings['columns'],
        '#parents' => [...$parents, 'columns'],
      ],
      'items' => [
        '#type' => 'fieldset',
        '#parents' => [...$parents, 'items'],
        '#title' => $this->t('Custom field items'),
        '#prefix' => '<div id="' . $wrapper_id . '">',
        '#suffix' => '</div>',
      ],
      'actions' => [
        '#type' => 'actions',
      ],
    ];

    $items_count = count($settings['items']);

    // Support copying settings from another custom field.
    if (!$has_data) {
      $sources = $this->getExistingCustomFieldStorageOptions($storage['entity_type_id']);
      if (!empty($sources)) {
        $element['clone'] = [
          '#type' => 'select',
          '#title' => $this->t('Clone settings from:'),
          '#description' => $this->t('Copy configuration from an existing field.'),
          '#options' => [
            '' => $this->t("- Don't clone settings -"),
          ] + $sources,
          '#attributes' => [
            'data-id' => 'custom-field-storage-clone',
          ],
          '#weight' => -10,
        ];
        $element['clone_message'] = [
          '#type' => 'container',
          '#states' => [
            'invisible' => [
              'select[data-id="custom-field-storage-clone"]' => ['value' => ''],
            ],
          ],
          // Initialize the display, so we don't see it flash on init page load.
          '#attributes' => [
            'style' => 'display: none;',
          ],
        ];
        $element['clone_message']['message'] = [
          '#markup' => 'The selected custom field field settings will be cloned. Any existing settings for this field will be overwritten. Field widget and formatter settings will not be cloned.',
          '#prefix' => '<div class="messages messages--warning" role="alert" style="display: block;">',
          '#suffix' => '</div>',
        ];
        // Add states to items.
        $element['items']['#states'] = [
          'visible' => [
            'select[data-id="custom-field-storage-clone"]' => ['value' => ''],
          ],
        ];
      }
    }

    foreach ($settings['items'] as $i => $item) {
      $type = $item['type'] ?? '';
      $element['items'][$i]['name'] = [
        '#type' => 'machine_name',
        '#description' => $this->t('A unique machine-readable name containing only letters, numbers, or underscores. This will be used in the column name on the field table in the database.'),
        '#default_value' => $item['name'],
        '#disabled' => $has_data,
        '#machine_name' => [
          'exists' => [$this, 'machineNameExists'],
          'label' => $this->t('Machine-readable name'),
          'standalone' => TRUE,
        ],
        '#maxlength' => $max_name_length,
      ];
      $element['items'][$i]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => $this->getCustomFieldManager()->fieldTypeOptions(),
        '#default_value' => $type,
        '#delta' => $i,
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
        '#disabled' => $has_data,
      ];
      $element['items'][$i]['max_length'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum length'),
        '#default_value' => !empty($item['max_length']) ? $item['max_length'] : $default_settings['max_length'],
        '#required' => TRUE,
        '#description' => $this->t('The maximum length of the field in characters.'),
        '#min' => 1,
        '#max' => $type === 'telephone' ? 256 : 255,
        '#disabled' => $has_data,
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => [
              ['value' => 'string'],
              ['value' => 'telephone'],
            ],
          ],
        ],
      ];
      $element['items'][$i]['size'] = [
        '#type' => 'select',
        '#title' => $this->t('Size'),
        '#default_value' => $item['size'] ?? $default_settings['size'],
        '#disabled' => $has_data,
        '#options' => [
          'tiny' => $this->t('Tiny'),
          'small' => $this->t('Small'),
          'medium' => $this->t('Medium'),
          'big' => $this->t('Big'),
          'normal' => $this->t('Normal'),
        ],
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => [
              ['value' => 'integer'],
              ['value' => 'float'],
            ],
          ],
        ],
      ];
      $element['items'][$i]['unsigned'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Unsigned'),
        '#default_value' => $item['unsigned'] ?? $default_settings['unsigned'],
        '#disabled' => $has_data,
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => [
              ['value' => 'integer'],
              ['value' => 'float'],
              ['value' => 'decimal'],
            ],
          ],
        ],
      ];
      $element['items'][$i]['precision'] = [
        '#type' => 'number',
        '#title' => $this->t('Precision'),
        '#min' => 10,
        '#max' => 32,
        '#default_value' => $item['precision'] ?? $default_settings['precision'],
        '#description' => $this->t('The total number of digits to store in the database, including those to the right of the decimal.'),
        '#disabled' => $has_data,
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => ['value' => 'decimal'],
          ],
        ],
      ];
      $element['items'][$i]['scale'] = [
        '#type' => 'number',
        '#title' => $this->t('Scale'),
        '#description' => $this->t('The number of digits to the right of the decimal.'),
        '#default_value' => $item['scale'] ?? $default_settings['scale'],
        '#disabled' => $has_data,
        '#min' => 0,
        '#max' => 10,
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => ['value' => 'decimal'],
          ],
        ],
      ];
      $element['items'][$i]['datetime_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Date type'),
        '#description' => $this->t('Choose the type of date to create.'),
        '#default_value' => $item['datetime_type'] ?? $default_settings['datetime_type'],
        '#disabled' => $has_data,
        '#options' => [
          CustomFieldTypeInterface::DATETIME_TYPE_DATETIME => $this->t('Date and time'),
          CustomFieldTypeInterface::DATETIME_TYPE_DATE => $this->t('Date only'),
        ],
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name*="[items][' . $i . '][type]"]' => ['value' => 'datetime'],
          ],
        ],
      ];
      if ($type === 'entity_reference') {
        // Only allow the field to target entity types that have an ID key. This
        // is enforced in ::propertyDefinitions().
        $entity_type_manager = \Drupal::entityTypeManager();
        $filter = function (string $entity_type_id) use ($entity_type_manager): bool {
          return $entity_type_manager->getDefinition($entity_type_id)
            ->hasKey('id');
        };
        $options = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE);

        $element['items'][$i]['target_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Type of item to reference'),
          '#default_value' => $item['target_type'] ?? NULL,
          '#required' => TRUE,
          '#disabled' => $has_data,
          '#size' => 1,
        ];
        foreach ($options as $group_name => $group) {
          $element['items'][$i]['target_type']['#options'][$group_name] = array_filter($group, $filter, ARRAY_FILTER_USE_KEY);
        }
      }
      if ($type === 'file' || $type === 'image') {
        $element['#attached']['library'][] = 'file/drupal.file';
        $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
        $element['items'][$i]['uri_scheme'] = [
          '#type' => 'radios',
          '#title' => $this->t('Upload destination'),
          '#options' => $scheme_options,
          '#default_value' => $item['uri_scheme'] ?? $default_settings['uri_scheme'],
          '#description' => $this->t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
          '#disabled' => $has_data,
        ];
        $element['items'][$i]['target_type'] = [
          '#type' => 'value',
          '#value' => 'file',
        ];
      }
      elseif ($type === 'viewfield') {
        $element['items'][$i]['target_type'] = [
          '#type' => 'value',
          '#value' => 'view',
        ];
      }
      $element['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [get_class($this) . '::removeSubmit'],
        '#name' => 'remove:' . $i,
        '#delta' => $i,
        '#access' => !($has_data || $items_count === 1),
        '#attributes' => [
          'id' => 'remove_' . $i,
        ],
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    if (!$has_data) {
      $element['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#submit' => [get_class($this) . '::addSubmit'],
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];
      if (!empty($sources)) {
        $element['actions']['add']['#states'] = [
          'visible' => [
            'select[data-id="custom-field-storage-clone"]' => ['value' => ''],
          ],
        ];
      }
    }

    $form_state->setCached(FALSE);

    return $element;
  }

  /**
   * Submit handler for the StorageConfigEditForm.
   *
   * This handler is added in custom_field.module since it has to be placed
   * directly on the submit button (which we don't have access to in our
   * ::storageSettingsForm() method above).
   */
  public static function submitStorageConfigEditForm(array &$form, FormStateInterface $form_state) {
    // Rekey our column settings and overwrite the values in form_state so that
    // we have clean settings saved to the db.
    $columns = [];
    $parents = ['field_storage', 'subform', 'settings'];
    $item_parents = ['field_storage', 'subform', 'settings', 'items'];
    /** @var \Drupal\Field\FieldConfigInterface $field_config */
    $field_config = $form_state->get('field_config');

    if ($field_name = $form_state->getValue([...$parents, 'clone'])) {
      [$entity_type, $bundle_name, $field_name] = explode('.', $field_name);
      // Grab the columns from the field storage config.
      $columns = FieldStorageConfig::loadByName($entity_type, $field_name)->getSetting('columns');
      // Grab the field settings too as a starting point.
      $source_field_config = FieldConfig::loadByName($entity_type, $bundle_name, $field_name);
      $field_config->setSettings($source_field_config->getSettings());
    }
    else {
      $items = $form_state->getValue($item_parents) ?? [];
      foreach ($items as $item) {
        $columns[$item['name']] = $item;
        unset($columns[$item['name']]['remove']);
      }
    }
    $form_state->setValue(['field_storage', 'subform', 'settings', 'columns'], $columns);
    $form_state->setValue(['field_storage', 'subform', 'settings', 'items'], NULL);

    // Reset the field storage config property - it will be recalculated when
    // accessed via the property definitions getter.
    // @see Drupal\field\Entity\FieldStorageConfig::getPropertyDefinitions()
    // If we don't do this, an exception is thrown during the table update that
    // is very difficult to recover from since the original field tables have
    // already been removed at that point.
    $field_storage_config = $form_state->getBuildInfo()['callback_object']->getEntity();
    $field_storage_config->set('propertyDefinitions', NULL);
  }

  /**
   * Check for duplicate names on our columns settings.
   */
  public function machineNameExists($value, array $form, FormStateInterface $form_state): bool {
    $count = 0;
    $parents = ['field_storage', 'subform', 'settings', 'items'];
    $settings = $form_state->getValue($parents) ?? [];
    foreach ($settings as $item) {
      if ($item['name'] == $value) {
        $count++;
      }
    }

    return $count > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty(): bool {
    $settings = $this->getSettings();
    $custom_items = $this->getCustomFieldManager()->getCustomFieldItems($settings);
    $emptyCounter = 0;
    $field_count = count($custom_items);
    foreach ($custom_items as $name => $custom_item) {
      $definition = $custom_item->getPluginDefinition();
      $check = $custom_item->checkEmpty();
      $no_check = array_key_exists('never_check_empty', $definition) && $definition['never_check_empty'];
      $item_value = $this->get($name)->getValue();
      if ($item_value === '' || ($item_value === NULL && !$no_check)) {
        $emptyCounter++;
        // If any of the empty check fields are filled or all fields are empty.
        if ($check || $emptyCounter === $field_count) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Callback for both ajax-enabled buttons in storage form.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#delta'])) {
      $field = $trigger['#delta'];
      $field_settings = $form_state->getValue(['settings', 'field_settings']);
      if (!empty($field_settings[$field])) {
        unset($field_settings[$field]);
        $form_state->setValue(['settings', 'field_settings'], $field_settings);
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('input[name="field_storage_submit"]', 'click'));

    return $response;
  }

  /**
   * Submit handler for the "Add another" button.
   *
   * Triggers form state notice to add item and causes a form rebuild.
   */
  public static function addSubmit(array &$form, FormStateInterface $form_state): void {
    $storage = $form_state->getValue('field_storage');
    $default_name = uniqid('value_');
    $default = static::defaultStorageSettings()['columns']['value'];
    unset($default['name']);
    $storage['subform']['settings']['items'][$default_name] = [
      'name' => $default_name,
    ] + $default;
    $form_state->setValue('field_storage', $storage);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove" button.
   *
   * Triggers form state notice to remove item and causes a form rebuild.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state): void {
    $remove = $form_state->getTriggeringElement()['#delta'];
    $settings = $form_state->getValue('settings');
    $storage = $form_state->getValue('field_storage');
    unset($storage['subform']['settings']['items'][$remove]);
    // Remove the field setting if it exists.
    if (isset($settings['field_settings'][$remove])) {
      unset($settings['field_settings'][$remove]);
      $form_state->setValue('settings', $settings);
    }
    $form_state->setValue('field_storage', $storage);
    $form_state->setRebuild();
  }

  /**
   * Get the existing custom field storage config options.
   *
   * @param string $entity_type_id
   *   The entity type to match for exclusion.
   *
   * @return array
   *   An array of existing field configurations.
   */
  protected function getExistingCustomFieldStorageOptions(string $entity_type_id): array {
    $sources = [];
    $existingCustomFields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('custom');
    $existing_field_name = $this->getFieldDefinition()->getName();
    if (!empty($existingCustomFields)) {
      foreach ($existingCustomFields as $entity_type => $fields) {
        $bundleInfo = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        foreach ($fields as $field_name => $info) {
          if ($entity_type === $entity_type_id && $existing_field_name == $field_name) {
            continue;
          }
          foreach ($info['bundles'] as $bundle) {
            $group = $bundleInfo[$bundle]['label'] . ' (' . $entity_type . ')' ?? '';
            if ($info = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
              $sources[$group][$entity_type . '.' . $bundle . '.' . $info->getName()] = $info->getLabel();
            }
          }
        }
      }
    }
    return $sources;
  }

  /**
   * Default widget settings.
   *
   * @param string $label
   *   Column name to convert to label.
   *
   * @return array
   *   An array of default widget settings.
   */
  public static function defaultWidgetSettings(string $label): array {
    return [
      'label' => ucfirst(str_replace(['-', '_'], ' ', $label)),
      'settings' => [
        'description' => '',
        'description_display' => 'after',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings(): array {
    return [
      'field_settings' => [],
      'add_more_label' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager $widget_manager */
    $widget_manager = \Drupal::service('plugin.manager.custom_field_widget');
    $wrapper_id = 'custom-field-settings-wrapper';
    $is_cloning = FALSE;

    if ($form_state->isRebuilding()) {
      $storage = $form_state->getValue('field_storage');
      $is_cloning = !empty($storage['subform']['settings']['clone']);
    }

    $element['add_more_label'] = [
      '#type' => 'textfield',
      '#title' => t('Add another button label'),
      '#description' => t('The add button label for multiple items. Leave empty for default button text.'),
      '#weight' => -100,
      '#default_value' => $this->getSetting('add_more_label'),
      '#attributes' => [
        'disabled' => !$this->getFieldDefinition()->getFieldStorageDefinition()->isMultiple(),
      ],
    ];

    $element['field_settings'] = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Form element'),
        $this->t('Settings'),
        $this->t('Check empty?'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
      '#attributes' => [
        'class' => ['customfield-settings-table'],
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-settings-order-weight',
        ],
      ],
      '#attached' => [
        'library' => ['custom_field/customfield-admin'],
      ],
      '#weight' => -99,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#access' => !$is_cloning,
    ];

    $current_settings = $form_state->get('current_settings') ?? $this->getSettings();
    $custom_items = $this->getCustomFieldManager()->getCustomFieldItems($current_settings);

    // Build the table rows and columns.
    foreach ($custom_items as $name => $custom_item) {
      $plugin_id = $custom_item->getPluginId();

      // UUid fields have no configuration.
      if ($plugin_id === 'uuid') {
        continue;
      }
      $definition = $custom_item->getPluginDefinition();
      $weight = $current_settings['field_settings'][$name]['weight'] ?? 0;

      $element['field_settings'][$name] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $weight,
      ];

      $element['field_settings'][$name]['handle'] = [
        '#type' => 'markup',
        '#markup' => '<span></span>',
      ];

      $options = static::getCustomFieldWidgetOptions($custom_item);
      $widget_type = $current_settings['field_settings'][$name]['type'] ?? NULL;
      if (!empty($widget_type) && in_array($widget_type, $widget_manager->getWidgetsForField($plugin_id))) {
        $type = $widget_type;
      }
      else {
        $type = $custom_item->getDefaultWidget();
      }

      $options_count = count($options);

      $element['field_settings'][$name]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('%name widget', ['%name' => $name]),
        '#options' => $options,
        '#default_value' => $type,
        '#value' => $type,
        '#ajax' => [
          'callback' => [$this, 'widgetSelectionCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#attributes' => [
          'disabled' => $options_count <= 1,
        ],
      ];

      // Add our plugin widget settings form.
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $widget */
      $widget = $widget_manager->createInstance($type, ['settings' => $custom_item->getWidgetSetting('settings')]);
      $element['field_settings'][$name]['widget_settings'] = $widget->widgetSettingsForm($form_state, $custom_item);

      $element['field_settings'][$name]['check_empty'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Check empty?'),
        '#description' => $this->t('Remove row when this value is empty.'),
        '#default_value' => $current_settings['field_settings'][$name]['check_empty'] ?? FALSE,
      ];

      if (!empty($definition['never_check_empty'])) {
        $element['field_settings'][$name]['check_empty']['#default_value'] = FALSE;
        $element['field_settings'][$name]['check_empty']['#disabled'] = TRUE;
        $element['field_settings'][$name]['check_empty']['#description'] = $this->t("<em>This custom field type can't be empty checked.</em>");
      }

      // TableDrag: Weight column element.
      $element['field_settings'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $name]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['field-settings-order-weight']],
      ];
    }

    return $element;
  }

  /**
   * Callback for widget type select.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function widgetSelectionCallback(array &$form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    $last_key = array_key_last($parents);
    $input = 'settings[field_settings][' . $parents[$last_key] . '][widget_settings][label]';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#custom-field-settings-wrapper', $form['settings']['field_settings']));
    $response->addCommand(new InvokeCommand('input[name="' . $input . '"]', 'focus'));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(FieldDefinitionInterface $field_definition) {
    $dependencies = parent::calculateDependencies($field_definition);
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $custom_items = $plugin_service->getCustomFieldItems($field_definition->getSettings());
    $default_value = $field_definition->getDefaultValueLiteral();

    foreach ($custom_items as $custom_item) {
      $plugin = $plugin_service->createInstance($custom_item->getPluginId());
      $plugin_dependencies = $plugin->calculateDependencies($custom_item, $default_value);
      $dependencies = array_merge_recursive($dependencies, $plugin_dependencies);
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateStorageDependencies(FieldStorageDefinitionInterface $field_definition) {
    $dependencies = parent::calculateStorageDependencies($field_definition);
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    $columns = $field_definition->getSetting('columns');
    foreach ($columns as $column) {
      /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
      $plugin = $plugin_service->createInstance($column['type']);
      $plugin_dependencies = $plugin->calculateStorageDependencies($column);
      $dependencies = array_merge_recursive($dependencies, $plugin_dependencies);
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(FieldDefinitionInterface $field_definition, array $dependencies) {
    $changed = parent::onDependencyRemoval($field_definition, $dependencies);
    $settings = $field_definition->getSettings();
    $columns = $settings['columns'];
    $field_settings = $settings['field_settings'];
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_type');
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface[] $custom_items */
    $custom_items = $plugin_service->getCustomFieldItems($settings);
    $settings_changed = FALSE;

    // Try to update the default value config dependency, if possible.
    if ($default_value = $field_definition->getDefaultValueLiteral()) {
      $entity_type_manager = \Drupal::entityTypeManager();
      foreach ($default_value as $key => $value) {
        foreach ($value as $column_key => $column_value) {
          if (isset($columns[$column_key])) {
            $column = $columns[(string) $column_key];
            if (isset($column['target_type']) && !empty($column_value)) {
              $entity = $entity_type_manager->getStorage($column['target_type'])
                ->load($column_value);
              if ($entity && isset($dependencies[$entity->getConfigDependencyKey()][$entity->getConfigDependencyName()])) {
                $default_value[$key][$column_key] = NULL;
                $changed = TRUE;
              }
            }
          }
        }
      }
    }
    if ($changed) {
      $field_definition->setDefaultValue($default_value);
    }

    foreach ($custom_items as $name => $custom_item) {
      /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
      $plugin = $plugin_service->createInstance($custom_item->getPluginId());
      $widget_settings = $plugin->onDependencyRemoval($custom_item, $dependencies);
      if (!empty($widget_settings)) {
        $field_settings[$name]['widget_settings']['settings'] = $widget_settings;
        $settings_changed = TRUE;
      }
    }

    if ($settings_changed) {
      $field_definition->setSetting('field_settings', $field_settings);
    }

    $changed |= $settings_changed;

    return $changed;
  }

  /**
   * Return the available widget plugins as an array keyed by plugin_id.
   *
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeInterface $custom_item
   *   The Custom field type interface.
   *
   * @return array
   *   The array of widget options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private static function getCustomFieldWidgetOptions(CustomFieldTypeInterface $custom_item): array {
    $options = [];
    /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager $plugin_service */
    $plugin_service = \Drupal::service('plugin.manager.custom_field_widget');
    $definitions = $plugin_service->getDefinitions();
    $type = $custom_item->getPluginId();
    // Remove undefined widgets for data_type.
    foreach ($definitions as $key => $definition) {
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetInterface $instance */
      $instance = $plugin_service->createInstance($definition['id']);
      if (!$instance::isApplicable($custom_item)) {
        unset($definitions[$key]);
      }
      if (!in_array($type, $definition['data_types'])) {
        unset($definitions[$key]);
      }
    }
    // Sort the widgets by category and then by name.
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['label'], $b['label']);
    });
    foreach ($definitions as $id => $definition) {
      $category = $definition['category'];
      // Add category grouping for multiple options.
      $options[(string) $category][$id] = $definition['label'];
    }
    if (count($options) <= 1) {
      $options = array_values($options)[0];
    }

    return $options;
  }

  /**
   * Get the custom field_type manager plugin.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   *   Returns the 'custom' field type plugin manager.
   */
  public function getCustomFieldManager(): CustomFieldTypeManagerInterface {
    return \Drupal::service('plugin.manager.custom_field_type');
  }

}
