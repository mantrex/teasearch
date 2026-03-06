<?php

namespace Drupal\teasearch_filter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\teasearch_filter\Helper\CustomFieldHelper;
use Drupal\teasearch_filter\Helper\SearchHelper;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\NodeInterface;
use Drupal\teasearch_filter\Config\SortConfig;
use Drupal\teasearch_filter\Helper\SortHelper;

/**
 * Search controller for teasearch_filter module.
 */
class SearchController extends ControllerBase
{

  /**
   * The search helper.
   *
   * @var \Drupal\teasearch_filter\Helper\SearchHelper
   */
  protected $searchHelper;


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
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    FormBuilderInterface $form_builder,
    SearchHelper $search_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->searchHelper = $search_helper;
  }

  private function getPaginatorConfig()
  {
    return [
      'paginator' => TRUE,
      'results' => [10, 25, 50],
      'results_default' => 10,
      'pages' => 5,
      'additional_buttons' => TRUE,
      'always_show' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('teasearch_filter.search_helper')
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
   * Main search page — /category/{content_type}
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
    $date_data = $this->prepareDateData($filters, $content_type_config, $request);

    // Paginator config
    $paginator_config = $this->getPaginatorConfig();

    // Pagination params
    $page = (int) $request->query->get('page', 0);
    $per_page = $request->query->get('per_page');

    $session = $request->getSession();
    $session_key = "teasearch_filter.{$content_type}.per_page";

    if ($per_page === null) {
      $per_page = $session->get($session_key, $paginator_config['results_default']);
    } else {
      $per_page = (int) $per_page;
      $session->set($session_key, $per_page);
    }

    // -------------------------------------------------------------------------
    // SORT: risolvi il sort attivo dalla request
    // -------------------------------------------------------------------------
    $sort_resolved = SortHelper::resolveFromRequest($content_type, $request);

    // Search entities
    $entity_type = $content_type_config['type'] ?? 'node';
    if ($entity_type === 'user') {
      $entities = $this->searchUsers($content_type_config, $request, $page, $per_page, $sort_resolved);
      $total = count($entities);
    } else {
      list($entities, $total) = $this->searchNodes($content_type_config, $request, $page, $per_page, $sort_resolved);
    }

    // Process entities for display
    foreach ($entities as &$entity) {
      $entity = $this->processEntityForDisplay($entity, $content_type_config);
    }

    // Paginator data
    $paginator_data = $this->preparePaginatorData(
      $total,
      $page,
      $per_page,
      $paginator_config
    );

    // -------------------------------------------------------------------------
    // SORT: prepara dati per il template
    // -------------------------------------------------------------------------
    $sort_data = $this->prepareSortData($content_type, $sort_resolved);

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
      '#date_data' => $date_data,
      '#module_path' => $request->getBasePath() . '/' . \Drupal::service('extension.list.module')->getPath('teasearch_filter'),
      '#paginator_config' => $paginator_config,
      '#paginator_data' => $paginator_data,
      '#current_page' => $page,
      '#current_query' => $request->query->all(),
      '#per_page' => $per_page,
      // Sort
      '#sort_options' => $sort_data['options'] ?? NULL,
      '#current_sort' => $sort_data['current_key'] ?? NULL,
      '#current_lang' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      '#attached' => [
        'library' => [
          'teasearch_filter/teasearch_filter_styles',
          'teasearch_filter/teasearch_filter_details_state',
          'teasearch_filter/teasearch_sort',
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
  private function getCategoryTitle(string $content_type): string
  {
    try {
      $content_type_lower = trim(strtolower($content_type));

      $storage = $this->entityTypeManager->getStorage('node');
      $all_nids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'categories')
        ->condition('status', 1)
        ->execute();

      if (!$all_nids) {
        return $this->getCategoryTitleFallback($content_type);
      }

      $all_nodes = $storage->loadMultiple($all_nids);
      $lm = \Drupal::languageManager();
      $langcode = $lm->getCurrentLanguage()->getId();

      foreach ($all_nodes as $node) {
        if (!$node->hasField('field_category_menu_list') || $node->get('field_category_menu_list')->isEmpty()) {
          continue;
        }

        $menu_field = $node->get('field_category_menu_list');
        $field_definition = $menu_field->getFieldDefinition();
        $allowed_values = $field_definition->getSetting('allowed_values');

        foreach ($menu_field as $item) {
          $machine_name = $item->value;
          $label = $allowed_values[$machine_name] ?? '';

          if (strtolower($label) === $content_type_lower) {
            if (!$node->hasField('field_link_title') || $node->get('field_link_title')->isEmpty()) {
              continue;
            }

            $translated = $node->hasTranslation($langcode)
              ? $node->getTranslation($langcode)
              : $node;

            return $translated->get('field_link_title')->value;
          }
        }
      }

    } catch (\Throwable $e) {
      \Drupal::logger('teasearch_filter')->error('Error in getCategoryTitle: @message', [
        '@message' => $e->getMessage()
      ]);
    }

    return $this->getCategoryTitleFallback($content_type);
  }

  private function getCategoryTitleFallback(string $content_type): string
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];
    return $content_types[$content_type]['label'] ?? ucfirst($content_type);
  }

  /**
   * Search nodes con supporto sort.
   */
  private function searchNodes($config, Request $request, $page = 0, $per_page = 20, ?array $sort_resolved = NULL)
  {
    $machine_name = $config['machine_name'];
    $filters = $config['filters'] ?: [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $machine_name)
      ->condition('status', 1);

    $this->applyWhereConditions($query, $config);
    $this->applyFiltersToQuery($query, $filters, $request, 'node');
    $this->applyYearRangeFiltering($query, $filters, $request);

    // Count totale PRIMA della paginazione
    $total_query = clone $query;
    $total = $total_query->count()->execute();

    $limit = $per_page;
    $offset = $page * $limit;

    // Applica sort DB per campi diretti (non nested, non internal_references)
    if ($sort_resolved && !SortHelper::requiresInMemorySort($sort_resolved)) {
      SortHelper::applyToQuery($query, $sort_resolved, 'node');
    } else {
      // Fallback se nessun sort configurato o sort in memoria
      $query->sort('created', 'DESC');
    }

    $ids = $query->range($offset, $limit)->execute();
    $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);

    // Sort in memoria per internal_references o nested (applicato DOPO il load)
    if ($sort_resolved && SortHelper::requiresInMemorySort($sort_resolved)) {
      $entities = SortHelper::sortEntitiesInMemory($entities, $sort_resolved, 'node');
    }

    return [$entities, $total];
  }

  /**
   * Search users con supporto sort.
   */
  private function searchUsers($config, Request $request, $page = 0, $per_page = 20, ?array $sort_resolved = NULL)
  {
    $filters = $config['filters'] ?: [];

    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);

    $this->applyFiltersToQuery($query, $filters, $request, 'user');

    // Count totale
    $total_query = clone $query;
    $total = $total_query->count()->execute();

    $limit = $per_page;
    $offset = $page * $limit;

    if ($sort_resolved && !SortHelper::requiresInMemorySort($sort_resolved)) {
      SortHelper::applyToQuery($query, $sort_resolved, 'user');
    } else {
      $query->sort('created', 'DESC');
    }

    $ids = $query->range($offset, $limit)->execute();
    $entities = $this->entityTypeManager->getStorage('user')->loadMultiple($ids);

    if ($sort_resolved && SortHelper::requiresInMemorySort($sort_resolved)) {
      $entities = SortHelper::sortEntitiesInMemory($entities, $sort_resolved, 'user');
    }

    return [$entities, $total];
  }

  /**
   * Prepara i dati del sort per il template.
   */
  private function prepareSortData(string $content_type, ?array $sort_resolved): array
  {
    if (!$sort_resolved) {
      return [];
    }
    return [
      'current_key' => $sort_resolved['key'],
      'options' => $sort_resolved['options'],
    ];
  }

  private function preparePaginatorData($total_results, $current_page, $per_page, $config)
  {
    if (!$config['paginator']) {
      return NULL;
    }

    if ($total_results == 0) {
      return NULL;
    }

    $total_pages = (int) ceil($total_results / $per_page);

    if (!isset($config['always_show']) || !$config['always_show']) {
      if ($total_pages <= 1) {
        return NULL;
      }
    }

    $max_pages_display = $config['pages'];

    $start_page = max(0, $current_page - floor($max_pages_display / 2));
    $end_page = min($total_pages - 1, $start_page + $max_pages_display - 1);

    if ($end_page - $start_page < $max_pages_display - 1) {
      $start_page = max(0, $end_page - $max_pages_display + 1);
    }

    $pages = [];
    for ($i = $start_page; $i <= $end_page; $i++) {
      $pages[] = $i;
    }

    return [
      'total_pages' => $total_pages,
      'current_page' => $current_page,
      'per_page' => $per_page,
      'results_options' => $config['results'],
      'results_default' => $config['results_default'],
      'pages' => $pages,
      'has_prev' => $current_page > 0,
      'has_next' => $current_page < $total_pages - 1,
      'has_first' => $config['additional_buttons'] && $current_page > 0,
      'has_last' => $config['additional_buttons'] && $current_page < $total_pages - 1,
      'show_more_prev' => $start_page > 0,
      'show_more_next' => $end_page < $total_pages - 1,
    ];
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
    $vocabulary = $this->getVocabularyForField($field_name, $config);
    if ($vocabulary) {
      return $this->getTermIdByName($value, $vocabulary);
    }

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
    $special_keys = ['century_selector', 'date_selector'];

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
    $from_field = null;
    $to_field = null;

    if (isset($filters['century_selector'])) {
      $century_config = $filters['century_selector'];
      $from_field = $century_config['from'] ?? 'year_from';
      $to_field = $century_config['to'] ?? 'year_to';
    } elseif (isset($filters['date_selector'])) {
      $date_config = $filters['date_selector'];
      $from_field = $date_config['from'] ?? 'year_from';
      $to_field = $date_config['to'] ?? 'year_to';
    }

    if (!$from_field || !$to_field) {
      return;
    }

    $year_from = $request->query->get('year_from');
    $year_to = $request->query->get('year_to');

    if (is_string($year_from)) {
      $year_from = trim($year_from);
      $year_from = ($year_from === '') ? null : $year_from;
    }

    if (is_string($year_to)) {
      $year_to = trim($year_to);
      $year_to = ($year_to === '') ? null : $year_to;
    }

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

    $data['century_options'] = $this->generateCenturyOptions($data['min_year'], $data['max_year']);

    return $data;
  }

  /**
   * Prepare date selector data.
   */
  private function prepareDateData($filters, $config, Request $request)
  {
    if (!isset($filters['date_selector'])) {
      return null;
    }

    $date_config = $filters['date_selector'];

    return [
      'enabled' => true,
      'from_field' => $date_config['from'] ?? 'year_from',
      'to_field' => $date_config['to'] ?? 'year_to',
      'selected_from' => $request->query->get('year_from'),
      'selected_to' => $request->query->get('year_to'),
      'min_year' => $date_config['min_year'] ?? -3000,
      'max_year' => $date_config['max_year'] ?? date('Y'),
    ];
  }

  /**
   * Generate century options for SELECT elements.
   */
  private function generateCenturyOptions($min_year, $max_year)
  {
    $centuries = [];

    $start_century = floor($min_year / 100);
    $end_century = ceil($max_year / 100);

    if ($start_century < -10) {
      $before_century = abs($start_century);
      $centuries[] = [
        'label' => "Before {$before_century} Century BC",
        'start_year' => -10000,
        'end_year' => ($start_century * 100) - 1
      ];
      $start_century = -10;
    }

    for ($century = $start_century; $century <= $end_century; $century++) {
      $century_start = $century * 100;
      $century_end = $century_start + 99;

      if ($century < 0) {
        $abs_century = abs($century);
        $label = "{$abs_century} Century BC";
      } else {
        $label = ($century == 0) ? "1 Century AD" : "{$century} Century AD";
      }

      $centuries[] = [
        'label' => $label,
        'start_year' => $century_start,
        'end_year' => $century_end,
      ];
    }

    return $centuries;
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
            'selected' => in_array((string) $term->tid, $selected, true)
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

  /**
   * Free search results — /search?q=...&content_type=...
   */
  public function freeSearchResults(Request $request)
  {
    $search_query = trim($request->query->get('q', ''));
    $content_type_filter = $request->query->get('content_type', 'all');

    if (empty($search_query)) {
      return [
        '#markup' => '<div class="no-search-query">' . $this->t('Please enter a search term.') . '</div>',
      ];
    }

    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];
    $global_search_config = $config->get('global_search') ?: [];

    $all_entities = [];
    $mixed_content_types = FALSE;
    $show_global_filters = FALSE;
    $filters_config = [];
    $content_type_config = null;
    $actual_content_type = $content_type_filter;

    // -------------------------------------------------------------------------
    // Sort per ricerca specifica — inizializzato qui per scope corretto
    // -------------------------------------------------------------------------
    $sort_resolved_free = NULL;

    // ========================================================================
    // CASE 1: Search ALL content types — sort non applicato (risultati misti)
    // ========================================================================
    if ($content_type_filter === 'all') {
      $mixed_content_types = TRUE;

      $show_global_filters = $global_search_config['show_filters'] ?? false;
      $filters_config = $global_search_config['filters'] ?? [];

      foreach ($content_types as $content_type => $content_type_config) {
        $entities = $this->searchInContentType($content_type, $content_type_config, $search_query);

        foreach ($entities as $entity) {
          $this->processEntityForDisplay($entity, $content_type_config);
          $entity->teasearch_content_type_label = $content_type_config['label'] ?? ucfirst($content_type);
          $entity->teasearch_results_config = $content_type_config['results'] ?? [];
        }

        $all_entities = array_merge($all_entities, $entities);
      }

      if ($show_global_filters && !empty($filters_config)) {
        $all_entities = $this->applyGlobalFilters($all_entities, $filters_config, $request);
      }

      $first_content_type = array_key_first($content_types);
      $content_type_config = $content_types[$first_content_type];
    }
    // ========================================================================
    // CASE 2: Search su content_type SPECIFICO — sort applicato in memoria
    // ========================================================================
    else {
      if (!isset($content_types[$content_type_filter])) {
        throw new NotFoundHttpException('Content type not found.');
      }

      $content_type_config = $content_types[$content_type_filter];
      $all_entities = $this->searchInContentType($content_type_filter, $content_type_config, $search_query);

      // -----------------------------------------------------------------------
      // SORT in memoria: searchInContentType non supporta sort DB,
      // quindi ordiniamo l'intero set PRIMA della paginazione
      // -----------------------------------------------------------------------
      $sort_resolved_free = SortHelper::resolveFromRequest($content_type_filter, $request);
      if ($sort_resolved_free) {
        $all_entities = SortHelper::sortEntitiesInMemory(
          $all_entities,
          $sort_resolved_free,
          $content_type_config['type'] ?? 'node'
        );
      }

      // Process entities
      foreach ($all_entities as $entity) {
        $this->processEntityForDisplay($entity, $content_type_config);
        $entity->teasearch_results_config = $content_type_config['results'] ?? [];
      }

      $filters_config = $content_type_config['filters'] ?? [];

      if (!empty($filters_config)) {
        $all_entities = $this->applyContentTypeFilters($all_entities, $filters_config, $request, $content_type_config);
      }
    }

    // Paginator
    $paginator_config = $this->getPaginatorConfig();
    $page = (int) $request->query->get('page', 0);
    $per_page = $request->query->get('per_page');

    $session = $request->getSession();
    $session_key = "teasearch_filter.search.per_page";

    if ($per_page === null) {
      $per_page = $session->get($session_key, $paginator_config['results_default']);
    } else {
      $per_page = (int) $per_page;
      $session->set($session_key, $per_page);
    }

    $total_results = count($all_entities);
    $offset = $page * $per_page;
    $paginated_entities = array_slice($all_entities, $offset, $per_page);

    $paginator_data = $this->preparePaginatorData($total_results, $page, $per_page, $paginator_config);

    $entity_type = $content_type_config['type'] ?? 'node';
    $page_title = $this->t('Search Results');

    // Filters
    $grouped_filters = [];
    $century_data = NULL;
    $date_data = NULL;

    if ($mixed_content_types && $show_global_filters && !empty($filters_config)) {
      $grouped_filters = $this->prepareGlobalFiltersFromResults($all_entities, $filters_config, $request);
    } elseif (!$mixed_content_types && !empty($filters_config)) {
      $grouped_filters = $this->prepareContentTypeFiltersFromResults(
        $all_entities,
        $filters_config,
        $content_type_config,
        $request
      );
      $century_data = $this->prepareCenturyData($filters_config, $content_type_config, $request);
      $date_data = $this->prepareDateData($filters_config, $content_type_config, $request);
    }

    // -------------------------------------------------------------------------
    // SORT: dati per il template (solo per ricerca su content_type specifico)
    // -------------------------------------------------------------------------
    $sort_data_free = $this->prepareSortData(
      $content_type_filter !== 'all' ? $content_type_filter : '',
      $sort_resolved_free
    );

    return [
      '#theme' => 'teasearch',
      '#filter_form' => NULL,
      '#entities' => $paginated_entities,
      '#content_type_config' => $content_type_config,
      '#entity_type' => $entity_type,
      '#content_type' => $actual_content_type,
      '#total_results' => $total_results,
      '#has_filters' => !empty($grouped_filters),
      '#grouped_filters' => $grouped_filters,
      '#century_data' => $century_data,
      '#date_data' => $date_data,
      '#paginator_data' => $paginator_data,
      '#current_query' => $request->query->all(),
      '#page_title' => $page_title,
      '#search_query' => $search_query,
      '#mixed_content_types' => $mixed_content_types,
      '#show_global_filters' => $show_global_filters,
      '#is_free_search' => TRUE,
      '#module_path' => \Drupal::service('extension.list.module')->getPath('teasearch_filter'),
      // Sort — NULL se modalità 'all' (mixed)
      '#sort_options' => $sort_data_free['options'] ?? NULL,
      '#current_sort' => $sort_data_free['current_key'] ?? NULL,
      '#current_lang' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      '#attached' => [
        'library' => [
          'teasearch_filter/teasearch_filter_styles',
          'teasearch_filter/teasearch_sort',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list', 'user_list', 'config:teasearch_filter.settings'],
      ],
    ];
  }

  /**
   * Search in a specific content type using SearchHelper.
   */
  protected function searchInContentType($content_type, array $content_type_config, $search_query)
  {
    $entity_type = $content_type_config['type'] ?? 'node';
    $machine_name = $content_type_config['machine_name'];

    $where_conditions = [];
    if (!empty($content_type_config['where'])) {
      $where_json = json_decode($content_type_config['where'], TRUE);
      if (is_array($where_json)) {
        $where_conditions = $where_json;
      }
    }

    $entity_ids = $this->searchHelper->buildDynamicSearchQuery(
      $entity_type,
      $machine_name,
      $search_query,
      $where_conditions
    );

    if (empty($entity_ids)) {
      return [];
    }

    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);

    foreach ($entities as $entity) {
      $this->processEntityForDisplay($entity, $content_type_config);
    }

    return $entities;
  }

  /**
   * Build filtered search results page (with sidebar filters).
   */
  protected function buildFilteredSearchResults($content_type, array $entities, $search_query, Request $request)
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];
    $content_type_config = $content_types[$content_type];

    $filters = $content_type_config['filters'] ?: [];
    $page_title = $this->getCategoryTitle($content_type);

    $form = $this->formBuilder()->getForm(
      'Drupal\teasearch_filter\Form\SearchFilterForm',
      $content_type
    );

    $grouped_filters = $this->prepareGroupedFilters($filters, $content_type_config, $request);
    $century_data = $this->prepareCenturyData($filters, $content_type_config, $request);
    $date_data = $this->prepareDateData($filters, $content_type_config, $request);

    $paginator_config = $this->getPaginatorConfig();
    $page = (int) $request->query->get('page', 0);
    $per_page = $request->query->get('per_page');

    $session = $request->getSession();
    $session_key = "teasearch_filter.{$content_type}.per_page";

    if ($per_page === null) {
      $per_page = $session->get($session_key, $paginator_config['results_default']);
    } else {
      $per_page = (int) $per_page;
      $session->set($session_key, $per_page);
    }

    $total_results = count($entities);
    $offset = $page * $per_page;
    $paginated_entities = array_slice($entities, $offset, $per_page);

    $paginator_data = $this->preparePaginatorData($total_results, $page, $per_page, $paginator_config);

    $entity_type = $content_type_config['type'] ?? 'node';

    return [
      '#theme' => 'teasearch',
      '#filter_form' => $form,
      '#entities' => $paginated_entities,
      '#content_type_config' => $content_type_config,
      '#entity_type' => $entity_type,
      '#content_type' => $content_type,
      '#total_results' => $total_results,
      '#has_filters' => FALSE,
      '#grouped_filters' => $grouped_filters,
      '#century_data' => $century_data,
      '#date_data' => $date_data,
      '#paginator_data' => $paginator_data,
      '#current_query' => $request->query->all(),
      '#page_title' => $page_title,
      '#search_query' => $search_query,
      '#attached' => [
        'library' => ['teasearch_filter/teasearch_filter_styles'],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list', 'user_list', 'config:teasearch_filter.settings'],
      ],
    ];
  }

  protected function processEntityForDisplay($entity, array $config)
  {
    $results_config = $config['results'] ?? [];

    if (!empty($results_config['function'])) {
      $function_name = $results_config['function'];
      $entity->teasearch_custom_content = CustomFieldHelper::execute($function_name, $entity);
      $entity->teasearch_uses_function = TRUE;
    } else {
      $entity->teasearch_uses_function = FALSE;
      $entity->teasearch_processed_content = $this->getProcessedContentForEntity($entity, $config);
    }

    return $entity;
  }

  protected function getProcessedContentForEntity($entity, array $config)
  {
    $results_config = $config['results'] ?? [];
    $sub_field = $results_config['subfield'] ?? NULL;

    if (!$sub_field) {
      return NULL;
    }

    $fields = array_map('trim', explode(',', $sub_field));

    foreach ($fields as $field_name) {
      if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
        continue;
      }

      $field_data = $entity->get($field_name);
      $first_item = $field_data->first();

      if (!$first_item) {
        continue;
      }

      if ($first_item->__isset('summary') && !empty($first_item->summary)) {
        return $first_item->summary;
      }

      if ($first_item->__isset('value') && !empty($first_item->value)) {
        return $first_item->value;
      }

      $value = $first_item->getValue();
      if (isset($value['value']) && !empty($value['value'])) {
        return $value['value'];
      }
    }

    return NULL;
  }

  /**
   * Apply global filters to search results.
   */
  protected function applyGlobalFilters(array $entities, array $filters_config, Request $request)
  {
    $filtered_entities = [];

    foreach ($entities as $entity) {
      $include = TRUE;

      foreach ($filters_config as $filter_key => $filter_config) {
        $selected_values = $request->query->get($filter_key);

        if (empty($selected_values)) {
          continue;
        }

        if (!is_array($selected_values)) {
          $selected_values = [$selected_values];
        }

        $field_name = 'field_' . $filter_key;

        if (!$entity->hasField($field_name)) {
          $include = FALSE;
          break;
        }

        $field_values = $entity->get($field_name);

        if ($field_values->isEmpty()) {
          $include = FALSE;
          break;
        }

        $entity_has_value = FALSE;
        foreach ($field_values as $item) {
          if (isset($item->target_id) && in_array($item->target_id, $selected_values)) {
            $entity_has_value = TRUE;
            break;
          }
        }

        if (!$entity_has_value) {
          $include = FALSE;
          break;
        }
      }

      if ($include) {
        $filtered_entities[] = $entity;
      }
    }

    return $filtered_entities;
  }

  /**
   * Prepare dynamic global filters based on actual search results.
   */
  protected function prepareGlobalFiltersFromResults(array $entities, array $filters_config, Request $request)
  {
    $grouped_filters = [];

    foreach ($filters_config as $filter_key => $filter_config) {
      $filter_type = $filter_config['type'] ?? 'taxonomy';

      if ($filter_type !== 'taxonomy') {
        continue;
      }

      $field_name = 'field_' . $filter_key;
      $vocabulary = $filter_config['vocabulary'] ?? '';

      if (empty($vocabulary)) {
        continue;
      }

      $term_counts = [];

      foreach ($entities as $entity) {
        if (!$entity->hasField($field_name)) {
          continue;
        }

        $field_values = $entity->get($field_name);

        if ($field_values->isEmpty()) {
          continue;
        }

        foreach ($field_values as $item) {
          if (isset($item->target_id)) {
            $tid = $item->target_id;
            $term_counts[$tid] = ($term_counts[$tid] ?? 0) + 1;
          }
        }
      }

      if (empty($term_counts)) {
        continue;
      }

      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadMultiple(array_keys($term_counts));

      $selected_values = $request->query->get($filter_key);
      if (!is_array($selected_values)) {
        $selected_values = $selected_values ? [$selected_values] : [];
      }
      $selected_values = array_map('intval', $selected_values);

      $options = [];
      $language_manager = \Drupal::languageManager();
      $current_language = $language_manager->getCurrentLanguage()->getId();

      foreach ($terms as $term) {
        if ($term->hasTranslation($current_language)) {
          $term = $term->getTranslation($current_language);
        }

        $tid = $term->id();
        $count = $term_counts[$tid] ?? 0;

        $options[$tid] = [
          'label' => $term->getName(),
          'count' => $count,
          'selected' => in_array((int) $tid, $selected_values, TRUE),
        ];
      }

      uasort($options, function ($a, $b) {
        return strcmp($a['label'], $b['label']);
      });

      $grouped_filters[$filter_key] = [
        'label' => $filter_config['label'] ?? ucfirst($filter_key),
        'type' => 'taxonomy',
        'options' => $options,
        'selected' => $selected_values,
        'is_open' => !empty($selected_values),
      ];
    }

    return $grouped_filters;
  }

  /**
   * Apply content type specific filters to search results.
   */
  protected function applyContentTypeFilters(array $entities, array $filters_config, Request $request, array $content_type_config)
  {
    $filtered_entities = [];
    $entity_type = $content_type_config['type'] ?? 'node';

    foreach ($entities as $entity) {
      $include = TRUE;

      foreach ($filters_config as $filter_key => $filter_config) {
        $filter_type = $filter_config['type'] ?? 'taxonomy';

        if ($filter_key === 'century_selector' || $filter_key === 'date_selector') {
          continue;
        }

        $selected_values = $request->query->get($filter_key);

        if (empty($selected_values)) {
          continue;
        }

        if (!is_array($selected_values)) {
          $selected_values = [$selected_values];
        }

        if ($filter_type === 'taxonomy') {
          $field_name = $entity_type === 'user' ? $filter_key : 'field_' . $filter_key;

          if (!$entity->hasField($field_name)) {
            $include = FALSE;
            break;
          }

          $field_values = $entity->get($field_name);

          if ($field_values->isEmpty()) {
            $include = FALSE;
            break;
          }

          $entity_has_value = FALSE;
          foreach ($field_values as $item) {
            if (isset($item->target_id) && in_array($item->target_id, $selected_values)) {
              $entity_has_value = TRUE;
              break;
            }
          }

          if (!$entity_has_value) {
            $include = FALSE;
            break;
          }
        }
      }

      if ($include) {
        $filtered_entities[] = $entity;
      }
    }

    return $filtered_entities;
  }

  /**
   * Prepare dynamic content type filters from search results.
   */
  protected function prepareContentTypeFiltersFromResults(array $entities, array $filters_config, array $content_type_config, Request $request)
  {
    $grouped_filters = [];
    $entity_type = $content_type_config['type'] ?? 'node';
    $language_manager = \Drupal::languageManager();
    $current_language = $language_manager->getCurrentLanguage()->getId();

    foreach ($filters_config as $filter_key => $filter_config) {
      $filter_type = $filter_config['type'] ?? 'taxonomy';

      if ($filter_key === 'century_selector' || $filter_key === 'date_selector') {
        continue;
      }

      if ($filter_type !== 'taxonomy') {
        continue;
      }

      $field_name = $entity_type === 'user' ? $filter_key : 'field_' . $filter_key;
      $vocabulary = $filter_config['vocabulary'] ?? '';

      if (empty($vocabulary)) {
        continue;
      }

      $term_counts = [];

      foreach ($entities as $entity) {
        if (!$entity->hasField($field_name)) {
          continue;
        }

        $field_values = $entity->get($field_name);

        if ($field_values->isEmpty()) {
          continue;
        }

        foreach ($field_values as $item) {
          if (isset($item->target_id)) {
            $tid = $item->target_id;
            $term_counts[$tid] = ($term_counts[$tid] ?? 0) + 1;
          }
        }
      }

      if (empty($term_counts)) {
        continue;
      }

      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $term_storage->loadMultiple(array_keys($term_counts));

      $selected_values = $request->query->get($filter_key);
      if (!is_array($selected_values)) {
        $selected_values = $selected_values ? [$selected_values] : [];
      }
      $selected_values = array_map('intval', $selected_values);

      $options = [];
      foreach ($terms as $term) {
        if ($term->hasTranslation($current_language)) {
          $term = $term->getTranslation($current_language);
        }

        $tid = $term->id();
        $count = $term_counts[$tid] ?? 0;

        $options[$tid] = [
          'label' => $term->getName(),
          'count' => $count,
          'selected' => in_array((int) $tid, $selected_values, TRUE),
        ];
      }

      uasort($options, function ($a, $b) {
        return strcmp($a['label'], $b['label']);
      });

      $grouped_filters[$filter_key] = [
        'label' => $filter_config['label'] ?? ucfirst($filter_key),
        'type' => 'taxonomy',
        'options' => $options,
        'selected' => $selected_values,
        'is_open' => !empty($selected_values),
      ];
    }

    return $grouped_filters;
  }
}