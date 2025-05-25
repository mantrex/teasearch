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

    // Prepare grouped filters for twig
    $grouped_filters = $this->prepareGroupedFilters($filters, $content_type, $request);

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
        // Gestione sicura per PHP 8.4
        if (is_string($value)) {
          $values = explode(',', $value);
        } elseif (is_array($value)) {
          $values = $value;
        } else {
          $values = [$value];
        }

        $clean_values = array_filter(array_map('intval', $values));

        if (!empty($clean_values)) {
          $query->condition("field_{$field}.target_id", $clean_values, 'IN');
        }
      }
      // Free text filter
      elseif ($filter['type'] === 'free_text') {
        // Assicuriamoci che value sia una stringa
        $value_string = is_array($value) ? implode(',', $value) : (string) $value;
        $terms = explode(',', $value_string);
        $clean_terms = array_filter(array_map('trim', $terms));

        if (!empty($clean_terms)) {
          $group = $query->orConditionGroup();
          foreach ($clean_terms as $term) {
            if (!empty($term)) {
              $group->condition('body.value', "%{$term}%", 'LIKE');
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

    // Build render array
    return [
      '#theme' => 'teasearch',
      '#filter_form' => $form,
      '#nodes' => $nodes,
      '#filters' => $filters,
      '#grouped_filters' => $grouped_filters,
      '#content_type' => $content_type,
      '#attached' => [
        'library' => [
          'teasearch_filter/teasearch_filter_styles',
          'teasearch_filter/teasearch_filter_details_state',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['node_list'],
      ],
    ];
  }

  /**
   * Prepare grouped filters data for twig template.
   */
  private function prepareGroupedFilters($filters, $content_type_value, Request $request)
  {
    $content_type_blocks = [
      "first_reference" => "first_reference",

    ];

    $content_type = @$content_type_blocks[$content_type_value] ? $content_type_blocks[$content_type_value] :  $content_type_value;

    $grouped_filters = [];
    $query_values = $request->query->all();

    foreach ($filters as $field_name => $filter) {
      $selected = $query_values[$field_name] ?? [];

      // Gestione sicura dei valori per evitare errori PHP 8.4
      if ($filter['type'] === 'taxonomy') {
        // Per taxonomy, convertiamo sempre in array di integers
        if (is_string($selected)) {
          $selected = explode(',', $selected);
        }
        if (!is_array($selected)) {
          $selected = [$selected];
        }
        $selected = array_filter(array_map('intval', $selected));
      } else {
        // Per free text, convertiamo sempre in stringa
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
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);

        foreach ($terms as $term) {
          $count = $this->entityTypeManager->getStorage('node')->getQuery()
            ->accessCheck(TRUE)
            ->condition('type', $content_type)
            ->condition('status', 1)
            ->condition("field_{$field_name}.target_id", $term->tid)
            ->count()
            ->execute();

          if ($count) {
            $filter_data['options'][$term->tid] = [
              'label' => $term->name ?? '',
              'count' => $count,
              'selected' => in_array((int)$term->tid, $selected, true)
            ];
          }
        }
      }

      $grouped_filters[$field_name] = $filter_data;
    }

    return $grouped_filters;
  }
}
