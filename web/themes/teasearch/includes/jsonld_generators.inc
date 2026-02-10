<?php

/**
 * @file
 * JSON-LD generators for Teasearch.
 * 
 * Questo file contiene le funzioni per generare dati strutturati JSON-LD
 * per SEO e Linked Open Data.
 * 
 * Standard supportati:
 * - SKOS (Simple Knowledge Organization System) per tassonomie
 * - Schema.org per content types
 */

/**
 * =============================================================================
 * TASSONOMIE - SKOS (Simple Knowledge Organization System)
 * =============================================================================
 */

/**
 * Genera JSON-LD SKOS per un termine di tassonomia.
 * 
 * @param \Drupal\taxonomy\Entity\Term $term
 *   Il termine di tassonomia.
 * 
 * @return array
 *   Array strutturato per JSON-LD con:
 *   - @context con namespaces SKOS e Schema.org
 *   - @graph con Concept (SKOS) e WebPage (Schema.org)
 */
function teasearch_generate_jsonld_taxonomy($term)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del termine se esiste
  if ($term->hasTranslation($current_language)) {
    $term = $term->getTranslation($current_language);
  }

  $vocabulary_id = $term->bundle();
  $term_id = $term->id();
  
  // Genera URIs base
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  $concept_uri = $base_url . '/web/' . $vocabulary_id . '/' . $term->toUrl()->toString();
  $scheme_uri = $base_url . '/web/' . $vocabulary_id;
  
  // Alias del termine per la WebPage
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/taxonomy/term/' . $term_id, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;

  // ========================================
  // COSTRUISCI IL CONCEPT SKOS
  // ========================================
  
  $concept = [
    '@id' => $concept_uri,
    '@type' => 'skos:Concept',
    'skos:inScheme' => ['@id' => $scheme_uri],
  ];

  // prefLabel - Nome del termine (obbligatorio)
  $concept['skos:prefLabel'] = [
    ['@value' => $term->getName(), '@language' => $current_language]
  ];

  // altLabel - Label alternative
  if ($term->hasField('field_alternative_labels') && !$term->get('field_alternative_labels')->isEmpty()) {
    $alt_labels = [];
    foreach ($term->get('field_alternative_labels') as $item) {
      if (!empty($item->value)) {
        $alt_labels[] = ['@value' => $item->value, '@language' => $current_language];
      }
    }
    if (!empty($alt_labels)) {
      $concept['skos:altLabel'] = $alt_labels;
    }
  }

  // definition - Scope note
  if ($term->hasField('field_description') && !$term->get('field_description')->isEmpty()) {
    $description = $term->get('field_description')->value;
    if (!empty($description)) {
      // Rimuovi HTML tags se presenti
      $description = strip_tags($description);
      $concept['skos:definition'] = [
        ['@value' => $description, '@language' => $current_language]
      ];
    }
  }

  // related - Concetti correlati
  if ($term->hasField('field_related_concepts') && !$term->get('field_related_concepts')->isEmpty()) {
    $related = [];
    foreach ($term->get('field_related_concepts')->referencedEntities() as $related_term) {
      $related_uri = $base_url . '/web/' . $vocabulary_id . '/term' . $related_term->id();
      $related[] = ['@id' => $related_uri];
    }
    if (!empty($related)) {
      $concept['skos:related'] = $related;
    }
  }

  // External authority mappings
  if ($term->hasField('field_external_authority_mapping') && !$term->get('field_external_authority_mapping')->isEmpty()) {
    
    $close_matches = [];
    $broad_matches = [];
    $related_matches = [];
    
    foreach ($term->get('field_external_authority_mapping')->referencedEntities() as $paragraph) {
      
      // Ottieni URI dell'authority
      $uri = NULL;
      if ($paragraph->hasField('field_authority_uri') && !$paragraph->get('field_authority_uri')->isEmpty()) {
        $link = $paragraph->get('field_authority_uri')->first();
        $uri = $link->getUrl()->toString();
      }
      
      if (!$uri) {
        continue; // Salta se non c'è URI
      }
      
      // Ottieni match type
      $match_type = NULL;
      if ($paragraph->hasField('field_match_type') && !$paragraph->get('field_match_type')->isEmpty()) {
        $match_type = $paragraph->get('field_match_type')->value;
      }
      
      // Categorizza in base al match type
      switch ($match_type) {
        case 'closematch':
          $close_matches[] = ['@id' => $uri];
          break;
        case 'broadmatch':
          $broad_matches[] = ['@id' => $uri];
          break;
        case 'relatedmatch':
          $related_matches[] = ['@id' => $uri];
          break;
      }
    }
    
    // Aggiungi al concept solo se popolati
    if (!empty($close_matches)) {
      $concept['skos:closeMatch'] = $close_matches;
    }
    if (!empty($broad_matches)) {
      $concept['skos:broadMatch'] = $broad_matches;
    }
    if (!empty($related_matches)) {
      $concept['skos:relatedMatch'] = $related_matches;
    }
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $term->getName(),
    'schema:about' => ['@id' => $concept_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => [
      'skos' => 'http://www.w3.org/2004/02/skos/core#',
      'schema' => 'https://schema.org/'
    ],
    '@graph' => [$concept, $webpage]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * VIDEOS - Schema.org VideoObject
 * =============================================================================
 */

/**
 * Genera JSON-LD VideoObject per un nodo video.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo video.
 * 
 * @return array
 *   Array strutturato per JSON-LD con VideoObject.
 */
function teasearch_generate_jsonld_videos($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $video_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // COSTRUISCI IL VIDEOOBJECT
  // ========================================
  
  $video = [
    '@id' => $video_uri,
    '@type' => 'schema:VideoObject',
  ];

  // OBBLIGATORI

  // name - Titolo
  $video['schema:name'] = $node->getTitle();

  // description - Descrizione (obbligatorio per Google Rich Results)
  $description = NULL;
  if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
    $description = strip_tags($node->get('field_description')->value);
  } elseif ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
    $summary = $node->get('field_content')->summary;
    $description = !empty($summary) ? strip_tags($summary) : strip_tags(substr($node->get('field_content')->value, 0, 200));
  }
  if ($description) {
    $video['schema:description'] = $description;
  }

  // thumbnailUrl - Immagine thumbnail
  $thumbnail = teasearch_jsonld_get_image_url($node, 'field_square_thumbnail');
  if (!$thumbnail) {
    $thumbnail = teasearch_jsonld_get_image_url($node, 'field_main_image');
  }
  if ($thumbnail) {
    $video['schema:thumbnailUrl'] = $thumbnail;
  }

  // uploadDate - Data di pubblicazione
  $created = $node->getCreatedTime();
  if ($created) {
    $video['schema:uploadDate'] = teasearch_jsonld_timestamp_to_iso8601($created);
  }

  // RACCOMANDATI

  // embedUrl e contentUrl - YouTube
  if ($node->hasField('field_youtube_code') && !$node->get('field_youtube_code')->isEmpty()) {
    $youtube_code = $node->get('field_youtube_code')->value;
    if (!empty($youtube_code)) {
      $video['schema:embedUrl'] = 'https://www.youtube.com/embed/' . $youtube_code;
      $video['schema:contentUrl'] = 'https://www.youtube.com/watch?v=' . $youtube_code;
    }
  }

  // duration - Durata
  if ($node->hasField('field_duration') && !$node->get('field_duration')->isEmpty()) {
    $duration = $node->get('field_duration')->value;
    // field_duration è un campo "Time" che salva secondi
    if (!empty($duration)) {
      $video['schema:duration'] = teasearch_jsonld_duration_to_iso8601($duration);
    }
  }

  // inLanguage - Lingue
  if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
    $languages = [];
    foreach ($node->get('field_language')->referencedEntities() as $term) {
      // Assumo che il term name sia il codice lingua (es. "en", "it")
      $languages[] = strtolower($term->getName());
    }
    if (!empty($languages)) {
      $video['schema:inLanguage'] = $languages;
    }
  }

  // UTILI

  // creator - Creatori/Autori
  $creators = teasearch_jsonld_get_authors($node, 'field_responsibles');
  if (!empty($creators)) {
    $video['schema:creator'] = [];
    foreach ($creators as $creator_name) {
      $video['schema:creator'][] = [
        '@type' => 'schema:Person',
        'schema:name' => $creator_name
      ];
    }
  }

  // genre - Generi
  if ($node->hasField('field_text_genres') && !$node->get('field_text_genres')->isEmpty()) {
    $genres = [];
    foreach ($node->get('field_text_genres')->referencedEntities() as $term) {
      $genres[] = $term->getName();
    }
    if (!empty($genres)) {
      $video['schema:genre'] = $genres;
    }
  }

  // about - Soggetti (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $video['schema:about'] = [];
    foreach ($subjects as $uri) {
      $video['schema:about'][] = ['@id' => $uri];
    }
  }

  // locationCreated - Paese
  if ($node->hasField('field_country') && !$node->get('field_country')->isEmpty()) {
    $country_uris = teasearch_jsonld_get_taxonomy_uris($node, 'field_country');
    if (!empty($country_uris)) {
      $video['schema:locationCreated'] = ['@id' => $country_uris[0]];
    }
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $video_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $video]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * PEOPLE - Schema.org Person
 * =============================================================================
 */

