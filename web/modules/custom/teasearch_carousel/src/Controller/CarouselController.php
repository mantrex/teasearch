<?php

namespace Drupal\teasearch_carousel\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\image\Entity\ImageStyle;

class CarouselController
{

  /*
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
  }*/

  public function getData(): \Symfony\Component\HttpFoundation\JsonResponse
  {
    $base = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $modulePath = \Drupal::service('extension.list.module')->getPath('teasearch_carousel');

    // ==========================
    // CONFIG (inline, minimale)
    // ==========================
    $config = [
      // 'random' | 'news_first' | 'news_only'
      'order' => 'news_first',
      'max' => 10,
      'valid_content_types' => [
        'texts'  => ['title' => 'title',            'image' => 'field_main_image'],
        'images' => ['title' => 'title',            'image' => 'field_main_image'],
      ],
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
      'start_date_constraint' => false

    ];

    $allBundles = array_merge(['news'], array_keys($config['valid_content_types']));
    $bundleLabels = $this->getBundleLabels($allBundles);

    $now          = \Drupal::time()->getRequestTime();
    $etm          = \Drupal::entityTypeManager();
    $storage      = $etm->getStorage('node');
    $fieldManager = \Drupal::service('entity_field.manager');

    // Helper inline: verifica esistenza campo.
    $hasField = function (string $bundle, string $field) use ($fieldManager): bool {
      static $cache = [];
      $k = $bundle . '::' . $field;
      if (!isset($cache[$k])) {
        $defs = $fieldManager->getFieldDefinitions('node', $bundle);
        $cache[$k] = isset($defs[$field]);
      }
      return $cache[$k];
    };

    // Helper inline: costruisci URL immagine con stile (se disponibile).
    $buildImageUrl = function (\Drupal\node\NodeInterface $node, ?string $fieldName) use ($config): ?string {
      $file = null;
      if ($fieldName && $node->hasField($fieldName) && !$node->get($fieldName)->isEmpty()) {
        $file = $node->get($fieldName)->entity;
      } elseif ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = $node->get('field_image')->entity;
      }
      if ($file) {
        $uri = $file->getFileUri();
        if ($style = \Drupal\image\Entity\ImageStyle::load($config['image_style'])) {
          return $style->buildUrl($uri);
        }
        return \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
      }

      // 👉 Fallback se non c'è immagine sul nodo
      return $config['default_image'];
    };

    // ==========================
    // 1) NEWS con filtri speciali
    // ==========================
    $qNews = $storage->getQuery()
      ->accessCheck(TRUE)          // identico per anonimo e loggato (solo pubblicate)
      ->condition('type', 'news')
      ->condition('status', 1);

    if ($hasField('news', 'field_hide')) {
      // Escludi nascoste: field_hide != 1
      $qNews->condition('field_hide', 1, '<>');
    }

    if ($hasField('news', 'field_end_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_end_date', '>=', true);
    }

    if ($config['start_date_constraint'] && $hasField('news', 'field_start_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_start_date', '<=', false);
    }

    // Ordine naturale per le news (usato anche in news_first)
    if ($hasField('news', 'field_date')) {
      $qNews->sort('field_date', 'DESC');
    } else {
      $qNews->sort('created', 'DESC');
    }
    $qNews->sort('nid', 'DESC');

    $qNews->range(0, $config['max'] * 3); // pool ampio per random

    $newsNids = $qNews->execute();
    $newsNodes = $storage->loadMultiple($newsNids);
    //debug
    /*
    foreach ($newsNodes as $node) {
      if ($node->hasField('field_start_date')) {
        $startDateValue = $node->get('field_start_date')->value;
        error_log('NEWS: ' . $node->label() . ' | field_start_date: ' . ($startDateValue ?? 'EMPTY'));
      }
    }*/

    $news = [];
    foreach ($newsNodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }
      // Scegli campo immagine fra i candidati
      $imgField = null;
      foreach ($config['news_image_candidates'] as $cand) {
        if ($node->hasField($cand) && !$node->get($cand)->isEmpty()) {
          $imgField = $cand;
          break;
        }
      }
      // Data per payload: usa field_date se c'è, altrimenti created
      $dateTs = $node->getCreatedTime();
      if ($hasField('news', 'field_date') && !$node->get('field_date')->isEmpty()) {
        $val = $node->get('field_date')->value; // 'YYYY-MM-DD' o 'YYYY-MM-DDTHH:MM:SS'
        $tmp = strtotime($val);
        if ($tmp) {
          $dateTs = $tmp;
        }
      }

