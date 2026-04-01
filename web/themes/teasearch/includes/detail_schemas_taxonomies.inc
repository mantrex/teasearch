<?php

/**
 * @file
 * Schema di configurazione per le pagine di dettaglio delle tassonomie.
 * 
 * Questo file definisce come devono essere renderizzati i campi nelle pagine
 * di dettaglio dei termini di tassonomia.
 */

/**
 * Traduzioni per le label delle tassonomie.
 * 
 * Struttura identica a teasearch_translations() in detail_schemas.inc
 * ma specifica per le pagine di dettaglio delle tassonomie.
 * 
 * @return array
 *   Array multidimensionale [vocabulary_id][language][field_identifier] => traduzione
 */
function teasearch_taxonomy_translations()
{
  // Traduzioni per SUBJECTS
  $subjects = [
    'it' => [
      'name' => 'Etichetta',
      'field_description' => 'Nota d\'ambito',
      'field_alternative_labels' => 'Varianti',
      'field_related_concepts' => 'Concetti correlati',
      'field_external_authority_mapping' => 'Corrispondenze con schemi esterni',
      'resources_separator' => 'Risorse',
      'essentials' => 'Fondamenti',
      'texts' => 'Testi',
      'images' => 'Immagini',
      'videos' => 'Video',
      'bibliography' => 'Bibliografia',
    ],
  ];

  // Traduzioni per COUNTRIES
  $countries = [
    'it' => [
      'name' => 'Etichetta',
      'field_description' => 'Nota d\'ambito',
      'field_alternative_labels' => 'Varianti',
      'field_related_concepts' => 'Concetti correlati',
      'field_external_authority_mapping' => 'Corrispondenze con schemi esterni',
      
      // Prima sezione resources
      'resources_separator_geo' => 'Risorse - Focus geografico',
      'essentials_geo' => 'Fondamenti',
      'texts_geo' => 'Testi',
      'images_geo' => 'Immagini',
      'videos_geo' => 'Video',
      'bibliography_geo' => 'Bibliografia',
      
      // Seconda sezione resources
      'resources_separator_country' => 'Risorse - Paese',
      'essentials_country' => 'Fondamenti',
      'texts_country' => 'Testi',
      'images_country' => 'Immagini',
      'videos_country' => 'Video',
      'bibliography_country' => 'Bibliografia',
    ],
  ];

  return [
    'subjects' => $subjects,
    'countries' => $countries,
    // Le altre tassonomie usano le stesse label di subjects, quindi non serve duplicare
  ];
}

/**
 * Mappa vocabulary_id ai nomi dei campi usati nei content types.
 * 
 * Questa funzione definisce quale campo entity_reference viene usato
 * nei content types per referenziare ogni vocabolario.
 * 
 * @param string $vocabulary_id
 *   Machine name del vocabolario.
 * 
 * @return string
 *   Nome del campo (es: 'field_subjects', 'field_language').
 */
function teasearch_get_taxonomy_field_name($vocabulary_id)
{
  // Mappa esplicita vocabulary_id => field_name
  $mapping = [
    'subjects' => 'field_subjects',
    'languages' => 'field_language',
    'countries' => 'field_country', // Nota: countries usa anche field_country2
    'activities' => 'field_activities',
    'text_types' => 'field_text_types',
    'text_genres' => 'field_text_genres',
    'image_formats' => 'field_image_category',
    'usage_rights' => 'field_usage_rights',
    'video_genres' => 'field_text_genres', // Nota: stesso campo di text_genres
    'publication_types' => 'field_pubtype',
    'periodical_frequency' => 'field_frequency',
    'news_types' => 'field_news_type',
    'access_type' => 'field_access_type',
    'attendance_mode' => 'field_attendance',
    'fee' => 'field_fee',
    'licenses' => 'field_license',
    'media_type' => 'field_media_type',
  ];

  // Restituisci il campo mappato o fallback generico
  return $mapping[$vocabulary_id] ?? 'field_' . $vocabulary_id;
}

/**
 * Restituisce lo schema per i termini di tassonomia.
 * 
 * Struttura simile a detail_schemas.inc ma per taxonomy_term entities.
 * 
 * @param string|null $vocabulary_id
 *   Machine name del vocabolario (es: 'subjects', 'countries').
 *   Se NULL o non trovato, restituisce lo schema di default.
 * 
 * @return array
 *   Schema di configurazione per il vocabolario.
 */
