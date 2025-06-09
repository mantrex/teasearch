<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Map Quest plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "map_quest",
 *   label = @Translation("Map Quest api"),
 *   api = "https://www.mapquestapi.com/geocoding/v1/address",
 * )
 */
class MapQuest extends AddressProviderBase {

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
      'location' => $string,
      'c' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    if (!empty($settings['countryName'])) {
      $query['location'] .= ' ' . $settings['countryName'];
    }
    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (empty($content["results"])) {
      return $results;
    }
    if (!empty($content['results'][0]['locations'])) {
      foreach ($content["results"][0]["locations"] as $key => $component) {
        $results[$key]["street_name"] = $component["street"] ?? '';
        $results[$key]["town_name"] = $component["adminArea5"] ?? '';
        $results[$key]["administrative_area"] = $component["adminArea4"] ?? '';
        $results[$key]["zip_code"] = $component["postalCode"] ?? '';
        $results[$key]["country_code"] = $component["adminArea1"] ?? $settings['country'];
        $results[$key]["value"] = $results[$key]["label"] = implode(' ', [
          $component["street"],
          $component["postalCode"],
          $component["adminArea5"],
        ]);
        if (!empty($component['latLng'])) {
          $results[$key]['location'] = [
            'longitude' => $component['latLng']['lng'],
            'latitude' => $component['latLng']['lat'],
          ];
        }
      }
    }

    return $results;
  }

}
