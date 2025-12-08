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
    // Get all configured content types
    $config = $this->configFactory->get('teasearch_filter.settings');
    $content_types = $config->get('content_types') ?: [];

    // Build options for dropdown
    $content_type_options = [
      'all' => $this->t('All'),
    ];

    foreach ($content_types as $machine_name => $content_type_config) {
      $label = $content_type_config['label'] ?? ucfirst($machine_name);
      $content_type_options[$machine_name] = $this->t($label);
    }

    // Get current query parameters if we're on search page
    $current_request = \Drupal::request();
    $current_query = $current_request->query->get('q', '');
    $current_content_type = $current_request->query->get('content_type', 'all');


    
    \Drupal::logger('teasearch_filter')->notice(
      'GlobalSearchBlock: URL query content_type = @ct',
      ['@ct' => $current_content_type]
    );

    return [
      '#theme' => 'teasearch_global_search_block',
      '#content_type_options' => $content_type_options,
      '#search_action' => Url::fromRoute('teasearch_filter.free_search_results')->toString(),
      '#current_query' => $current_query,
      '#current_content_type' => $current_content_type,
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['config:teasearch_filter.settings'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return ['url.query_args'];
  }
}