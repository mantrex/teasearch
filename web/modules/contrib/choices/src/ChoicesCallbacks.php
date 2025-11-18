<?php

namespace Drupal\choices;

use Drupal\choices\Form\ConfigForm;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a Choices.js element callback.
 */
class ChoicesCallbacks implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Pre-render handler for select elements.
   */
  public static function preRender($element) {
    $attached = &$element['#attached'];
    // Load Choices.js if applicable.
    if (static::isGloballyApplicable()) {
      $cssSelector = \Drupal::config('choices.settings')->get('css_selector');

      // Add choices global settings:
      $globalConfigurationOptionsString = \Drupal::config('choices.settings')->get('configuration_options');
      $globalConfigurationOptions = !empty($globalConfigurationOptionsString) ? Json::decode($globalConfigurationOptionsString) : [];
      $attached['drupalSettings']['choices']['global']['configurationOptions'] = (object) $globalConfigurationOptions;

      // Add Choices global css selector:
      // Replace multiple spaces and newlines with a comma:
      $attached['drupalSettings']['choices']['global']['cssSelector'] = trim(preg_replace('/\s\s+/', ',', $cssSelector));

      // Initialize the global library:
      $attached['library'][] = 'choices/global';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected static function isGloballyApplicable() {
    $include = \Drupal::config('choices.settings')->get('include');
    if ($include === ConfigForm::CHOICES_INCLUDE_EVERYWHERE) {
      return TRUE;
    }

    $isAdminRoute = \Drupal::service('router.admin_context')->isAdminRoute();
    switch ($include) {
      case ConfigForm::CHOICES_INCLUDE_ADMIN:
        return $isAdminRoute;

      case ConfigForm::CHOICES_INCLUDE_NO_ADMIN:
        return !$isAdminRoute;

      default:
        return TRUE;
    }
  }

}
