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
    'publib'=>'teasearch_render_field_publib',
    'contrib' => 'teasearch_render_field_contrib',
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


/**
 * TYPE: publib
 * Renderizza pubblicazioni bibliografiche filtrate per pubtype.
 */
function teasearch_render_field_publib($node, $field_config, $field_identifier)
{
  // Recupero il pubtype dalla configurazione (es: 'journal_article', 'book', etc.)
  $target_pubtype = $field_config['pubtype'] ?? NULL;

  if (!$target_pubtype) {
    return NULL;
  }

  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $current_node_id = $node->id();

  // Carico tutte le bibliografie
  $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
  $query->condition('type', 'bibliography')
    ->condition('status', 1)
    ->accessCheck(TRUE);

  $bibliography_ids = $query->execute();

  if (empty($bibliography_ids)) {
    return NULL;
  }

  $bibliographies = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($bibliography_ids);
  $citations_data = [];

  foreach ($bibliographies as $bib) {
    // Traduzione nella lingua corrente se disponibile
    if ($bib->hasTranslation($current_language)) {
      $bib = $bib->getTranslation($current_language);
    }

    // Verifico il pubtype
    $pubtype_field = $bib->get('field_pubtype');
    if ($pubtype_field->isEmpty()) {
      continue;
    }

    $pubtype_term = $pubtype_field->entity;
    if (!$pubtype_term) {
      continue;
    }

    // Recupero il campo key dalla taxonomy
    $pubtype_key = $pubtype_term->hasField('field_key') ? $pubtype_term->get('field_key')->value : NULL;

    // Verifico match con il pubtype richiesto
    if ($pubtype_key !== $target_pubtype) {
      continue;
    }

    // Verifico se il nodo corrente è autore o curatore
    $is_author = FALSE;
    $is_curator = FALSE;

    // Check field_author
    $authors_field = $bib->get('field_authors_ref');
    if (!$authors_field->isEmpty()) {
      foreach ($authors_field as $author_ref) {
        if ($author_ref->target_id == $current_node_id) {
          $is_author = TRUE;
          break;
        }
      }
    }

    // Check field_curators
    $curators_field = $bib->get('field_curators_ref');
    if (!$curators_field->isEmpty()) {
      foreach ($curators_field as $curator_ref) {
        if ($curator_ref->target_id == $current_node_id) {
          $is_curator = TRUE;
          break;
        }
      }
    }

    // Se non è né autore né curatore, salto
    if (!$is_author && !$is_curator) {
      continue;
    }

    // Costruisco la citazione
    $citation = teasearch_build_citation($bib, $is_curator, $current_language);

    if ($citation) {
      // Recupero dati per sorting
      $first_author_lastname = teasearch_get_first_author_lastname($bib);
      $year = $bib->hasField('field_year') && !$bib->get('field_year')->isEmpty()
        ? $bib->get('field_year')->value
        : '9999'; // Anno molto alto per mettere in fondo quelli senza anno

      $citations_data[] = [
        'citation' => $citation,
        'lastname' => $first_author_lastname,
        'year' => $year,
      ];
    }
  }

  // Ordino per cognome, poi per anno
  usort($citations_data, function ($a, $b) {
    // Prima ordino per cognome
    $lastname_compare = strcasecmp($a['lastname'], $b['lastname']);
    if ($lastname_compare !== 0) {
      return $lastname_compare;
    }

    // Se cognome uguale, ordino per anno
    return strcmp($a['year'], $b['year']);
  });

  // Estraggo solo le citazioni ordinate
  $citations = array_map(function ($item) {
    return $item['citation'];
  }, $citations_data);

  // Restituisco le citazioni separate da <br>
  return !empty($citations) ? implode('<br>', $citations) : NULL;
}

/**
 * Helper: Recupera il cognome del primo autore per il sorting.
 */
function teasearch_get_first_author_lastname($bibliography_node)
{
  if (!$bibliography_node->hasField('field_responsibles')) {
    return '';
  }

  $authors_field = $bibliography_node->get('field_responsibles');

  if ($authors_field->isEmpty()) {
    return '';
  }

  // Prendo il primo autore
  $first_author = $authors_field->first()->entity;

  if (!$first_author) {
    return '';
  }

  // Recupero il cognome
  if ($first_author->hasField('field_lastname_latin') && !$first_author->get('field_lastname_latin')->isEmpty()) {
    return $first_author->get('field_lastname_latin')->value;
  }

  return '';
}

/**
 * Helper: Costruisce una citazione bibliografica.
 */