function teasearch_get_taxonomy_detail_schema($vocabulary_id = NULL)
{
  $schemas = [];

  // ===================================================================
  // SCHEMA DI DEFAULT (usato per quasi tutte le tassonomie)
  // ===================================================================
  // Questo schema viene usato per tutte le tassonomie tranne quelle
  // con configurazione specifica (attualmente solo 'countries').
  // 
  // Tassonomie che usano questo schema:
  // - subjects
  // - access_type
  // - activities
  // - attendance_mode
  // - fee
  // - image_formats
  // - languages
  // - licenses
  // - media_type
  // - news_types
  // - periodical_frequency
  // - publication_types
  // - text_genres
  // - text_types
  // - usage_rights
  // - video_genres
  // ===================================================================
  $default_schema = [
    'fields' => [
      // Titolo principale (nome del termine)
      'maintitle' => [
        'type' => 'taxonomy_main_title',
      ],

      // Label del termine
      'name' => [
        'label' => 'Label',
        'type' => 'taxonomy_name',
      ],

      // Scope note
      'description' => [
        'label' => 'Scope Note',
      ],

      // Variants
      'field_alternative_labels' => [
        'label' => 'Variants',
        'type' => 'taxonomy_alternatives',
      ],

      // Related concepts
      'field_related_concepts' => [
        'label' => 'Related Concepts',
        'type' => 'taxonomy',
      ],

      // Matching concepts in external schemes
      'field_external_authority_mapping' => [
        'label' => 'Matching concepts in external schemes',
        'type' => 'taxonomy_external_mapping',
      ],

      // Separatore Resources
      'resources_separator' => 'Resources',

      // Essentials
      'essentials' => [
        'label' => 'Essentials',
        'type' => 'taxonomy_resources',
        'content_type' => 'first_reference',
        'field' => 'field_subjects', // Nota: questo campo verrà adattato dinamicamente
      ],

      // Texts
      'texts' => [
        'label' => 'Texts',
        'type' => 'taxonomy_resources',
        'content_type' => 'primary_sources',
        'field' => 'field_subjects',
      ],

      // Images
      'images' => [
        'label' => 'Images',
        'type' => 'taxonomy_resources',
        'content_type' => 'images',
        'field' => 'field_subjects',
      ],

      // Videos
      'videos' => [
        'label' => 'Videos',
        'type' => 'taxonomy_resources',
        'content_type' => 'videos',
        'field' => 'field_subjects',
      ],

      // Bibliography
      'bibliography' => [
        'label' => 'Bibliography',
        'type' => 'taxonomy_resources',
        'content_type' => 'bibliography',
        'field' => 'field_subjects',
      ],
    ],
  ];

  // ===================================================================
  // SCHEMA PER COUNTRIES (unico caso speciale)
  // ===================================================================
  $schemas['countries'] = [
    'fields' => [
      // Titolo principale (nome del termine)
      'maintitle' => [
        'type' => 'taxonomy_main_title',
      ],

      // Label del termine
      'name' => [
        'label' => 'Label',
        'type' => 'taxonomy_name',
      ],

      // Scope note
      'field_description' => [
        'label' => 'Scope Note',
      ],

      // Variants
      'field_alternative_labels' => [
        'label' => 'Variants',
        'type' => 'taxonomy_alternatives',
      ],

      // Related concepts
      'field_related_concepts' => [
        'label' => 'Related Concepts',
        'type' => 'taxonomy',
      ],

      // Matching concepts in external schemes
      'field_external_authority_mapping' => [
        'label' => 'Matching concepts in external schemes',
        'type' => 'taxonomy_external_mapping',
      ],

      // Separatore Resources
      'resources_separator' => 'Resources',

      // Essentials
      'essentials' => [
        'label' => 'Essentials',
        'type' => 'taxonomy_resources',
        'content_type' => 'first_reference',
        'field' => 'field_subjects',
      ],

      // Texts
      'texts' => [
        'label' => 'Texts',
        'type' => 'taxonomy_resources',
        'content_type' => 'primary_sources',
        'field' => 'field_subjects',
      ],

      // Images
      'images' => [
        'label' => 'Images',
        'type' => 'taxonomy_resources',
        'content_type' => 'images',
        'field' => 'field_subjects',
      ],

      // Videos
      'videos' => [
        'label' => 'Videos',
        'type' => 'taxonomy_resources',
        'content_type' => 'videos',
        'field' => 'field_subjects',
      ],

      // Bibliography
      'bibliography' => [
        'label' => 'Bibliography',
        'type' => 'taxonomy_resources',
        'content_type' => 'bibliography',
        'field' => 'field_subjects',
      ],
    ],
  ];

  // ===================================================================
  // SCHEMA PER COUNTRIES
  // ===================================================================
  $schemas['countries'] = [
    'fields' => [
      // Titolo principale (nome del termine)
      'maintitle' => [
        'type' => 'taxonomy_main_title',
      ],

      // Label del termine
      'name' => [
        'label' => 'Label',
        'type' => 'taxonomy_name',
      ],

      // Scope note
      'field_description' => [
        'label' => 'Scope Note',
      ],

      // Variants
      'field_alternative_labels' => [
        'label' => 'Variants',
        'type' => 'taxonomy_alternatives',
      ],

      // Related concepts
      'field_related_concepts' => [
        'label' => 'Related Concepts',
        'type' => 'taxonomy',
      ],

      // Matching concepts in external schemes
      'field_external_authority_mapping' => [
        'label' => 'Matching concepts in external schemes',
        'type' => 'taxonomy_external_mapping',
      ],

      // ===================================================================
      // PRIMA SEZIONE RESOURCES: GEOGRAPHICAL FOCUS (field_country2)
      // ===================================================================
      'resources_separator_geo' => 'Resources - Geographical Scope',

      // Essentials con field_country2
      'essentials_geo' => [
        'label' => 'Essentials',
        'type' => 'taxonomy_resources',
        'content_type' => 'first_reference',
        'field' => 'field_country2',
      ],

      // Texts con field_country2
      'texts_geo' => [
        'label' => 'Texts',
        'type' => 'taxonomy_resources',
        'content_type' => 'primary_sources',
        'field' => 'field_country2',
      ],

      // Images con field_country2
      'images_geo' => [
        'label' => 'Images',
        'type' => 'taxonomy_resources',
        'content_type' => 'images',
        'field' => 'field_country2',
      ],

      // Videos con field_country2
      'videos_geo' => [
        'label' => 'Videos',
        'type' => 'taxonomy_resources',
        'content_type' => 'videos',
        'field' => 'field_country2',
      ],

      // Bibliography con field_country2
      'bibliography_geo' => [
        'label' => 'Bibliography',
        'type' => 'taxonomy_resources',
        'content_type' => 'bibliography',
        'field' => 'field_country2',
      ],

      // ===================================================================
      // SECONDA SEZIONE RESOURCES: COUNTRY (field_country)
      // ===================================================================
      'resources_separator_country' => 'Resources - Country',

      // Essentials con field_country
      'essentials_country' => [
        'label' => 'Essentials',
        'type' => 'taxonomy_resources',
        'content_type' => 'first_reference',
        'field' => 'field_country',
      ],

      // Texts con field_country
      'texts_country' => [
        'label' => 'Texts',
        'type' => 'taxonomy_resources',
        'content_type' => 'primary_sources',
        'field' => 'field_country',
      ],

      // Images con field_country
      'images_country' => [
        'label' => 'Images',
        'type' => 'taxonomy_resources',
        'content_type' => 'images',
        'field' => 'field_country',
      ],

      // Videos con field_country
      'videos_country' => [
        'label' => 'Videos',
        'type' => 'taxonomy_resources',
        'content_type' => 'videos',
        'field' => 'field_country',
      ],

      // Bibliography con field_country
      'bibliography_country' => [
        'label' => 'Bibliography',
        'type' => 'taxonomy_resources',
        'content_type' => 'bibliography',
        'field' => 'field_country',
      ],
    ],
  ];

  // Restituisci lo schema specifico se esiste
  if ($vocabulary_id && isset($schemas[$vocabulary_id])) {
    return $schemas[$vocabulary_id];
  }

  // Schema di default per tutte le altre tassonomie
  // IMPORTANTE: Adatta dinamicamente il nome del campo in base al vocabulary_id
  if ($vocabulary_id) {
    $field_name = teasearch_get_taxonomy_field_name($vocabulary_id);
    
    // Aggiorna tutti i campi resources con il field_name corretto
    foreach ($default_schema['fields'] as $key => &$config) {
      if (isset($config['field'])) {
        $config['field'] = $field_name;
      }
    }
  }

  return $default_schema;
}
