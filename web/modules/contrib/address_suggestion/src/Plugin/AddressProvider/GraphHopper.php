<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Graph Hopper plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "graph_hopper",
 *   label = @Translation("Graph Hopper api"),
 *   api = "https://graphhopper.com/api/1/geocode",
 * )
 */
class GraphHopper extends AddressProviderBase {

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
    if (!empty($settings['countryName'])) {
      $string .= ' ' . $settings['countryName'];
    }
    $query = [
      'q' => $string,
      'key' => $settings['api_key'],
    ];
    if (!empty($settings['country'])) {
      $country = $settings['country'];
    }
    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (empty($content["hits"])) {
      return $results;
    }
    if (!empty($content['hits'])) {
      // Some country have format Street number street name.
      $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
        'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
      ];
      foreach ($content["hits"] as $key => $component) {
        $results[$key]["street_name"] = $component["street"] ?? '';
        $streetNumber = $component['housenumber'] ?? '';
        if (!empty($streetNumber) && !empty($component['street'])) {
          if (!empty($country) && in_array($country, $countryFormatSpecial)) {
            $results[$key]['street_name'] = $streetNumber . ' ' . $component['street'];
          }
          else {
            $results[$key]['street_name'] = $component['street'] . ', ' . $streetNumber;
          }
        }
        $results[$key]["town_name"] = $component["city"] ?? '';
        $results[$key]["administrative_area"] = $component["state"] ?? '';
        $results[$key]["zip_code"] = $component["postcode"] ?? '';
        $results[$key]["country_code"] = $component["countrycode"] ?? $settings['country'];
        $results[$key]["value"] = $results[$key]["label"] = implode(' ', [
          $results[$key]['street_name'],
          $component["postcode"],
          $component["city"],
        ]);
        if (!empty($component['point'])) {
          $results[$key]['location'] = [
            'longitude' => $component['point']['lng'],
            'latitude' => $component['point']['lat'],
          ];
        }
      }
    }

    return $results;
  }

}
