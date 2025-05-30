<?php

namespace Drupal\select2;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class EntityAutocompleteMatcher {

  /**
   * Constructs a EntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selectionManager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(protected readonly SelectionPluginManagerInterface $selectionManager, protected readonly ModuleHandlerInterface $moduleHandler) {
  }

  /**
   * Gets matched labels based on a given search string.
   *
   * @param string $target_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param array $selection_settings
   *   An array of settings that will be passed to the selection handler.
   * @param string $string
   *   (optional) The label of the entity to query by.
   * @param array $selected
   *   (optional) An array of already selected items.
   *
   * @return array
   *   An array of matched entity labels, in the format required by the AJAX
   *   autocomplete API (e.g. array('value' => $value, 'label' => $label)).
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public function getMatches(string $target_type, string $selection_handler, array $selection_settings, string $string = '', array $selected = []): array {
    $matches = [];

    $options = $selection_settings + [
      'target_type' => $target_type,
      'handler' => $selection_handler,
    ];
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
    $handler = $this->selectionManager->getInstance($options);

    if ($string) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit + count($selected));

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          // Filter out already selected items.
          if (in_array($entity_id, $selected)) {
            continue;
          }

          $matches[$entity_id] = [
            'id' => $entity_id,
            'text' => Html::decodeEntities($label),
          ];
        }
      }
      if ($match_limit >= 1) {
        $matches = array_slice($matches, 0, $match_limit, TRUE);
      }

      $this->moduleHandler->alter('select2_autocomplete_matches', $matches, $options);
    }

    return array_values($matches);
  }

}
