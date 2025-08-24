<?php

namespace Drupal\address_suggestion\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Action description.
 *
 * @Action(
 *   id = "address_suggestion_action",
 *   label = @Translation("Address suggestion action"),
 *   description = @Translation("Address suggestion update geolocation field"),
 *   type = ""
 * )
 */
class AddressSuggestionAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $config = $this->getConfiguration();
    $addressField = $entity->get($config['field_address'])->getValue();
    if (!empty($addressField[0])) {
      $address = implode(' ', array_filter([
        $addressField[0]['address_line1'],
        $addressField[0]['address_line2'],
        $addressField[0]['postal_code'],
        $addressField[0]['locality'],
        $addressField[0]["country_code"],
      ]));
    }
    else {
      $address = $entity->get($config['field_address'])->value;
    }
    if (!empty($address)) {
      $addressService = \Drupal::service('address_suggestion.query_services');
      $results = $addressService->getData($entity->getEntityTypeId(), $entity->bundle(), $config['field_address'], $address);
      $result = current($results);
      if (!empty($result['location'])) {
        $latitude = $result['location']['latitude'];
        $longitude = $result['location']['longitude'];
        if ((!empty($latitude) && !empty($longitude))) {
          $field = $this->view->getDisplay()->getOption('fields')[$config['field_geo']];
          // Get Geo field service.
          if (in_array($field['type'], [
            'geofield_default',
            'geofield_latlon',
            'address_map',
          ])) {
            $geoService = \Drupal::service('geofield.wkt_generator');
            $point = $geoService->wktBuildPoint([$longitude, $latitude]);
            $entity->set($config['field_geo'], $point);
            $entity->save();
          }
          // Set geolocation field.
          if (in_array($field['type'], [
            'geolocation_latlng',
            'geolocation_map',
            'geolocation_sexagesimal',
            'geolocation_token',
          ])) {
            $entity->set($config['field_geo'], ['lat' => $latitude, 'lng' => $longitude]);
            $entity->save();
          }
        }
      }
    }
    $label_field = $entity->getEntityType()->getKey('label');
    $title = $label_field ? $entity->get($label_field)?->value : '';
    return $this->t('Update @title', ['@title' => $title]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $addressFields = [];
    $geoFields = [];
    $fields = $this->view->getDisplay()->getOption('fields');
    foreach ($fields as $field_name => $field_options) {
      if (!empty($field_options['type'])) {
        if (in_array($field_options['type'], [
          'string',
          'address_default',
          'address_plain',
          'address_suggestion_map',
        ])) {
          $addressFields[$field_name] = $field_options["label"];
        }
        if (in_array($field_options['type'], [
          'geofield_default',
          'address_map',
          'geofield_latlon',
          'geolocation_latlng',
          'geolocation_map',
          'geolocation_sexagesimal',
          'geolocation_token',
        ])) {
          $geoFields[$field_name] = $field_options["label"];
        }
      }
    }
    $form['field_address'] = [
      '#type' => 'select',
      '#title' => $this->t('Address field'),
      '#default_value' => $values['field_address'] ?? '',
      '#options' => $addressFields,
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Address field in entity.'),
    ];
    $form['field_geo'] = [
      '#type' => 'select',
      '#title' => $this->t('Geolocation field'),
      '#default_value' => $values['field_geo'] ?? '',
      '#options' => $geoFields,
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Geolocation field in entity'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
