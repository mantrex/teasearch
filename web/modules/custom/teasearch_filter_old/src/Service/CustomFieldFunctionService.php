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
    // Example function: combine title and subtitle
    $this->registerFunction('title_with_subtitle', function (EntityInterface $entity) {
      $title = $entity->label();
      $subtitle = '';

      if ($entity->hasField('field_subtitle') && !$entity->get('field_subtitle')->isEmpty()) {
        $subtitle = $entity->get('field_subtitle')->value;
      }

      return $subtitle ? "{$title} - {$subtitle}" : $title;
    });

    // Example function: format author name
    $this->registerFunction('format_author_name', function (EntityInterface $entity) {
      $name = $entity->label();

      if ($entity->hasField('field_first_name') && $entity->hasField('field_last_name')) {
        $first = $entity->get('field_first_name')->value ?? '';
        $last = $entity->get('field_last_name')->value ?? '';

        if ($first && $last) {
          return "{$first} {$last}";
        }
      }

      return $name;
    });
  }
}