/**
 * Genera JSON-LD Person per un nodo people.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo people.
 * 
 * @return array
 *   Array strutturato per JSON-LD con Person.
 */
function teasearch_generate_jsonld_people($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $person_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // COSTRUISCI IL PERSON
  // ========================================
  
  $person = [
    '@id' => $person_uri,
    '@type' => 'schema:Person',
  ];

  // OBBLIGATORIO

  // name - Nome completo
  $person['schema:name'] = $node->getTitle();

  // RACCOMANDATI

  // alternateName - Altri nomi
  $alt_names = [];
  
  if ($node->hasField('field_known_as') && !$node->get('field_known_as')->isEmpty()) {
    foreach ($node->get('field_known_as') as $item) {
      if (!empty($item->value)) {
        $alt_names[] = $item->value;
      }
    }
  }
  
  if ($node->hasField('field_fullname') && !$node->get('field_fullname')->isEmpty()) {
    $fullname = $node->get('field_fullname')->value;
    if (!empty($fullname)) {
      $alt_names[] = $fullname;
    }
  }
  
  if (!empty($alt_names)) {
    $person['schema:alternateName'] = $alt_names;
  }

  // knowsLanguage - Lingue conosciute
  if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
    $languages = [];
    foreach ($node->get('field_language')->referencedEntities() as $term) {
      $languages[] = strtolower($term->getName());
    }
    if (!empty($languages)) {
      $person['schema:knowsLanguage'] = $languages;
    }
  }

  // knowsAbout - Soggetti di expertise (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $person['schema:knowsAbout'] = [];
    foreach ($subjects as $uri) {
      $person['schema:knowsAbout'][] = ['@id' => $uri];
    }
  }

  // UTILI

  // homeLocation - Paese di origine
  if ($node->hasField('field_country') && !$node->get('field_country')->isEmpty()) {
    $country_uris = teasearch_jsonld_get_taxonomy_uris($node, 'field_country');
    if (!empty($country_uris)) {
      $person['schema:homeLocation'] = ['@id' => $country_uris[0]];
    }
  }

  // workLocation - Geographic focus (luoghi di lavoro)
  if ($node->hasField('field_country2') && !$node->get('field_country2')->isEmpty()) {
    $work_locations = [];
    foreach (teasearch_jsonld_get_taxonomy_uris($node, 'field_country2') as $uri) {
      $work_locations[] = ['@id' => $uri];
    }
    if (!empty($work_locations)) {
      $person['schema:workLocation'] = $work_locations;
    }
  }

  // jobTitle - Posizione/Titolo
  if ($node->hasField('field_title') && !$node->get('field_title')->isEmpty()) {
    $title = $node->get('field_title')->value;
    if (!empty($title)) {
      $person['schema:jobTitle'] = $title;
    }
  }

  // affiliation - Affiliazione
  if ($node->hasField('field_affiliation') && !$node->get('field_affiliation')->isEmpty()) {
    $affiliation = $node->get('field_affiliation')->value;
    if (!empty($affiliation)) {
      $person['schema:affiliation'] = [
        '@type' => 'schema:Organization',
        'schema:name' => $affiliation
      ];
    }
  }

  // description - Bio (summary)
  if ($node->hasField('field_bio') && !$node->get('field_bio')->isEmpty()) {
    $bio = $node->get('field_bio')->summary;
    if (empty($bio)) {
      $bio = strip_tags(substr($node->get('field_bio')->value, 0, 200));
    } else {
      $bio = strip_tags($bio);
    }
    if (!empty($bio)) {
      $person['schema:description'] = $bio;
    }
  }

  // image - Avatar
  $avatar_url = teasearch_jsonld_get_image_url($node, 'field_avatar');
  if ($avatar_url) {
    $person['schema:image'] = $avatar_url;
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $person_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $person]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * BIBLIOGRAPHY - Schema.org ScholarlyArticle / Book
 * =============================================================================
 */

