<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Nominatim plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 * @AddressProvider(
 *   id = "nominatim",
 *   label = @Translation("Nominatim Openstreetmap"),
 *   api = "https://nominatim.openstreetmap.org/search",
 *   nokey = "TRUE",
 * )
 */
class Nominatim extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];
    if (empty($string) || strlen($string) <= 3) {
      return $results;
    }

    $size = !empty($settings['limit']) ? $settings['limit'] : 10;
    $query = [
      'q' => $string,
      'format' => 'json',
      'addressdetails' => 1,
      'group_hierarchy' => 1,
      'accept-language' => $this->languageManager->getCurrentLanguage()->getId(),
      'limit' => $size,
    ];
    if (!empty($settings['country'])) {
      $country = $settings['country'];
      $query['countrycodes'] = $country;
    }
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    $url .= '?' . http_build_query($query);

    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());
    // Some country have format Street number street name.
    $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
      'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
    ];
    if (!empty($content)) {
      foreach ($content as $key => $feature) {
        if (!empty($feature["address"])) {
          $results[$key]['street_name'] = $feature["address"]['road'];
          if (!empty($country) && in_array($country, $countryFormatSpecial)) {
            $results[$key]['street_name'] = $feature["address"]['house_number'] . ' ' . $results[$key]['street_name'];
          }
          else {
            $results[$key]['street_name'] .= ', ' . $feature["address"]['house_number'];
          }
          $town = $feature["address"]["town"] ?? '';
          if (empty($town) && !empty($feature["address"]["city"])) {
            $town = $feature["address"]["city"];
          }
          $results[$key]['administrative_area'] = $feature["address"]['state'] ?? '';
          $results[$key]['town_name'] = $town;
          $results[$key]['country_code'] = $feature["address"]["country_code"] ?? $settings['country'];
          $results[$key]['zip_code'] = $feature["address"]["postcode"] ?? '';
          $results[$key]["value"] = $results[$key]['label'] = $feature["display_name"] ?? '';
          $results[$key]['location'] = [
            'longitude' => $feature["lon"],
            'latitude' => $feature["lat"],
          ];
        }
      }
    }
    return $results;
  }

}
