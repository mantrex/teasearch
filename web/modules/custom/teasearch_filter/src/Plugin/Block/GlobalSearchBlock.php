<?php

namespace Drupal\teasearch_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Provides a 'Global Search' Block.
 *
 * @Block(
 *   id = "teasearch_global_search",
 *   admin_label = @Translation("Teasearch Global Search"),
 *   category = @Translation("Teasearch")
 * )
 */
class GlobalSearchBlock extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GlobalSearchBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Load teasearch_filter configuration
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];

    // Load categories nodes to get weights
    $category_weights = $this->getCategoryWeights();

    
    // Add content types with their weights for sorting
    $content_types_with_weight = [];
    foreach ($content_types as $machine_name => $content_type_config) {
      $label = $content_type_config['label'] ?? ucfirst($machine_name);

      // Get weight from categories node
      // Se non trova il peso, usa -9999 così va in fondo
      $weight = $category_weights[$machine_name] ?? -9999;

      $content_types_with_weight[] = [
        'machine_name' => $machine_name,
        'label' => $this->t($label),
        'weight' => $weight,
      ];
    }

    // Sort by weight DESCENDING: higher weight = first position
    usort($content_types_with_weight, function ($a, $b) {
      return $b['weight'] <=> $a['weight'];
    });

    // Build final options array in sorted order
    $content_type_options = [
      'all' => $this->t('All'),
    ];

    foreach ($content_types_with_weight as $item) {
      $content_type_options[$item['machine_name']] = $item['label'];
    }

    // Get current query parameters if we're on search page
    $current_request = \Drupal::request();
    $current_query = $current_request->query->get('q', '');
    $current_content_type = $current_request->query->get('content_type', 'all');


    $current_path = \Drupal::service('path.current')->getPath();
    if (preg_match('#^/category/([^/]+)#', $current_path, $matches)) {
      $path_content_type = $matches[1];
      // Verifica che sia un content type valido
      if (isset($content_types[$path_content_type])) {
        $current_content_type = $path_content_type;
      }
    }

    // Subito dopo getCategoryWeights()
    \Drupal::logger('teasearch_filter')->notice('Weights: @weights', ['@weights' => print_r($category_weights, TRUE)]);

    // Subito dopo usort
    \Drupal::logger('teasearch_filter')->notice('Sorted: @sorted', [
      '@sorted' => print_r(array_map(function ($item) {
        return $item['machine_name'] . ' (weight: ' . $item['weight'] . ')';
      }, $content_types_with_weight), TRUE)
    ]);

    return [
      '#theme' => 'teasearch_global_search_block',
      '#content_type_options' => $content_type_options,
      '#search_action' => Url::fromRoute('teasearch_filter.free_search_results')->toString(),
      '#current_query' => $current_query,
      '#current_content_type' => $current_content_type,
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['config:teasearch_filter.settings', 'node_list:categories'],
      ],
    ];
  }

  /**
   * Get weights from categories nodes.
   *
   * @return array
   *   Array of weights keyed by category machine name.
   */
  protected function getCategoryWeights()
  {
    $weights = [];

    // Mapping tra field_category_menu_list (nei nodi) e machine_name (nel config)
    $mapping = [
      'first_reference' => 'essentials',
      'primary_sources' => 'texts',
      'videos' => 'video',
      'images' => 'images',
      'bibliography' => 'bibliography',
      'people' => 'people',
      'contributors' => 'people', // Se contributors = people
    ];

    $storage = \Drupal::entityTypeManager()->getStorage('node');

    // Get all categories nodes
    $query = $storage->getQuery()
      ->condition('type', 'categories')
      ->condition('status', 1)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return $weights;
    }

    $nodes = $storage->loadMultiple($nids);

    foreach ($nodes as $node) {
      // Get category selector (valore da field_category_menu_list)
      if ($node->hasField('field_category_menu_list') && !$node->get('field_category_menu_list')->isEmpty()) {
        $category_selector = $node->get('field_category_menu_list')->value;

        // Get weight (default 0 if not set)
        $weight = 0;
        if ($node->hasField('field_weight') && !$node->get('field_weight')->isEmpty()) {
          $weight = (int) $node->get('field_weight')->value;
        }

        // Usa il mapping per convertire al machine_name del config
        $config_machine_name = $mapping[$category_selector] ?? $category_selector;
        $weights[$config_machine_name] = $weight;
      }
    }

    return $weights;
  }
  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return ['url.query_args'];
  }



  /**
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   */
  public function access(\Drupal\Core\Session\AccountInterface $account, $return_as_object = FALSE)
  {
    $request = \Drupal::request();
    $current_path = $request->getPathInfo();

    // Rimuovi prefisso lingua se presente
    $path_without_lang = preg_replace('#^/[a-z]{2}/#', '/', $current_path);

    // NASCONDI in:
    // 1. Pagine di dettaglio: /category/{type}/QUALCOSA
    // 2. Pagine di dettaglio nodo: /node/{id}
    // 3. Pagine di dettaglio news: /news/{anything}
    // 4. Pagina carousel-all: /carousel-all
    // 5. Pagina news lista: /news (opzionale con flag)

    $hideNews = true; // Imposta a false se vuoi mostrare nella lista news

    $forbidden = false;

    // Dettagli categoria
    if (preg_match('#^/category/[^/]+/.+#', $path_without_lang)) {
      $forbidden = true;
    }

    // Dettagli nodo
    if (preg_match('#^/node/\d+#', $path_without_lang)) {
      $forbidden = true;
    }

    // Dettagli news (URL con path pattern /news/titolo)
    if (preg_match('#^/news/.+#', $path_without_lang)) {
      $forbidden = true;
    }

    // Carousel-all
    if (preg_match('#^/carousel-all#', $path_without_lang)) {
      $forbidden = true;
    }

    // News lista (opzionale) - deve essere DOPO il check del dettaglio
    if ($hideNews && $path_without_lang === '/news') {
      $forbidden = true;
    }

    $access = $forbidden
      ? \Drupal\Core\Access\AccessResult::forbidden()
      : \Drupal\Core\Access\AccessResult::allowed();

    return $return_as_object ? $access : $access->isAllowed();
  }
}