function teasearch_build_citation($bibliography_node, $is_curator, $language)
{
  $parts = [];

  // 1. AUTORI
  $authors_string = teasearch_format_authors($bibliography_node);
  if ($authors_string) {
    $parts[] = $authors_string;
  }

  // 2. ANNO
  $year_field = $bibliography_node->get('field_year');
  if (!$year_field->isEmpty()) {
    $year = $year_field->value;
    $parts[] = '(' . $year . ')';
  }

  // 3. TITOLO
  $title = $bibliography_node->getTitle(); // Titolo principale del nodo

  $title_parts = [];
  if (!empty($title)) {
    $title_parts[] = $title;
  }

  // Titolo originale (se esiste come campo)
  if ($bibliography_node->hasField('field_title_original')) {
    $title_original_field = $bibliography_node->get('field_title_original');
    if (!$title_original_field->isEmpty()) {
      $title_parts[] = $title_original_field->value;
    }
  }

  if (!empty($title_parts)) {
    $parts[] = implode(' ', $title_parts);
  }

  // 4. NOTA CURATORE
  if ($is_curator) {
    $curator_label = $language === 'it' ? '(a cura di)' : '(edited by)';
    $parts[] = $curator_label;
  }

  if (empty($parts)) {
    return NULL;
  }

  // Costruisco la citazione completa
  $citation_text = implode(' ', $parts);

  // Genero l'URL del nodo bibliography
  $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $bibliography_node->id()]);
  $url_string = $url->toString();

  // Ritorno la citazione come link
  return '<a href="' . $url_string . '">' . htmlspecialchars($citation_text) . '</a>';
}

/**
 * Helper: Formatta gli autori dal paragraph field_responsibles.
 */
function teasearch_format_authors($bibliography_node)
{
  // Il campo paragraph degli autori si chiama field_responsibles
  if (!$bibliography_node->hasField('field_responsibles')) {
    return NULL;
  }

  $authors_field = $bibliography_node->get('field_responsibles');

  if ($authors_field->isEmpty()) {
    return NULL;
  }

  $authors = [];

  foreach ($authors_field as $paragraph_item) {
    $paragraph = $paragraph_item->entity;

    if (!$paragraph) {
      continue;
    }

    // Recupero i campi dell'autore
    $surname_first = $paragraph->hasField('field_surname_first') && !$paragraph->get('field_surname_first')->isEmpty()
      ? $paragraph->get('field_surname_first')->value
      : FALSE;

    $lastname_latin = $paragraph->hasField('field_lastname_latin') && !$paragraph->get('field_lastname_latin')->isEmpty()
      ? $paragraph->get('field_lastname_latin')->value
      : '';

    $firstname_latin = $paragraph->hasField('field_firstname_latin') && !$paragraph->get('field_firstname_latin')->isEmpty()
      ? $paragraph->get('field_firstname_latin')->value
      : '';

    $fullname = $paragraph->hasField('field_fullname') && !$paragraph->get('field_fullname')->isEmpty()
      ? $paragraph->get('field_fullname')->value
      : '';

    // Costruisco il nome dell'autore
    $author_parts = [];

    if ($surname_first) {
      // Cognome e nome separati da spazio
      if (!empty($lastname_latin)) {
        $author_parts[] = $lastname_latin;
      }
      if (!empty($firstname_latin)) {
        $author_parts[] = $firstname_latin;
      }
    } else {
      // Cognome e nome separati da virgola+spazio
      if (!empty($lastname_latin) && !empty($firstname_latin)) {
        $author_parts[] = $lastname_latin . ', ' . $firstname_latin;
      } elseif (!empty($lastname_latin)) {
        $author_parts[] = $lastname_latin;
      } elseif (!empty($firstname_latin)) {
        $author_parts[] = $firstname_latin;
      }
    }

    // Aggiungo fullname se presente
    if (!empty($fullname)) {
      $author_parts[] = $fullname;
    }

    if (!empty($author_parts)) {
      $authors[] = implode(' ', $author_parts);
    }
  }

  // Formatto in base al numero di autori
  $count = count($authors);

  if ($count === 0) {
    return NULL;
  } elseif ($count === 1) {
    return $authors[0];
  } elseif ($count === 2) {
    return $authors[0] . ' et ' . $authors[1];
  } else {
    // 3 o più autori
    return $authors[0] . ' et al.';
  }
}

/**
 * TYPE: contrib
 * Renderizza contributi ai content types (essentials, texts, videos, etc.).
 */
