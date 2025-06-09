<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a DistanceMatrix plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "distance_matrix",
 *   label = @Translation("Distance Matrix"),
 *   api = "https://api.distancematrix.ai/maps/api/geocode/json",
 * )
 */
class DistanceMatrix extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    if (empty($string) && empty($settings['api_key'])) {
      return $results;
    }
    $query = [
      'key' => $settings['api_key'],
      'address' => $string,
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
      'types' => 'address',
      'location' => '0,0',
      'radius' => 1,
    ];
    if (!empty($settings['countryName'])) {
      $query['components'] = 'country:' . $settings['countryName'];
    }
    if (!empty($settings['country'])) {
      $country = $settings['country'];
    }

    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (empty($content["result"])) {
      return $results;
    }

    $content = Json::decode($response->getBody());

    // Some country have format Street number street name.
    $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
      'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
    ];
    foreach ($content["result"] as $key => $result) {
      $streetNumber = '';
      foreach ($result["address_components"] as $component) {
        $results[$key]["country_code"] = $settings['country'];
        switch ($component["types"][0]) {
          case "street_number":
            $streetNumber = $component["long_name"] ?? '';
            break;

          case "route":
            $results[$key]["street_name"] = $component["long_name"] ?? '';
            break;

          case "locality":
            $results[$key]["town_name"] = $component["long_name"] ?? '';
            break;

          case "administrative_area_level_1":
            $results[$key]["administrative_area"] = $component["short_name"] ?? '';
            break;

          case "postal_code":
            $results[$key]["zip_code"] = $component["long_name"] ?? '';
            break;

          case "country":
            $results[$key]["country_code"] = $component["short_name"] ?? '';
            break;
        }
      }
      if (!empty($country) && in_array($country, $countryFormatSpecial)) {
        $results[$key]['street_name'] = $streetNumber . ' ' . $results[$key]['street_name'];
      }
      elseif (!empty($streetNumber)) {
        $results[$key]['street_name'] .= ', ' . $streetNumber;
      }

      $results[$key]["value"] = $results[$key]["label"] = $result["formatted_address"];
      if (!empty($result["geometry"])) {
        $results[$key]['location'] = [
          'longitude' => $result['geometry']['location']['lng'],
          'latitude' => $result['geometry']['location']['lat'],
        ];
      }
    }

    return $results;
  }

}
