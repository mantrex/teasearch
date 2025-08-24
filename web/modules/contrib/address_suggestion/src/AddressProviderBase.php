<?php

namespace Drupal\address_suggestion;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Address provider plugins.
 */
abstract class AddressProviderBase extends PluginBase implements AddressProviderInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  protected $client;

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, public LanguageManagerInterface $languageManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = new Client();
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Swap config with a values from config object.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_id' => $this->pluginId,
    ];
  }

}