/**
 * Genera JSON-LD ScholarlyArticle o Book per un nodo bibliography.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo bibliography.
 * 
 * @return array
 *   Array strutturato per JSON-LD con ScholarlyArticle o Book.
 */
function teasearch_generate_jsonld_bibliography($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $publication_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // DETERMINA IL TIPO (@type)
  // ========================================
  
  // Default: ScholarlyArticle
  $type = 'schema:ScholarlyArticle';
  
  // Controlla field_pubtype per determinare il tipo
  if ($node->hasField('field_pubtype') && !$node->get('field_pubtype')->isEmpty()) {
    $pubtype_term = $node->get('field_pubtype')->entity;
    if ($pubtype_term) {
      $pubtype_name = strtolower($pubtype_term->getName());
      
      // Mapping tipi di pubblicazione -> Schema.org types
      if (strpos($pubtype_name, 'book') !== false || 
          strpos($pubtype_name, 'monograph') !== false) {
        $type = 'schema:Book';
      } elseif (strpos($pubtype_name, 'thesis') !== false || 
                strpos($pubtype_name, 'dissertation') !== false) {
        $type = 'schema:Thesis';
      } elseif (strpos($pubtype_name, 'chapter') !== false) {
        $type = 'schema:Chapter';
      }
      // Altrimenti rimane ScholarlyArticle
    }
  }

  // ========================================
  // COSTRUISCI LA PUBBLICAZIONE
  // ========================================
  
  $publication = [
    '@id' => $publication_uri,
    '@type' => $type,
  ];

  // OBBLIGATORI

  // name - Titolo
  $publication['schema:name'] = $node->getTitle();

  // author - Autori
  $authors = teasearch_jsonld_get_authors($node, 'field_responsibles');
  if (!empty($authors)) {
    $publication['schema:author'] = [];
    foreach ($authors as $author_name) {
      $publication['schema:author'][] = [
        '@type' => 'schema:Person',
        'schema:name' => $author_name
      ];
    }
  }

  // RACCOMANDATI

  // datePublished - Data di pubblicazione
  if ($node->hasField('field_date') && !$node->get('field_date')->isEmpty()) {
    $date = $node->get('field_date')->value;
    if (!empty($date)) {
      $publication['schema:datePublished'] = $date;
    }
  } elseif ($node->hasField('field_year') && !$node->get('field_year')->isEmpty()) {
    $year = $node->get('field_year')->value;
    if (!empty($year)) {
      $publication['schema:datePublished'] = $year;
    }
  }

  // publisher - Editore
  if ($node->hasField('field_publisher') && !$node->get('field_publisher')->isEmpty()) {
    $publisher = $node->get('field_publisher')->value;
    if (!empty($publisher)) {
      $publication['schema:publisher'] = [
        '@type' => 'schema:Organization',
        'schema:name' => $publisher
      ];
    }
  }

  // inLanguage - Lingua
  if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
    $languages = [];
    foreach ($node->get('field_language')->referencedEntities() as $term) {
      $languages[] = strtolower($term->getName());
    }
    if (!empty($languages)) {
      $publication['schema:inLanguage'] = $languages;
    }
  }

  // about - Soggetti (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $publication['schema:about'] = [];
    foreach ($subjects as $uri) {
      $publication['schema:about'][] = ['@id' => $uri];
    }
  }

  // UTILI

  // isbn - ISBN
  if ($node->hasField('field_isbn') && !$node->get('field_isbn')->isEmpty()) {
    $isbn = $node->get('field_isbn')->value;
    if (!empty($isbn)) {
      $publication['schema:isbn'] = $isbn;
    }
  }

  // issn - ISSN
  if ($node->hasField('field_issn') && !$node->get('field_issn')->isEmpty()) {
    $issn = $node->get('field_issn')->value;
    if (!empty($issn)) {
      $publication['schema:issn'] = $issn;
    }
  }

  // abstract - Abstract
  if ($node->hasField('field_original_abstract') && !$node->get('field_original_abstract')->isEmpty()) {
    $abstract = strip_tags($node->get('field_original_abstract')->value);
    if (!empty($abstract)) {
      $publication['schema:abstract'] = $abstract;
    }
  }

  // keywords - Parole chiave
  if ($node->hasField('field_keywords') && !$node->get('field_keywords')->isEmpty()) {
    $keywords = $node->get('field_keywords')->value;
    if (!empty($keywords)) {
      $publication['schema:keywords'] = $keywords;
    }
  }

  // numberOfPages - Numero di pagine
  if ($node->hasField('field_pages') && !$node->get('field_pages')->isEmpty()) {
    $pages = $node->get('field_pages')->value;
    if (!empty($pages)) {
      $publication['schema:numberOfPages'] = $pages;
    }
  }

  // url - Link di accesso online
  if ($node->hasField('field_online_links') && !$node->get('field_online_links')->isEmpty()) {
    $link = $node->get('field_online_links')->first();
    if ($link) {
      $url = $link->getUrl()->toString();
      if (!empty($url)) {
        $publication['schema:url'] = $url;
      }
    }
  }

  // editor - Editori (field_editors)
  if ($node->hasField('field_editors') && !$node->get('field_editors')->isEmpty()) {
    $editors = teasearch_jsonld_get_authors($node, 'field_editors');
    if (!empty($editors)) {
      $publication['schema:editor'] = [];
      foreach ($editors as $editor_name) {
        $publication['schema:editor'][] = [
          '@type' => 'schema:Person',
          'schema:name' => $editor_name
        ];
      }
    }
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $publication_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $publication]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * PRIMARY_SOURCES (Texts) - Schema.org CreativeWork
 * =============================================================================
 */

