<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldFormatter;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\address\AddressInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Address Suggestion Map' formatter.
 *
 * @FieldFormatter(
 *   id = "address_suggestion_map",
 *   label = @Translation("Address Suggestion Map"),
 *   field_types = {"address"},
 * )
 */
class AddressSuggestionMapFormatter extends FormatterBase {

  /**
   * Constructs a new FileUriLinkFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \CommerceGuys\Addressing\AddressFormat\AddressFormatRepositoryInterface $addressFormatRepository
   *   The address format service.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $countryRepository
   *   The country service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, protected EntityFieldManagerInterface $entityFieldManager, protected RendererInterface $renderer, protected AddressFormatRepositoryInterface $addressFormatRepository, protected CountryRepositoryInterface $countryRepository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager'),
      $container->get('renderer'),
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $setting = [
      'provider' => 'osm',
      'location_field' => '',
      'width' => '',
      'height' => 300,
      'api_key' => '',
      'zoom' => 12,
    ];
    return $setting + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {

    // Get entity type and bundle.
    $entity_type = $form["#entity_type"];
    $bundle = $form["#bundle"];
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $options = [];
    // Supported field types.
    $typeSupport = ['geofield', 'geolocation'];
    foreach ($fieldDefinitions as $fieldDefinition) {
      if ($fieldDefinition instanceof FieldConfig) {
        $type = $fieldDefinition->getType();
        if (in_array($type, $typeSupport)) {
          $options[$fieldDefinition->getName()] = $fieldDefinition->label();
        }
      }
    }

    // Add location field select element if options are available.
    if (!empty($options)) {
      $elements['location_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Location field'),
        '#default_value' => $this->getSetting('location_field'),
        '#options' => $options,
        "#empty_option" => $this->t('- Select field -'),
        "#description" => $this->t('You must attach a location field to get the coordinates'),
      ];
    }

    $options = [
      'osm' => 'Open street map',
      'arcgis' => 'Esri ArcGIS',
      'google' => 'Google map iframe',
      'mapbox' => 'Map box',
      'mapquest' => 'Map quest',
      'tomtom' => 'Tom tom',
      'here' => 'Here',
    ];
    $apikeyRequire = [
      'mapbox' => 'Map box',
      'mapquest' => 'Map quest',
      'tomtom' => 'Tom tom',
      'here' => 'Here',
    ];
    // Add provider select element.
    $elements['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#default_value' => $this->getSetting('provider'),
      '#options' => $options,
      "#empty_option" => $this->t('- Select provider -'),
    ];
    $elements['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->getSetting('api_key'),
      "#description" => $this->t('Required for provider: @key', ['@key' => implode(', ', $apikeyRequire)]),
    ];
    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
    ];
    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
    ];
    $elements['zoom'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom'),
      '#default_value' => $this->getSetting('zoom'),
      '#options' => range(0, 15),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    if (empty($this->getSetting('location_field'))) {
      return [$this->t('You need to add a geolocation field')];
    }
    return [
      $this->t('Geolocation field: @location_field', ['@location_field' => $this->getSetting('location_field')]),
      $this->t('Provider: @provider', ['@provider' => $this->getSetting('provider')]),
      $this->t('Width: @width', ['@width' => $this->getSetting('width')]),
      $this->t('Height: @height', ['@height' => $this->getSetting('height')]),
      $this->t('Zoom: @zoom', ['@zoom' => $this->getSetting('zoom')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    $entity = $items->getEntity();
    $location_field = $this->getSetting('location_field');
    // Render normal address.
    if (empty($location_field)) {
      foreach ($items as $delta => $item) {
        $address = $this->getValues($item, $langcode);
        $googleUrl = 'https://www.google.com/maps/dir/' . strip_tags(str_replace("\n", ' ', $address));
        $link = [
          '#type' => 'link',
          '#title' => $address,
          '#url' => Url::fromUri($googleUrl),
        ];
        $element[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'address',
          '#value' => $this->renderer->render($link),
          '#cache' => [
            'contexts' => [
              'languages:' . LanguageInterface::TYPE_INTERFACE,
              'languages:' . LanguageInterface::TYPE_CONTENT,
            ],
          ],
        ];
      }
      return $element;
    }
    // Render with map.
    $points = $entity->get($location_field)->getValue();
    foreach ($items as $index => $item) {
      $points[$index]['address'] = $this->getValues($item, $langcode);
    }
    $longitude = $points[0]['lon'] ?? '';
    $latitude = $points[0]['lat'] ?? '';
    $address = $points[0]['address'] ?? '';
    $field_name = $this->fieldDefinition->getName();
    $id = 'map_' . $field_name;
    $element[] = [
      'map' => [
        '#id' => $id,
        '#type' => 'container',
        '#attributes' => [
          'id' => $id,
          'class' => ['map', $this->getSetting('provider')],
          'data-width' => $this->getSetting('width'),
          'data-height' => $this->getSetting('height'),
          'data-zoom' => $this->getSetting('zoom'),
          'data-lon' => $longitude,
          'data-lat' => $latitude,
          'data-address' => $address,
        ],
      ],
    ];
    $element["#attached"]["drupalSettings"]['address_map'][$id]['points'] = $points;
    $element["#attached"]["drupalSettings"]['address_map'][$id]['api_key'] = $this->getSetting('api_key');
    $element["#attached"]["library"][] = 'address_suggestion/address_map.' . $this->getSetting('provider');

    return $element;
  }

  /**
   * Gets the address values used for rendering.
   *
   * {@inheritDoc}
   */
  protected function getValues(AddressInterface $address, $langcode) {
    $countries = $this->countryRepository->getList($langcode);
    $country_code = $address->getCountryCode();
    $address_format = $this->addressFormatRepository->get($country_code);
    $format = $address_format->getFormat() . "\n" . '%country';
    $values = ['%country' => $countries[$country_code]];
    foreach (AddressField::getAll() as $field) {
      $getter = 'get' . ucfirst($field);
      $values["%$field"] = $address->$getter();
    }
    $address = trim(strtr($format, $values));
    return str_replace(['  ', "\n\n"], '', $address);
  }

}
