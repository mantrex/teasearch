<?php

namespace Drupal\custom_field\Plugin\CustomField;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base plugin class for entity_reference custom field widgets.
 */
class EntityReferenceWidgetBase extends CustomFieldWidgetBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity reference selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->selectionPluginManager = $container->get('plugin.manager.entity_reference_selection');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'handler_settings' => [],
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $field_name = $field->getName();
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $target_type = $field->getTargetType();
    if (!isset($settings['handler'])) {
      $settings['handler'] = 'default:' . $target_type;
    }
    // Get all selection plugins for this entity type.
    $selection_plugins = $this->selectionPluginManager->getSelectionGroups($target_type);
    $handlers_options = [];
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      // We only display base plugins (e.g. 'default', 'views', ...) and not
      // entity type specific plugins (e.g. 'default:node', 'default:user',
      // ...).
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
      }
      elseif (array_key_exists($selection_group_id . ':' . $target_type, $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $target_type;
        $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label'] ?? '');
      }
    }
    $wrapper_id = 'reference-wrapper-' . $field_name;
    $element['settings']['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['settings']['#suffix'] = '</div>';

    $element['settings']['handler'] = [
      '#type' => 'details',
      '#title' => $this->t('Reference type'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[static::class, 'formProcessMergeParent']],
    ];

    $element['settings']['handler']['handler'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference method'),
      '#options' => $handlers_options,
      '#default_value' => $settings['handler'],
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => $wrapper_id,
        'callback' => [static::class, 'actionCallback'],
      ],
    ];

    $element['settings']['handler']['handler_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change handler'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => [[static::class, 'settingsAjaxSubmit']],
    ];

    $element['settings']['handler']['handler_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity_reference-settings']],
    ];

    $handler = $this->getSelectionHandler($settings, $target_type);
    $configuration_form = $handler->buildConfigurationForm([], $form_state);

    // Alter configuration to use our custom callback.
    foreach ($configuration_form as $key => $item) {
      if (isset($item['#limit_validation_errors'])) {
        unset($item['#limit_validation_errors']);
      }
      if (isset($item['#ajax'])) {
        $item['#ajax'] = [
          'wrapper' => $wrapper_id,
          'callback' => [static::class, 'actionCallback'],
        ];
      }
      if (is_array($item)) {
        foreach ($item as $prop_key => $prop) {
          if (!is_array($prop)) {
            continue;
          }
          if (isset($prop['#limit_validation_errors'])) {
            unset($prop['#limit_validation_errors']);
          }
          if (isset($prop['#ajax'])) {
            $prop['#ajax'] = [
              'wrapper' => $wrapper_id,
              'callback' => [static::class, 'actionCallback'],
            ];
          }
          $item[(string) $prop_key] = $prop;
        }
      }
      $configuration_form[(string) $key] = $item;
    }

    $element['settings']['handler']['handler_settings'] += $configuration_form;

    return $element;
  }

  /**
   * Render API callback that moves entity reference elements up a level.
   *
   * The elements (i.e. 'handler_settings') are moved for easier processing by
   * the validation and submission handlers.
   *
   * @see _entity_reference_field_settings_process()
   */
  public static function formProcessMergeParent($element) {
    $parents = $element['#parents'];
    array_pop($parents);
    $element['#parents'] = $parents;
    return $element;
  }

  /**
   * Ajax callback for the handler settings form.
   */
  public static function actionCallback(array &$form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $sliced_parents = array_slice($parents, 0, 5, TRUE);

    return NestedArray::getValue($form, $sliced_parents);
  }

  /**
   * Submit handler for the non-JS case.
   *
   * @see static::fieldSettingsForm()
   */
  public static function settingsAjaxSubmit($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Gets the selection handler for a given entity_reference field.
   *
   * @param array $settings
   *   An array of field settings.
   * @param string $target_type
   *   The target entity type.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity containing the reference field.
   *
   * @return mixed
   *   The selection handler.
   */
  public function getSelectionHandler(array $settings, string $target_type, ?EntityInterface $entity = NULL) {
    $options = $settings['handler_settings'] ?: [];
    $options += [
      'target_type' => $target_type,
      'handler' => $settings['handler'],
      'entity' => $entity,
    ];

    return $this->selectionPluginManager->getInstance($options);
  }

}
