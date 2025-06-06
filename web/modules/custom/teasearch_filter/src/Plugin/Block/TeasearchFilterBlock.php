<?php

namespace Drupal\teasearch_filter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\teasearch_filter\Form\SearchFilterForm;

/**
 * Provides a 'Teasearch Filter' Block.
 *
 * @Block(
 *   id = "teasearch_filter_block",
 *   admin_label = @Translation("Teasearch Filter"),
 * )
 */
class TeasearchFilterBlock extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
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
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // Se la rotta ha un parametro content_type, lo usiamo; altrimenti default.
    $content_type = \Drupal::routeMatch()->getParameter('content_type') ?: 'primary_sources';
    // Renderizza il form di filtro per quel content type.
    return $this->formBuilder->getForm(SearchFilterForm::class, $content_type);
  }
}
