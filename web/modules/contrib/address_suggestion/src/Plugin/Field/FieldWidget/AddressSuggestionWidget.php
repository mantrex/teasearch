<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\address_suggestion\AddressProviderManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Plugin implementation of the 'address_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "address_suggestion",
 *   label = @Translation("Address suggestion"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressSuggestionWidget extends AddressDefaultWidget {

  /**
   * Constructs a WidgetBase object.
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
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\address_suggestion\AddressProviderManager $addressProvider
   *   The plugin address provider manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CountryRepositoryInterface $country_repository, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory, protected EntityFieldManagerInterface $entityFieldManager, protected AddressProviderManager $addressProvider) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $country_repository, $event_dispatcher, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition,
      $configuration['field_definition'], $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address.country_repository'),
      $container->get('event_dispatcher'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.address_provider'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['address']['#type'] = 'address_suggestion';
    $fieldDefinitions = $items->getFieldDefinition();
    $element['address']['#entity_type'] = $fieldDefinitions->getTargetEntityTypeId();
    $element['address']['#bundle'] = $fieldDefinitions->getTargetBundle();
    $element['address']['#field_name'] = $fieldDefinitions->getName();
    $settings = $this->getSettings();
    $field_name = $this->getSetting('location_field');
    if (!empty($field_name)) {
      $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
      $bundle = $this->fieldDefinition->getTargetBundle();
      $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      $field_name = $this->getSetting('location_field');
      if (!empty($fieldDefinitions[$field_name])) {
        $settings['type_field'] = $fieldDefinitions[$field_name]->getType();
      }
      if (!empty($form['#parents'])) {
        $settings['location_field'] .= ']';
      }
    }
    $form['#attached']['drupalSettings']['address_suggestion'] = $settings;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'location_field' => '',
      'provider' => '',
      'endpoint' => '',
      'api_key' => '',
      'username' => '',
      'password' => '',
      'hide' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    // Get entity type and bundle.
    $entity_type = $form["#entity_type"];
    $bundle = $form["#bundle"];
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $userInput = $form_state->getUserInput();

    // Supported field types.
    $typeSupport = ['geofield_latlon', 'geolocation_latlng'];

    // Build options for location fields.
    $options = $this->buildLocationFieldOptions($userInput, $fieldDefinitions, $typeSupport);

    // Add location field select element if options are available.
    if (!empty($options)) {
      $element['location_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Location field'),
        '#default_value' => $this->getSetting('location_field'),
        '#options' => $options,
        "#empty_option" => $this->t('- Select field -'),
        "#description" => $this->t('You can attach a location field to get the coordinates'),
      ];
    }

    // Build options for providers.
    $options = [];

    $endPointUrl = [$this->t("Fill in if you want to use your own api")];
    $key = [];
    $state = [];
    $login = [];
    $addressProvider = $this->addressProvider->getDefinitions();
    foreach ($addressProvider as $provider) {
      $options[$provider['id']] = $provider['label'];
      if (!empty($provider['api'])) {
        $endPointUrl[] = $provider['label'] . ': ' . $provider['api'];
        if (!isset($provider['nokey'])) {
          $key[] = $provider['label'];
          $state[] = ['value' => $provider['id']];
        }
        if (isset($provider['login'])) {
          $login[] = ['value' => $provider['id']];
        }
      }
    }

    // Add provider select element.
    $element['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#default_value' => $this->getSetting('provider'),
      '#options' => $options,
      "#empty_option" => $this->t('- Select provider -'),
    ];

    // Add endpoint URL element.
    $element['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Custom API Address endpoint'),
      '#default_value' => $this->getSetting('endpoint'),
      "#description" => $this->buildEndpointDescription(),
    ];

    // Add API key element.
    $element['api_key'] = $this->buildApiKeyElement($fieldDefinitions, $state);

    // Add post.ch API username and password elements.
    $element += $this->buildPostChApiElements($login);

    // Add hide address field checkbox.
    $element['hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide address field'),
      '#default_value' => $this->getSetting('hide'),
      "#description" => $this->t('It will show input suggestions automatically'),
    ];

    return $element;
  }

  /**
   * Build options for location fields.
   */
  private function buildLocationFieldOptions(array $userInput, array $fieldDefinitions, array $typSupport) {
    $options = [];

    if (!empty($userInput["fields"])) {
      foreach ($userInput["fields"] as $field_name => $field_widget) {
        if (!empty($field_widget['type']) && in_array($field_widget['type'], $typSupport)) {
          $options[$field_name] = (string) $fieldDefinitions[$field_name]->getLabel();
        }
      }
    }

    if (empty($options)) {
      foreach ($fieldDefinitions as $field_name => $field_definition) {
        if ($field_definition instanceof FieldConfigInterface && in_array($field_definition->getType(), $typSupport)) {
          $options[$field_name] = (string) $field_definition->getLabel();
        }
      }
    }

    return $options;
  }

  /**
   * Build description for endpoint URL.
   */
  private function buildEndpointDescription() {
    $endPointUrl = [
      $this->t("Fill in if you want to use your own api"),
    ];

    $addressProvider = $this->addressProvider->getDefinitions();
    foreach ($addressProvider as $provider) {
      if (!empty($provider['api'])) {
        $endPointUrl[] = $provider['label'] . ': ' . $provider['api'];
      }
    }

    return implode('<br/>', $endPointUrl);
  }

  /**
   * Build API key element.
   */
  private function buildApiKeyElement(array $fieldDefinitions, $state) {
    $fieldName = $this->fieldDefinition->getName();
    $options = [];

    foreach ($fieldDefinitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface &&
        in_array($field_definition->getType(), ['geolocation', 'geofield'])) {
        $options[$field_name] = (string) $field_definition->getLabel();
      }
    }

    return [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->getSetting('api_key'),
      "#description" => $this->t('Required for provider: @key', ['@key' => implode(', ', $options)]),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $fieldName . '][settings_edit_form][settings][provider]"]' => [$state],
        ],
      ],
    ];
  }

  /**
   * Build post.ch API username and password elements.
   */
  private function buildPostChApiElements($login) {
    $fieldName = $this->fieldDefinition->getName();

    return [
      'username' => [
        '#type' => 'textfield',
        '#title' => $this->t('post.ch API username'),
        '#default_value' => $this->getSetting('username'),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldName . '][settings_edit_form][settings][provider]"]' => [$login],
          ],
        ],
      ],
      'password' => [
        '#type' => 'password',
        '#title' => $this->t('post.ch API password'),
        '#default_value' => $this->getSetting('password'),
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $fieldName . '][settings_edit_form][settings][provider]"]' => [$login],
          ],
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if (!empty($this->getSetting('location_field'))) {
      $summary[] = $this->t('Location field: @field', ['@field' => $this->getSetting('location_field')]);
    }
    if (!empty($provider = $this->getSetting('provider'))) {
      $provider = ucfirst(str_replace('_', ' ', $provider));
      $summary[] = $this->t('Provider: @provider', ['@provider' => $provider]);
    }
    return $summary;
  }

}
