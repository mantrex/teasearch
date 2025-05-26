<?php

namespace Drupal\teasearch_filter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the search page with form and results.
 */
class SearchController extends ControllerBase
{

  protected $configFactory;
  protected $entityTypeManager;

  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  public function title($content_type)
  {
    return $this->t('Search %type', ['%type' => $content_type]);
  }

  public function search($content_type, Request $request)
  {
    // Build the filter form
    $form = $this->formBuilder()->getForm(
      'Drupal\teasearch_filter\Form\SearchFilterForm',
      $content_type
    );

    // Load configuration and filters
    $config = $this->configFactory->get('teasearch_filter.settings');
    $filters = $config->get("content_types.{$content_type}.filters") ?: [];

    // Check if this is a user-based search (contributors)
    $is_user_search = $this->isUserBasedContentType($content_type);

    if ($is_user_search) {
      // Handle user search (contributors)
      $results = $this->searchUsers($content_type, $filters, $request);
      $grouped_filters = $this->prepareUserGroupedFilters($filters, $content_type, $request);
    } else {
      // Handle node search (normal content types)
      $results = $this->searchNodes($content_type, $filters, $request);
      $grouped_filters = $this->prepareNodeGroupedFilters($filters, $content_type, $request);
    }

    // Build render array
    return [
      '#theme' => 'teasearch',
      '#filter_form' => $form,
      '#nodes' => $results['entities'], // Chiamiamo sempre 'nodes' per compatibilità template
      '#users' => $is_user_search ? $results['entities'] : [], // Per distinguere nel template
      '#filters' => $filters,
      '#grouped_filters' => $grouped_filters,
      '#content_type' => $content_type,
      '#is_user_search' => $is_user_search,
      '#attached' => [
        'library' => [
          'teasearch_filter/teasearch_filter_styles',
          'teasearch_filter/teasearch_filter_details_state',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => $is_user_search ? ['user_list'] : ['node_list'],
      ],
    ];
  }

  /**
   * Check if content type is user-based.
   */
  private function isUserBasedContentType($content_type)
  {
    $user_based_types = ['contributors']; // Aggiungi altri se necessario
    return in_array($content_type, $user_based_types);
  }

  /**
   * Search users (for contributors).
   */
  private function searchUsers($content_type, $filters, Request $request)
  {
    // Initialize user query
    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1); // Solo utenti attivi

    // Apply role filter for contributors
    if ($content_type === 'contributors') {
      $query->condition('roles', 'contributor', 'IN');
    }

    // Apply filters
    foreach ($filters as $field => $filter) {
      $value = $request->query->get($field);
      if (empty($value)) continue;

      // Taxonomy filter
      if ($filter['type'] === 'taxonomy') {
        $field_name = "field_{$field}";

        // Verifica che il campo esista sui profili utente
        if (!$this->userFieldExists($field_name)) {
          \Drupal::logger('teasearch_filter')->warning('User field @field does not exist', [
            '@field' => $field_name
          ]);
          continue;
        }

        $values = is_string($value) ? explode(',', $value) : (array) $value;
        $clean_values = array_filter(array_map('intval', $values));

        if (!empty($clean_values)) {
          $query->condition("{$field_name}.target_id", $clean_values, 'IN');
        }
      }
      // Free text filter
      elseif ($filter['type'] === 'free_text') {
        $value_string = is_array($value) ? implode(',', $value) : (string) $value;
        $terms = explode(',', $value_string);
        $clean_terms = array_filter(array_map('trim', $terms));

        if (!empty($clean_terms)) {
          $group = $query->orConditionGroup();
          foreach ($clean_terms as $term) {
            if (!empty($term)) {
              // Cerca nel nome utente
              $group->condition('name', "%{$term}%", 'LIKE');

              // Cerca anche in altri campi se esistono
              if ($this->userFieldExists('field_display_name')) {
                $group->condition('field_display_name.value', "%{$term}%", 'LIKE');
              }
              if ($this->userFieldExists('field_bio')) {
                $group->condition('field_bio.value', "%{$term}%", 'LIKE');
              }
              if ($this->userFieldExists('field_description')) {
                $group->condition('field_description.value', "%{$term}%", 'LIKE');
              }
            }
          }
          $query->condition($group);
        }
      }
    }

    // Execute query
    $uids = $query->sort('created', 'DESC')
      ->range(0, 50)
      ->execute();

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    return ['entities' => $users];
  }

  /**
   * Search nodes (for normal content types).
   */
  private function searchNodes($content_type, $filters, Request $request)
  {
    // Initialize query
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $content_type)
      ->condition('status', 1);

    // Apply filters
    foreach ($filters as $field => $filter) {
      $value = $request->query->get($field);
      if (empty($value)) continue;

      // Taxonomy filter
      if ($filter['type'] === 'taxonomy') {
        $field_name = "field_{$field}";

        if (!$this->nodeFieldExists($content_type, $field_name)) {
          continue;
        }

        $values = is_string($value) ? explode(',', $value) : (array) $value;
        $clean_values = array_filter(array_map('intval', $values));

        if (!empty($clean_values)) {
          $query->condition("{$field_name}.target_id", $clean_values, 'IN');
        }
      }
      // Free text filter
      elseif ($filter['type'] === 'free_text') {
        $value_string = is_array($value) ? implode(',', $value) : (string) $value;
        $terms = explode(',', $value_string);
        $clean_terms = array_filter(array_map('trim', $terms));

        if (!empty($clean_terms)) {
          $group = $query->orConditionGroup();
          foreach ($clean_terms as $term) {
            if (!empty($term)) {
              $group->condition('title', "%{$term}%", 'LIKE');
              if ($this->nodeFieldExists($content_type, 'body')) {
                $group->condition('body.value', "%{$term}%", 'LIKE');
              }
            }
          }
          $query->condition($group);
        }
      }
    }

