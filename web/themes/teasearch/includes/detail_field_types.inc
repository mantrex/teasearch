<?php

/**
 * @file
 * Definizione dei type per il rendering dei campi nelle schede di dettaglio.
 *
 * Ogni type ha una funzione di rendering che riceve:
 * - $node: Il nodo completo
 * - $field_config: La configurazione del campo dallo schema
 * - $field_identifier: L'identificativo del campo nello schema
 *
 * La funzione deve restituire una stringa (il contenuto renderizzato) o NULL.
 */

/**
 * Registro dei type disponibili.
 */
function teasearch_get_field_type_registry()
{
  return [
    'text' => 'teasearch_render_field_text',
    'taxonomy' => 'teasearch_render_field_taxonomy',
    'link' => 'teasearch_render_field_link',
    'media_image' => 'teasearch_render_field_media_image',
    'media_image_round' => 'teasearch_render_field_media_image_round',
    'image_round' => 'teasearch_render_field_image_round', 
    'people_main_title' => 'teasearch_render_field_people_main_title',
    'people_name' => 'teasearch_render_field_people_name',
    'video' => 'teasearch_render_field_video',
    'location' => 'teasearch_render_location',
    'record_data' => 'teasearch_render_field_record_data',
    'systemdate' => 'teasearch_render_field_systemdate',
    'publib'=>'teasearch_render_field_publib'
  ];
}
/**
 * TYPE: people_main_title
 * Renderizza il titolo principale per il content type people (campo verde in alto).
 */
function teasearch_render_field_people_main_title($node, $field_config, $field_identifier)
{
  // Recupera i campi necessari
  $surname_first = teasearch_get_field_value($node, 'field_surname_first');
  $lastname = teasearch_get_field_value($node, 'field_lastname');
  $firstname = teasearch_get_field_value($node, 'field_firstname');
  $fullname = teasearch_get_field_value($node, 'field_fullname');

  // Estrai i valori (gestendo campi vuoti)
  $surname_first_value = $surname_first && !$surname_first->isEmpty() ? $surname_first->value : FALSE;
  $lastname_value = $lastname && !$lastname->isEmpty() ? $lastname->value : '';
  $firstname_value = $firstname && !$firstname->isEmpty() ? $firstname->value : '';
  $fullname_value = $fullname && !$fullname->isEmpty() ? $fullname->value : '';

  // Costruisci il risultato secondo la logica
  $parts = [];

  if ($surname_first_value) {
    // SURNAME_FIRST = true: lastname + firstname + fullname
    if (!empty($lastname_value)) {
      $parts[] = $lastname_value;
    }
    if (!empty($firstname_value)) {
      $parts[] = $firstname_value;
    }
    if (!empty($fullname_value)) {
      $parts[] = $fullname_value;
    }
  } else {
    // SURNAME_FIRST = false: firstname + lastname + fullname
    if (!empty($firstname_value)) {
      $parts[] = $firstname_value;
    }
    if (!empty($lastname_value)) {
      $parts[] = $lastname_value;
    }
    if (!empty($fullname_value)) {
      $parts[] = $fullname_value;
    }
  }

  // Unisci con spazi
  return !empty($parts) ? implode(' ', $parts) : NULL;
}

/**
 * TYPE: people_name
 * Renderizza il nome per il content type people (campo NAME nella tabella).
 */
function teasearch_render_field_people_name($node, $field_config, $field_identifier)
{
  // Recupera i campi necessari
  $surname_first = teasearch_get_field_value($node, 'field_surname_first');
  $lastname = teasearch_get_field_value($node, 'field_lastname');
  $firstname = teasearch_get_field_value($node, 'field_firstname');
  $fullname = teasearch_get_field_value($node, 'field_fullname');

  // Estrai i valori (gestendo campi vuoti)
  $surname_first_value = $surname_first && !$surname_first->isEmpty() ? $surname_first->value : FALSE;
  $lastname_value = $lastname && !$lastname->isEmpty() ? $lastname->value : '';
  $firstname_value = $firstname && !$firstname->isEmpty() ? $firstname->value : '';
  $fullname_value = $fullname && !$fullname->isEmpty() ? $fullname->value : '';

  // Costruisci il risultato secondo la logica
  $parts = [];

  if ($surname_first_value) {
    // SURNAME_FIRST = true: LASTNAME [CAPITALISED] + firstname + dash + fullname
    if (!empty($lastname_value)) {
      $parts[] = strtoupper($lastname_value);
    }
    if (!empty($firstname_value)) {
      $parts[] = $firstname_value;
    }
  } else {
    // SURNAME_FIRST = false: firstname + LASTNAME [CAPITALISED] + dash + fullname
    if (!empty($firstname_value)) {
      $parts[] = $firstname_value;
    }
    if (!empty($lastname_value)) {
      $parts[] = strtoupper($lastname_value);
    }
  }

  // Prima parte: nome occidentale
  $western_name = !empty($parts) ? implode(' ', $parts) : '';

  // Seconda parte: fullname (nome originale)
  // Se entrambi esistono, unisci con " – " (dash con spazi)
  // Usa <strong> per il bold del fullname
  if (!empty($western_name) && !empty($fullname_value)) {
    return $western_name . ' – <strong>' . $fullname_value . '</strong>';
  } elseif (!empty($western_name)) {
    return $western_name;
  } elseif (!empty($fullname_value)) {
    return '<strong>' . $fullname_value . '</strong>';
  }

  return NULL;
}

