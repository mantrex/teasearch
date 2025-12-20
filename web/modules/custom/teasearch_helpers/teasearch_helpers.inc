<?php

/**
 * @file
 * Helper functions globali per Teasearch.
 * 
 * Queste funzioni sono disponibili in tutti i moduli e temi.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Ottiene il nome tradotto di un content type dalla tassonomia categories.
 * 
 * La tassonomia 'categories' ha:
 * - title: Nome principale (es. "Texts")
 * - field_link_title: Machine name del content type (es. "primary_sources")
 * 
 * @param string $machine_name
 *   Il machine name del content type (es. 'first_reference', 'primary_sources').
 * @param string|null $language
 *   La lingua desiderata (es. 'it', 'en'). Se NULL, usa la lingua corrente.
 * 
 * @return string
 *   Il nome tradotto del content type, oppure il machine name se non trovato.
 * 
 * @example
 * ```php
 * // Ottieni il nome in italiano
 * $name = getContentTypeNameByMachineName('first_reference', 'it');
 * // Risultato: "Fondamenti"
 * 
 * // Ottieni il nome nella lingua corrente
 * $name = getContentTypeNameByMachineName('primary_sources');
 * // Risultato: "Texts" (en) o "Testi" (it)
 * ```
 */
function getContentTypeNameByMachineName($machine_name, $language = NULL)
{
  // Se non specificata, usa la lingua corrente
  if ($language === NULL) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  // Cache statica per evitare query multiple nella stessa richiesta
  static $cache = [];
  $cache_key = $machine_name . ':' . $language;

  if (isset($cache[$cache_key])) {
    return $cache[$cache_key];
  }

  try {
    // Query sulla tassonomia categories
    $query = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->getQuery();

    $query->condition('vid', 'categories')
      ->condition('field_link_title', $machine_name)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $term_ids = $query->execute();

    if (empty($term_ids)) {
      // Non trovato, ritorna il machine name come fallback
      $cache[$cache_key] = ucfirst(str_replace('_', ' ', $machine_name));
      return $cache[$cache_key];
    }

    // Carica il termine
    $term_id = reset($term_ids);
    $term = Term::load($term_id);

    if (!$term) {
      $cache[$cache_key] = ucfirst(str_replace('_', ' ', $machine_name));
      return $cache[$cache_key];
    }

    // Ottieni la traduzione nella lingua richiesta
    if ($term->hasTranslation($language)) {
      $term = $term->getTranslation($language);
    }

    // Ritorna il nome (title del termine)
    $name = $term->getName();
    $cache[$cache_key] = $name;

    return $name;

  } catch (\Exception $e) {
    \Drupal::logger('teasearch_helpers')->error('Error getting content type name: @message', [
      '@message' => $e->getMessage(),
    ]);

    // Fallback
    return ucfirst(str_replace('_', ' ', $machine_name));
  }
}

/**
 * Ottiene la label tradotta di una categoria dal bundle (machine name).
 * 
 * Cerca nei nodi "categories" usando field_category_menu_list per trovare
 * il bundle corrispondente e restituisce field_link_title tradotto.
 * 
 * @param string $bundle
 *   Il bundle/machine name (es. 'first_reference', 'primary_sources').
 * @param string|null $language
 *   La lingua desiderata. Se NULL, usa la lingua corrente.
 * 
 * @return string
 *   La label tradotta, oppure il bundle formattato se non trovato.
 * 
 * @example
 * ```php
 * $label = getCategoryLabelByBundle('first_reference', 'it');
 * // Risultato: "Fondamenti"
 * ```
 */
