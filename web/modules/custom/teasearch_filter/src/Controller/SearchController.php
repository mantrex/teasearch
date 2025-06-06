<?php

namespace Drupal\teasearch_filter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Search controller for teasearch_filter module.
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

  /**
   * Handle legacy redirects.
   */
  public function legacyRedirect(Request $request)
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $legacy_mappings = $config->get('legacy_mappings') ?: [];
    
    $path_parts = explode('/', $request->getPathInfo());
    $old_content_type = end($path_parts);
    
    if (isset($legacy_mappings[$old_content_type])) {
      $new_content_type = $legacy_mappings[$old_content_type];
      $query_params = $request->query->all();
      
      $url = Url::fromRoute('teasearch_filter.search', 
        ['content_type' => $new_content_type],
        ['query' => $query_params]
      );
      
      return new RedirectResponse($url->toString(), 301);
    }
    
    throw new NotFoundHttpException();
  }

  /**
   * Page title callback.
   */
  public function title($content_type)
  {
    $page_title = $this->getCategoryTitle($content_type);
    return $this->t('Search @type', ['@type' => $page_title]);
  }

  /**
   * Main search page.
   */
  public function search($content_type, Request $request)
  {
    // Load configuration
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];
    
    if (!isset($content_types[$content_type])) {
      throw new NotFoundHttpException('Content type not configured for search.');
    }
    
    $content_type_config = $content_types[$content_type];
    $filters = $content_type_config['filters'] ?: [];

    // Get page title from categories
    $page_title = $this->getCategoryTitle($content_type);

    // Build filter form
    $form = $this->formBuilder()->getForm(
      'Drupal\teasearch_filter\Form\SearchFilterForm',
      $content_type
    );

    // Prepare grouped filters
    $grouped_filters = $this->prepareGroupedFilters($filters, $content_type_config, $request);

    // Search entities
    $entity_type = $content_type_config['type'] ?? 'node';
    if ($entity_type === 'user') {
      $entities = $this->searchUsers($content_type_config, $request);
      $total = count($entities);
    } else {
      list($entities, $total) = $this->searchNodes($content_type_config, $request);
    }

    // Build render array
    return [
      '#theme' => 'teasearch',
      '#filter_form' => $form,
      '#entities' => $entities,
      '#filters' => $filters,
      '#grouped_filters' => $grouped_filters,
      '#content_type' => $content_type,
      '#content_type_config' => $content_type_config,
      '#entity_type' => $entity_type,
      '#total_results' => $total,
      '#has_filters' => $this->hasActiveFilters($request, $filters),
      '#page_title' => $page_title,
      '#module_path' => $request->getBasePath() . '/' . \Drupal::service('extension.list.module')->getPath('teasearch_filter'),
      '#attached' => [
        'library' => [
          'teasearch_filter/teasearch_filter_styles',
          'teasearch_filter/teasearch_filter_details_state',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list', 'user_list', 'config:teasearch_filter.settings', 'node_list:categories'],
      ],
    ];
  }

  /**
   * Get category title from categories entity.
   */
  private function getCategoryTitle($content_type)
  {
    try {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'categories')
        ->condition('status', 1);

      $nids = $query->execute();

      if (!empty($nids)) {
        $category_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($category_nodes as $category_node) {
          if ($category_node->hasField('field_category_menu_list') && !$category_node->get('field_category_menu_list')->isEmpty()) {

            // Recupera il valore testuale del campo.
            $field_value = $category_node->get('field_category_menu_list')->getString();

            // Confronta il valore (non la chiave) con il parametro $content_type
            if (trim($field_value) === trim($content_type)) {
              if ($category_node->hasField('field_link_title') && !$category_node->get('field_link_title')->isEmpty()) {
                return $category_node->get('field_link_title')->value;
              }
            }
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Errore in getCategoryTitle: @message', ['@message' => $e->getMessage()]);
    }

    // Fallback al titolo configurato
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];
    return $content_types[$content_type]['label'] ?? ucfirst($content_type);
  }


  /**
   * Search nodes.
   */
  private function searchNodes($config, Request $request)
  {
    $machine_name = $config['machine_name'];
    $filters = $config['filters'] ?: [];
    
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $machine_name)
      ->condition('status', 1);

    // Apply WHERE conditions
    if (!empty($config['where'])) {
      $where_conditions = json_decode($config['where'], TRUE);
      if (is_array($where_conditions)) {
        foreach ($where_conditions as $condition) {
          foreach ($condition as $field => $value) {
            if (strpos($field, '.target_id') !== FALSE) {
              $clean_field = str_replace('.target_id', '', $field);
              if (!is_numeric($value)) {
                $term_id = $this->getTermIdByName($value);
                if ($term_id) {
                  $query->condition($clean_field, $term_id);
                }
              } else {
                $query->condition($clean_field, $value);
              }
            } else {
              $query->condition($field, $value);
            }
          }
        }
      }
    }

    // Apply user filters
    $this->applyFiltersToQuery($query, $filters, $request, 'node');

    // Get total count
    $total_query = clone $query;
    $total = $total_query->count()->execute();
    
    // Apply pagination
    $page = $request->query->get('page', 0);
    $limit = 20;
    $offset = $page * $limit;

    $ids = $query->sort('created', 'DESC')
      ->range($offset, $limit)
      ->execute();

    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
    
    return [$entities, $total];
  }

  /**
   * Search users.
   */
  private function searchUsers($config, Request $request)
  {
    $filters = $config['filters'] ?: [];
    
    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);

    $this->applyFiltersToQuery($query, $filters, $request, 'user');

    $ids = $query->sort('created', 'DESC')
      ->range(0, 50)
      ->execute();

    return $this->entityTypeManager->getStorage('user')->loadMultiple($ids);
  }

  /**
   * Apply filters to query.
   */
  private function applyFiltersToQuery($query, $filters, Request $request, $entity_type)
  {
    foreach ($filters as $field => $filter) {
      $value = $request->query->get($field);
      if (empty($value)) continue;

      switch ($filter['type']) {
        case 'taxonomy':
          $this->applyTaxonomyFilter($query, $field, $value, $entity_type);
          break;
          
        case 'free_text':
          $this->applyFreeTextFilter($query, $field, $value, $entity_type);
          break;
          
        case 'user_roles':
          if ($entity_type === 'user') {
            $this->applyUserRolesFilter($query, $value);
          }
          break;
          
        case 'user_status':
          if ($entity_type === 'user') {
            $this->applyUserStatusFilter($query, $value);
          }
          break;
      }
    }
  }

  /**
   * Apply taxonomy filter.
   */
  private function applyTaxonomyFilter($query, $field, $value, $entity_type)
  {
    if (is_string($value)) {
      $values = explode(',', $value);
    } elseif (is_array($value)) {
      $values = $value;
    } else {
      $values = [$value];
    }
    
    $clean_values = array_filter(array_map('intval', $values));
    if (!empty($clean_values)) {
      $field_name = $entity_type === 'user' ? $field : "field_{$field}";
      $query->condition("{$field_name}.target_id", $clean_values, 'IN');
    }
  }

  /**
   * Apply free text filter.
   */
  private function applyFreeTextFilter($query, $field, $value, $entity_type)
  {
    $value_string = is_array($value) ? implode(',', $value) : (string) $value;
    $terms = explode(',', $value_string);
    $clean_terms = array_filter(array_map('trim', $terms));

    if (!empty($clean_terms)) {
      $group = $query->orConditionGroup();
      foreach ($clean_terms as $term) {
        if (!empty($term)) {
          $text_fields = $this->getTextFieldsForEntity($entity_type);
          foreach ($text_fields as $text_field) {
            $group->condition($text_field, "%{$term}%", 'LIKE');
          }
        }
      }
      $query->condition($group);
    }
  }

  /**
   * Apply user roles filter.
   */
  private function applyUserRolesFilter($query, $value)
  {
    $roles = is_array($value) ? $value : explode(',', $value);
    $roles = array_filter($roles);
    if (!empty($roles)) {
      $query->condition('roles', $roles, 'IN');
    }
  }

  /**
   * Apply user status filter.
   */
  private function applyUserStatusFilter($query, $value)
  {
    if ($value === 'active') {
      $query->condition('status', 1);
    } elseif ($value === 'blocked') {
      $query->condition('status', 0);
    }
  }

  /**
   * Get text fields for entity type.
   */
  private function getTextFieldsForEntity($entity_type)
  {
    if ($entity_type === 'user') {
      return ['name'];
    }
    
    return ['title', 'body.value'];
  }

  /**
   * Check if there are active filters.
   */
  private function hasActiveFilters(Request $request, $filters)
  {
    foreach (array_keys($filters) as $field) {
      if (!empty($request->query->get($field))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get term ID by name.
   */
  private function getTermIdByName($term_name, $vocabulary = null)
  {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    
    $query = $term_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('name', $term_name);
      
    if ($vocabulary) {
      $query->condition('vid', $vocabulary);
    }
    
    $tids = $query->execute();
    
    if (!empty($tids)) {
      return reset($tids);
    }
    
    return null;
  }

  /**
   * Prepare grouped filters for twig template.
   */
  private function prepareGroupedFilters($filters, $config, Request $request)
  {
    $grouped_filters = [];
    $query_values = $request->query->all();
    $entity_type = $config['type'] ?? 'node';
    $machine_name = $config['machine_name'];

    foreach ($filters as $field_name => $filter) {
      $selected = $query_values[$field_name] ?? [];
      
      if (in_array($filter['type'], ['taxonomy', 'user_roles'])) {
        if (is_string($selected)) {
          $selected = explode(',', $selected);
        }
        if (!is_array($selected)) {
          $selected = [$selected];
        }
        $selected = array_filter($selected);
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

      switch ($filter['type']) {
        case 'taxonomy':
          $filter_data['options'] = $this->getTaxonomyOptions($filter, $machine_name, $field_name, $selected, $entity_type);
          break;
          
        case 'user_roles':
          $filter_data['options'] = $this->getUserRoleOptions($selected);
          break;
          
        case 'user_status':
          $filter_data['options'] = $this->getUserStatusOptions($selected);
          break;
      }

      $grouped_filters[$field_name] = $filter_data;
    }

    return $grouped_filters;
  }

  /**
   * Get taxonomy options with counts.
   */
  private function getTaxonomyOptions($filter, $machine_name, $field_name, $selected, $entity_type)
  {
    if (empty($filter['vocabulary'])) {
      return [];
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);
    $options = [];
    
    foreach ($terms as $term) {
      if ($entity_type === 'user') {
        $count = $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 1)
          ->condition("{$field_name}.target_id", $term->tid)
          ->count()
          ->execute();
      } else {
        $count = $this->entityTypeManager->getStorage('node')->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', $machine_name)
          ->condition('status', 1)
          ->condition("field_{$field_name}.target_id", $term->tid)
          ->count()
          ->execute();
      }

      if ($count) {
        $options[$term->tid] = [
          'label' => $term->name ?? '',
          'count' => $count,
          'selected' => in_array((string)$term->tid, $selected, true)
        ];
      }
    }
    
    return $options;
  }

  /**
   * Get user role options.
   */
  private function getUserRoleOptions($selected)
  {
    $roles = user_roles(TRUE);
    $options = [];
    
    foreach ($roles as $rid => $role) {
      $count = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 1)
        ->condition('roles', $rid)
        ->count()
        ->execute();
        
      if ($count) {
        $options[$rid] = [
          'label' => $role->label(),
          'count' => $count,
          'selected' => in_array($rid, $selected, true)
        ];
      }
    }
    
    return $options;
  }

  /**
   * Get user status options.
   */
  private function getUserStatusOptions($selected)
  {
    return [
      'active' => [
        'label' => $this->t('Active'),
        'count' => $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(TRUE)->condition('status', 1)->count()->execute(),
        'selected' => $selected === 'active'
      ],
      'blocked' => [
        'label' => $this->t('Blocked'),
        'count' => $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(TRUE)->condition('status', 0)->count()->execute(),
        'selected' => $selected === 'blocked'
      ]
    ];
  }
}