/**
 * Genera JSON-LD CreativeWork per un nodo primary_sources.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo primary_sources.
 * 
 * @return array
 *   Array strutturato per JSON-LD con CreativeWork.
 */
function teasearch_generate_jsonld_primary_sources($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $work_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // COSTRUISCI IL CREATIVEWORK
  // ========================================
  
  $work = [
    '@id' => $work_uri,
    '@type' => 'schema:CreativeWork',
  ];

  // name - Titolo
  $work['schema:name'] = $node->getTitle();

  // author - Autori
  $authors = teasearch_jsonld_get_authors($node, 'field_responsibles');
  if (!empty($authors)) {
    $work['schema:author'] = [];
    foreach ($authors as $author_name) {
      $work['schema:author'][] = [
        '@type' => 'schema:Person',
        'schema:name' => $author_name
      ];
    }
  }

  // inLanguage - Lingua
  if ($node->hasField('field_language') && !$node->get('field_language')->isEmpty()) {
    $languages = [];
    foreach ($node->get('field_language')->referencedEntities() as $term) {
      $languages[] = strtolower($term->getName());
    }
    if (!empty($languages)) {
      $work['schema:inLanguage'] = $languages;
    }
  }

  // genre - Genere
  if ($node->hasField('field_text_genres') && !$node->get('field_text_genres')->isEmpty()) {
    $genres = [];
    foreach ($node->get('field_text_genres')->referencedEntities() as $term) {
      $genres[] = $term->getName();
    }
    if (!empty($genres)) {
      $work['schema:genre'] = $genres;
    }
  }

  // about - Soggetti (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $work['schema:about'] = [];
    foreach ($subjects as $uri) {
      $work['schema:about'][] = ['@id' => $uri];
    }
  }

  // description - Descrizione
  if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
    $description = strip_tags($node->get('field_description')->value);
    if (!empty($description)) {
      $work['schema:description'] = $description;
    }
  }

  // keywords - Parole chiave
  if ($node->hasField('field_keywords') && !$node->get('field_keywords')->isEmpty()) {
    $keywords = $node->get('field_keywords')->value;
    if (!empty($keywords)) {
      $work['schema:keywords'] = $keywords;
    }
  }

  // locationCreated - Luogo di creazione
  if ($node->hasField('field_country') && !$node->get('field_country')->isEmpty()) {
    $country_uris = teasearch_jsonld_get_taxonomy_uris($node, 'field_country');
    if (!empty($country_uris)) {
      $work['schema:locationCreated'] = ['@id' => $country_uris[0]];
    }
  }

  // spatialCoverage - Geographic focus
  if ($node->hasField('field_country2') && !$node->get('field_country2')->isEmpty()) {
    $spatial_coverage = [];
    foreach (teasearch_jsonld_get_taxonomy_uris($node, 'field_country2') as $uri) {
      $spatial_coverage[] = ['@id' => $uri];
    }
    if (!empty($spatial_coverage)) {
      $work['schema:spatialCoverage'] = $spatial_coverage;
    }
  }

  // dateCreated - Data (da field_year_range se presente)
  if ($node->hasField('field_year_range') && !$node->get('field_year_range')->isEmpty()) {
    $year_from = $node->get('field_year_range')->year_from;
    if (!empty($year_from)) {
      $work['schema:dateCreated'] = $year_from;
    }
  }

  // publisher - Editore
  if ($node->hasField('field_text_publisher') && !$node->get('field_text_publisher')->isEmpty()) {
    $publisher = $node->get('field_text_publisher')->value;
    if (!empty($publisher)) {
      $work['schema:publisher'] = [
        '@type' => 'schema:Organization',
        'schema:name' => $publisher
      ];
    }
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $work_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $work]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * FIRST_REFERENCE (Essentials) - Schema.org Article
 * =============================================================================
 */

