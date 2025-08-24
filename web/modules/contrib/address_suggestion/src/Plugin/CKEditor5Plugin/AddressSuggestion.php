<?php

namespace Drupal\address_suggestion\Plugin\CKEditor5Plugin;

use Drupal\address_suggestion\AddressProviderManager;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Utility\Random;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 Address Suggestion plugin.
 */
class AddressSuggestion extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * Constructs a new AddressSuggestion instance.
   *
   * {@inheritDoc}
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, protected AddressProviderManager $addressProvider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.address_provider'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => 'photon',
      'endpoint' => '',
      'api_key' => '',
      'username' => '',
      'password' => '',
      'token' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $endPointUrl = $options = $key = $state = $login = [];
    $addressProvider = $this->addressProvider->getDefinitions();
    foreach ($addressProvider as $provider) {
      $options[$provider['id']] = $provider['label'];
      if (!empty($provider['api'])) {
        $endPointUrl[] = $provider['label'] . ': ' . $provider['api'];
        if (!isset($provider['nokey'])) {
          $key[] = $provider['label'];
          $state[] = ['value' => $provider['id']];
        }
        if (isset($provider['login'])) {
          $login[] = ['value' => $provider['id']];
        }
      }
    }
    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Provider'),
      '#default_value' => $this->configuration['provider'],
      '#options' => $options,
      "#empty_option" => $this->t('- Select provider -'),
    ];
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Custom API Address endpoint'),
      '#default_value' => $this->configuration['endpoint'],
      "#description" => implode('<br/>', $endPointUrl),
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->configuration['api_key'],
      "#description" => $this->t('Required for provider:') . implode(', ', $key),
      '#states' => [
        'visible' => [
          'select[name="editor[settings][plugins][address_suggestion_plugin][provider]"]' => [
            $state,
          ],
        ],
      ],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('post.ch API username'),
      '#default_value' => $this->configuration['username'],
      '#states' => [
        'visible' => [
          'select[name="editor[settings][plugins][address_suggestion_plugin][provider]"]' => [$login],
        ],
      ],
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('post.ch API password'),
      '#default_value' => $this->configuration['password'],
      '#states' => [
        'visible' => [
          'select[name="editor[settings][plugins][address_suggestion_plugin][provider]"]' => [$login],
        ],
      ],
    ];
    $token = !empty($this->configuration['token']) ? $this->configuration['token'] : (new Random)->name();
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#default_value' => $token,
      '#description' => $this->t('Token is required to protect api'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['provider'] = $form_state->getValue('provider') ?? '';
    $this->configuration['endpoint'] = $form_state->getValue('endpoint') ?? '';
    $this->configuration['api_key'] = $form_state->getValue('api_key') ?? '';
    $this->configuration['username'] = $form_state->getValue('username') ?? '';
    $this->configuration['password'] = $form_state->getValue('password') ?? '';
    $this->configuration['token'] = $form_state->getValue('token') ?? '';
  }

  /**
   * {@inheritdoc}
   *
   * Get options values in editor config.
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $url = Url::fromRoute('address_suggestion.ckeditor', ['format' => $editor->id()], ['query' => ['token' => $this->configuration['token']]]);
    return [
      'address_suggestion' => ['endpoint' => $url->toString()],
    ];
  }

}