function teasearch_render_field_contrib($node, $field_config, $field_identifier)
{
  // Recupero il content_type dalla configurazione
  $target_content_type = $field_config['content_type'] ?? NULL;

  if (!$target_content_type) {
    return NULL;
  }

  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $current_node_id = $node->id();

  // Carico tutti i nodi del content type specificato
  $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
  $query->condition('type', $target_content_type)
    ->condition('status', 1)
    ->accessCheck(TRUE);

  $node_ids = $query->execute();

  if (empty($node_ids)) {
    return NULL;
  }

  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($node_ids);
  $contributions_data = [];

  // Campi di riferimento da controllare con traduzioni
  $reference_fields = [
    'field_authors_ref' => [
      'en' => 'Author',
      'it' => 'Autore',
    ],
    'field_reviewers_ref' => [
      'en' => 'Reviewer',
      'it' => 'Revisore',
    ],
    'field_curators_ref' => [
      'en' => 'Entry curator',
      'it' => 'Curatore della scheda',
    ],
    'field_featured_ref' => [
      'en' => 'Person featured',
      'it' => 'Soggetto rappresentato',
    ],
  ];

  foreach ($nodes as $contrib_node) {
    // Traduzione nella lingua corrente se disponibile
    if ($contrib_node->hasTranslation($current_language)) {
      $contrib_node = $contrib_node->getTranslation($current_language);
    }

    // Verifico se il nodo corrente è referenziato
    $role = NULL;

    foreach ($reference_fields as $field_name => $translations) {
      if (!$contrib_node->hasField($field_name)) {
        continue;
      }

      $ref_field = $contrib_node->get($field_name);
      if ($ref_field->isEmpty()) {
        continue;
      }

      foreach ($ref_field as $ref_item) {
        if ($ref_item->target_id == $current_node_id) {
          $role = $translations[$current_language] ?? $translations['en'];
          break 2; // Esci da entrambi i loop
        }
      }
    }

    // Se non è referenziato, salto
    if (!$role) {
      continue;
    }

    // Costruisco la contribution string
    $contribution = teasearch_build_contribution($contrib_node, $target_content_type, $role, $current_language);

    if ($contribution) {
      // Recupero dati per sorting
      $title_for_sort = teasearch_get_contribution_title_for_sort($contrib_node);
      $year = $contrib_node->hasField('field_year_from') && !$contrib_node->get('field_year_from')->isEmpty()
        ? $contrib_node->get('field_year_from')->value
        : '9999';

      $contributions_data[] = [
        'contribution' => $contribution,
        'title' => $title_for_sort,
        'year' => $year,
      ];
    }
  }

  // Ordino per title, poi per anno
  usort($contributions_data, function ($a, $b) {
    // Prima ordino per titolo
    $title_compare = strcasecmp($a['title'], $b['title']);
    if ($title_compare !== 0) {
      return $title_compare;
    }

    // Se titolo uguale, ordino per anno
    return strcmp($a['year'], $b['year']);
  });

  // Estraggo solo le contributions ordinate
  $contributions = array_map(function ($item) {
    return $item['contribution'];
  }, $contributions_data);

  // Restituisco le contributions separate da <br>
  return !empty($contributions) ? implode('<br>', $contributions) : NULL;
}

/**
 * Helper: Formatta gli autori in modo universale per qualsiasi content type.
 */