/**
 * Genera JSON-LD Article per un nodo first_reference.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo first_reference.
 * 
 * @return array
 *   Array strutturato per JSON-LD con Article.
 */
function teasearch_generate_jsonld_first_reference($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $article_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // COSTRUISCI L'ARTICLE
  // ========================================
  
  $article = [
    '@id' => $article_uri,
    '@type' => 'schema:Article',
  ];

  // name - Titolo
  $article['schema:name'] = $node->getTitle();

  // author - Autori (field_authors_ref - entity reference a people)
  if ($node->hasField('field_authors_ref') && !$node->get('field_authors_ref')->isEmpty()) {
    $article['schema:author'] = [];
    foreach ($node->get('field_authors_ref')->referencedEntities() as $person) {
      $article['schema:author'][] = [
        '@type' => 'schema:Person',
        'schema:name' => $person->getTitle()
      ];
    }
  }

  // description - Short description
  if ($node->hasField('field_short_description') && !$node->get('field_short_description')->isEmpty()) {
    $description = strip_tags($node->get('field_short_description')->value);
    if (!empty($description)) {
      $article['schema:description'] = $description;
    }
  }

  // articleBody - Main content
  if ($node->hasField('field_content') && !$node->get('field_content')->isEmpty()) {
    $content = strip_tags($node->get('field_content')->value);
    if (!empty($content)) {
      $article['schema:articleBody'] = $content;
    }
  }

  // about - Soggetti (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $article['schema:about'] = [];
    foreach ($subjects as $uri) {
      $article['schema:about'][] = ['@id' => $uri];
    }
  }

  // keywords - Parole chiave
  if ($node->hasField('field_keywords') && !$node->get('field_keywords')->isEmpty()) {
    $keywords = $node->get('field_keywords')->value;
    if (!empty($keywords)) {
      $article['schema:keywords'] = $keywords;
    }
  }

  // spatialCoverage - Geographic focus
  if ($node->hasField('field_country2') && !$node->get('field_country2')->isEmpty()) {
    $spatial_coverage = [];
    foreach (teasearch_jsonld_get_taxonomy_uris($node, 'field_country2') as $uri) {
      $spatial_coverage[] = ['@id' => $uri];
    }
    if (!empty($spatial_coverage)) {
      $article['schema:spatialCoverage'] = $spatial_coverage;
    }
  }

  // datePublished - Data creazione
  $created = $node->getCreatedTime();
  if ($created) {
    $article['schema:datePublished'] = teasearch_jsonld_timestamp_to_iso8601($created);
  }

  // dateModified - Data ultima modifica
  $changed = $node->getChangedTime();
  if ($changed) {
    $article['schema:dateModified'] = teasearch_jsonld_timestamp_to_iso8601($changed);
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $article_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $article]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * IMAGES - Schema.org ImageObject
 * =============================================================================
 */

