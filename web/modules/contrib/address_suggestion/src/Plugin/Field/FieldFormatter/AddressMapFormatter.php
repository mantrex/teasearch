<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Address Suggestion Map' formatter.
 *
 * @FieldFormatter(
 *   id = "address_map",
 *   label = @Translation("Address Suggestion Map"),
 *   field_types = {"geofield"},
 * )
 */
class AddressMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $setting = [
      'provider' => 'osm',
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
    $options = [
      'osm' => 'Open street map',
      'arcgis' => 'Esri ArcGIS',
      'mapbox' => 'Mapbox',
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
    return [
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
    $points = [];
    foreach ($items as $item) {
      $points[] = ['lon' => $item->lon, 'lat' => $item->lat];
    }
    $longitude = $points[0]['lon'] ?? '';
    $latitude = $points[0]['lat'] ?? '';
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
        ],
      ],
    ];
    $element["#attached"]["drupalSettings"]['address_map'][$id]['points'] = $points;
    $element["#attached"]["drupalSettings"]['address_map'][$id]['api_key'] = $this->getSetting('api_key');
    $element["#attached"]["library"][] = 'address_suggestion/address_map.' . $this->getSetting('provider');

    return $element;
  }

}
