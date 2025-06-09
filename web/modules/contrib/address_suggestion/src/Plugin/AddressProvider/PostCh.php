<?php

namespace Drupal\address_suggestion\Plugin\AddressProvider;

use Drupal\address_suggestion\AddressProviderBase;
use Drupal\Component\Utility\Html;

/**
 * Defines a PostCh plugin for address_suggestion.
 *
 * @package Drupal\address_suggestion\Plugin\AddressProvider
 * @AddressProvider(
 *   id = "post_ch",
 *   label = @Translation("Post CH"),
 *   api = "https://post.ch",
 *   login = "TRUE",
 * )
 */
class PostCh extends AddressProviderBase {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings) {
    $addresses = $this->prepareRequest($string, $settings);
    $results = [];

    foreach ($addresses as $address) {
      $street_name = Html::escape($address->Streetname);
      $house_number = Html::escape($address->HouseNumber);
      $zip_code = Html::escape($address->Zipcode);
      $town_name = Html::escape($address->TownName);
      $country_code = Html::escape($address->countryCode ?? 'CH');

      $results[] = [
        'street_name' => $street_name . " " . $house_number,
        'zip_code' => $zip_code,
        'town_name' => $town_name,
        'country_code' => $country_code,
        'label' => $street_name . " " . $house_number . " " . $zip_code . " " . $town_name,
      ];
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRequest($string, $settings) {
    $request = [
      'request' => [
        'Onrp' => 0,
        'Zipcode' => '',
        'ZipAddition' => '',
        'TownName' => '',
        'StrId' => 0,
        'Streetname' => $string,
        'HouseKey' => 0,
        'HouseNumber' => '',
        'HouseNumberAddition' => '',
      ],
      'zipOrderMode' => 0,
    ];

    // If last entered word starts with number, let's guess it's house number
    // ie: Schultheissenstrasse 2b.
    $pieces = explode(' ', $string);
    $pos_number = array_pop($pieces);

    if (!empty($pieces) && is_numeric($pos_number[0])) {
      $request['request']['Streetname'] = implode(' ', $pieces);
      $request['request']['HouseNumber'] = $pos_number;
    }

    $results = $this->request($request, $settings);

    // Sometimes guessing may be wrong, as numbers could be part of streetname
    // ie: Avenue 14-Avril
    // do fallback here.
    if (empty($results) && !empty($request['request']['HouseNumber'])) {
      $request['request']['Streetname'] = $string;
      $request['request']['HouseNumber'] = '';
      $results = $this->request($request, $settings);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function request($request, $settings) {
    if (empty($settings['api'])) {
      $settings['api'] = $this->pluginDefinition['api'];
    }
    $response = $this->client->post(
      !empty($settings['endpoint']) ? $settings['endpoint'] : $settings['api'],
      [
        'auth' => [
          $settings['username'],
          $settings['password'],
        ],
        'body' => json_encode($request),
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
      ]
    );
    $content = json_decode($response->getBody()->getContents());
    $results = $content->QueryAutoComplete2Result->AutoCompleteResult;
    // Limit number of results to 10.
    return array_slice($results, 0, 10);
  }

}
