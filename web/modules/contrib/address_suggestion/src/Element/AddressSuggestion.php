<?php

namespace Drupal\address_suggestion\Element;

use Drupal\address\Element\Address;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;

/**
 * Provides an address_suggestion form element.
 *
 * Usage example:
 *
 * @code
 * $form['address_suggestion'] = [
 *   '#type' => 'address_suggestion',
 * ];
 * @endcode
 *
 * @FormElement("address_suggestion")
 */
class AddressSuggestion extends Address {

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    $info['#process'][] = [
      get_class($this),
      'processAutocomplete',
    ];

    return $info;
  }

  /**
   * {@inheritDoc}
   */
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    $element["#attached"]["library"][] = 'address_suggestion/address_suggestion';
    $field_name = $element["#field_name"];
    $bundle = $element["#bundle"];
    $entity_type_id = $element["#entity_type"];
    $form_display = EntityFormDisplay::load("$entity_type_id.$bundle.default");
    $address_component = $form_display ? $form_display->getComponent($field_name) : NULL;
    $isFormSetting = $complete_form["#form_id"] == 'field_config_edit_form';
    if (!$isFormSetting && !empty($address_component) && !empty($address_component['settings']['hide'])) {
      $value = $element['#value'];
      // Set default country.
      if (empty($value['country_code'])) {
        $system_country = \Drupal::config('system.date')->get('country.default');
        $value['country_code'] = !empty($system_country) ? $system_country : 'US';
      }
      $element = static::addressElements($element, $value);
      $element["address_line"] = $element["address_line1"];
      $element["address_line"]["#required"] = FALSE;
      $element["address_line"]["#attributes"] = [
        "class" => ['address-line', 'address-suggestion'],
        'placeholder' => t('Please start typing your address...'),
        'maxlength' => 255,
        'data-hide' => 'true',
      ];
      $element["address_line"]['#default_value'] = implode(' ', array_filter([
        $value['address_line1'],
        $value['address_line2'],
        $value['postal_code'],
        $value['locality'],
        $value['administrative_area'],
        $value['country_code'],
      ]));
      $element["address_line"]['#autocomplete_route_name'] = 'address_suggestion.addresses';
      $element["address_line"]['#autocomplete_query_parameters'] = ['country' => FALSE];
      $element["address_line"]['#autocomplete_route_parameters'] = [
        'entity_type' => $element["#entity_type"],
        'bundle' => $element["#bundle"],
        'field_name' => $element["#field_name"],
        'country' => $value['country_code'],
      ];

      $hideAddress = [
        'country_code',
        'address_line1',
        'address_line2',
        'address_line3',
        'postal_code',
        'locality',
        'dependent_locality',
        'sorting_code',
        'administrative_area',
      ];
      foreach ($hideAddress as $fieldAddress) {
        if (!empty($element[$fieldAddress]['#type'])) {
          $element[$fieldAddress]['#type'] = 'hidden';
          $element[$fieldAddress]["#required"] = FALSE;
        }
      }
      $element['country_code']['#attributes'] = ['class' => ['country']];
      return $element;
    }
    $element["address_line1"]['#attributes']['class'][] = 'address-suggestion';
    $element["address_line1"]['#autocomplete_route_name'] = 'address_suggestion.addresses';
    $element["address_line1"]['#autocomplete_route_parameters'] = $parameters = [
      'entity_type' => $element["#entity_type"],
      'bundle' => $element["#bundle"],
      'field_name' => $element["#field_name"],
    ];
    $values = $form_state->getValue($element["#field_name"]);
    if (!empty($values[0]) && !empty($values[0]["address"]["country_code"])) {
      $listCountry = CountryManager::getStandardList();
      $country = $values[0]["address"]["country_code"];
      \Drupal::state()->set($stateField = implode('|', $parameters), $country);
      if (!empty($listCountry[$country])) {
        $country = (string) $listCountry[$country];
      }
      \Drupal::state()->set($stateField . '|Country', $country);
      $element["address_line1"]['#autocomplete_route_parameters']['country'] = $values[0]["address"]["country_code"];
    }
    $element["address_line1"]["#attributes"]['placeholder'] = t('Please start typing your address...');
    return $element;
  }

}
