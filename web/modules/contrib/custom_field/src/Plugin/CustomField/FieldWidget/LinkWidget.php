<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\LinkAttributesManager;
use Drupal\custom_field\Plugin\CustomField\UrlWidgetBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'url' widget.
 */
#[CustomFieldWidget(
  id: 'link_default',
  label: new TranslatableMarkup('Link'),
  category: new TranslatableMarkup('Url'),
  field_types: [
    'link',
  ],
)]
class LinkWidget extends UrlWidgetBase {

  /**
   * The link attributes manager.
   *
   * @var \Drupal\custom_field\LinkAttributesManager
   */
  protected LinkAttributesManager $linkAttributesManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->linkAttributesManager = $container->get('plugin.manager.custom_field_link_attributes');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $settings = parent::defaultSettings();
    $settings['settings'] = [
      'placeholder_url' => '',
      'placeholder_title' => '',
      'enabled_attributes' => [
        'id' => FALSE,
        'name' => FALSE,
        'target' => TRUE,
        'rel' => TRUE,
        'class' => TRUE,
        'accesskey' => FALSE,
      ],
      'title' => DRUPAL_OPTIONAL,
      'widget_default_open' => self::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
    ] + $settings['settings'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $field_name = $field->getName();
    $options = array_map(function ($plugin_definition) {
      return $plugin_definition['title'];
    }, $this->linkAttributesManager->getDefinitions());
    $selected = array_keys(array_filter($settings['enabled_attributes']));

    // Add description display help text to clarify behavior.
    $element['settings']['description_display']['#description'] = $this->t('This setting is applies to the help text for the URL field.');
    // Append additional clarification to description field help text.
    $element['settings']['description']['#description'] = [
      '#theme' => 'item_list',
      '#items' => [
        $element['settings']['description']['#description'],
        $this->t('Appears as fieldset help text when title or attributes are enabled.'),
      ],
    ];

    $element['settings']['title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow link text'),
      '#default_value' => $settings['title'],
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
    ];
    $element['settings']['enabled_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable attributes'),
      '#options' => $options,
      '#default_value' => array_combine($selected, $selected),
      '#description' => $this->t('Select the attributes to allow the user to edit.<br />Single value attributes (e.g. "ID") will replace corresponding attribute set in formatter settings.<br />Multi-value attributes (e.g. "Class") will be merged with corresponding attribute set in formatter settings.'),
      '#description_display' => 'before',
    ];
    $element['settings']['widget_default_open'] = [
      '#type' => 'select',
      '#title' => $this->t('Attributes default open behavior'),
      '#options' => [
        self::WIDGET_OPEN_EXPAND_IF_VALUES_SET => $this->t('Expand if values set (Default)'),
        self::WIDGET_OPEN_EXPANDED => $this->t('Expand'),
        self::WIDGET_OPEN_COLLAPSED => $this->t('Collapse'),
      ],
      '#default_value' => $settings['widget_default_open'] ?? self::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
      '#description' => $this->t('Set the widget default open behavior.'),
    ];
    $element['settings']['placeholder_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for URL'),
      '#default_value' => $settings['placeholder_url'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['placeholder_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder for link text'),
      '#default_value' => $settings['placeholder_title'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      '#states' => [
        'invisible' => [
          'input[name="settings[field_settings][' . $field_name . '][widget_settings][settings][title]"]' => ['value' => DRUPAL_DISABLED],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $field_definition = $items->getFieldDefinition();
    $item = $items[$delta];
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $subfield = $field->getName();
    $options = $item->{$subfield . '__options'};
    $attributes = $options['attributes'] ?? [];

    // Overrides for this widget.
    $element['uri']['#title'] = $this->t('URL');
    $element['uri']['#placeholder'] = $settings['placeholder_url'];

    // Make uri required on the front-end when title filled-in.
    if (!$this->isDefaultValueWidget($form_state) && $settings['title'] !== DRUPAL_DISABLED && !$element['uri']['#required']) {
      $parents = $element['#field_parents'] ?? [];
      $parents[] = $field_definition->getName();
      $selector = $root = array_shift($parents);
      if ($parents) {
        $selector = $root . '[' . implode('][', $parents) . ']';
      }

      $element['uri']['#states']['required'] = [
        ':input[name="' . $selector . '[' . $delta . '][' . $subfield . '][title]"]' => ['filled' => TRUE],
      ];
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $settings['placeholder_title'],
      '#default_value' => $item->{$subfield . '__title'} ?? NULL,
      '#maxlength' => 255,
      '#access' => $settings['title'] != DRUPAL_DISABLED,
      '#required' => $settings['title'] === DRUPAL_REQUIRED && $element['#required'],
    ];
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    //
    // Validate that title field is filled out (regardless of uri) when it is a
    // required field.
    if (!$this->isDefaultValueWidget($form_state) && $settings['title'] === DRUPAL_REQUIRED) {
      $element['#element_validate'][] = [static::class, 'validateTitleElement'];
      $element['#element_validate'][] = [static::class, 'validateTitleNoLink'];

      if (!$element['title']['#required']) {
        // Make title required on the front-end when URI filled-in.
        $parents = $element['#field_parents'];
        $parents[] = $field_definition->getName();
        $selector = $root = array_shift($parents);
        if ($parents) {
          $selector = $root . '[' . implode('][', $parents) . ']';
        }

        $element['title']['#states']['required'] = [
          ':input[name="' . $selector . '[' . $delta . '][' . $subfield . '][uri]"]' => ['filled' => TRUE],
        ];
      }
    }

    // Ensure that a URI is always entered when an optional title field is
    // submitted.
    if (!$this->isDefaultValueWidget($form_state) && $settings['title'] == DRUPAL_OPTIONAL) {
      $element['#element_validate'][] = [static::class, 'validateTitleNoLink'];
    }

    if (!empty(array_filter($settings['enabled_attributes']))) {
      $widget_default_open_setting = $settings['widget_default_open'];

      $open = NULL;
      match ($widget_default_open_setting) {
        self::WIDGET_OPEN_EXPANDED => $open = TRUE,
        self::WIDGET_OPEN_COLLAPSED => $open = FALSE,
        default => $open = count($attributes),
      };

      $element['options']['attributes'] = [
        '#type' => 'details',
        '#title' => $this->t('Attributes'),
        '#tree' => TRUE,
        '#open' => $open,
      ];
      $required = FALSE;
      $plugin_definitions = $this->linkAttributesManager->getDefinitions();
      foreach (array_keys(array_filter($settings['enabled_attributes'])) as $attribute) {
        if (isset($plugin_definitions[$attribute])) {
          foreach ($plugin_definitions[$attribute] as $property => $value) {
            if ($property === 'id') {
              // Don't set ID.
              continue;
            }
            $element['options']['attributes'][$attribute]['#' . $property] = $value;
          }
          // Set the default value, in case of a class that is stored as array,
          // convert it back to a string.
          $default_value = $attributes[$attribute] ?? NULL;
          if ($attribute === 'class' && is_array($default_value)) {
            $default_value = implode(' ', $default_value);
          }
          if (isset($default_value)) {
            $element['options']['attributes'][$attribute]['#default_value'] = $default_value;
          }
          $required = $required || !empty($element['options']['attributes'][$attribute]['#required']);
        }
      }
      // Open the widget by default if there is a required attribute.
      $element['options']['attributes']['#open'] = $element['options']['attributes']['#open'] || $required;
    }
    // Wrap everything in a details' element.
    if ($settings['title'] != DRUPAL_DISABLED || !empty(array_filter($settings['enabled_attributes']))) {
      $element += [
        '#type' => 'fieldset',
      ];
    }
    else {
      $element += [
        '#type' => 'container',
      ];
      // If we're only showing the uri field, replace the uri title with the
      // element title.
      $element['uri']['#title'] = $element['#title'];
      if (!empty($element['#description'])) {
        // If we have the description of the type of field together with
        // the user provided description, we want to make a distinction
        // between "core help text" and "user entered help text". To make
        // this distinction more clear, we put them in an unordered list.
        $element['uri']['#description'] = [
          '#theme' => 'item_list',
          '#items' => [
            // Assume the user-specified description has the most relevance,
            // so place it first.
            $element['#description'],
            $element['uri']['#description'],
          ],
        ];
      }
    }

    if ($element['#type'] === 'fieldset') {
      // Force the fieldset description to logical position.
      $element['#description_display'] = 'before';
    }

    return $element;
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Conditionally requires the link title if a URL value was filled in.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $form
   *   The form.
   */
  public static function validateTitleElement(array &$element, FormStateInterface $form_state, array $form): void {
    if ($element['uri']['#value'] !== '' && $element['title']['#value'] === '') {
      // We expect the field name placeholder value to be wrapped in $this->t()
      // here, so it won't be escaped again as it's already marked safe.
      $form_state->setError($element['title'], new TranslatableMarkup('@title field is required if there is @uri input.', [
        '@title' => $element['title']['#title'],
        '@uri' => $element['uri']['#title'],
      ]));
    }
  }

  /**
   * Form element validation handler for the 'title' element.
   *
   * Requires the URL value if a link title was filled in.
   *
   * @param array<string, mixed> $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $form
   *   The form.
   */
  public static function validateTitleNoLink(array &$element, FormStateInterface $form_state, array $form): void {
    if ($element['uri']['#value'] === '' && $element['title']['#value'] !== '') {
      $form_state->setError($element['uri'], new TranslatableMarkup('The @uri field is required when the @title field is specified.', [
        '@title' => $element['title']['#title'],
        '@uri' => $element['uri']['#title'],
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): ?array {
    if (empty($value['uri'])) {
      return NULL;
    }
    $value['uri'] = static::getUserEnteredStringAsUri($value['uri']);
    $value += ['options' => []];
    if (isset($value['options']['attributes'])) {
      $attributes = $value['options']['attributes'];
      $value['options']['attributes'] = array_filter($attributes, function ($attribute) {
        return $attribute !== "";
      });
      // Convert a class string to an array so that it can be merged reliable.
      if (isset($value['options']['attributes']['class']) && is_string($value['options']['attributes']['class'])) {
        $value['options']['attributes']['class'] = explode(' ', $value['options']['attributes']['class']);
      }
    }

    return $value;
  }

}
