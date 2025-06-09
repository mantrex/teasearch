<?php

namespace Drupal\teasearch_filter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search/filter form dynamically from config.
 */
class SearchFilterForm extends FormBase
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
   * Constructs a new SearchFilterForm.
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
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'teasearch_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $content_type = NULL)
  {
    if (!$content_type) {
      return $form;
    }

    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_type_config = $config->get("content_types.{$content_type}");

    if (!$content_type_config) {
      return $form;
    }

    $filters = $content_type_config['filters'] ?? [];
    $query_values = \Drupal::request()->query->all();

    // Process only standard filters (exclude special configurations)
    $standard_filters = $this->getStandardFilters($filters);

    foreach ($standard_filters as $field_name => $filter) {
      $this->buildFilterElement($form, $field_name, $filter, $query_values, $content_type);
    }

    $form['content_type'] = [
      '#type' => 'value',
      '#value' => $content_type,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $content_type = $form_state->getValue('content_type');
    $config = $this->configFactory->get('teasearch_filter.settings');
    $filters = $config->get("content_types.{$content_type}.filters") ?? [];
    $query = [];

    // Process standard filters
    $standard_filters = $this->getStandardFilters($filters);

    foreach ($standard_filters as $field_name => $filter) {
      $values = $form_state->getValue([$field_name, 'values']);

      if ($filter['type'] === 'taxonomy') {
        $filtered_values = is_array($values) ? array_filter($values) : [];
        if (!empty($filtered_values)) {
          $query[$field_name] = implode(',', $filtered_values);
        }
      } else {
        $cleaned_value = is_string($values) ? trim($values) : '';
        if (!empty($cleaned_value)) {
          $query[$field_name] = $cleaned_value;
        }
      }
    }

    // Preserve year range parameters from century selector
    $this->preserveYearRangeParams($query);

    $form_state->setRedirect(
      'teasearch_filter.search',
      ['content_type' => $content_type],
      ['query' => $query]
    );
  }

  /**
   * Get only standard filters, excluding special configurations.
   *
   * @param array $filters
   *   All filters configuration.
   *
   * @return array
   *   Standard filters only.
   */
  private function getStandardFilters(array $filters)
  {
    $standard_filters = [];
    $special_keys = ['century_selector']; // Add other special keys here

    foreach ($filters as $field_name => $filter) {
      // Skip special configurations
      if (in_array($field_name, $special_keys)) {
        continue;
      }

      // Validate filter structure
      if (!$this->isValidFilter($filter, $field_name)) {
        continue;
      }

      $standard_filters[$field_name] = $filter;
    }

    return $standard_filters;
  }

  /**
   * Validate if a filter has the required structure.
   *
   * @param array $filter
   *   Filter configuration.
   * @param string $field_name
   *   Field name for logging.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  private function isValidFilter(array $filter, string $field_name)
  {
    if (empty($filter['type'])) {
      \Drupal::logger('teasearch_filter')->warning('Filter @field missing type property', [
        '@field' => $field_name
      ]);
      return FALSE;
    }

    if (empty($filter['label'])) {
      \Drupal::logger('teasearch_filter')->warning('Filter @field missing label property', [
        '@field' => $field_name
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Build individual filter element.
   *
   * @param array $form
   *   Form array.
   * @param string $field_name
   *   Field name.
   * @param array $filter
   *   Filter configuration.
   * @param array $query_values
   *   Current query values.
   * @param string $content_type
   *   Content type.
   */
  private function buildFilterElement(array &$form, string $field_name, array $filter, array $query_values, string $content_type)
  {
    $selected = $query_values[$field_name] ?? [];

    // Handle taxonomy filters
    if ($filter['type'] === 'taxonomy') {
      if (!is_array($selected)) {
        $selected = explode(',', (string) $selected);
      }
      $selected = array_filter($selected);
    }

    $form[$field_name] = [
      '#type' => 'details',
      '#title' => $this->t($filter['label']),
      '#open' => !empty($selected),
      '#tree' => TRUE,
      '#attributes' => ['data-name' => $field_name],
    ];

    switch ($filter['type']) {
      case 'taxonomy':
        $this->buildTaxonomyFilter($form[$field_name], $filter, $selected, $content_type, $field_name);
        break;

      case 'free_text':
      default:
        $this->buildTextFilter($form[$field_name], $filter, $selected, $content_type);
        break;
    }
  }

  /**
   * Build taxonomy filter options.
   *
   * @param array $form_element
   *   Form element to build.
   * @param array $filter
   *   Filter configuration.
   * @param array $selected
   *   Selected values.
   * @param string $content_type
   *   Content type.
   * @param string $field_name
   *   Field name.
   */
  private function buildTaxonomyFilter(array &$form_element, array $filter, array $selected, string $content_type, string $field_name)
  {
    if (empty($filter['vocabulary'])) {
      return;
    }

    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);
    $options = [];

    foreach ($terms as $term) {
      $count = $this->getTermCount($term->tid, $field_name, $content_type);
      if ($count > 0) {
        $options[$term->tid] = "{$term->name} ({$count})";
      }
    }

    $form_element['values'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $selected,
    ];
  }

  /**
   * Build text filter.
   *
   * @param array $form_element
   *   Form element to build.
   * @param array $filter
   *   Filter configuration.
   * @param mixed $selected
   *   Selected value.
   * @param string $content_type
   *   Content type.
   */
  private function buildTextFilter(array &$form_element, array $filter, $selected, string $content_type)
  {
    $is_user_search = $this->isUserBasedContentType($content_type);

    $form_element['values'] = [
      '#type' => 'textfield',
      '#description' => $is_user_search
        ? $this->t('Search in user names, bio, or profile fields.')
        : $this->t('Enter one or more comma-separated keywords.'),
      '#default_value' => is_string($selected) ? $selected : '',
      '#placeholder' => $this->t('Enter keywords...'),
    ];
  }

  /**
   * Get count of entities for a taxonomy term.
   *
   * @param int $term_id
   *   Term ID.
   * @param string $field_name
   *   Field name.
   * @param string $content_type
   *   Content type.
   *
   * @return int
   *   Count of entities.
   */
  private function getTermCount(int $term_id, string $field_name, string $content_type)
  {
    try {
      if ($this->isUserBasedContentType($content_type)) {
        $query = $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 1)
          ->condition("{$field_name}.target_id", $term_id);

        // Special handling for contributors
        if ($content_type === 'contributors') {
          $query->condition('roles', 'contributor', 'IN');
        }

        return $query->count()->execute();
      } else {
        return $this->entityTypeManager->getStorage('node')->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', $this->getContentTypeMachineName($content_type))
          ->condition('status', 1)
          ->condition("field_{$field_name}.target_id", $term_id)
          ->count()
          ->execute();
      }
    } catch (\Exception $e) {
      \Drupal::logger('teasearch_filter')->error('Error getting term count: @message', [
        '@message' => $e->getMessage()
      ]);
      return 0;
    }
  }

  /**
   * Get machine name for content type.
   *
   * @param string $content_type
   *   Content type key.
   *
   * @return string
   *   Machine name.
   */
  private function getContentTypeMachineName(string $content_type)
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_type_config = $config->get("content_types.{$content_type}");
    return $content_type_config['machine_name'] ?? $content_type;
  }

  /**
   * Check if content type is user-based.
   *
   * @param string $content_type
   *   Content type.
   *
   * @return bool
   *   TRUE if user-based, FALSE otherwise.
   */
  private function isUserBasedContentType(string $content_type)
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_type_config = $config->get("content_types.{$content_type}");
    return ($content_type_config['type'] ?? 'node') === 'user';
  }

  /**
   * Preserve year range parameters from request.
   *
   * @param array $query
   *   Query parameters array to modify.
   */
  private function preserveYearRangeParams(array &$query)
  {
    $request = \Drupal::request();

    $year_from = $request->query->get('year_from');
    if ($year_from !== null && $year_from !== '') {
      $query['year_from'] = $year_from;
    }

    $year_to = $request->query->get('year_to');
    if ($year_to !== null && $year_to !== '') {
      $query['year_to'] = $year_to;
    }
  }
}
