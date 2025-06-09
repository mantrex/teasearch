<?php

namespace Drupal\address_suggestion\Controller;

use Drupal\address_suggestion\AddressProviderManager;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AddressSuggestion extends ControllerBase {

  /**
   * {@inheritDoc}
   */
  public function __construct(protected AddressProviderManager $providerManager, protected AccountProxyInterface $account, protected ?StateInterface $state = NULL) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.address_provider'),
      $container->get('current_user'),
      $container->get('state'),
    );
  }

  /**
   * Handler for the autocomplete request.
   */
  public function handleAutocomplete(Request $request, $entity_type, $bundle, $field_name) {
    $results = [];
    $input = Xss::filter($request->query->get('q'));

    if (empty($input)) {
      return new JsonResponse($results);
    }
    $form_mode = 'default';
    $form_display = $this->entityTypeManager()->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . $form_mode)
      ->getComponent($field_name);
    $settings = $form_display['settings'] ?? [];
    $stateField = '';
    if (empty($settings["hide"])) {
      $country = $this->state->get(
        $stateField = implode('|', [$entity_type, $bundle, $field_name])
      );
    }
    if (!empty($country)) {
      $settings['country'] = $country;
      $settings['countryName'] = $this->state->get($stateField . '|Country');
    }
    $results = $this->getProviderResults($input, $settings);
    return new JsonResponse($results);
  }

  /**
   * Get Provider Results.
   *
   * {@inheritDoc}
   */
  public function getProviderResults($string, $settings = []) {
    $plugin_id = $settings['provider'];
    $plugin = $this->providerManager->createInstance($plugin_id);
    $addressProvider = $this->providerManager->getDefinitions()[$plugin_id];
    if (!empty($addressProvider["api"])) {
      $settings['api'] = $addressProvider["api"];
    }
    return $plugin->processQuery($string, $settings);
  }

  /**
   * Get ckeditor configuration for provider.
   *
   * {@inheritDoc}
   */
  public function ckeditor(Request $request, $format = 'basic_html') {
    $results = ['error' => 'Permission required'];
    $permission = $this->account->hasPermission('use text format ' . $format);
    $token = $request->query->get('token');
    if (!empty($token)) {
      $token = Xss::filter($token);
    }
    $editor = $this->entityTypeManager()->getStorage('editor')->load($format);
    $editorPlugins = $editor->getSettings()['plugins'];
    $editorToken = $editorPlugins['address_suggestion_plugin']['token'] ?? '';
    if ($editorToken != $token) {
      $permission = FALSE;
    }
    if (!$permission) {
      return new JsonResponse([
        'data' => $results,
        'method' => 'GET',
        'status' => 403,
      ]);
    }
    $input = Xss::filter($request->query->get('q'));
    $ckeditor = editor_load($format);
    $ckeditor_settings = $ckeditor->getSettings();
    if (!empty($ckeditor_settings['plugins']) && $ckeditor_settings['plugins']['address_suggestion_plugin']) {
      $settings = $ckeditor_settings['plugins']['address_suggestion_plugin'];
      if (!empty($request->query->get('country'))) {
        $settings['country'] = $request->query->get('country');
      }
      $results = $this->getProviderResults($input, $settings);
    }
    return new JsonResponse($results);
  }

}