/**
 * Genera JSON-LD ImageObject per un nodo images.
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo images.
 * 
 * @return array
 *   Array strutturato per JSON-LD con ImageObject.
 */
function teasearch_generate_jsonld_images($node)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  
  // Ottieni la traduzione del nodo se esiste
  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  $nid = $node->id();
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  
  // URI della risorsa
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid, $current_language);
  $alias = ltrim($alias, '/');
  $webpage_uri = $base_url . '/web/' . $current_language . '/' . $alias;
  $image_uri = $base_url . '/web/node/' . $nid;

  // ========================================
  // COSTRUISCI L'IMAGEOBJECT
  // ========================================
  
  $image_obj = [
    '@id' => $image_uri,
    '@type' => 'schema:ImageObject',
  ];

  // name - Titolo
  $image_obj['schema:name'] = $node->getTitle();

  // contentUrl - URL dell'immagine principale
  $content_url = teasearch_jsonld_get_image_url($node, 'field_main_image');
  if (!$content_url) {
    $content_url = teasearch_jsonld_get_image_url($node, 'field_large_image');
  }
  if ($content_url) {
    $image_obj['schema:contentUrl'] = $content_url;
  }

  // thumbnailUrl - Thumbnail
  $thumbnail = teasearch_jsonld_get_image_url($node, 'field_square_thumbnail');
  if ($thumbnail) {
    $image_obj['schema:thumbnailUrl'] = $thumbnail;
  }

  // creator - Creatori
  $creators = teasearch_jsonld_get_authors($node, 'field_responsibles');
  if (!empty($creators)) {
    $image_obj['schema:creator'] = [];
    foreach ($creators as $creator_name) {
      $image_obj['schema:creator'][] = [
        '@type' => 'schema:Person',
        'schema:name' => $creator_name
      ];
    }
  }

  // dateCreated - Data creazione (da field_year_range)
  if ($node->hasField('field_year_range') && !$node->get('field_year_range')->isEmpty()) {
    $year_from = $node->get('field_year_range')->year_from;
    if (!empty($year_from)) {
      $image_obj['schema:dateCreated'] = $year_from;
    }
  }

  // about - Soggetti (link a taxonomy SKOS)
  $subjects = teasearch_jsonld_get_taxonomy_uris($node, 'field_subjects');
  if (!empty($subjects)) {
    $image_obj['schema:about'] = [];
    foreach ($subjects as $uri) {
      $image_obj['schema:about'][] = ['@id' => $uri];
    }
  }

  // description - Descrizione
  if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
    $description = strip_tags($node->get('field_description')->value);
    if (!empty($description)) {
      $image_obj['schema:description'] = $description;
    }
  }

  // license - Licenza
  if ($node->hasField('field_license') && !$node->get('field_license')->isEmpty()) {
    $license_uris = teasearch_jsonld_get_taxonomy_uris($node, 'field_license');
    if (!empty($license_uris)) {
      $image_obj['schema:license'] = $license_uris[0];
    }
  }

  // keywords - Parole chiave
  if ($node->hasField('field_keywords') && !$node->get('field_keywords')->isEmpty()) {
    $keywords = $node->get('field_keywords')->value;
    if (!empty($keywords)) {
      $image_obj['schema:keywords'] = $keywords;
    }
  }

  // spatialCoverage - Geographic focus
  if ($node->hasField('field_country2') && !$node->get('field_country2')->isEmpty()) {
    $spatial_coverage = [];
    foreach (teasearch_jsonld_get_taxonomy_uris($node, 'field_country2') as $uri) {
      $spatial_coverage[] = ['@id' => $uri];
    }
    if (!empty($spatial_coverage)) {
      $image_obj['schema:spatialCoverage'] = $spatial_coverage;
    }
  }

  // ========================================
  // COSTRUISCI LA WEBPAGE
  // ========================================
  
  $webpage = [
    '@id' => $webpage_uri,
    '@type' => 'schema:WebPage',
    'schema:url' => $webpage_uri,
    'schema:inLanguage' => $current_language,
    'schema:name' => $node->getTitle(),
    'schema:mainEntity' => ['@id' => $image_uri]
  ];

  // ========================================
  // ASSEMBLA IL GRAPH
  // ========================================
  
  $jsonld = [
    '@context' => ['schema' => 'https://schema.org/'],
    '@graph' => [$webpage, $image_obj]
  ];

  return $jsonld;
}

