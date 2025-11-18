<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\address_suggestion\AddressProviderBase;
use Drupal\address_suggestion\Attribute\AddressProvider;

/**
 * Defines a GoogleMaps plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 */
#[AddressProvider(
  id: 'capost',
  label: new TranslatableMarkup('Canada post'),
  api: 'https://ws1.postescanada-canadapost.ca/addresscomplete/interactive/find/v2.10/json3ex.ws',
)]
class CAPost extends AddressProviderBase {

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
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    $query = [
      'Key' => $settings['api_key'],
      'SearchTerm' => $string,
      'LanguagePreference' => $lang,
      'provider' => 'AddressComplete',
      'package' => 'Interactive',
      'service' => 'Find',
      'version' => '2.1',
      'endpoint' => 'json3ex.ws',
    ];
    if (!empty($settings['country'])) {
      $country = $query['Country'] = $settings['country'];
    }

    $response = $this->client->request('GET', $url, [
      'query' => $query,
    ]);

    $content = Json::decode($response->getBody());

    if (!empty($content["error_message"])) {
      return $results;
    }

    $content = Json::decode($response->getBody());
    if (!empty($content['Items'])) {
      $url = "https://ws1.postescanada-canadapost.ca/addresscomplete/interactive/retrieve/v2.11/json3ex.ws";
      foreach ($content['Items'] as $prediction) {
        $query = [
          'Key' => $settings['api_key'],
          'Id' => $prediction['id'],
          'LanguagePreference' => $lang,
          'provider' => 'AddressComplete',
          'package' => 'Retrieve',
          'service' => 'Find',
          'version' => '2.11',
          'endpoint' => 'json3ex.ws',
        ];
        $response = $this->client->request('GET', $url, [
          'query' => $query,
        ]);
        $detailsData = Json::decode($response->getBody());
        foreach ($detailsData as $result) {
          $content["results"][] = $result;
        }
      }
    }

    // Some country have format Street number street name.
    $countryFormatSpecial = ['FR', 'CA', 'IE', 'IN', 'IL', 'HK', 'MY', 'OM',
      'NZ', 'PH', 'SA', 'SE', 'SG', 'LK', 'TH', 'UK', 'US', 'VN',
    ];
    foreach ($content["results"] as $key => $result) {
      $streetNumber = '';
      foreach ($result["Results"] as $component) {
        switch ($component["types"][0]) {
          case "street_number":
            $streetNumber = $component["Label"] ?? '';
            break;

          case "route":
            $results[$key]["street_name"] = $component["Street"] ?? '';
            break;

          case "locality":
            $results[$key]["town_name"] = $component["City"] ?? '';
            break;

          case "administrative_area_level_1":
            $results[$key]["administrative_area"] = $component["AdminAreaName"] ?? '';
            break;

          case "postal_code":
            $results[$key]["zip_code"] = $component["PostalCode"] ?? '';
            break;

          case "country":
            $results[$key]["country_code"] = $component["CountryIso2"] ?? $settings['country'];
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
      if (!empty($result["location"])) {
        $results[$key]['location'] = [
          'longitude' => $result['location']['lng'],
          'latitude' => $result['location']['lat'],
        ];
      }
    }

    return $results;
  }

}