/**
 * TYPE: text
 * Renderizza un campo di testo (plain o rich text).
 */
function teasearch_render_field_text($node, $field_config, $field_identifier)
{
  $field = teasearch_get_field_value($node, $field_identifier);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  // Gestisce multivalore
  if (teasearch_is_field_multiple($node, $field_identifier)) {
    $values = [];
    foreach ($field as $item) {
      if (isset($item->value) && !empty($item->value)) {
        $values[] = $item->value;
      }
    }
    return !empty($values) ? implode(', ', $values) : NULL;
  }

  // Singolo valore
  // Gestisce sia plain text che rich text
  if (isset($field->processed)) {
    // Rich text (text_long, text_with_summary)
    return $field->processed;
  } elseif (isset($field->value)) {
    // Plain text
    $plainText = $field->value;
    $plainText = strtr($plainText, [PHP_EOL => "<br>"]);

    return $plainText;
  }

  return NULL;
}
/**
 * TYPE: taxonomy
 * Renderizza termini di tassonomia separati da virgola (multilingua).
 */
function teasearch_render_field_taxonomy($node, $field_config, $field_identifier)
{
  $field = teasearch_get_field_value($node, $field_identifier);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $terms = [];

  // Itera sui singoli item del campo
  foreach ($field as $item) {
    if (!$item->isEmpty() && $item->entity) {
      $term = $item->entity;

      // Ottieni la traduzione del termine se esiste
      if ($term->hasTranslation($current_language)) {
        $term = $term->getTranslation($current_language);
      }
      $terms[] = $term->getName();
    }
  }

  return !empty($terms) ? implode(', ', $terms) : NULL;
}

/**
 * TYPE: link
 * Renderizza un campo link.
 */
function teasearch_render_field_link($node, $field_config, $field_identifier)
{
  $field = teasearch_get_field_value($node, $field_identifier);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  // Gestisce multivalore
  if (teasearch_is_field_multiple($node, $field_identifier)) {
    $links = [];
    foreach ($field as $item) {
      if (!$item->isEmpty()) {
        $url = $item->getUrl();
        $title = $item->title ?: $url->toString();
        $links[] = '<a href="' . $url->toString() . '" target="_blank">' . htmlspecialchars($title) . '</a>';
      }
    }
    return !empty($links) ? implode('<br>', $links) : NULL;
  }

  // Singolo valore
  $url = $field->first()->getUrl();
  $title = $field->first()->title ?: $url->toString();

  return '<a href="' . $url->toString() . '" target="_blank">' . htmlspecialchars($title) . '</a>';
}

/**
 * TYPE: media_image
 * Renderizza un'immagine da campo media.
 */
function teasearch_render_field_media_image($node, $field_config, $field_identifier)
{
  // TODO: Implementare
  return NULL;
}

/**
 * TYPE: media_image_round
 * Renderizza un'immagine tonda (per icone/avatar).
 */
function teasearch_render_field_media_image_round($node, $field_config, $field_identifier)
{
  // TODO: Implementare
  return NULL;
}

/**
 * TYPE: video
 * Renderizza immagine con overlay play button e modal per video YouTube.
 */
function teasearch_render_field_video($node, $field_config, $field_identifier)
{
  // TODO: Implementare
  return NULL;
}

/**
 * TYPE: record_data
 * Mostra data di creazione e ultimo aggiornamento del nodo.
 */
function teasearch_render_field_record_data($node, $field_config, $field_identifier)
{
  // TODO: Implementare
  return NULL;
}

function teasearch_render_field_image_round($node, $field_config, $field_identifier)
{

  $field_name = $field_config['field'] ?? $field_identifier;

  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  $image = $field->first();
  if (!$image || !$image->entity) {
    return NULL;
  }

  $file = $image->entity;
  $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
  $alt = $image->alt ?: 'Profile image';

  return '<img src="' . $image_url . '" alt="' . htmlspecialchars($alt) . '" />';
}

function teasearch_render_location($node, $field_config, $field_identifier)
{
  $cityArr = teasearch_get_field_value($node, 'field_city');
  $regionArr = teasearch_get_field_value($node, 'field_region');
  $city = $cityArr && !$cityArr->isEmpty() ? $cityArr->value: FALSE;
  $region = $regionArr && !$regionArr->isEmpty() ? $regionArr->value: FALSE;
  return(($city && $region) ? $city . ", " . $region : ($city)) ? $city : "";

}

/**
 * TYPE: systemdate
 * Mostra data di creazione e ultimo aggiornamento del nodo.
 */
function teasearch_render_field_systemdate($node, $field_config, $field_identifier)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Ottieni le date
  $created = $node->getCreatedTime();
  $changed = $node->getChangedTime();

  // Formatta le date in base alla lingua
  if ($current_language === 'it') {
    $created_formatted = date('d/m/Y', $created);
    $changed_formatted = date('d/m/Y', $changed);

    return 'Data creazione: ' . $created_formatted . ' – Ultimo aggiornamento: ' . $changed_formatted;
  } else {
    // Inglese (default)
    $created_formatted = date('d/m/Y', $created);
    $changed_formatted = date('d/m/Y', $changed);

    return 'First created on: ' . $created_formatted . ' – Last updated on: ' . $changed_formatted;
  }
}


function teasearch_render_field_publib($node, $field_config, $field_identifier) {
  $field = teasearch_get_field_value($node, $field_identifier);
}