    // Execute query
    $nids = $query->sort('created', 'DESC')
      ->range(0, 50)
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    return ['entities' => $nodes];
  }

  /**
   * Check if a field exists on user entity.
   */
  private function userFieldExists($field_name)
  {
    try {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
      return isset($field_definitions[$field_name]);
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Check if a field exists on node entity.
   */
  private function nodeFieldExists($content_type, $field_name)
  {
    try {
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type);
      return isset($field_definitions[$field_name]);
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Prepare grouped filters for users.
   */
  private function prepareUserGroupedFilters($filters, $content_type, Request $request)
  {
    $grouped_filters = [];
    $query_values = $request->query->all();

    foreach ($filters as $field_name => $filter) {
      $selected = $query_values[$field_name] ?? [];

      if ($filter['type'] === 'taxonomy') {
        if (is_string($selected)) {
          $selected = explode(',', $selected);
        }
        if (!is_array($selected)) {
          $selected = [$selected];
        }
        $selected = array_filter(array_map('intval', $selected));
      } else {
        if (is_array($selected)) {
          $selected = implode(',', array_filter($selected));
        }
        $selected = (string) $selected;
      }

      $filter_data = [
        'field_name' => $field_name,
        'label' => $filter['label'] ?? $field_name,
        'type' => $filter['type'],
        'selected' => $selected,
        'is_open' => !empty($selected),
        'options' => []
      ];

      if ($filter['type'] === 'taxonomy' && !empty($filter['vocabulary'])) {
        try {
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);
          $field_full_name = "field_{$field_name}";

          if (!$this->userFieldExists($field_full_name)) {
            continue;
          }

          foreach ($terms as $term) {
            // Query per contare gli utenti
            $count_query = $this->entityTypeManager->getStorage('user')->getQuery()
              ->accessCheck(TRUE)
              ->condition('status', 1)
              ->condition("{$field_full_name}.target_id", $term->tid);

            // Apply role filter for contributors
            if ($content_type === 'contributors') {
              $count_query->condition('roles', 'contributor', 'IN');
            }

            $count = $count_query->count()->execute();

            if ($count) {
              $filter_data['options'][$term->tid] = [
                'label' => $term->name ?? '',
                'count' => $count,
                'selected' => in_array((int)$term->tid, $selected, true)
              ];
            }
          }
        } catch (\Exception $e) {
          \Drupal::logger('teasearch_filter')->error('Error loading taxonomy for users: @error', [
            '@error' => $e->getMessage()
          ]);
        }
      }

      $grouped_filters[$field_name] = $filter_data;
    }

    return $grouped_filters;
  }

  /**
   * Prepare grouped filters for nodes.
   */
  private function prepareNodeGroupedFilters($filters, $content_type, Request $request)
  {
    $grouped_filters = [];
    $query_values = $request->query->all();

    foreach ($filters as $field_name => $filter) {
      $selected = $query_values[$field_name] ?? [];

      if ($filter['type'] === 'taxonomy') {
        if (is_string($selected)) {
          $selected = explode(',', $selected);
        }
        if (!is_array($selected)) {
          $selected = [$selected];
        }
        $selected = array_filter(array_map('intval', $selected));
      } else {
        if (is_array($selected)) {
          $selected = implode(',', array_filter($selected));
        }
        $selected = (string) $selected;
      }

      $filter_data = [
        'field_name' => $field_name,
        'label' => $filter['label'] ?? $field_name,
        'type' => $filter['type'],
        'selected' => $selected,
        'is_open' => !empty($selected),
        'options' => []
      ];

      if ($filter['type'] === 'taxonomy' && !empty($filter['vocabulary'])) {
        try {
          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);
          $field_full_name = "field_{$field_name}";

          if (!$this->nodeFieldExists($content_type, $field_full_name)) {
            continue;
          }

          foreach ($terms as $term) {
            $count_query = $this->entityTypeManager->getStorage('node')->getQuery()
              ->accessCheck(TRUE)
              ->condition('type', $content_type)
              ->condition('status', 1)
              ->condition("{$field_full_name}.target_id", $term->tid);

            $count = $count_query->count()->execute();

            if ($count) {
              $filter_data['options'][$term->tid] = [
                'label' => $term->name ?? '',
                'count' => $count,
                'selected' => in_array((int)$term->tid, $selected, true)
              ];
            }
          }
        } catch (\Exception $e) {
          \Drupal::logger('teasearch_filter')->error('Error loading taxonomy for nodes: @error', [
            '@error' => $e->getMessage()
          ]);
        }
      }

      $grouped_filters[$field_name] = $filter_data;
    }

    return $grouped_filters;
  }
}
