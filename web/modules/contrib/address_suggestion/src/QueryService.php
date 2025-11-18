<?php

namespace Drupal\address_suggestion;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\State;

/**
 * Service Class to query.
 *
 * @package Drupal\mymodule\Services
 */
class QueryService {

  /**
   * {@inheritDoc}
   */
  public function __construct(protected AddressProviderManager $providerManager, protected EntityTypeManagerInterface $entityTypeManager, protected State $state) {
  }

  /**
   * {@inheritDoc}
   */
  public function getData($entity_type, $bundle, $field_name, $query) {
    $form_mode = 'default';
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_mode)
      ->getComponent($field_name);
    $settings = $form_display['settings'] ?? [];
    $country = $this->state->get(
      $stateField = implode('|', [$entity_type, $bundle, $field_name])
    );
    if (!empty($country)) {
      $settings['country'] = $country;
      $settings['countryName'] = $this->state->get($stateField . '|Country');
    }
    return $this->getProviderResults($query, $settings);
  }

  /**
   * Get Provider Results.
   *
   * @inheritDoc
   */
  public function getProviderResults($string, $settings = []) {
    $plugin_id = $settings['provider'];
    $plugin = $this->providerManager->createInstance($plugin_id);
    return $plugin->processQuery($string, $settings);
  }

}
