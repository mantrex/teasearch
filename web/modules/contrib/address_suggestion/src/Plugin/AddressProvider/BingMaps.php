<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a BingMaps plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "bing_maps",
 *   label = @Translation("Bing Maps"),
 *   api = "http://dev.virtualearth.net/REST/v1/Autosuggest",
 * )
 */
class BingMaps extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];

    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    if (empty($string) && empty($settings['api_key'])) {
      return $results;
    }
    $query = [
      'key' => $settings['api_key'],
      'q' => $string,
      'c' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    if (!empty($settings['country'])) {
      $country = $settings['country'];
      $query['cf'] = $country;
    }
    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (!empty($content["Error"]) || $content['statusCode'] != 200) {
      return $results;
    }
    $urlLocation = 'http://dev.virtualearth.net/REST/v1/Locations';
    if (!empty($content["resourceSets"][0]["resources"][0]["value"])) {
      // Some country have format Street number street name.
      $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
        'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
      ];
      foreach ($content["resourceSets"][0]["resources"][0]["value"] as $key => $result) {
        $component = $result['address'];
        $results[$key]["street_name"] = $component["addressLine"] ?? '';
        $streetNumber = $component['houseNumber'] ?? '';
        if (!empty($streetNumber) && !empty($component['streetName'])) {
          if (!empty($country) && in_array($country, $countryFormatSpecial)) {
            $results[$key]['street_name'] = $streetNumber . ' ' . $component['streetName'];
          }
        }
        $results[$key]["town_name"] = $component["locality"] ?? '';
        $results[$key]["administrative_area"] = $component["adminDistrict"] ?? '';
        $results[$key]["zip_code"] = $component["postalCode"] ?? '';
        $results[$key]["country_code"] = $component["countryRegionIso2"] ?? $settings['country'];
        $results[$key]["value"] = $results[$key]["label"] = $component["formattedAddress"] ?? '';
        // Find geo location.
        if (!empty($settings["location_field"])) {
          $query['q'] = $component["formattedAddress"];
          $response = $this->client->request('GET', $urlLocation, [
            'query' => $query,
          ]);
          $location = Json::decode($response->getBody());
          if ($location['statusCode'] == 200) {
            $geo = $location["resourceSets"][0]["resources"][0];
            if (!empty($geo['point']['coordinates'])) {
              $results[$key]['location'] = [
                'latitude' => $geo['point']['coordinates'][0],
                'longitude' => $geo['point']['coordinates'][1],
              ];
            }
          }
        }

      }
    }

    return $results;
  }

}
