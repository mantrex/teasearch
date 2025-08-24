<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Serialization\Json;

/**
 * Defines a Photon Komoot plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 * @AddressProvider(
 *   id = "photon",
 *   label = @Translation("Photon Komoot"),
 *   api = "https://photon.komoot.io/api/",
 *   nokey = "TRUE"
 * )
 */
class PhotonKomoot extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $results = [];
    if (empty($string)) {
      return $results;
    }
    $size = !empty($settings['limit']) ? $settings['limit'] : 10;
    $query = [
      'limit' => $size,
      'q' => $string,
    ];
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    $langSupport = ['en', 'de', 'fr'];
    if (in_array($lang, $langSupport)) {
      $query['lang'] = $lang;
    }
    if (!empty($settings['country'])) {
      $country = $settings['country'];
      $query['q'] .= ', ' . $country;
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
      foreach ($content["features"] as $key => $feature) {
        if (!empty($properties = $feature["properties"])) {
          $street = !empty($properties['street']) ? $properties['street'] : $properties['name'];
          if (!empty($properties['housenumber'])) {
            if (!empty($country) && in_array($country, $countryFormatSpecial)) {
              $street = $properties['housenumber'] . ' ' . $street;
            }
            else {
              $street .= ', ' . $properties['housenumber'];
            }
          }

          $town = $properties["city"] ?? '';
          $district = $properties["district"] ?? '';
          $results[$key]['street_name'] = $street;
          $results[$key]['administrative_area'] = $properties['state'] ?? '';
          $results[$key]['town_name'] = $town;
          $results[$key]['district'] = $district;
          $results[$key]['zip_code'] = $properties["postcode"] ?? '';
          $results[$key]["value"] = $results[$key]['label'] = implode(', ', [
            $street,
            $district,
            $town,
          ]);
          if (!empty($coordinates = $feature["geometry"]["coordinates"])) {
            $results[$key]['location'] = [
              'longitude' => $coordinates[0],
              'latitude' => $coordinates[1],
            ];
          }
          $results[$key]['country_code'] = $properties["countrycode"] ?? $settings['country'];
        }
      }
    }
    return $results;
  }

}