      $news[] = [
        'title'  => $node->label(),
        'url'    => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'image'  => $buildImageUrl($node, $imgField),
        'date'   => $dateTs,
        'bundle' => 'news',
        'nid'   => $node->id(),
      ];
    }

    // ==================================
    // 2) Altri content type (sempre on)
    // ==================================
    $others = [];
    $bundles = array_keys($config['valid_content_types']);
    if ($config['order'] !== 'news_only' && !empty($bundles)) {
      $qOther = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', $bundles, 'IN')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->sort('nid', 'DESC')
        ->range(0, $config['max'] * 3);

      $otherNids = $qOther->execute();
      if ($otherNids) {
        $otherNodes = $storage->loadMultiple($otherNids);
        foreach ($otherNodes as $node) {
          if (!$node instanceof \Drupal\node\NodeInterface) continue;

          $bundle = $node->bundle();
          $map    = $config['valid_content_types'][$bundle] ?? ['title' => 'title', 'image' => null];

          $title = $node->label();
          if (!empty($map['title']) && $node->hasField($map['title']) && !$node->get($map['title'])->isEmpty()) {
            $title = (string) $node->get($map['title'])->value;
          }

          $imageUrl = $buildImageUrl($node, $map['image'] ?? null);

          $others[] = [
            'title'  => $title,
            'url'    => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
            'image'  => $imageUrl,
            'date'   => $node->getCreatedTime(),
            'bundle' => $bundle,
            'label'  => $bundleLabels[$bundle] ?? ucfirst($bundle),
            'nid'    => $node->id(),
          ];
        }
      }
    }

    // ==========================
    // 3) Composizione / Ordinamento
    // ==========================
    $order = $config['order']; // 'random' | 'news_first' | 'news_only'
    $max   = (int) $config['max'];

    if ($order === 'news_only') {
      $items = array_slice($news, 0, $max);
    } elseif ($order === 'news_first') {
      // news già ordinate (field_date DESC / created DESC, poi nid DESC)
      // altre per created DESC, poi nid DESC
      $items = array_slice(array_merge($news, $others), 0, $max);
    } else { // random (default)
      $pool = array_merge($news, $others);
      shuffle($pool);
      $items = array_slice($pool, 0, $max);
    }

    // ==========================
    // 4) Response
    // ==========================
    $resp = new \Symfony\Component\HttpFoundation\JsonResponse($items);
    // Disabilita cache per evitare risposte stale dal proxy/browser.
    $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $resp->headers->set('Pragma', 'no-cache');
    $resp->headers->set('Expires', '0');
    return $resp;
  }

  /**
   * Mappa i bundle (es. news, texts, images) al titolo tradotto preso dai nodi "categories".
   * Usa il campo list "field_category" per capire quale bundle rappresenta.
   */
  private function getBundleLabels(array $bundles): array
  {
    $storage   = \Drupal::entityTypeManager()->getStorage('node');
    $repo      = \Drupal::service('entity.repository');
    $langcode  = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'categories')
      ->condition('status', 1)
      ->condition('field_category_menu_list', $bundles, 'IN')
      ->range(0, 200)
      ->execute();

    $labels = [];
    if ($nids) {
      foreach ($storage->loadMultiple($nids) as $n) {
        // Traduzione sicura per D11 (fallback automatico se non esiste la traduzione).
        /** @var \Drupal\node\NodeInterface $t */
        $t = $repo->getTranslationFromContext($n, $langcode);
        $title = $t->label();

        if ($t->hasField('field_category_menu_list') && !$t->get('field_category_menu_list')->isEmpty()) {
          // field_category è un list(text) → leggo i values.
          foreach ($t->get('field_category_menu_list')->getValue() as $item) {
            $bundle = (string) ($item['value'] ?? '');
            if ($bundle && !isset($labels[$bundle])) {
              $labels[$bundle] = $title;
            }
          }
        }
      }
    }
    return $labels;
  }

  /**
   * Applica una condizione di confronto data su un campo datetime.
   * 
   * @param bool $allowEmpty Se true, include i nodi con campo vuoto (OR condition)
   */
  private function addDateTimeCondition($query, string $bundle, string $fieldName, string $operator, bool $allowEmpty = false): void
  {
    $fieldManager = \Drupal::service('entity_field.manager');
    $defs = $fieldManager->getFieldDefinitions('node', $bundle);

    if (!isset($defs[$fieldName])) {
      return; // Campo non esiste
    }

    $fc = \Drupal\field\Entity\FieldConfig::loadByName('node', $bundle, $fieldName);
    if ($fc) {
      $storage = $fc->getFieldStorageDefinition();
      $datetime_type = $storage->getSetting('datetime_type') ?? 'date';

      $nowValue = ($datetime_type === 'date')
        ? gmdate('Y-m-d')
        : gmdate('Y-m-d\TH:i:s');

      if ($allowEmpty) {
        // Campo vuoto O condizione soddisfatta
        $group = $query->orConditionGroup()
          ->notExists($fieldName)
          ->condition($fieldName . '.value', $nowValue, $operator);
        $query->condition($group);
      } else {
        // Solo condizione (campo deve essere compilato)
        $query->condition($fieldName . '.value', $nowValue, $operator);
      }
    }
  }





  /* view all */
  public function viewAll()
  {
    // Recupera tutti i dati senza limiti
    $allData = $this->getAllCarouselData();

    // Filtra per IDs specifici se passati via GET
    $request = \Drupal::request();
    $filterIds = $request->query->get('ids');

    if ($filterIds) {
      $filterIds = explode(',', $filterIds);
      $allData['news'] = array_filter($allData['news'], function ($item) use ($filterIds) {
        return in_array($item['nid'], $filterIds);
      });
      $allData['highlights'] = array_filter($allData['highlights'], function ($item) use ($filterIds) {
        return in_array($item['nid'], $filterIds);
      });
    }

    $dataToReturn = [
      '#theme' => 'carousel_view_all',
      '#news' => $allData['news'],
      '#highlights' => $allData['highlights'],
      '#attached' => [
        'library' => [
          'teasearch_carousel/view_all_styles',
        ],
      ],
    ];

    return $dataToReturn;
  }

  public function newsOnly()
  {
    $newsData = $this->getNewsOnlyData();

    return [
      '#theme' => 'news_view_all',
      '#news' => $newsData,
      '#attached' => [
        'library' => [
          'teasearch_carousel/view_all_styles',
        ],
      ],
    ];
  }

  private function getAllCarouselData(): array
  {
    $base = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $modulePath = \Drupal::service('extension.list.module')->getPath('teasearch_carousel');

    $config = [
      'valid_content_types' => [
        'texts'  => ['title' => 'title', 'image' => 'field_main_image'],
        'images' => ['title' => 'title', 'image' => 'field_main_image'],
      ],
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
      'start_date_constraint' => false
    ];

    $allBundles = array_merge(['news'], array_keys($config['valid_content_types']));
    $bundleLabels = $this->getBundleLabels($allBundles);

    $etm = \Drupal::entityTypeManager();
    $storage = $etm->getStorage('node');
    $fieldManager = \Drupal::service('entity_field.manager');

    $hasField = function (string $bundle, string $field) use ($fieldManager): bool {
      static $cache = [];
      $k = $bundle . '::' . $field;
      if (!isset($cache[$k])) {
        $defs = $fieldManager->getFieldDefinitions('node', $bundle);
        $cache[$k] = isset($defs[$field]);
      }
      return $cache[$k];
    };

    $buildImageUrl = function (\Drupal\node\NodeInterface $node, ?string $fieldName) use ($config): ?string {
      $file = null;
      if ($fieldName && $node->hasField($fieldName) && !$node->get($fieldName)->isEmpty()) {
        $file = $node->get($fieldName)->entity;
      } elseif ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = $node->get('field_image')->entity;
      }
      if ($file) {
        $uri = $file->getFileUri();
        if ($style = \Drupal\image\Entity\ImageStyle::load($config['image_style'])) {
          return $style->buildUrl($uri);
        }
        return \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
      }
      return $config['default_image'];
    };

    // Recupera tutte le news
    $qNews = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1);

    if ($hasField('news', 'field_hide')) {
      $qNews->condition('field_hide', 1, '<>');
    }

    if ($hasField('news', 'field_end_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_end_date', '>=', true);
    }

    if ($config['start_date_constraint'] && $hasField('news', 'field_start_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_start_date', '<=', false);
    }

    if ($hasField('news', 'field_date')) {
      $qNews->sort('field_date', 'DESC');
    } else {
      $qNews->sort('created', 'DESC');
    }
    $qNews->sort('nid', 'DESC');

    $newsNids = $qNews->execute();
    $newsNodes = $storage->loadMultiple($newsNids);

    $news = [];
    foreach ($newsNodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) continue;

      $imgField = null;
      foreach ($config['news_image_candidates'] as $cand) {
        if ($node->hasField($cand) && !$node->get($cand)->isEmpty()) {
          $imgField = $cand;
          break;
        }
      }

      $dateTs = $node->getCreatedTime();
      if ($hasField('news', 'field_date') && !$node->get('field_date')->isEmpty()) {
        $val = $node->get('field_date')->value;
        $tmp = strtotime($val);
        if ($tmp) {
          $dateTs = $tmp;
        }
      }

      $news[] = [
        'title' => $node->label(),
        'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'image' => $buildImageUrl($node, $imgField),
        'date' => $dateTs,
        'bundle' => 'news',
        'nid' => $node->id(),
        'location' => $this->getNodeLocation($node),
        'formatted_date' => date('d/m/Y', $dateTs),
      ];
    }

    // Recupera tutti gli highlights
    $highlights = [];
    $bundles = array_keys($config['valid_content_types']);
    if (!empty($bundles)) {
      $qOther = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', $bundles, 'IN')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->sort('nid', 'DESC');

      $otherNids = $qOther->execute();
      if ($otherNids) {
        $otherNodes = $storage->loadMultiple($otherNids);
        foreach ($otherNodes as $node) {
          if (!$node instanceof \Drupal\node\NodeInterface) continue;

          $bundle = $node->bundle();
          $map = $config['valid_content_types'][$bundle] ?? ['title' => 'title', 'image' => null];

          $title = $node->label();
          if (!empty($map['title']) && $node->hasField($map['title']) && !$node->get($map['title'])->isEmpty()) {
            $title = (string) $node->get($map['title'])->value;
          }

          $highlights[] = [
            'title' => $title,
            'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
            'image' => $buildImageUrl($node, $map['image'] ?? null),
            'date' => $node->getCreatedTime(),
            'bundle' => $bundle,
            'label' => $bundleLabels[$bundle] ?? ucfirst($bundle),
            'nid' => $node->id(),
            'location' => $this->getNodeLocation($node),
            'formatted_date' => date('d/m/Y', $node->getCreatedTime()),
          ];
        }
      }
    }

    return [
      'news' => $news,
      'highlights' => $highlights,
    ];
  }


  /**
   * Recupera solo le news per la pagina /news
   */
  private function getNewsOnlyData(): array
  {
    $base = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $modulePath = \Drupal::service('extension.list.module')->getPath('teasearch_carousel');

    $config = [
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
      'start_date_constraint' => false
    ];

    $etm = \Drupal::entityTypeManager();
    $storage = $etm->getStorage('node');
    $fieldManager = \Drupal::service('entity_field.manager');

    $hasField = function (string $bundle, string $field) use ($fieldManager): bool {
      static $cache = [];
      $k = $bundle . '::' . $field;
      if (!isset($cache[$k])) {
        $defs = $fieldManager->getFieldDefinitions('node', $bundle);
        $cache[$k] = isset($defs[$field]);
      }
      return $cache[$k];
    };

    $buildImageUrl = function (\Drupal\node\NodeInterface $node, ?string $fieldName) use ($config): ?string {
      $file = null;
      if ($fieldName && $node->hasField($fieldName) && !$node->get($fieldName)->isEmpty()) {
        $file = $node->get($fieldName)->entity;
      } elseif ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = $node->get('field_image')->entity;
      }
      if ($file) {
        $uri = $file->getFileUri();
        if ($style = \Drupal\image\Entity\ImageStyle::load($config['image_style'])) {
          return $style->buildUrl($uri);
        }
        return \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
      }
      return $config['default_image'];
    };

    // Recupera tutte le news
    $qNews = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1);

    if ($hasField('news', 'field_hide')) {
      $qNews->condition('field_hide', 1, '<>');
    }

    if ($hasField('news', 'field_end_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_end_date', '>=', true);
    }

    if ($config['start_date_constraint'] && $hasField('news', 'field_start_date')) {
      $this->addDateTimeCondition($qNews, 'news', 'field_start_date', '<=', false);
    }

    if ($hasField('news', 'field_date')) {
      $qNews->sort('field_date', 'DESC');
    } else {
      $qNews->sort('created', 'DESC');
    }
    $qNews->sort('nid', 'DESC');

    $newsNids = $qNews->execute();
    $newsNodes = $storage->loadMultiple($newsNids);

    $news = [];
    foreach ($newsNodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) continue;

      $imgField = null;
      foreach ($config['news_image_candidates'] as $cand) {
        if ($node->hasField($cand) && !$node->get($cand)->isEmpty()) {
          $imgField = $cand;
          break;
        }
      }

      $dateTs = $node->getCreatedTime();
      if ($hasField('news', 'field_date') && !$node->get('field_date')->isEmpty()) {
        $val = $node->get('field_date')->value;
        $tmp = strtotime($val);
        if ($tmp) {
          $dateTs = $tmp;
        }
      }

      $news[] = [
        'title' => $node->label(),
        'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
        'image' => $buildImageUrl($node, $imgField),
        'date' => $dateTs,
        'bundle' => 'news',
        'nid' => $node->id(),
        'location' => $this->getNodeLocation($node),
        'formatted_date' => date('d/m/Y', $dateTs),
      ];
    }

    return $news;
  }


  /**
   * Recupera la location di un nodo (se presente)
   */
  private function getNodeLocation(\Drupal\node\NodeInterface $node): string
  {
    // Controlla diversi possibili campi location
    $locationFields = ['field_location', 'field_place', 'field_city'];

    foreach ($locationFields as $fieldName) {
      if ($node->hasField($fieldName) && !$node->get($fieldName)->isEmpty()) {
        $value = $node->get($fieldName)->value;
        if ($value) {
          return (string) $value;
        }
      }
    }

    return '';
  }
}
