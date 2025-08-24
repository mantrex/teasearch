<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Mapbox Geocoding plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 * @AddressProvider(
 *   id = "mapbox_geocoding",
 *   label = @Translation("Mapbox Geocoding"),
 *   api = "https://api.mapbox.com/geocoding/v5/mapbox.places/",
 * )
 */
class MapboxGeocoding extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];

    if (!empty($settings['countryName'])) {
      $string .= ', ' . $settings['countryName'];
    }
    if (!empty($settings['country'])) {
      $country = $settings['country'];
    }
    $token = $settings['api_key'];
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    $url .= $string . '.json';
    $size = !empty($settings['limit']) ? $settings['limit'] : 10;
    $query = [
      'access_token' => $token,
      'autocomplete' => 'true',
      'types' => 'address',
      'limit' => $size,
    ];

    $url .= '?' . http_build_query($query);

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());
    // Some country have format Street number street name.
    $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
      'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
    ];

    foreach ($content["features"] as $key => $feature) {
      $results[$key]['street_name'] = $feature["text"];
      if (!empty($feature["address"])) {
        if (!empty($country) && in_array($country, $countryFormatSpecial)) {
          $results[$key]['street_name'] = $feature["address"] . ' ' . $results[$key]['street_name'];
        }
        else {
          $results[$key]['street_name'] .= isset($feature["address"]) ? ', ' . $feature["address"] : '';
        }
      }

      if (!empty($feature["context"])) {
        foreach ($feature["context"] as $context) {
          if (str_contains($context['id'], 'region')) {
            $results[$key]['administrative_area'] = $context['text'];
            if (!empty($context['short_code'])) {
              [$countryCode, $explode_region] = explode('-', $context['short_code']);
              $results[$key]['country_code'] = $countryCode;
              $results[$key]['administrative_area'] = $explode_region;
            }
          }
          if (str_contains($context['id'], 'postcode')) {
            $results[$key]['zip_code'] = $context["text"];
          }
          if (str_contains($context['id'], 'locality')) {
            $results[$key]['town_name'] = $context["text"];
          }
          if (str_contains($context['id'], 'place')) {
            $results[$key]['town_name'] = $context["text"];
          }
        }
      }

      $results[$key]["value"] = $results[$key]['label'] = $feature["place_name"] ?? '';
      $results[$key]['location'] = [
        'longitude' => $feature["geometry"]["coordinates"][0],
        'latitude' => $feature["geometry"]["coordinates"][1],
      ];
    }

    return $results;
  }

}
