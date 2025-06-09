<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Tomtom plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "tomtom",
 *   label = @Translation("Tomtom api"),
 *   api = "https://api.tomtom.com/search/2/geocode/"
 * )
 */
class TomTom extends AddressProviderBase {

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
    ];
    if (!empty($settings['countryName'])) {
      $string .= ' ' . $settings['countryName'];
    }
    if (!empty($settings['country'])) {
      $country = $settings['country'];
    }
    $url .= $string . '.json';
    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (empty($content["results"])) {
      return $results;
    }
    if (!empty($content['results'])) {
      // Some country have format Street number street name.
      $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
        'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
      ];
      foreach ($content["results"] as $key => $result) {
        if (empty($component = $result['address'])) {
          continue;
        }
        $results[$key]["street_name"] = $component["streetName"] ?? '';
        $streetNumber = $component['streetNumber'] ?? '';
        if (!empty($streetNumber) && !empty($component['streetName'])) {
          if (!empty($country) && in_array($country, $countryFormatSpecial)) {
            $results[$key]['street_name'] = $streetNumber . ' ' . $component['streetName'];
          }
          else {
            $results[$key]['street_name'] = $component['streetName'] . ', ' . $streetNumber;
          }
        }
        $results[$key]["town_name"] = $component["localName"] ?? '';
        $results[$key]["administrative_area"] = $component["countrySecondarySubdivision"] ?? '';
        $results[$key]["zip_code"] = $component["postalCode"] ?? '';
        $results[$key]["value"] = $results[$key]["label"] = $component["freeformAddress"] ?? '';
        if (!empty($result['position'])) {
          $results[$key]['location'] = [
            'longitude' => $result['position']['lon'],
            'latitude' => $result['position']['lat'],
          ];
        }
        $results[$key]['country_code'] = $component["countryCode"] ?? $settings['country'];
      }
    }

    return $results;
  }

}
