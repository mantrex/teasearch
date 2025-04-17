<?php

namespace Drupal\teasearch_carousel\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\image\Entity\ImageStyle;

class CarouselController
{

  public function getData()
  {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'news')
      ->condition('status', 1)
      ->range(0, 10)
      ->sort('created', 'DESC');

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);

    $data = [];

    foreach ($nodes as $node) {
      $image_url = '';
      if ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = $node->get('field_image')->entity;
        //$image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        $image_url = ImageStyle::load("large")->buildUrl($file->getFileUri());
      }

      $data[] = [
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'image' => $image_url,
      ];
    }

    return new JsonResponse($data);
  }
}
