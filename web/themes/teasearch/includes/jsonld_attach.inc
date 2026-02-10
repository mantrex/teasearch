<?php

/**
 * @file
 * JSON-LD attachment hooks for Teasearch.
 * 
 * Questo file contiene gli hook per iniettare automaticamente i dati
 * strutturati JSON-LD nel <head> delle pagine.
 */

// IMPORTANTE: Carica le funzioni generatrici
require_once __DIR__ . '/jsonld_generators.inc';

/**
 * Implements hook_page_attachments_alter() proxy.
 * 
 * Questa funzione viene chiamata da teasearch_page_attachments_alter()
 * definita in teasearch.theme.
 */
function teasearch_jsonld_page_attachments(array &$attachments)
{
  // Ottieni la route corrente
  $route_match = \Drupal::routeMatch();
  $route_name = $route_match->getRouteName();

  $jsonld = NULL;

  // ========================================
  // PAGINE DI DETTAGLIO NODI
  // ========================================
  
  if ($route_name === 'entity.node.canonical') {
    $node = $route_match->getParameter('node');
    
    if ($node) {
      $content_type = $node->bundle();
      
      // Mappa content type → funzione generatore
      $generators = [
        'videos' => 'teasearch_generate_jsonld_videos',
        'people' => 'teasearch_generate_jsonld_people',
        'bibliography' => 'teasearch_generate_jsonld_bibliography',
        'primary_sources' => 'teasearch_generate_jsonld_primary_sources',
        'first_reference' => 'teasearch_generate_jsonld_first_reference',
        'images' => 'teasearch_generate_jsonld_images',
      ];
      
      if (isset($generators[$content_type])) {

        $generator_function = $generators[$content_type];
        if (function_exists($generator_function)) {
          $jsonld = $generator_function($node);
        }
      }
    }
  }

  // ========================================
  // PAGINE DI DETTAGLIO TASSONOMIE
  // ========================================
  
  if ($route_name === 'entity.taxonomy_term.canonical') {
    $term = $route_match->getParameter('taxonomy_term');
    
    if ($term) {
      if (function_exists('teasearch_generate_jsonld_taxonomy')) {
        $jsonld = teasearch_generate_jsonld_taxonomy($term);
      }
    }
  }

  // ========================================
  // INIETTA NEL <HEAD> SE JSON-LD GENERATO
  // ========================================
  
  if ($jsonld) {
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => [
          'type' => 'application/ld+json',
        ],
        '#value' => json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
      ],
      'teasearch_jsonld_structured_data'
    ];
  }
}