function teasearch_format_authors_universal($node, $content_type)
{
  // Mappatura content_type -> campo paragraph
  $paragraph_field_map = [
    'first_reference' => 'field_texts_authors',
    'bibliography' => 'field_responsibles',
    'images' => 'field_texts_authors',
    'videos' => 'field_creators',
  ];

  // Recupero il campo paragraph per questo content type
  $paragraph_field_name = $paragraph_field_map[$content_type] ?? NULL;

  if (!$paragraph_field_name || !$node->hasField($paragraph_field_name)) {
    return NULL;
  }

  $authors_field = $node->get($paragraph_field_name);

  if ($authors_field->isEmpty()) {
    return NULL;
  }

  $authors = [];

  foreach ($authors_field as $paragraph_item) {
    $paragraph = $paragraph_item->entity;

    if (!$paragraph) {
      continue;
    }

    // Recupero i campi dell'autore (con nomi flessibili)
    $surname_first = $paragraph->hasField('field_surname_first') && !$paragraph->get('field_surname_first')->isEmpty()
      ? $paragraph->get('field_surname_first')->value
      : FALSE;

    // Cognome: prova field_lastname_latin, poi field_lastname
    $lastname = '';
    if ($paragraph->hasField('field_lastname_latin') && !$paragraph->get('field_lastname_latin')->isEmpty()) {
      $lastname = $paragraph->get('field_lastname_latin')->value;
    } elseif ($paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname')->isEmpty()) {
      $lastname = $paragraph->get('field_lastname')->value;
    }

    // Nome: prova field_firstname_latin, poi field_firstname
    $firstname = '';
    if ($paragraph->hasField('field_firstname_latin') && !$paragraph->get('field_firstname_latin')->isEmpty()) {
      $firstname = $paragraph->get('field_firstname_latin')->value;
    } elseif ($paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname')->isEmpty()) {
      $firstname = $paragraph->get('field_firstname')->value;
    }

    // Fullname
    $fullname = $paragraph->hasField('field_fullname') && !$paragraph->get('field_fullname')->isEmpty()
      ? $paragraph->get('field_fullname')->value
      : '';

    // Pseudonym (solo per alcuni paragraph types)
    $pseudonym = $paragraph->hasField('field_pseudonym') && !$paragraph->get('field_pseudonym')->isEmpty()
      ? $paragraph->get('field_pseudonym')->value
      : '';

    // Costruisco il nome dell'autore
    $author_parts = [];

    if ($surname_first) {
      // Cognome e nome separati da spazio
      if (!empty($lastname)) {
        $author_parts[] = $lastname;
      }
      if (!empty($firstname)) {
        $author_parts[] = $firstname;
      }
    } else {
      // Cognome e nome separati da virgola+spazio
      if (!empty($lastname) && !empty($firstname)) {
        $author_parts[] = $lastname . ', ' . $firstname;
      } elseif (!empty($lastname)) {
        $author_parts[] = $lastname;
      } elseif (!empty($firstname)) {
        $author_parts[] = $firstname;
      }
    }

    // Aggiungo fullname se presente
    if (!empty($fullname)) {
      $author_parts[] = $fullname;
    }

    // Aggiungo pseudonym con parentesi se presente
    if (!empty($pseudonym)) {
      $author_parts[] = '(' . $pseudonym . ')';
    }

    if (!empty($author_parts)) {
      $authors[] = implode(' ', $author_parts);
    }
  }

  // Formatto in base al numero di autori
  $count = count($authors);

  if ($count === 0) {
    return NULL;
  } elseif ($count === 1) {
    return $authors[0];
  } elseif ($count === 2) {
    return $authors[0] . ' et ' . $authors[1];
  } else {
    // 3 o più autori
    return $authors[0] . ' et al.';
  }
}



/**
 * Helper: Costruisce una contribution string.
 */
function teasearch_build_contribution($node, $content_type, $role, $language)
{
  $parts = [];

  // 1. TITOLO
  $title = $node->getTitle();
  $title_original = $node->hasField('field_title_original') && !$node->get('field_title_original')->isEmpty()
    ? $node->get('field_title_original')->value
    : NULL;
  $title_translated = $node->hasField('field_title_translated') && !$node->get('field_title_translated')->isEmpty()
    ? $node->get('field_title_translated')->value
    : NULL;

  if (!empty($title)) {
    $title_string = $title;
    if ($title_original) {
      $title_string .= ' ' . $title_original;
    }
    $parts[] = $title_string;
  } elseif ($title_translated) {
    $parts[] = $title_translated;
  }

  // 2. AUTORI (se il paragraph field esiste per questo content type)
  $authors_string = teasearch_format_authors_universal($node, $content_type);
  if ($authors_string) {
    $parts[] = $authors_string;
  }

  // 3. RUOLO
  if ($role) {
    $parts[] = '(' . $role . ')';
  }

  if (empty($parts)) {
    return NULL;
  }

  // Costruisco la contribution completa
  $contribution_text = implode(' – ', $parts);

  // Genero l'URL del nodo
  $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $node->id()]);
  $url_string = $url->toString();

  // Ritorno la contribution come link
  return '<a href="' . $url_string . '">' . htmlspecialchars($contribution_text) . '</a>';
}


/**
 * Helper: Recupera il titolo per il sorting.
 */
function teasearch_get_contribution_title_for_sort($node) {
  $title = $node->getTitle();
  
  if (!empty($title)) {
    return $title;
  }
  
  if ($node->hasField('field_title_translated') && !$node->get('field_title_translated')->isEmpty()) {
    return $node->get('field_title_translated')->value;
  }
  
  return '';
}