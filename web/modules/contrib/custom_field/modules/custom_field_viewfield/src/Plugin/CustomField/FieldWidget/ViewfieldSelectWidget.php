<?php

namespace Drupal\custom_field_viewfield\Plugin\CustomField\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Drupal\views\Views;

/**
 * Plugin implementation of the 'select' custom field widget.
 *
 * @FieldWidget(
 *   id = "viewfield_select",
 *   label = @Translation("Viewfield select"),
 *   category = @Translation("Viewfield"),
 *   data_types = {
 *     "viewfield",
 *   },
 * )
 */
class ViewfieldSelectWidget extends CustomFieldWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'empty_option' => '- None -',
        'force_default' => 0,
        'allowed_views' => [],
        'items_to_display' => NULL,
        'token_browser' => [
          'recursion_limit' => 3,
          'global_types' => FALSE,
        ],
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $range = range(1, 6);
    $views = [];
    foreach ($this->getViewOptions(FALSE) as $id => $view) {
      $displays = $this->getDisplayOptions($id);
      if (!empty($displays)) {
        $views[$id] = [
          'label' => $view,
          'displays' => $displays,
        ];
      }
    }
    $element['settings']['allowed_views'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed views'),
      '#description' => $this->t('Views displays available for content authors. Leave empty to allow all.'),
      '#description_display' => 'before',
      '#element_validate' => [[$this, 'validateAllowedViews']],
    ];
    foreach ($views as $view_name => $view) {
      $element['settings']['allowed_views'][$view_name] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('@label', ['@label' => $view['label']]),
        '#options' => $view['displays'],
        '#default_value' => $settings['allowed_views'][$view_name] ?? [],
      ];
    }
    $element['settings']['force_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always use default value'),
      '#description' => $this->t('The allowed views will not immediately be available in the default value form until they are saved into configuration. It is recommended to save the field settings prior to setting the default values for this particular setting.'),
      '#default_value' => $settings['force_default'],
    ];
    $element['settings']['empty_option'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty option'),
      '#description' => $this->t('Option to show when field is not required.'),
      '#default_value' => $settings['empty_option'],
      '#required' => TRUE,
    ];
    $element['settings']['token_browser'] = [
      '#type' => 'details',
      '#title' => $this->t('Token browser'),
      '#description' => $this->t('Settings to handle available tokens for the arguments field when token module is enabled.'),
      '#description_display' => 'before',
    ];
    $element['settings']['token_browser']['recursion_limit'] = [
      '#type' => 'select',
      '#title' => t('Recursion limit'),
      '#description' => t('The depth of the token browser tree.'),
      '#options' => array_combine($range, $range),
      '#default_value' => $settings['token_browser']['recursion_limit'] ?? 3,
    ];
    $element['settings']['token_browser']['global_types'] = [
      '#type' => 'checkbox',
      '#title' => t('Global types'),
      '#description' => t("Enable 'global' context tokens like [current-user:*] or [site:*]."),
      '#default_value' => $settings['token_browser']['global_types'] ?? FALSE,
    ];

    $element['#element_validate'][] = [static::class, 'fieldSettingsFormValidate'];

    return $element;
  }

  /**
   * Validates the allowed views fieldset to enforce at least one view enabled.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form.
   */
  public function validateAllowedViews(array &$element, FormStateInterface &$form_state, array &$complete_form): void {
    $any_enabled = FALSE;
    $views = $form_state->getValue($element['#parents']);
    // Iterate for each view's displays to check for enabled.
    foreach ($views as $displays) {
      if (!empty(array_filter($displays))) {
        $any_enabled = TRUE;
        break;
      }
    }
    // No displays for any view are enabled, so set an error.
    if (!$any_enabled) {
      $form_state->setError($element, $this->t('At least one view display must be enabled.'));
    }
  }

  /**
   * Form API callback.
   *
   * Requires that field defaults be supplied when the 'force_default' option
   * is checked.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function fieldSettingsFormValidate(array &$element, FormStateInterface $form_state, array &$form) {
    $parents = $element['#array_parents'];
    $subfield = array_slice($parents, 0, -1, TRUE);
    $settings = $form_state->getValue($subfield)['widget_settings']['settings'];

    if ($settings['force_default']) {
      $default_value = $form_state->getValue('default_value_input');
      $field_name = $form_state->getFormObject()->getEntity()->getName();
      $subfield_name = end($subfield);
      if (empty($default_value[$field_name][0][$subfield_name]['display_id'])) {
        $form_element = NestedArray::getValue($form, $subfield)['widget_settings']['settings'];
        // Set an error on the default value checkbox.
        $form_state->setErrorByName('set_default_value', t('%title requires a default value.', [
          '%title' => $form_element['force_default']['#title'],
        ]));
        // Set an error on the target id field.
        $target_form_keys = [
          'default_value',
          'widget',
          0,
          $subfield_name,
          'target_id',
        ];
        $target_id_element = NestedArray::getValue($form_state->getCompleteForm(), $target_form_keys);
        if ($target_id_element) {
          $form_state->setError($target_id_element, t('The field %view requires a default view.', [
            '%view' => $target_id_element['#title'],
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $allowed_views = $this->getAllowedViewsOptions($settings['allowed_views']);
    $token_module_installed = $this->moduleHandler->moduleExists('token');
    /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
    $item = $items[$delta];
    $entity_type_id = $item->getEntity()->getEntityTypeId();
    $is_required = $item->getFieldDefinition()->isRequired() && $settings['required'];
    if ($this->isDefaultValueWidget($form_state) && !$settings['force_default']) {
      $is_required = FALSE;
    }
    $field_name = $item->getFieldDefinition()->getName();
    $name = $field->getName();
    if (!$this->isDefaultValueWidget($form_state) && $settings['force_default']) {
      $element['#access'] = FALSE;
    }
    $parents = $form['#parents'] ?? [];
    // Create an ID suffix from the parents to make sure each widget is unique.
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $wrapper = $field_name . '-' . $delta . '-' . $name . '-' . $id_suffix;

    // Account for parents structure from paragraphs field if applicable.
    $value_keys = array_merge($parents, [$field_name, $delta, $name]);

    // Create a condition string for states api.
    $path_parts = [...$value_keys];
    $base = array_shift($path_parts);
    $visibility_path = $base . '[' . implode('][', $path_parts) . ']';

    $field_value = NestedArray::getValue($form_state->getValues(), $value_keys);
    // If there are no processed values, use the input.
    if (empty($field_value)) {
      $field_value = NestedArray::getValue($form_state->getUserInput(), $value_keys);
    }
    if (!empty($field_value)) {
      $target_id = $field_value['target_id'];
      $default_display_id = $field_value['display_id'];
      $default_arguments = $field_value['view_options']['arguments'];
      $default_items_to_display = $field_value['view_options']['items_to_display'];
    }
    // Use the saved values.
    else {
      $target_id = $item->{$name};
      $default_display_id = $item->{$name . '__display'} ?? NULL;
      $default_arguments = $item->{$name . '__arguments'} ?? NULL;
      $default_items_to_display = $item->{$name . '__items'} ?? NULL;
    }

    // Use the allowed displays by current view selected.
    $display_id_options = $target_id ? $allowed_views[$target_id]['displays'] ?? [] : [];

    // Add a container div for flex layout compatibility.
    $element['#theme_wrappers'] = ['container'];
    // Add our widget type and additional properties and return.
    $element['target_id'] = [
      '#title' => $this->t('View'),
      '#type' => 'select',
      '#options' => $this->getViewOptions(TRUE, $settings['allowed_views']),
      '#empty_value' => '',
      '#default_value' => $target_id,
      '#description' => $this->t('View name.'),
      '#required' => $is_required,
      '#ajax' => [
        'callback' => [$this, 'ajaxGetDisplayOptions'],
        'wrapper' => $wrapper,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Getting options...'),
        ],
      ],
    ];
    if (!$is_required) {
      $element['target_id']['#empty_option'] = $settings['empty_option'];
    }

    $element['display_id'] = [
      '#title' => $this->t('Display'),
      '#type' => 'select',
      '#options' => $display_id_options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $default_display_id,
      '#description' => $this->t('View display to be used.'),
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
    ];

    // Hide the display id field and set its value to NULL if no view selected.
    if (count($display_id_options) < 1) {
      $element['display_id']['#type'] = 'hidden';
      $element['display_id']['#value'] = NULL;
      unset($element['display_id']['#options']);
    }
    // Otherwise, require it.
    else {
      $element['display_id']['#required'] = TRUE;
    }

    $element['view_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#states' => [
        'visible' => [
          [':input[name="' . $visibility_path . '[target_id]"]' => ['filled' => TRUE]],
        ],
      ],
    ];
    $element['view_options']['arguments'] = [
      '#title' => $this->t('Arguments'),
      '#type' => 'textfield',
      '#default_value' => $default_arguments ?? NULL,
      '#description' => Markup::create($this->t('Separate contextual filters with a "/". Each filter may use "+" or "," for multi-value arguments.<br> @tokens', [
        '@tokens' => $token_module_installed ? $this->t('This field supports tokens.') : '',
      ])),
      '#maxlength' => 255,
    ];
    $element['view_options']['items_to_display'] = [
      '#title' => $this->t('Items to display'),
      '#type' => 'number',
      '#default_value' => $default_items_to_display,
      '#description' => $this->t('Override the number of items to display. This also disables the pager if one is configured. Leave empty for default limit.'),
      '#min' => 1,
      '#max' => 100,
    ];
    if ($token_module_installed) {
      $token_mapper = \Drupal::service('token.entity_mapper');
      $token_type = $token_mapper->getTokenTypeForEntityType($entity_type_id);
      $element['view_options']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$token_type],
        '#recursion_limit' => $settings['token_browser']['recursion_limit'] ?? 3,
        '#recursion_limit_max' => 6,
        '#global_types' => $settings['token_browser']['global_types'] ?? FALSE,
      ];
    }

    return $element;
  }

  /**
   * Get an options array of views.
   *
   * @param bool $filter
   *   Flag to filter the output using the 'allowed_views' setting.
   * @param array $allowed_views_setting
   *   (optional) An array of 'allowed_views' from settings to filter by.
   *
   * @return array
   *   The array of options.
   */
  public function getViewOptions(bool $filter, array $allowed_views_setting = []): array {
    $views_options = [];
    $allowed_views = [];
    if ($filter) {
      // Add only the views where displays are allowed.
      foreach ($allowed_views_setting as $id => $displays) {
        if (!empty(array_filter($displays))) {
          $allowed_views[$id] = $displays;
        }
      }
    }

    foreach (Views::getEnabledViews() as $key => $view) {
      if (empty($allowed_views) || isset($allowed_views[$key])) {
        $views_options[$key] = FieldFilteredMarkup::create($view->get('label'));
      }
    }
    natcasesort($views_options);

    return $views_options;
  }

  /**
   * Get display ID options for a view.
   *
   * @param string $entity_id
   *   The entity_id of the view.
   * @param bool $filter
   *   (optional) Flag to filter the output using the 'allowed_display_types'
   *   setting.
   *
   * @return array
   *   The array of options.
   */
  public function getDisplayOptions(string $entity_id, bool $filter = TRUE): array {
    $display_options = [];
    $views = Views::getEnabledViews();
    if (isset($views[$entity_id])) {
      foreach ($views[$entity_id]->get('display') as $key => $display) {
        if (isset($display['display_options']['enabled']) && !$display['display_options']['enabled']) {
          continue;
        }
        $display_options[$key] = FieldFilteredMarkup::create($display['display_title']);
      }
      natcasesort($display_options);
    }

    return $display_options;
  }

  /**
   * Get allowed views for widget options.
   *
   * @param array $allowed_views
   *   An array of views to filter by.
   *
   * @return array
   *   A filtered array of views based on enabled displays.
   */
  public function getAllowedViewsOptions(array $allowed_views): array {
    $views = Views::getEnabledViews();
    $allowed_options = [];

    foreach ($allowed_views as $view_name => $displays) {
      // Check if the view and our filters exists in $views before adding.
      if (isset($views[$view_name])) {
        $filtered_displays = array_filter($displays);
        $display_options = [];
        foreach ($filtered_displays as $key => $display) {
          $views_display = $views[$view_name]->getDisplay($key);
          if (isset($views_display['display_options']['enabled']) && !$views_display['display_options']['enabled']) {
            continue;
          }
          $display_options[$key] = FieldFilteredMarkup::create($views_display['display_title']);
        }
        if (!empty($display_options)) {
          natcasesort($display_options);
          $allowed_options[$view_name] = [
            'label' => $views[$view_name]->label(),
            'displays' => $display_options,
          ];
        }
      }
    }

    return $allowed_options;
  }

  /**
   * Ajax callback to retrieve display IDs.
   *
   * @param array $form
   *   The form from which the display IDs are being requested.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function ajaxGetDisplayOptions(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $wrapper_id = $trigger['#ajax']['wrapper'];
    $form_state_keys = array_slice($trigger['#array_parents'], 0, -1);

    // Get the updated element from the form structure.
    $updated_element = NestedArray::getValue($form, $form_state_keys)['display_id'];
    $sliced_parents = array_slice($trigger['#parents'], 0, -1, TRUE);

    NestedArray::unsetValue($form_state->getUserInput(), [
      ...$sliced_parents,
      'display_id',
    ]);

    $response = new AjaxResponse();
    // Add a ReplaceCommand to replace the content inside the widget's wrapper.
    $response->addCommand(new ReplaceCommand('#' . $wrapper_id, $updated_element));
    $form_state->setRebuild();

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, array $column): mixed {
    if (isset($value['view_options'])) {
      $value = array_merge($value, $value['view_options']);
      unset($value['view_options']);
    }
    return $value;
  }

}