/**
 * =============================================================================
 * HELPER FUNCTIONS
 * =============================================================================
 */

/**
 * Converte una durata in formato ISO 8601 (PT1H30M).
 * 
 * @param int $seconds
 *   Durata in secondi.
 * 
 * @return string
 *   Durata in formato ISO 8601 (es. "PT1H30M45S")
 */
function teasearch_jsonld_duration_to_iso8601($seconds)
{
  if (empty($seconds) || !is_numeric($seconds)) {
    return NULL;
  }
  
  $hours = floor($seconds / 3600);
  $minutes = floor(($seconds % 3600) / 60);
  $secs = $seconds % 60;
  
  $duration = 'PT';
  if ($hours > 0) {
    $duration .= $hours . 'H';
  }
  if ($minutes > 0) {
    $duration .= $minutes . 'M';
  }
  if ($secs > 0) {
    $duration .= $secs . 'S';
  }
  
  // Se nessun componente, ritorna almeno PT0S
  return ($duration === 'PT') ? 'PT0S' : $duration;
}

/**
 * Converte un timestamp Drupal in formato ISO 8601.
 * 
 * @param int $timestamp
 *   Timestamp UNIX.
 * 
 * @return string
 *   Data in formato ISO 8601 (es. "2024-01-15T10:30:00+00:00")
 */
