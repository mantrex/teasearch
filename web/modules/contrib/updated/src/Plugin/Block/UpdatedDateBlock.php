<?php

namespace Drupal\updated\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders the last updated date value.
 *
 * @Block(
 *   id = "updated_date_block",
 *   admin_label = @Translation("Last Updated date block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class UpdatedDateBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Creates a SystemBrandingBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DateFormatterInterface $date_formatter,
    EntityStorageInterface $date_format_storage,
    TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'date_prefix' => $this->t('Last updated on'),
      'date_format' => 'custom',
      'custom_date_format' => 'F j, Y g:ia',
      'timezone' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Get node from the plugin context.
    $node = $this->getContextValue('node');

    // If display_updated is not checked, forbid access.
    $display_updated = $node->get('display_updated')->value;
    if (!$display_updated) {
      return AccessResult::forbidden('Forbidden by Update module')
        ->addCacheableDependency($node);
    }

    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['updated_block'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Date display settings'),
    ];

    $form['updated_block']['date_prefix'] = [
      '#title' => $this->t('Date Prefix'),
      '#type' => 'textfield',
      '#description' => $this->t('Text displayed immediately preceding the date.'),
      '#default_value' => $this->configuration['date_prefix'],
    ];

    $date_formats = [];
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format($this->time->getRequestTime(), $machine_name),
      ]);
    }
    $date_formats['custom'] = $this->t('Custom');

    $form['updated_block']['formatter_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings for the timestamp <em>Default</em> field formatter.'),
    ];

    $form['updated_block']['formatter_settings']['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats,
      '#default_value' => $this->configuration['date_format'],
    ];

    $form['updated_block']['formatter_settings']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->configuration['custom_date_format'] ?: '',
    ];

    $form['updated_block']['formatter_settings']['custom_date_format']['#states']['visible'][] = [
      ':input[name="settings[updated_block][formatter_settings][date_format]"]' => ['value' => 'custom'],
    ];

    $timezones = TimeZoneFormHelper::getOptionsListByRegion();
    $form['updated_block']['formatter_settings']['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => ['' => $this->t('- Default site/user time zone -')] + $timezones,
      '#default_value' => $this->configuration['timezone'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $updated_block = $form_state->getValue('updated_block');
    $this->setConfigurationValue('date_prefix', strip_tags($updated_block['date_prefix']));
    $this->setConfigurationValue('date_format', $updated_block['formatter_settings']['date_format']);
    $this->setConfigurationValue('custom_date_format', $updated_block['formatter_settings']['custom_date_format']);
    $this->setConfigurationValue('timezone', $updated_block['formatter_settings']['timezone']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Get node from the plugin context.
    $node = $this->getContextValue('node');

    // Check for value in 'changed' field.
    $updated_date_field = $node->get('changed');
    if ($updated_date_field->isEmpty()) {
      return $build;
    }

    // Get configuration values.
    $configuration = $this->getConfiguration();

    // Create updated_date_prefix render array.
    $build['updated_date_prefix'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $configuration['date_prefix'],
    ];
    $build['updated_date_prefix']['#attributes']['class'][] = 'updated-date-message';

    // Create updated_date (field) render array.
    $display_options = [
      'label' => 'hidden',
      'type' => 'timestamp',
      'settings' => [
        'date_format' => $configuration['date_format'],
        'custom_date_format' => $configuration['custom_date_format'],
        'timezone' => $configuration['timezone'],
      ],
    ];
    $build['updated_date'] = $updated_date_field->view($display_options);
    $build['updated_date']['#theme'] = 'field__node__changed__updated';
    $build['updated_date']['#attributes']['class'][] = 'updated-date';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $parent_tags = parent::getCacheTags();
    // Get node from the plugin context.
    $node = $this->getContextValue('node');

    // Block should cache for display_updated field values.
    $node_tag = ['updated:display_updated:' . $node->get('display_updated')->value];
    return Cache::mergeTags($parent_tags, $node_tag);
  }

}
