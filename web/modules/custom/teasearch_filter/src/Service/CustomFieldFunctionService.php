<?php

namespace Drupal\teasearch_filter\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Service for custom field rendering functions.
 */
class CustomFieldFunctionService
{

  /**
   * Registry of available custom functions.
   *
   * @var array
   */
  protected $functions = [];

  /**
   * Constructor.
   */
  public function __construct()
  {
    $this->registerDefaultFunctions();
  }

  /**
   * Register a custom function.
   *
   * @param string $name
   *   Function name.
   * @param callable $callback
   *   Callback function that receives the entity and returns a string.
   */
  public function registerFunction(string $name, callable $callback)
  {
    $this->functions[$name] = $callback;
  }

  /**
   * Execute a custom function.
   *
   * @param string $function_name
   *   Name of the function to execute.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   *
   * @return string|null
   *   The rendered string or NULL if function doesn't exist.
   */
  public function executeFunction(string $function_name, EntityInterface $entity)
  {
    if (!isset($this->functions[$function_name])) {
      \Drupal::logger('teasearch_filter')->error('Custom function @name not found', ['@name' => $function_name]);
      return NULL;
    }

    try {
      return call_user_func($this->functions[$function_name], $entity);
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Error executing custom function @name: @message', [
        '@name' => $function_name,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Check if a function exists.
   *
   * @param string $function_name
   *   Function name.
   *
   * @return bool
   *   TRUE if function exists.
   */
  public function hasFunction(string $function_name): bool
  {
    return isset($this->functions[$function_name]);
  }

  /**
   * Register default example functions.
   */
  protected function registerDefaultFunctions()
  {




    /**
     * Custom function for PEOPLE content type
     * 
     * Da registrare in CustomFieldFunctionService.php nel metodo registerDefaultFunctions()
     * 
     * Logica dal PDF:
     * - Se SURNAME_FIRST = true: SURNAME + GIVEN_NAME + FULL_NAME
     * - Altrimenti: GIVEN_NAME + SURNAME + FULL_NAME
     * - Poi: TITLE/POSITION, AFFILIATION, COUNTRY, CITY (REGION)
     */

    $this->registerFunction('format_people_display', function (EntityInterface $entity) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $output = [];

      // =============================================================================
      // PARTE 1: NOME COMPLETO
      // =============================================================================

      $name_parts = [];
      $surname_first = FALSE;

      // Leggi field_surname_first (boolean)
      if ($entity->hasField('field_surname_first')) {
        $field_surname_first = $entity->get('field_surname_first');
        if ($field_surname_first && !$field_surname_first->isEmpty()) {
          $surname_first = (bool) $field_surname_first->value;
        }
      }

      // Accedi al paragraph field_authors
      if ($entity->hasField('field_authors')) {
        $field_authors = $entity->get('field_authors');

        if ($field_authors && !$field_authors->isEmpty()) {
          $first_author = $field_authors->first();

          if ($first_author && $first_author->entity) {
            $author_paragraph = $first_author->entity;

            // Estrai i 3 campi dal paragraph
            $surname_latin = '';
            $given_name_latin = '';
            $full_name_original = '';

            // field_lastname_latin
            if ($author_paragraph->hasField('field_lastname_latin')) {
              $field_lastname = $author_paragraph->get('field_lastname_latin');
              if ($field_lastname && !$field_lastname->isEmpty()) {
                $surname_latin = $field_lastname->value;
              }
            }

            // field_firstname_latin
            if ($author_paragraph->hasField('field_firstname_latin')) {
              $field_firstname = $author_paragraph->get('field_firstname_latin');
              if ($field_firstname && !$field_firstname->isEmpty()) {
                $given_name_latin = $field_firstname->value;
              }
            }

            // field_fullname
            if ($author_paragraph->hasField('field_fullname')) {
              $field_fullname = $author_paragraph->get('field_fullname');
              if ($field_fullname && !$field_fullname->isEmpty()) {
                $full_name_original = $field_fullname->value;
              }
            }

            // Costruisci il nome secondo la logica SURNAME_FIRST
            if ($surname_first) {
              // Ordine: SURNAME + GIVEN_NAME + FULL_NAME
              if (!empty($surname_latin)) {
                $name_parts[] = $surname_latin;
              }
              if (!empty($given_name_latin)) {
                $name_parts[] = $given_name_latin;
              }
              if (!empty($full_name_original)) {
                $name_parts[] = $full_name_original;
              }
            } else {
              // Ordine: GIVEN_NAME + SURNAME + FULL_NAME
              if (!empty($given_name_latin)) {
                $name_parts[] = $given_name_latin;
              }
              if (!empty($surname_latin)) {
                $name_parts[] = $surname_latin;
              }
              if (!empty($full_name_original)) {
                $name_parts[] = $full_name_original;
              }
            }
          }
        }
      }

      // Aggiungi il nome formattato all'output
      if (!empty($name_parts)) {
        $output[] = '<strong>' . implode(' ', $name_parts) . '</strong>';
      }

      // =============================================================================
      // PARTE 2: TITLE/POSITION
      // =============================================================================

      if ($entity->hasField('field_title')) {
        $field_title = $entity->get('field_title');
        if ($field_title && !$field_title->isEmpty()) {
          $output[] = $field_title->value;
        }
      }

      // =============================================================================
      // PARTE 3: AFFILIATION
      // =============================================================================

      if ($entity->hasField('field_affiliation')) {
        $field_affiliation = $entity->get('field_affiliation');
        if ($field_affiliation && !$field_affiliation->isEmpty()) {
          $output[] = '<em>' . $field_affiliation->value . '</em>';
        }
      }

      // =============================================================================
      // PARTE 4: LOCATION - COUNTRY, CITY (REGION)
      // =============================================================================

      $location_parts = [];

      // Country
      if ($entity->hasField('field_country')) {
        $field_country = $entity->get('field_country');
        if ($field_country && !$field_country->isEmpty()) {
          $location_parts[] = $field_country->value;
        }
      }

      // City
      if ($entity->hasField('field_city')) {
        $field_city = $entity->get('field_city');
        if ($field_city && !$field_city->isEmpty()) {
          $location_parts[] = $field_city->value;
        }
      }

      // Costruisci la stringa location
      if (!empty($location_parts)) {
        $location_string = implode(', ', $location_parts);

        // Aggiungi Region in parentesi se presente
        if ($entity->hasField('field_region')) {
          $field_region = $entity->get('field_region');
          if ($field_region && !$field_region->isEmpty()) {
            $location_string .= ' (' . $field_region->value . ')';
          }
        }

        $output[] = '<span class="location">' . $location_string . '</span>';
      }

      // =============================================================================
      // OUTPUT FINALE
      // =============================================================================

      return implode('<br>', $output);
    });



    
  }
}