function teasearch_jsonld_timestamp_to_iso8601($timestamp)
{
  if (empty($timestamp)) {
    return NULL;
  }
  
  $date = new DateTime();
  $date->setTimestamp($timestamp);
  return $date->format('c'); // ISO 8601
}

/**
 * Ottiene l'URL assoluto di un'immagine da un campo media o image.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   L'entità (node, term, etc.).
 * @param string $field_name
 *   Nome del campo immagine.
 * 
 * @return string|null
 *   URL assoluto dell'immagine o NULL.
 */
function teasearch_jsonld_get_image_url($entity, $field_name)
{
  if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
    return NULL;
  }

  $field = $entity->get($field_name);
  $field_type = $field->getFieldDefinition()->getType();

  // Campo Image diretto
  if ($field_type === 'image') {
    $image = $field->entity;
    if ($image) {
      return \Drupal::service('file_url_generator')->generateAbsoluteString($image->getFileUri());
    }
  }

  // Campo Media reference
  if ($field_type === 'entity_reference') {
    $media = $field->entity;
    if ($media && $media->hasField('field_media_image')) {
      $image = $media->get('field_media_image')->entity;
      if ($image) {
        return \Drupal::service('file_url_generator')->generateAbsoluteString($image->getFileUri());
      }
    }
  }

  return NULL;
}

/**
 * Estrae nomi da un campo paragraph Authors General.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   L'entità (node).
 * @param string $field_name
 *   Nome del campo paragraph (es. field_responsibles).
 * 
 * @return array
 *   Array di nomi (stringhe).
 */
function teasearch_jsonld_get_authors($entity, $field_name)
{
  $authors = [];

  if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
    return $authors;
  }

  foreach ($entity->get($field_name)->referencedEntities() as $paragraph) {
    
    // Prova field_person_ref (entity reference a people)
    if ($paragraph->hasField('field_person_ref') && !$paragraph->get('field_person_ref')->isEmpty()) {
      $person = $paragraph->get('field_person_ref')->entity;
      if ($person) {
        $authors[] = $person->getTitle();
      }
    }
    // Fallback: field_name_string (testo libero)
    elseif ($paragraph->hasField('field_name_string') && !$paragraph->get('field_name_string')->isEmpty()) {
      $name = $paragraph->get('field_name_string')->value;
      if (!empty($name)) {
        $authors[] = $name;
      }
    }
  }

  return $authors;
}

/**
 * Ottiene termini di tassonomia da un campo entity reference.
 * 
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   L'entità.
 * @param string $field_name
 *   Nome del campo tassonomia.
 * 
 * @return array
 *   Array di URIs dei termini.
 */
function teasearch_jsonld_get_taxonomy_uris($entity, $field_name)
{
  $uris = [];
  $base_url = \Drupal::request()->getSchemeAndHttpHost();

  if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
    return $uris;
  }

  foreach ($entity->get($field_name)->referencedEntities() as $term) {
    $vocabulary_id = $term->bundle();
    $term_id = $term->id();
    $uris[] = $base_url . '/web/' . $vocabulary_id . '/term' . $term_id;
  }

  return $uris;
}
