<?php

/**
 * @file
 * Theme preprocess used to prepare Twig variables.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Prepares term objects for Twig template.
 *
 * @param array $variables
 *   An associative array with preprocess variables for this theme.
 *   by theme_preprocess.
 */
function template_preprocess_custom_field_hierarchical_formatter(array &$variables) {
  $terms = [];
  $variables['terms_objects'] = $variables['terms'];

  /** @var \Drupal\taxonomy\TermInterface|[] $item */
  foreach ($variables['terms'] as $item) {
    if (is_array($item)) {
      $group = [];
      foreach ($item as $value) {
        if ($variables['link']) {
          $link = Link::fromTextAndUrl($value->label(), $value->toUrl())->toRenderable();
          $group[] = \Drupal::service('renderer')->render($link);
        }
        else {
          $group[] = $value->label();
        }
      }

      $terms[] = Markup::create(implode('<span class="child-separator">, </span>', $group));
    }
    else {
      if ($variables['link']) {
        $link = Link::fromTextAndUrl($item->label(), $item->toUrl())->toRenderable();
        $terms[] = \Drupal::service('renderer')->render($link);
      }
      else {
        $terms[] = $item->label();
      }
    }
  }

  if ($variables['wrapper'] != 'none') {
    $count = 0;
    foreach ($terms as &$term) {
      $count++;
      $term = [
        '#type' => 'html_tag',
        '#tag' => in_array($variables['wrapper'], ['ol', 'ul']) ? 'li' : $variables['wrapper'],
        '#value' => $term,
        '#attributes' => [
          'class' => [
            Html::cleanCssIdentifier('taxonomy-term'),
            Html::cleanCssIdentifier("count {$count}"),
          ],
        ],
      ];
    }
  }

  unset($variables['link']);
  $variables['terms'] = $terms;
}
