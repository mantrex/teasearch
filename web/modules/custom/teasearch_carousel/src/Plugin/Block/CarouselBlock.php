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
    return [
      '#markup' => '
        
        <div class="container-fluid">'.
          '<div class="swiper">'
        . '<div class="news_title">'.$this->t('News and Highlights').'</div>'.
           '<div class="swiper-wrapper" id="carousel-content"></div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
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
