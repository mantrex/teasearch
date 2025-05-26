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

  public function getFormId()
  {
    return 'teasearch_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $content_type = NULL)
  {
    $config = $this->configFactory->get('teasearch_filter.settings');
    $filters = $config->get("content_types.{$content_type}.filters") ?: [];
    $query_values = \Drupal::request()->query->all();

    // Check if this is a user-based search
    $is_user_search = $this->isUserBasedContentType($content_type);

    foreach ($filters as $field_name => $filter) {
      // Gestione valori selezionati
      $selected = $query_values[$field_name] ?? [];

      // Conversione stringa -> array per taxonomy
      if ($filter['type'] === 'taxonomy' && !is_array($selected)) {
        $selected = explode(',', (string) $selected);
        $selected = array_filter($selected);
      }

      $form[$field_name] = [
        '#type' => 'details',
        '#title' => $this->t($filter['label']),
        '#open' => !empty($selected),
        '#tree' => TRUE,
        '#attributes' => ['data-name' => $field_name],
      ];

      if ($filter['type'] === 'taxonomy') {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($filter['vocabulary']);
        $options = [];

        foreach ($terms as $term) {
          if ($is_user_search) {
            // Query per contare utenti
            $count_query = $this->entityTypeManager->getStorage('user')->getQuery()
              ->accessCheck(TRUE)
              ->condition('status', 1)
              ->condition("field_{$field_name}.target_id", $term->tid);

            // Apply role filter for contributors
            if ($content_type === 'contributors') {
              $count_query->condition('roles', 'contributor', 'IN');
            }

            $count = $count_query->count()->execute();
          } else {
            // Query per contare nodi
            $count_query = $this->entityTypeManager->getStorage('node')->getQuery()
              ->accessCheck(TRUE)
              ->condition('type', $content_type)
              ->condition('status', 1)
              ->condition("field_{$field_name}.target_id", $term->tid);

            $count = $count_query->count()->execute();
          }

          if ($count) {
            $options[$term->tid] = "{$term->name} ({$count})";
          }
        }

        $form[$field_name]['values'] = [
          '#type' => 'checkboxes',
          '#options' => $options,
          '#default_value' => $selected,
        ];
      } else {
        $form[$field_name]['values'] = [
          '#type' => 'textfield',
          '#description' => $is_user_search
            ? $this->t('Search in user names, bio, or profile fields.')
            : $this->t('Enter one or more comma-separated keywords.'),
          '#default_value' => is_string($selected) ? $selected : '',
        ];
      }
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

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $content_type = $form_state->getValue('content_type');
    $config = $this->configFactory->get('teasearch_filter.settings');
    $filters = $config->get("content_types.{$content_type}.filters") ?: [];
    $query = [];

    foreach ($filters as $field_name => $filter) {
      $values = $form_state->getValue([$field_name, 'values']);

      if ($filter['type'] === 'taxonomy') {
        // Pulizia valori e conversione array -> stringa
        $filtered_values = is_array($values) ? array_filter($values) : [];
        $query[$field_name] = implode(',', $filtered_values);
      } else {
        $query[$field_name] = is_string($values) ? trim($values) : '';
      }
    }

    $form_state->setRedirect(
      'teasearch_filter.search',
      ['content_type' => $content_type],
      ['query' => array_filter($query)]
    );
  }

  /**
   * Check if content type is user-based.
   */
  private function isUserBasedContentType($content_type)
  {
    $user_based_types = ['contributors']; // Aggiungi altri se necessario
    return in_array($content_type, $user_based_types);
  }
}
