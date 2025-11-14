<?php

/**
 * @file
 * Funzioni helper per le schede di dettaglio.
 */

/**
 * Helper: Recupera il valore di un campo dal nodo nella lingua corrente.
 *
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo da cui recuperare il campo.
 * @param string $field_name
 *   Il nome del campo.
 *
 * @return \Drupal\Core\Field\FieldItemListInterface|null
 *   Il campo o NULL se non esiste.
 */
function teasearch_get_field_value($node, $field_name)
{
  // Assicuriamoci di avere il nodo nella lingua corrente
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  if ($node->hasTranslation($current_language)) {
    $node = $node->getTranslation($current_language);
  }

  // Recuperiamo il campo
  if ($node->hasField($field_name)) {
    return $node->get($field_name);
  }

  return NULL;
}

/**
 * Helper: Inferisce automaticamente il type di un campo.
 *
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo.
 * @param string $field_name
 *   Il nome del campo.
 *
 * @return string
 *   Il type inferito.
 */
function teasearch_infer_field_type($node, $field_name)
{
  if (!$node->hasField($field_name)) {
    return 'text'; // Fallback
  }

  $field_definition = $node->get($field_name)->getFieldDefinition();
  $field_type = $field_definition->getType();

  switch ($field_type) {
    case 'entity_reference':
      $target_type = $field_definition->getSetting('target_type');
      if ($target_type === 'taxonomy_term') {
        return 'taxonomy';
      }
      if ($target_type === 'media') {
        // TODO: Distinguere tra image/video/file se necessario
        return 'media_image';
      }
      return 'entity_reference';

    case 'link':
      return 'link';

    case 'text':
    case 'text_long':
    case 'text_with_summary':
    case 'string':
    case 'string_long':
      return 'text';

    case 'image':
      return 'image';

    case 'datetime':
      return 'date';

    default:
      return 'text'; // Fallback
  }
}

/**
 * Helper: Verifica se un campo è multivalore.
 *
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo.
 * @param string $field_name
 *   Il nome del campo.
 *
 * @return bool
 *   TRUE se multivalore, FALSE altrimenti.
 */
function teasearch_is_field_multiple($node, $field_name)
{
  if (!$node->hasField($field_name)) {
    return FALSE;
  }

  $field_definition = $node->get($field_name)->getFieldDefinition();
  $field_storage = $field_definition->getFieldStorageDefinition();

  return $field_storage->isMultiple();
}

/**
 * Helper: Ottiene la label tradotta per un campo.
 *
 * @param array $field_config
 *   La configurazione del campo dallo schema.
 * @param string $field_identifier
 *   L'identificativo del campo.
 * @param string $content_type
 *   Il machine name del content type.
 *
 * @return string|null
 *   La label tradotta o NULL.
 */
function teasearch_get_field_label($field_config, $field_identifier, $content_type)
{
  // Se non c'è label specificata, non mostrare nulla
  if (!isset($field_config['label'])) {
    return NULL;
  }

  $label = $field_config['label'];

  // Ottieni traduzioni dal sistema custom
  $translations = teasearch_translations();
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Se esiste traduzione nel sistema custom, usala
  if (isset($translations[$content_type][$current_language][$field_identifier])) {
    return $translations[$content_type][$current_language][$field_identifier];
  }

  // Altrimenti usa la label dallo schema (già in inglese di default)
  return $label;
}

/**
 * Helper: Ottiene la funzione di render per un type.
 *
 * @param string $type
 *   Il type del campo.
 *
 * @return callable|null
 *   La funzione di render o NULL.
 */
function teasearch_get_type_render_function($type)
{
  $registry = teasearch_get_field_type_registry();

  if (isset($registry[$type]) && function_exists($registry[$type])) {
    return $registry[$type];
  }

  // Fallback: usa il renderer text
  return 'teasearch_render_field_text';
}

/**
 * Helper: Ottiene la label tradotta del content type.
 *
 * @param string $content_type
 *   Il machine name del content type.
 *
 * @return string
 *   La label tradotta del content type.
 */
function teasearch_get_content_type_label($content_type)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Mappa statica per le label dei content type
  $labels = [
    'en' => [
      'people' => 'People',
      'article' => 'Article',
      'event' => 'Event',
      'about' => 'About',
      // Aggiungi altri content type qui...
    ],
    'it' => [
      'people' => 'Persone',
      'article' => 'Articolo',
      'event' => 'Evento',
      'about' => 'Chi siamo'
      // Aggiungi altri content type qui...
    ],
  ];

  // Restituisci la label nella lingua corrente
  if (isset($labels[$current_language][$content_type])) {
    return $labels[$current_language][$content_type];
  }

  // Fallback: usa il nome del content type con prima lettera maiuscola
  return ucfirst(str_replace('_', ' ', $content_type));
}