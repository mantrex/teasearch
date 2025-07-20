<?php

namespace Drupal\choices_facets\Plugin\facets\widget;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Widget\WidgetPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Choices.js widget.
 *
 * @FacetsWidget(
 *   id = "choices_js",
 *   label = @Translation("Choices.js"),
 *   description = @Translation("A widget that shows a Choices dropdown."),
 * )
 */
class ChoicesWidget extends WidgetPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $this->facet = $facet;

    $items = [];
    $active_items = [];
    foreach ($facet->getResults() as $result) {
      if (empty($result->getUrl())) {
        continue;
      }

      $count = $result->getCount();
      $this->showNumbers = $this->getConfiguration()['show_numbers'] && ($count !== NULL);
      $items[$result->getUrl()->toString()] = ($this->showNumbers ? sprintf('%s (%d)', $result->getDisplayValue(), $result->getCount()) : $result->getDisplayValue());
      if ($result->isActive()) {
        $active_items[] = $result->getUrl()->toString();
      }
    }

    return [
      '#type' => 'select',
      '#options' => $items,
      '#required' => FALSE,
      '#value' => $active_items,
      '#multiple' => !$facet->getShowOnlyOneResult(),
      '#name' => $facet->getName(),
      '#title' => $facet->get('show_title') ? $facet->getName() : '',
      '#attributes' => [
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-selector' => 'facet-' . $facet->id(),
        'class' => ['js-facets-choices', 'js-facets-widget'],
      ],
      '#attached' => [
        'library' => ['choices/widget'],
        'drupalSettings' => [
          'choices' => [
            'facets' => [
              'hasFacetsWidget' => TRUE,
            ],
          ],
        ],
      ],
    ];
  }

}
