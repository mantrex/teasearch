<?php

namespace Drupal\teasearch_carousel\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a fully custom carousel block.
 *
 * @Block(
 *   id = "carousel_block",
 *   admin_label = @Translation("Carousel Block"),
 * )
 */
class CarouselBlock extends BlockBase
{
  

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $view_all_url = \Drupal\Core\Url::fromRoute('teasearch_carousel.view_all')->toString();
    return [
      '#markup' => '
        
        <div class="container-fluid">' .
        '<div class="swiper">'
        . '<div class="news_title">' . $this->t('News and Highlights') . '</div>' .
        '<div class="swiper-wrapper" id="carousel-content"></div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>
          <div class="carousel-view-all-wrapper">
            <a href="'.$view_all_url.'" class="carousel-view-all-btn" id="carousel-view-all">' . $this->t('View All') . '</a>
          </div>
        </div>
      ',
      '#attached' => [
        'library' => [
          'teasearch_carousel/carousel',
        ],
      ],
    ];
  }
}
