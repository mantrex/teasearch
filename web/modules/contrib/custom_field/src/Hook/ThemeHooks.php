<?php

declare(strict_types=1);

namespace Drupal\custom_field\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Provides hooks related to config schemas.
 */
class ThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    $item = ['render element' => 'elements'];
    return [
      'custom_field' => $item,
      'custom_field_item' => $item,
      'custom_field_hierarchical_formatter' => [
        'variables' => [
          'terms' => [],
          'wrapper' => '',
          'separator' => ' » ',
          'link' => FALSE,
        ],
        'file' => 'custom_field_hierarchical_formatter.theme.inc',
      ],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_custom_field')]
  public function themeSuggestionsCustomField(array $variables): array {
    return [
      'custom_field__' . $variables['elements']['#field_name'],
    ];
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_custom_field_item')]
  public function themeSuggestionsCustomFieldItem(array $variables): array {
    $hook = 'custom_field_item';
    return [
      $hook . '__' . $variables['elements']['#field_name'],
      $hook . '__' . $variables['elements']['#field_name'] . '__' . $variables['elements']['#type'],
      $hook . '__' . $variables['elements']['#field_name'] . '__' . $variables['elements']['#type'] . '__' . $variables['elements']['#name'],
      $hook . '__' . $variables['elements']['#field_name'] . '__' . $variables['elements']['#name'],
    ];
  }

}
