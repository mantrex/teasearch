<?php

namespace Drupal\address_suggestion\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldLatLonWidget;

/**
 * Widget implementation of the 'address_geofield_default' widget.
 */
#[FieldWidget(
  id: "address_geofield_default",
  label: new TranslatableMarkup('Address suggestion'),
  field_types: ['geofield']
)]
class AddressGeofieldWidget extends GeofieldLatLonWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'location_field' => '',
      'provider' => '',
      'api_key' => '',
      'hide' => TRUE,
      'show_map' => FALSE,
      'wrapper_type' => 'fieldset',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_map'] = [
      '#type' => 'checkbox',
      '#title' => 'Show map',
      '#default_value' => $this->getSetting('show_map'),
      '#description' => $this->t('It shows mini map.'),
    ];
    // Build options for providers.
    $options = [];
    $key = [];
    $addressProvider = \Drupal::service('plugin.manager.address_provider')->getDefinitions();
    foreach ($addressProvider as $provider) {
      $options[$provider['id']] = $provider['label'];
      if (!empty($provider['api'])) {
        if (!isset($provider['nokey'])) {
          $key[] = $provider['label'];
        }
      }
    }
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
      "#description" => $this->t('Required for provider: @key', ['@key' => implode(', ', $key)]),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#attributes']['class'][] = 'js-hide';
    $country = \Drupal::config('system.date')->get('country.default');
    $fieldDefinition = $this->fieldDefinition;
    $settings = $this->getSettings();
    $parameters = [
      'entity_type' => $fieldDefinition->getTargetEntityTypeId(),
      'bundle' => $fieldDefinition->getTargetBundle(),
      'field_name' => $items->getName(),
    ];
    $element['suggestion'] = [
      '#title' => $element['value']['#title'],
      '#type' => 'textfield',
      '#description' => $this->t('Longitude: <span class="lon">@lon</span>, Latitude: <span class="lat">@lat</span>', [
        '@lon' => $element["value"]["#default_value"]["lon"],
        '@lat' => $element["value"]["#default_value"]["lat"],
      ]),
      '#attributes' => [
        'class' => ['address-suggestion-widget'],
        'placeholder' => $this->t('Find an address'),
      ],
      '#autocomplete_route_name' => 'address_suggestion.addresses',
      '#autocomplete_query_parameters' => ['country' => $country ?: FALSE],
      '#autocomplete_route_parameters' => $parameters,
    ];
    $settings['type_field'] = 'geofield';
    $settings['location_field'] = $items->getName();
    if (!empty($form['#parents'])) {
      $settings['location_field'] .= ']';
    }
    $form['#attached']['drupalSettings']['address_suggestion'] = $settings;
    $form["#attached"]["library"][] = 'address_suggestion/address_suggestion_widget';

    if ($settings['show_map']) {
      $id = 'map_' . $items->getName();
      $points = [
        'lon' => $element["value"]["#default_value"]["lon"],
        'lat' => $element["value"]["#default_value"]["lat"],
      ];
      $element['map'] = [
        '#id' => $id,
        '#type' => 'container',
        '#attributes' => [
          'id' => $id,
          'class' => ['map', 'osm'],
          'data-width' => $this->getSetting('width') ?? '',
          'data-height' => $this->getSetting('height') ?? 300,
          'data-zoom' => $this->getSetting('zoom') ?? 12,
          'data-lon' => $element["value"]["#default_value"]["lon"],
          'data-lat' => $element["value"]["#default_value"]["lat"],
        ],
      ];
      $form["#attached"]["drupalSettings"]['address_map'][$id]['points'] = $points;
      $form["#attached"]["library"][] = 'address_suggestion/address_map.osm';
    }
    return $element;
  }

}
