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
        $values = is_array($value) ? $value : explode(',', (string) $value);
        $clean_values = array_filter(array_map('intval', $values));

        if (!empty($clean_values)) {
          $query->condition("field_{$field}.target_id", $clean_values, 'IN');
        }
      }
      // Free text filter
      elseif ($filter['type'] === 'free_text') {
        $terms = is_array($value) ? $value : explode(',', (string) $value);
        $clean_terms = array_filter(array_map('trim', $terms));

        if (!empty($clean_terms)) {
          $group = $query->orConditionGroup();
          foreach ($clean_terms as $term) {
            $group->condition('body.value', "%{$term}%", 'LIKE');
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
}