function getCategoryLabelByBundle($bundle, $language = NULL)
{
  // Se non specificata, usa la lingua corrente
  if ($language === NULL) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  // Cache statica
  static $cache = [];
  $cache_key = $bundle . ':' . $language;

  if (isset($cache[$cache_key])) {
    return $cache[$cache_key];
  }

  try {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $all_nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'categories')
      ->condition('status', 1)
      ->execute();

    if (empty($all_nids)) {
      $fallback = ucfirst(str_replace('_', ' ', $bundle));
      $cache[$cache_key] = $fallback;
      return $fallback;
    }

    $all_nodes = $storage->loadMultiple($all_nids);

    foreach ($all_nodes as $node) {
      if (!$node->hasField('field_category_menu_list') || $node->get('field_category_menu_list')->isEmpty()) {
        continue;
      }

      // Cerca il bundle nella lista
      foreach ($node->get('field_category_menu_list') as $item) {
        $machine_name = $item->value;

        if (trim(strtolower($machine_name)) === trim(strtolower($bundle))) {
          // Trovato! Prendi field_link_title tradotto
          if (!$node->hasField('field_link_title') || $node->get('field_link_title')->isEmpty()) {
            continue;
          }

          $translated = $node->hasTranslation($language)
            ? $node->getTranslation($language)
            : $node;

          $label = $translated->get('field_link_title')->value;
          $cache[$cache_key] = $label;
          return $label;
        }
      }
    }

  } catch (\Throwable $e) {
    \Drupal::logger('teasearch_helpers')->error('Error in getCategoryLabelByBundle: @message', [
      '@message' => $e->getMessage()
    ]);
  }

  // Fallback
  $fallback = ucfirst(str_replace('_', ' ', $bundle));
  $cache[$cache_key] = $fallback;
  return $fallback;
}

/**
 * Ottiene i nomi tradotti di un content type in tutte le lingue disponibili.
 * 
 * @param string $machine_name
 *   Il machine name del content type.
 * 
 * @return array
 *   Array associativo con le traduzioni: ['en' => 'Texts', 'it' => 'Testi']
 * 
 * @example
 * ```php
 * $names = getContentTypeNameByMachineNameAllLanguages('primary_sources');
 * // Risultato: ['en' => 'Texts', 'it' => 'Testi']
 * ```
 */
function getContentTypeNameByMachineNameAllLanguages($machine_name)
{
  static $cache = [];

  if (isset($cache[$machine_name])) {
    return $cache[$machine_name];
  }

  try {
    // Query sulla tassonomia categories
    $query = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->getQuery();

    $query->condition('vid', 'categories')
      ->condition('field_link_title', $machine_name)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $term_ids = $query->execute();

    if (empty($term_ids)) {
      // Non trovato, ritorna fallback
      $fallback = ucfirst(str_replace('_', ' ', $machine_name));
      $cache[$machine_name] = ['en' => $fallback, 'it' => $fallback];
      return $cache[$machine_name];
    }

    // Carica il termine
    $term_id = reset($term_ids);
    $term = Term::load($term_id);

    if (!$term) {
      $fallback = ucfirst(str_replace('_', ' ', $machine_name));
      $cache[$machine_name] = ['en' => $fallback, 'it' => $fallback];
      return $cache[$machine_name];
    }

    // Ottieni tutte le lingue disponibili
    $language_manager = \Drupal::languageManager();
    $languages = $language_manager->getLanguages();

    $translations = [];

    foreach ($languages as $lang_code => $language) {
      if ($term->hasTranslation($lang_code)) {
        $translated_term = $term->getTranslation($lang_code);
        $translations[$lang_code] = $translated_term->getName();
      } else {
        // Se non c'è traduzione, usa il nome originale
        $translations[$lang_code] = $term->getName();
      }
    }

    $cache[$machine_name] = $translations;
    return $translations;

  } catch (\Exception $e) {
    \Drupal::logger('teasearch_helpers')->error('Error getting content type names: @message', [
      '@message' => $e->getMessage(),
    ]);

    // Fallback
    $fallback = ucfirst(str_replace('_', ' ', $machine_name));
    return ['en' => $fallback, 'it' => $fallback];
  }
}
