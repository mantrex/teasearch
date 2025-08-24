<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Vietnam post plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 * @AddressProvider(
 *   id = "vnpost",
 *   label = @Translation("Vietnam post"),
 *   api = "https://maps.vnpost.vn/api/autocomplete",
 * )
 */
class VNPost extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $token = $settings['api_key'];
    if (empty($string) && empty($token)) {
      return $results;
    }
    $url = !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'];
    $size = !empty($settings['limit']) ? $settings['limit'] : 10;
    $query = [
      'apikey' => $token,
      'api-version' => '1.1',
      'layers' => 'address',
      'size' => $size,
      'text' => $string,
    ];
    $url .= '?' . http_build_query($query);
    $response = $this->client->request('GET', $url);
    $content = Json::decode($response->getBody());
    $subdivisions = \Drupal::service('address.subdivision_repository')
      ->getList(['VN'], 'vi');
    $province = array_flip($subdivisions);
    if (!empty($content['code']) && $content['code'] == 'OK') {
      foreach ($content['data']["features"] as $key => $feature) {
        $properties = $feature["properties"];
        $city = '';
        if (strpos($properties["region"], 'Hồ Chí Minh') === FALSE) {
          $city = str_replace(['Thành Phố '], '', $properties["region"]);
        }
        // Exception 'Hồ Chí Minh'.
        $results[$key]['street_name'] = $properties["label"] ?? '';
        $results[$key]['town_name'] = $properties["county"] ?? '';
        $results[$key]['district'] = $properties["locality"] ?? '';
        if (!empty($province[$city])) {
          $results[$key]['state'] = $province[$city];
        }
        $results[$key]['zip_code'] = $properties["postcode"] ?? '';
        $results[$key]['name'] = $properties["name"];
        $results[$key]["value"] = $results[$key]['label'] = $properties["label"];
        $results[$key]['location'] = [
          'longitude' => $feature["geometry"]["coordinates"][0],
          'latitude' => $feature["geometry"]["coordinates"][1],
        ];
        $results[$key]['country_code'] = $settings['country'] ?? 'VN';
      }
    }

    return $results;
  }

}
