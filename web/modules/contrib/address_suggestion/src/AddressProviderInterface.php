<?php

namespace Drupal\address_suggestion;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Address provider plugins.
 */
interface AddressProviderInterface extends PluginInspectionInterface {

  /**
   * {@inheritDoc}
   */
  public function processQuery($string, $settings);

}
