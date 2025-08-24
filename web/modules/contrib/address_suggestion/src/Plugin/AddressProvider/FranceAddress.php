<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a France address plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "france_address",
 *   label = @Translation("France Address"),
 *   api = "https://api-adresse.data.gouv.fr/search",
 *   nokey = "TRUE"
 * )
 */
class FranceAddress extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];

    $explode_query = explode('||', $string);
    $string = $explode_query[0];
    if (empty($string) || strlen($string) <= 3) {
      return $results;
    }
    $query = [
      'autocomplete' => 0,
      'limit' => 10,
      'q' => $string,
    ];
    if (strlen($string) == 5 && is_numeric($string)) {
      $query['postcode'] = $string;
    }
    $types = ['municipality', 'locality', 'street', 'housenumber'];
    if (!empty($explode_query[1]) && in_array($explode_query[1], $types)) {
      $query['type'] = $explode_query[1];
    }
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    $url .= '?' . http_build_query($query);

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());
    if (!empty($content["features"])) {
      foreach ($content["features"] as $key => $feature) {
        if (!empty($feature["properties"])) {
          $results[$key]['street_name'] = $feature["properties"]["name"] ?? '';
          $results[$key]['town_name'] = $feature["properties"]["city"] ?? '';
          $results[$key]['zip_code'] = $feature["properties"]["postcode"] ?? '';
          $results[$key]['country_code'] = $settings['country'] ?? 'FR';
          $results[$key]["value"] = $results[$key]['label'] = $feature["properties"]["label"] ?? '';
        }
        if (!empty($feature["geometry"])) {
          $results[$key]['location'] = [
            'longitude' => $feature["geometry"]["coordinates"][0],
            'latitude' => $feature["geometry"]["coordinates"][1],
          ];
        }
      }
    }

    return $results;
  }

}
