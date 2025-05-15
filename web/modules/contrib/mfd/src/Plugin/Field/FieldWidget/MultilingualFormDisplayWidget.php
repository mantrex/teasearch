<?php

namespace Drupal\mfd\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\node\NodeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'multilingual_form_display_widget' widget.
 *
 * @FieldWidget(
 *   id = "multilingual_form_display_widget",
 *   label = @Translation("Multilingual Form Display"),
 *   field_types = {
 *     "multilingual_form_display"
 *   },
 * )
 */
class MultilingualFormDisplayWidget extends WidgetBase {

  use StringTranslationTrait;


  /**
   * State indicating all collapsible fields are removed.
   */
  const COLLAPSIBLE_STATE_NONE = -1;

  /**
   * State indicating all collapsible fields are closed.
   */
  const COLLAPSIBLE_STATE_ALL_CLOSED = 0;

  /**
   * State indicating all collapsible fields are closed except the first one.
   */
  const COLLAPSIBLE_STATE_FIRST = 1;

  /**
   * State indicating all collapsible fields are open.
   */
  const COLLAPSIBLE_STATE_ALL_OPEN = 2;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * The settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The third party settings.
   *
   * @var array
   */
  protected $thirdPartySettings;

  /**
   * The field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManager
   */
  protected $translationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Construct a mfd widget instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\language\ConfigurableLanguageManager $language_manager
   *   The language manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The account.
   * @param \Drupal\content_translation\ContentTranslationManager $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigurableLanguageManager $language_manager, ContainerInterface $container, AccountProxyInterface $account, ContentTranslationManager $translation_manager, EntityTypeManager $entity_type_manager) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $language_manager);

    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->fieldDefinition = $field_definition;
    $this->languageManager = $language_manager;
    $this->continer = $container;
    $this->account = $account;
    $this->translationManager = $translation_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $plugin_id,
        $plugin_definition,
        $configuration['field_definition'],
        $configuration['settings'],
        $configuration['third_party_settings'],
        $container->get('language_manager'),
        $container,
        $container->get('current_user'),
        $container->get('content_translation.manager'),
        $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_label' => TRUE,
      'display_description' => TRUE,
      'collapsible_state' => self::COLLAPSIBLE_STATE_FIRST,
      'mfd_languages' => [],
    ] + parent::defaultSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Find out if this bundle is translatable.
    $bundle = $form['#bundle'];
    $entity = $this->entityTypeManager->getStorage('node')->create(
        [
          'type' => $bundle,
          'title' => 'Translation testing',
        ]);

    if (!$entity->isTranslatable()) {

      $elements['not_translatable'] = [

        '#markup' => '<h2>' . $this->t('Entity type %bundle is not translatable.', ['%bundle' => $bundle]) . '</h2>'
        . '<p>' . $this->t('You must enable translation at /admin/structure/types/manage/%bundle', ['%bundle' => $bundle]) . '</p>',
      ];

      return $elements;
    }

    $elements['display_label'] = [
      '#title' => $this->t('Display the label in the form'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_label'),
    ];

    $elements['display_description'] = [
      '#title' => $this->t('Display the description in the form'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_description'),
    ];

    $collapsible_state_options = [
      self::COLLAPSIBLE_STATE_NONE => $this->t('Not collapsible -- all visible'),
      self::COLLAPSIBLE_STATE_ALL_CLOSED => $this->t('Collapsible and all closed'),
      self::COLLAPSIBLE_STATE_FIRST => $this->t('Collapsible with first language open'),
      self::COLLAPSIBLE_STATE_ALL_OPEN => $this->t('Collapsible and all open'),
    ];

    $elements['collapsible_state'] = [
      '#title' => $this->t('Choose whether the languages will be displayed in a collapsible field or not.'),
      '#type' => 'select',
      '#options' => $collapsible_state_options,
      '#default_value' => $this->getSetting('collapsible_state'),
    ];

    $available_langcodes = $this->languageManager->getLanguages();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    unset($available_langcodes[$default_langcode]);

    foreach ($available_langcodes as $key => $lang_obj) {
      $languages[$key] = ['lang' => $lang_obj->getName()];
    }

    $elements['mfd_languages_markup'] = [
      '#type' => 'item',
      '#title' => $this->t('Choose languages to display'),
      '#markup' => $this->t('<p>You may select to have all the languages displayed in one field or pick and choose which ones to make visible. Each MFD field can display its own language and effectively swap out the current language for the one associated here.</p>'),
    ];

    $header = [
      'lang' => $this->t('Language'),
    ];
    $elements['mfd_languages'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $languages,
      '#empty' => $this->t('No languages found'),
      '#default_value' => $this->getSetting('mfd_languages'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $display_label = $this->getSetting('display_label');
    $display_description = $this->getSetting('display_description');
    $collapsible_state = $this->getSetting('collapsible_state');
    $languages = $this->getSetting('mfd_languages');

    if (!empty($display_label)) {
      $summary[] = $this->t('Label Displayed');
    }
    if (!empty($display_description)) {
      $summary[] = $this->t('Description Displayed');
    }
    if (!empty(array_filter($languages))) {
      $summary[] = $this->t('Languages Displayed: @languages', ['@languages' => implode(' | ', $this->getLanguageNames($languages))]);
    }

    switch ($collapsible_state) {
      case self::COLLAPSIBLE_STATE_NONE:
        $summary[] = $this->t('This field will be open and non-collapsible.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_CLOSED:
        $summary[] = $this->t('This field will collapsed by default.');
        break;

      case self::COLLAPSIBLE_STATE_FIRST:
        $summary[] = $this->t('This field will have the first language open and the others collapsed.');
        break;

      case self::COLLAPSIBLE_STATE_ALL_OPEN:
        $summary[] = $this->t('This field will have all languages open and collapsible.');
        break;

    }

    return $summary;
  }

  /**
   * Collect translation field widgets.
   *
   * Collect widgets, assign them values and add them to our tree.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $current_user = $this->account;
    if (!$current_user->hasPermission('edit multilingual form')) {
      return $element;
    }

    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // If ($entity instanceof FieldConfigInterface) {
    //
    // }.
    if ($entity instanceof NodeInterface) {

      $entity_type_id = $entity->getEntityTypeId();
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $form_display = $form_state->getStorage('entity_form_display')['form_display'];

      $element += [
        '#type' => 'container',
        '#tree' => TRUE,
        '#weight' => 1,
        '#description_display' => 'before',
      ];

      if (empty($this->getSetting('display_label'))) {
        unset($element['#title']);
      }

      if (empty($this->getSetting('display_description'))) {
        unset($element['#description']);
      }

      $available_langcodes = ($this->languageManager->getLanguages());
      unset($available_langcodes[$current_language]);

      $selected_languages = $this->getSetting('mfd_languages');

      if (array_key_exists($current_language, array_flip($selected_languages))) {
        $default_language = $this->languageManager->getDefaultLanguage()->getId();
        unset($selected_languages[$current_language]);
        $selected_languages[$default_language] = $default_language;
      }

      $available_langcodes = array_intersect_key($available_langcodes, array_flip($selected_languages));
      reset($available_langcodes);

      $first_language = key($available_langcodes);

      // Iterate languages and add any translations we don't already have.
      foreach ($available_langcodes as $langcode => $language) {

        $form_state->set('language', $language);
        $language_name = $language->getName();

        if ($entity->hasTranslation($langcode)) {
          $translated_entity = $entity->getTranslation($langcode);
        }
        else {
          $translated_entity = $entity->addTranslation($langcode, [
            'title' => 'untitled',
          ]);

        }

        $this->translationManager->getTranslationMetadata($entity)->setSource($entity->language()->getId());

        $element['value'][$langcode] = [
          '#title' => $language_name,
        ];

        // Create a form element to hold the entity's fields.
        $collapsible_state = $this->getSetting('collapsible_state');
        if ($collapsible_state == self::COLLAPSIBLE_STATE_NONE) {
          $element['value'][$langcode] += [
            '#type' => 'item',
          ];
        }
        else {

          $element['value'][$langcode] += [
            '#type' => 'details',
            '#open' => ($langcode === $first_language && $collapsible_state == self::COLLAPSIBLE_STATE_FIRST) || ($collapsible_state == self::COLLAPSIBLE_STATE_ALL_OPEN),
          ];
        }

        foreach ($translated_entity->getFieldDefinitions() as $field_name => $definition) {
          $storage_definition = $definition->getFieldStorageDefinition();

          if (($definition->isComputed() || (!empty($storage_definition)  && $this->isFieldTranslatabilityConfigurable($entity_type, $storage_definition))) && $definition->isTranslatable()) {

            $translated_items = $translated_entity->get($field_name);
            $translated_items->filterEmptyItems();
            $translated_form['#parents'] = [];
            $widget = $form_display->getRenderer($field_name);

            if (!is_null($widget)) {
              $component_form = $widget->form($translated_items, $translated_form, $form_state);
              // Avoid namespace collisions.
              $field_name_with_ident = $this->getUniqueName($field_name, $langcode);
              $component_form['#field_name'] = $field_name_with_ident;
              $component_form['#multiform_display_use'] = TRUE;

              $component_form['widget']['#field_name'] = $field_name_with_ident;
              $parents_flipped = array_flip($component_form['widget']['#parents']);
              $component_form['widget']['#parents'][$parents_flipped[$field_name]] = $field_name_with_ident;

              // Create a container for the entity's fields.
              $element['value'][$langcode][$field_name] = $component_form;
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * Checks whether translatability should be configurable for a field.
   *
   * Hijacked method from the content_translation module.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   *
   * @return bool
   *   TRUE if field translatability can be configured, FALSE otherwise.
   *
   * @internal
   */
  protected function isFieldTranslatabilityConfigurable(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $definition) {

    // Skip our own fields as they are always translatable.
    return $definition->isTranslatable() &&
      $definition->getProvider() != 'content_translation' &&
      !in_array($definition->getName(), [
        $entity_type->getKey('langcode'),
        $entity_type->getKey('default_langcode'),
        'revision_translation_affected',
        $this->fieldDefinition->getName(),
      ]) &&
      !in_array($definition->getType(), [
        'multilingual_form_display',
      ]);
  }

  /**
   * Creates a unique identifier.
   */
  public function getUniqueName($field_name = 'stub', $langcode = '__') {
    return $field_name . '_' . $langcode;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues() {
    return [];
  }

  /**
   * Get language names.
   */
  protected function getLanguageNames($languages) {

    $available_langcodes = ($this->languageManager->getLanguages());
    foreach ($languages as $key => $name) {
      if ($name != FALSE) {
        $languages_names[] = $available_langcodes[$key]->getName();
      }
    }

    return $languages_names;
  }

}
