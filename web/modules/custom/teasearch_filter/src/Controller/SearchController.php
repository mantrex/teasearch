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

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SearchController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager)
  {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
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

      $url = Url::fromRoute(
        'teasearch_filter.search',
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
    // Load and validate configuration
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];

    if (!isset($content_types[$content_type])) {
      throw new NotFoundHttpException('Content type not configured for search.');
    }

    $content_type_config = $content_types[$content_type];
    $filters = $content_type_config['filters'] ?: [];

    // Get page title
    $page_title = $this->getCategoryTitle($content_type);

    // Build filter form
    $form = $this->formBuilder()->getForm(
      'Drupal\teasearch_filter\Form\SearchFilterForm',
      $content_type
    );

    // Prepare data for templates
    $grouped_filters = $this->prepareGroupedFilters($filters, $content_type_config, $request);
    $century_data = $this->prepareCenturyData($filters, $content_type_config, $request);

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
      '#century_data' => $century_data,
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
            $field_value = $category_node->get('field_category_menu_list')->getString();

            if (trim($field_value) === trim($content_type)) {
              if ($category_node->hasField('field_link_title') && !$category_node->get('field_link_title')->isEmpty()) {
                return $category_node->get('field_link_title')->value;
              }
            }
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Error in getCategoryTitle: @message', ['@message' => $e->getMessage()]);
    }

    // Fallback to configured title
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

    // Apply WHERE conditions from config
    $this->applyWhereConditions($query, $config);

    // Apply user filters
    $this->applyFiltersToQuery($query, $filters, $request, 'node');

    // Apply year range filtering
    $this->applyYearRangeFiltering($query, $filters, $request);

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
   * Apply WHERE conditions from configuration.
   */
  private function applyWhereConditions($query, $config)
  {
    if (empty($config['where'])) {
      return;
    }

    $where_conditions = json_decode($config['where'], TRUE);
    if (!is_array($where_conditions)) {
      return;
    }

    foreach ($where_conditions as $condition) {
      foreach ($condition as $field => $value) {
        if (strpos($field, '.target_id') !== FALSE) {
          $clean_field = str_replace('.target_id', '', $field);

          if (!is_numeric($value)) {
            $resolved_value = $this->resolveFieldValue($clean_field, $value, $config);
            if ($resolved_value !== null) {
              $query->condition($clean_field, $resolved_value);
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

  /**
   * Resolve field value for entity references.
   */
  private function resolveFieldValue($field_name, $value, $config)
  {
    // Try to find vocabulary for taxonomy fields
    $vocabulary = $this->getVocabularyForField($field_name, $config);
    if ($vocabulary) {
      return $this->getTermIdByName($value, $vocabulary);
    }

    // Try to find node by title for entity reference
    if (strpos($field_name, 'field_') === 0) {
      $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('title', $value)
        ->condition('status', 1)
        ->execute();

      if (!empty($nodes)) {
        return reset($nodes);
      }
    }

    return null;
  }

  /**
   * Get vocabulary for a field.
   */
  private function getVocabularyForField($field_name, $config)
  {
    // Check in current config filters
    if (!empty($config['filters'])) {
      foreach ($config['filters'] as $filter_field => $filter_config) {
        if (
          $filter_field === str_replace('field_', '', $field_name) &&
          isset($filter_config['vocabulary'])
        ) {
          return $filter_config['vocabulary'];
        }
      }
    }

    // Common field mappings
    $field_vocabulary_mapping = [
      'field_roles' => 'roles',
      'field_categories' => 'categories',
      'field_subjects' => 'subjects',
      'field_skills' => 'skills',
      'field_location' => 'location',
    ];

    if (isset($field_vocabulary_mapping[$field_name])) {
      return $field_vocabulary_mapping[$field_name];
    }

    // Try to infer from field name
    $clean_field_name = str_replace('field_', '', $field_name);
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();

    if (isset($vocabularies[$clean_field_name])) {
      return $clean_field_name;
    }

    return null;
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
    return !empty($tids) ? reset($tids) : null;
  }

  /**
   * Apply filters to query.
   */
  private function applyFiltersToQuery($query, $filters, Request $request, $entity_type)
  {
    $standard_filters = $this->getStandardFilters($filters);

    foreach ($standard_filters as $field => $filter) {
      $value = $request->query->get($field);
      if (empty($value)) {
        continue;
      }

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
   * Get standard filters, excluding special configurations.
   */
  private function getStandardFilters(array $filters)
  {
    $standard_filters = [];
    $special_keys = ['century_selector'];

    foreach ($filters as $field_name => $filter) {
      if (in_array($field_name, $special_keys)) {
        continue;
      }

      if (!isset($filter['type'])) {
        continue;
      }

      $standard_filters[$field_name] = $filter;
    }

    return $standard_filters;
  }

  /**
   * Apply year range filtering.
   */
  private function applyYearRangeFiltering($query, $filters, Request $request)
  {
    if (!isset($filters['century_selector'])) {
      return;
    }

    $century_config = $filters['century_selector'];
    $from_field = $century_config['from'] ?? 'year_from';
    $to_field = $century_config['to'] ?? 'year_to';

    $year_from = $request->query->get('year_from');
    $year_to = $request->query->get('year_to');

    if ($year_from === null && $year_to === null) {
      return;
    }

    $this->applyNodeYearRangeFilter($query, $from_field, $to_field, $year_from, $year_to);
  }

  /**
   * Apply year range filter to node query.
   */
  private function applyNodeYearRangeFilter($query, $from_field, $to_field, $year_from, $year_to)
  {
    $year_group = $query->orConditionGroup();

    if ($year_from !== null && $year_to !== null) {
      // Entity range overlaps with selected range
      $overlap1 = $query->andConditionGroup()
        ->condition($from_field, $year_from, '>=')
        ->condition($from_field, $year_to, '<=');

      $overlap2 = $query->andConditionGroup()
        ->condition($to_field, $year_from, '>=')
        ->condition($to_field, $year_to, '<=');

      $overlap3 = $query->andConditionGroup()
        ->condition($from_field, $year_from, '<=')
        ->condition($to_field, $year_to, '>=');

      $year_group->condition($overlap1);
      $year_group->condition($overlap2);
      $year_group->condition($overlap3);
    } elseif ($year_from !== null) {
      $year_group->condition($to_field, $year_from, '>=');
    } elseif ($year_to !== null) {
      $year_group->condition($from_field, $year_to, '<=');
    }

    $query->condition($year_group);
  }

  /**
   * Apply taxonomy filter.
   */
  private function applyTaxonomyFilter($query, $field, $value, $entity_type)
  {
    $values = is_string($value) ? explode(',', $value) : (array) $value;
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
    return $entity_type === 'user' ? ['name'] : ['title', 'body.value'];
  }

  /**
   * Check if there are active filters.
   */
  private function hasActiveFilters(Request $request, $filters)
  {
    $standard_filters = $this->getStandardFilters($filters);

    foreach (array_keys($standard_filters) as $field) {
      if (!empty($request->query->get($field))) {
        return TRUE;
      }
    }

    // Check year range
    return !empty($request->query->get('year_from')) || !empty($request->query->get('year_to'));
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
    $standard_filters = $this->getStandardFilters($filters);

    foreach ($standard_filters as $field_name => $filter) {
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
   * Prepare century selector data.
   */
  private function prepareCenturyData($filters, $config, Request $request)
  {
    if (!isset($filters['century_selector'])) {
      return null;
    }

    $century_config = $filters['century_selector'];
    $machine_name = $config['machine_name'];
    $display_mode = $century_config['display'] ?? 'manual';

    $data = [
      'enabled' => true,
      'from_field' => $century_config['from'] ?? 'year_from',
      'to_field' => $century_config['to'] ?? 'year_to',
      'display_mode' => $display_mode,
      'selected_from' => $request->query->get('year_from'),
      'selected_to' => $request->query->get('year_to'),
    ];

    if ($display_mode === 'manual') {
      $data['min_year'] = -3000;
      $data['max_year'] = date('Y');
    } else {
      $year_range = $this->calculateYearRange($machine_name, $data['from_field'], $data['to_field']);
      $data['min_year'] = $year_range['min'];
      $data['max_year'] = $year_range['max'];
    }

    $data['centuries'] = $this->generateCenturies($data['min_year'], $data['max_year']);

    return $data;
  }

  /**
   * Calculate year range from existing data.
   */
  private function calculateYearRange($machine_name, $from_field, $to_field)
  {
    try {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', $machine_name)
        ->condition('status', 1);

      $nids = $query->execute();

      if (empty($nids)) {
        return ['min' => -3000, 'max' => date('Y')];
      }

      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      $min_year = null;
      $max_year = null;

      foreach ($nodes as $node) {
        if ($node->hasField($from_field) && !$node->get($from_field)->isEmpty()) {
          $from_value = $node->get($from_field)->value;
          if ($min_year === null || $from_value < $min_year) {
            $min_year = $from_value;
          }
        }

        if ($node->hasField($to_field) && !$node->get($to_field)->isEmpty()) {
          $to_value = $node->get($to_field)->value;
          if ($max_year === null || $to_value > $max_year) {
            $max_year = $to_value;
          }
        }
      }

      return [
        'min' => $min_year ?? -3000,
        'max' => $max_year ?? date('Y')
      ];
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Error calculating year range: @message', [
        '@message' => $e->getMessage()
      ]);
      return ['min' => -3000, 'max' => date('Y')];
    }
  }

  /**
   * Generate centuries for timeline.
   */
  private function generateCenturies($min_year, $max_year)
  {
    $centuries = [];

    $start_century = intval(floor($min_year / 100));
    $end_century = intval(ceil($max_year / 100));

    for ($century = $start_century; $century <= $end_century; $century++) {
      $century_start = $century * 100;
      $century_end = $century_start + 99;

      if ($century < 0) {
        $label = abs($century_start) . '-' . abs($century_end) . ' AC';
      } else {
        $label = $century_start . '-' . $century_end . ' CE';
      }

      $centuries[] = [
        'value' => $century,
        'label' => $label,
        'start_year' => $century_start,
        'end_year' => $century_end,
      ];
    }

    return $centuries;
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
      try {
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

        if ($count > 0) {
          $options[$term->tid] = [
            'label' => $term->name ?? '',
            'count' => $count,
            'selected' => in_array((string)$term->tid, $selected, true)
          ];
        }
      } catch (\Exception $e) {
        \Drupal::logger('teasearch_filter')->error('Error getting taxonomy count: @message', [
          '@message' => $e->getMessage()
        ]);
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
      try {
        $count = $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 1)
          ->condition('roles', $rid)
          ->count()
          ->execute();

        if ($count > 0) {
          $options[$rid] = [
            'label' => $role->label(),
            'count' => $count,
            'selected' => in_array($rid, $selected, true)
          ];
        }
      } catch (\Exception $e) {
        \Drupal::logger('teasearch_filter')->error('Error getting user role count: @message', [
          '@message' => $e->getMessage()
        ]);
      }
    }

    return $options;
  }

  /**
   * Get user status options.
   */
  private function getUserStatusOptions($selected)
  {
    try {
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
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Error getting user status count: @message', [
        '@message' => $e->getMessage()
      ]);
      return [];
    }
  }
}
