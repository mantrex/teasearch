<?php

/**
 * @file
 * Schema di configurazione per le pagine di dettaglio delle tassonomie.
 * 
 * Questo file definisce come devono essere renderizzati i campi nelle pagine
 * di dettaglio dei termini di tassonomia.
 */

/**
 * Restituisce lo schema per i termini di tassonomia.
 * 
 * Struttura simile a detail_schemas.inc ma per taxonomy_term entities.
 */
function teasearch_get_taxonomy_detail_schema()
{
  return [
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

      // Separatore Resources - Subjects
      'resources_separator' => 'Resources - Subjects',

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
}
