<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Here plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 *
 * @AddressProvider(
 *   id = "here",
 *   label = @Translation("Here api"),
 *   api = "https://geocode.search.hereapi.com/v1/geocode"
 * )
 */
class Here extends AddressProviderBase {

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
      'apiKey' => $settings['api_key'],
      'q' => $string,
      'c' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    if (!empty($settings['countryName'])) {
      $query['q'] .= ' ' . $settings['countryName'];
    }
    if (!empty($settings['country'])) {
      $country = $settings['country'];
    }
    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (empty($content["items"])) {
      return $results;
    }
    if (!empty($content['items'])) {
      // Some country have format Street number street name.
      $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
        'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
      ];
      foreach ($content['items'] as $key => $result) {
        $component = $result['address'];
        $results[$key]["street_name"] = $component["street"];
        $streetNumber = $component['houseNumber'] ?? '';
        if (!empty($streetNumber) && !empty($component['streetName'])) {
          if (!empty($country) && in_array($country, $countryFormatSpecial)) {
            $results[$key]['street_name'] = $streetNumber . ' ' . $component['streetName'];
          }
          else {
            $results[$key]['street_name'] = $component['streetName'] . ', ' . $streetNumber;
          }
        }
        $results[$key]["town_name"] = $component["city"] ?? '';
        $results[$key]["administrative_area"] = $component["county"] ?? '';
        $results[$key]["zip_code"] = $component["postalCode"] ?? '';
        $results[$key]["country_code"] = $component["countryCode"] ?? $settings['country'];
        $results[$key]["value"] = $results[$key]["label"] = $component["label"] ?? '';
        if (!empty($result['position'])) {
          $results[$key]['location'] = [
            'longitude' => $result['position']['lng'],
            'latitude' => $result['position']['lat'],
          ];
        }
      }
    }

    return $results;
  }

}
