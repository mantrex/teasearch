<?php

namespace Drupal\teasearch_carousel\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\image\Entity\ImageStyle;

class CarouselController
{

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
        'first_reference' => ['title' => 'title', 'image' => 'field_square_thumbnail'], // Essentials
        'primary_sources' => ['title' => 'title', 'image' => 'field_main_image'], // Texts
        'images' => ['title' => 'title', 'image' => 'field_main_image'],
        'videos' => ['title' => 'title', 'image' => 'field_main_image'],
        'people' => ['title' => 'title', 'image' => 'field_avatar'],
      ],
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
    ];

    $allBundles = array_merge(['news'], array_keys($config['valid_content_types']));
    $bundleLabels = $this->getBundleLabels($allBundles);

    $etm = \Drupal::entityTypeManager();
    $storage = $etm->getStorage('node');
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
      return $config['default_image'];
    };

    // ==========================
    // 1) NEWS con filtri date
    // ==========================
    $newsNodes = $this->getFilteredNewsNodes($storage, $hasField);

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
      ];
    }

    // ==========================
    // 2) Altri content type - distribuzione round-robin con randomizzazione
    // ==========================
    $others = [];
    $bundles = array_keys($config['valid_content_types']);

    if ($config['order'] !== 'news_only' && !empty($bundles)) {
      $maxSlots = (int) $config['max'];
      $newsCount = count($news);
      $remainingSlots = $maxSlots - $newsCount;

      if ($remainingSlots > 0) {
        // Carica un pool di elementi per ogni content type
        $pools = [];
        foreach ($bundles as $bundle) {
          $qPool = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('type', $bundle)
            ->condition('status', 1)
            ->sort('created', 'DESC')
            ->sort('nid', 'DESC')
            ->range(0, 20);

          $poolNids = $qPool->execute();

          if (!empty($poolNids)) {
            $poolNodes = $storage->loadMultiple($poolNids);
            $pools[$bundle] = array_values($poolNodes);
            shuffle($pools[$bundle]);
          } else {
            $pools[$bundle] = [];
          }
        }

        // Round-robin: cicla sui content type
        $usedNids = [];
        $currentIndex = [];
        foreach ($bundles as $bundle) {
          $currentIndex[$bundle] = 0;
        }

        $slotsAdded = 0;
        while ($slotsAdded < $remainingSlots) {
          $addedInThisRound = false;

          foreach ($bundles as $bundle) {
            if ($slotsAdded >= $remainingSlots) {
              break;
            }

            while ($currentIndex[$bundle] < count($pools[$bundle])) {
              $node = $pools[$bundle][$currentIndex[$bundle]];
              $nid = $node->id();

              if (!in_array($nid, $usedNids)) {
                $nodeBundle = $node->bundle();
                $map = $config['valid_content_types'][$nodeBundle] ?? ['title' => 'title', 'image' => null];

                $title = $node->label();
                if (!empty($map['title']) && $node->hasField($map['title']) && !$node->get($map['title'])->isEmpty()) {
                  $title = (string) $node->get($map['title'])->value;
                }

                $imageUrl = $buildImageUrl($node, $map['image'] ?? null);

                $others[] = [
                  'title' => $title,
                  'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
                  'image' => $imageUrl,
                  'date' => $node->getCreatedTime(),
                  'bundle' => $nodeBundle,
                  'label' => $bundleLabels[$nodeBundle] ?? ucfirst($nodeBundle),
                  'nid' => $nid,
                ];

                $usedNids[] = $nid;
                $currentIndex[$bundle]++;
                $slotsAdded++;
                $addedInThisRound = true;
                break;
              }

              $currentIndex[$bundle]++;
            }
          }

          if (!$addedInThisRound) {
            break;
          }
        }
      }
    }

    // ==========================
    // 3) Composizione / Ordinamento
    // ==========================
    $order = $config['order'];
    $max = (int) $config['max'];

    if ($order === 'news_only') {
      $items = array_slice($news, 0, $max);
    } elseif ($order === 'news_first') {
      $items = array_slice(array_merge($news, $others), 0, $max);
    } else {
      $pool = array_merge($news, $others);
      shuffle($pool);
      $items = array_slice($pool, 0, $max);
    }

    // ==========================
    // 4) Response
    // ==========================
    $resp = new \Symfony\Component\HttpFoundation\JsonResponse($items);
    $resp->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $resp->headers->set('Pragma', 'no-cache');
    $resp->headers->set('Expires', '0');
    return $resp;
  }

  /**
   * Filtra le news in base a field_start_date e field_end_date
   * 
   * LOGICA:
   * - Nessun campo → sempre visibile
   * - Solo field_start_date → visibile da quella data in poi
   * - Entrambi → visibile nell'intervallo
   */
  private function getFilteredNewsNodes($storage, $hasField): array
  {
    $qNews = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'news')
      ->condition('status', 1);


    if ($hasField('news', 'field_date')) {
      $qNews->sort('field_date', 'DESC');
    } else {
      $qNews->sort('created', 'DESC');
    }
    $qNews->sort('nid', 'DESC');

    $allNewsNids = $qNews->execute();

    if (empty($allNewsNids)) {
      return [];
    }

    $allNewsNodes = $storage->loadMultiple($allNewsNids);
    $now = new \DateTime('now', new \DateTimeZone('UTC'));
    $nowString = $now->format('Y-m-d');

    $filteredNodes = [];

    foreach ($allNewsNodes as $node) {

      \Drupal::logger('teasearch_carousel')->notice('News: @title | Start: @start | End: @end | Now: @now', [
        '@title' => $node->label(),
        '@start' => $node->hasField('field_start_date') && !$node->get('field_start_date')->isEmpty() ? $node->get('field_start_date')->value : 'EMPTY',
        '@end' => $node->hasField('field_end_date') && !$node->get('field_end_date')->isEmpty() ? $node->get('field_end_date')->value : 'EMPTY',
        '@now' => $nowString,
      ]);


      $hasStartDate = $hasField('news', 'field_start_date')
        && $node->hasField('field_start_date')
        && !$node->get('field_start_date')->isEmpty();

      $hasEndDate = $hasField('news', 'field_end_date')
        && $node->hasField('field_end_date')
        && !$node->get('field_end_date')->isEmpty();

      // CASO 1: Nessuna data → sempre visibile
      if (!$hasStartDate && !$hasEndDate) {
        $filteredNodes[] = $node;
        continue;
      }

      // CASO 2: Solo start_date → visibile da quella data in poi
      if ($hasStartDate && !$hasEndDate) {
        $startDate = $node->get('field_start_date')->value;
        if ($startDate <= $nowString) {
          $filteredNodes[] = $node;
        }
        continue;
      }

      // CASO 3: Solo end_date → visibile fino a quella data
      if (!$hasStartDate && $hasEndDate) {
        $endDate = $node->get('field_end_date')->value;
        if ($endDate >= $nowString) {
          $filteredNodes[] = $node;
        }
        continue;
      }

      // CASO 4: Entrambe le date → visibile nell'arco di tempo
      if ($hasStartDate && $hasEndDate) {
        $startDate = $node->get('field_start_date')->value;
        $endDate = $node->get('field_end_date')->value;

        if ($startDate <= $nowString && $endDate >= $nowString) {
          $filteredNodes[] = $node;
        }
        continue;
      }
    }

    return $filteredNodes;
  }

  /**
   * Mappa i bundle al titolo tradotto da nodi "categories"
   */
  private function getBundleLabels(array $bundles): array
  {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $repo = \Drupal::service('entity.repository');
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

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
        $t = $repo->getTranslationFromContext($n, $langcode);
        $title = $t->label();

        if ($t->hasField('field_category_menu_list') && !$t->get('field_category_menu_list')->isEmpty()) {
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
   * View all carousel
   */
  public function viewAll()
  {
    $allData = $this->getAllCarouselData();

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

    return [
      '#theme' => 'carousel_view_all',
      '#news' => $allData['news'],
      '#highlights' => $allData['highlights'],
      '#attached' => [
        'library' => [
          'teasearch_carousel/view_all_styles',
        ],
      ],
    ];
  }

  /**
   * News only page
   */
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

  /**
   * Recupera tutti i dati per carousel-all
   */
  private function getAllCarouselData(): array
  {
    $base = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $modulePath = \Drupal::service('extension.list.module')->getPath('teasearch_carousel');

    $config = [
      'valid_content_types' => [
        'first_reference' => ['title' => 'title', 'image' => 'field_square_thumbnail'],
        'primary_sources' => ['title' => 'title', 'image' => 'field_main_image'],
        'images' => ['title' => 'title', 'image' => 'field_main_image'],
        'videos' => ['title' => 'title', 'image' => 'field_main_image'],
        'people' => ['title' => 'title', 'image' => 'field_avatar'],
      ],
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
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

    // Recupera news filtrate
    $newsNodes = $this->getFilteredNewsNodes($storage, $hasField);

    $news = [];
    foreach ($newsNodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface)
        continue;

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


      $newsType = '';
      if ($node->hasField('field_news_type') && !$node->get('field_news_type')->isEmpty()) {
        $term = $node->get('field_news_type')->entity;
        if ($term) {
          // Traduzione del termine
          $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
          if ($term->hasTranslation($langcode)) {
            $term = $term->getTranslation($langcode);
          }
          $newsType = $term->getName();
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
        'news_type' => $newsType
      ];
    }

    // Recupera highlights
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
          if (!$node instanceof \Drupal\node\NodeInterface)
            continue;

          $bundle = $node->bundle();
          $map = $config['valid_content_types'][$bundle] ?? ['title' => 'title', 'image' => null];

          $title = $node->label();
          if (!empty($map['title']) && $node->hasField($map['title']) && !$node->get($map['title'])->isEmpty()) {
            $title = (string) $node->get($map['title'])->value;
          }
          $categoryLabel = '';

          if (function_exists('getCategoryLabelByBundle')) {
            $categoryLabel = getCategoryLabelByBundle($bundle);
          } else {
            $categoryLabel = $bundleLabels[$bundle] ?? ucfirst($bundle);
          }

          $highlights[] = [
            'title' => $title,
            'url' => $node->toUrl('canonical', ['absolute' => TRUE])->toString(),
            'image' => $buildImageUrl($node, $map['image'] ?? null),
            'date' => $node->getCreatedTime(),
            'bundle' => $bundle,
            'label' => $categoryLabel, 
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
   * Recupera solo news per la pagina /news
   */
  private function getNewsOnlyData(): array
  {
    $base = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBasePath();
    $modulePath = \Drupal::service('extension.list.module')->getPath('teasearch_carousel');

    $config = [
      'news_image_candidates' => ['field_main_image', 'field_image'],
      'image_style' => 'large',
      'default_image' => $base . '/' . $modulePath . '/assets/default-card.png',
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

    // Recupera news filtrate
    $newsNodes = $this->getFilteredNewsNodes($storage, $hasField);

    $news = [];
    foreach ($newsNodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface)
        continue;

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

      $newsType = '';
      if ($node->hasField('field_news_type') && !$node->get('field_news_type')->isEmpty()) {
        $term = $node->get('field_news_type')->entity;
        if ($term) {
          $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
          if ($term->hasTranslation($langcode)) {
            $term = $term->getTranslation($langcode);
          }
          $newsType = $term->getName();
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
        'news_type' => $newsType,
        'formatted_date' => date('d/m/Y', $dateTs),
      ];
    }

    return $news;
  }

  /**
   * Recupera la location di un nodo
   */
  private function getNodeLocation(\Drupal\node\NodeInterface $node): string
  {
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