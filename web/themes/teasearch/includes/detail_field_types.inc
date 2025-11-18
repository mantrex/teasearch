<?php

use Drupal\Core\Render\Markup;
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
    'location' => 'teasearch_render_location',
    'record_data' => 'teasearch_render_field_record_data',
    'systemdate' => 'teasearch_render_field_systemdate',
    'publib'=>'teasearch_render_field_publib',
    'contrib' => 'teasearch_render_field_contrib',
    'bibliography_title' => 'teasearch_render_field_bibliography_title',
    'bibliography_icon' => 'teasearch_render_field_bibliography_icon',
    'bibliography_authors' => 'teasearch_render_field_bibliography_authors',
    'bibliography_editors' => 'teasearch_render_field_bibliography_editors',
    "people_reference_list" => 'teasearch_render_field_people_reference_list',
    'journal_name' => 'teasearch_render_field_journal_name',
    'vol_issue' => 'teasearch_render_field_vol_issue',
    'curated_references' => 'teasearch_render_field_curated_references',
    'teasearch_references' => 'teasearch_render_field_teasearch_references',
    'people_roles' => 'teasearch_render_field_people_roles',
    'referenced_in' => 'teasearch_render_field_referenced_in',
    'square_or_main' => 'teasearch_render_field_square_or_main',
    'texts_title' => 'teasearch_render_field_texts_title',
    'authors_general' => 'teasearch_render_field_authors_general',
    'unified_title' => 'teasearch_render_field_unified_title',
    'date_label' => 'teasearch_render_field_date_label',
    'image' => 'teasearch_render_field_image',
    'video' => 'teasearch_render_field_video',
    'pdf' => 'teasearch_render_field_pdf',
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

  return '<img src="' . $image_url . '" alt="' . htmlspecialchars($alt) . '" class="detail-icon-image" />';

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
  if ($first_author->hasField('field_lastname') && !$first_author->get('field_lastname')->isEmpty()) {
    return $first_author->get('field_lastname')->value;
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

    $lastname_latin = $paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname')->isEmpty()
      ? $paragraph->get('field_lastname')->value
      : '';

    $firstname_latin = $paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname')->isEmpty()
      ? $paragraph->get('field_firstname')->value
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
    if ($paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname')->isEmpty()) {
      $lastname = $paragraph->get('field_lastname')->value;
    }

    // Nome: prova field_firstname_latin, poi field_firstname
    $firstname = '';
    if ($paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname')->isEmpty()) {
      $firstname = $paragraph->get('field_firstname')->value;
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
      $title_string .= ' &middot; ' . $title_original;
    }
    $parts[] = $title_string;
  } elseif ($title_translated) {
    $parts[] = "[".$title_translated."]";
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






/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */
/* BIBLIOGRAPHY */







function teasearch_render_field_bibliography_title($node, $field_config, $field_identifier)
{
  $parts = [];

  // 1. Titolo principale del nodo
  $main_title = $node->getTitle();
  if (!empty($main_title)) {
    $parts[] = htmlspecialchars($main_title);
  }

  // 2. field_title_original
  $title_original = teasearch_get_field_value($node, 'field_title_original');
  if ($title_original && !$title_original->isEmpty()) {
    $parts[] = htmlspecialchars($title_original->value);
  }

  // Unisci con spazio
  return !empty($parts) ? implode(' ', $parts) : NULL;
}

/**
 * TYPE: bibliography_authors
 * Renderizza il campo paragraph field_responsibles (Authors).
 * 
 * Logica:
 * - Se field_is_anonymous = true: "Anonymous"
 * - Altrimenti:
 *   - Se field_is_org = true: SURNAME_(LATIN) + FULL_NAME_(ORIG_NON_LATIN)
 *   - Altrimenti:
 *     - Se field_surname_first = true: SURNAME_(LATIN) [CAPS] + GIVEN_NAME_(LATIN) + · + FULL_NAME + PSEUDONYM [brackets]
 *     - Altrimenti: GIVEN_NAME_(LATIN) + SURNAME_(LATIN) [CAPS] + · + FULL_NAME + PSEUDONYM [brackets]
 * - Aggiunge "(Translator)" se field_is_translator = true
 * - Aggiunge "(Editor)" se field_is_editor = true
 */
function teasearch_render_field_bibliography_authors($node, $field_config, $field_identifier)
{
  $field_name = $field_config['field'] ?? 'field_responsibles';
  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  // Check se il nodo è anonimo
  $is_anonymous_node = FALSE;
  if (@$node->hasField('field_is_anonymous')) {
    $is_anonymous_field = $node->get('field_is_anonymous');
    if (!$is_anonymous_field->isEmpty()) {
      $is_anonymous_node = $is_anonymous_field->value;
    }
  }

  // Se il nodo è anonimo, restituisci "Anonymous" o "Anonimo" in base alla lingua
  if ($is_anonymous_node) {
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    return $current_language === 'it' ? 'Anonimo' : 'Anonymous';
  }

  $authors = [];

  // Itera su ogni elemento del paragraph
  foreach ($field as $paragraph_item) {
    $paragraph = $paragraph_item->entity;

    if (!$paragraph) {
      continue;
    }

    $author_string = teasearch_format_single_author($paragraph);
    if ($author_string) {
      $authors[] = $author_string;
    }
  }

  if (empty($authors)) {
    return NULL;
  }

  // Unisci gli autori con ";" (come separatore standard bibliografico)
  return implode('; ', $authors);
}


/**
 * Helper: Formatta un singolo autore dal paragraph.
 */
function teasearch_format_single_author($paragraph)
{
  // Recupera i campi dal paragraph con check hasField
  $is_org = $paragraph->hasField('field_is_org') && !$paragraph->get('field_is_org')->isEmpty()
    ? $paragraph->get('field_is_org')->value
    : FALSE;

  $surname_first = $paragraph->hasField('field_surname_first') && !$paragraph->get('field_surname_first')->isEmpty()
    ? $paragraph->get('field_surname_first')->value
    : FALSE;

  $lastname_latin = $paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname_latin')->isEmpty()
    ? $paragraph->get('field_lastname')->value
    : '';

  $firstname_latin = $paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname_latin')->isEmpty()
    ? $paragraph->get('field_firstname')->value
    : '';

  $fullname = $paragraph->hasField('field_fullname') && !$paragraph->get('field_fullname')->isEmpty()
    ? $paragraph->get('field_fullname')->value
    : '';

  $pseudonym = $paragraph->hasField('field_pseudonym') && !$paragraph->get('field_pseudonym')->isEmpty()
    ? $paragraph->get('field_pseudonym')->value
    : '';

  $is_translator = $paragraph->hasField('field_is_translator') && !$paragraph->get('field_is_translator')->isEmpty()
    ? $paragraph->get('field_is_translator')->value
    : FALSE;

  $is_editor = $paragraph->hasField('field_is_editor') && !$paragraph->get('field_is_editor')->isEmpty()
    ? $paragraph->get('field_is_editor')->value
    : FALSE;

  $parts = [];

  if ($is_org) {
    // Organizzazione: SURNAME_(LATIN) + FULL_NAME_(ORIG_NON_LATIN)
    if (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    }
    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  } else {
    // Persona
    if ($surname_first) {
      // SURNAME first: SURNAME_(LATIN) [CAPS] + GIVEN_NAME_(LATIN) + · + FULL_NAME + PSEUDONYM
      if (!empty($lastname_latin)) {
        $parts[] = mb_strtoupper($lastname_latin);
      }
      if (!empty($firstname_latin)) {
        $parts[] = $firstname_latin;
      }
      // Puntino catalano (Middle Dot) U+00B7
      if (!empty($fullname)) {
        $parts[] = '·';
        $parts[] = $fullname;
      }
      if (!empty($pseudonym)) {
        $parts[] = '[' . $pseudonym . ']';
      }
    } else {
      // GIVEN_NAME first: GIVEN_NAME_(LATIN) + SURNAME_(LATIN) [CAPS] + · + FULL_NAME + PSEUDONYM
      if (!empty($firstname_latin)) {
        $parts[] = $firstname_latin;
      }
      if (!empty($lastname_latin)) {
        $parts[] = mb_strtoupper($lastname_latin);
      }
      // Puntino catalano
      if (!empty($fullname)) {
        $parts[] = '·';
        $parts[] = $fullname;
      }
      if (!empty($pseudonym)) {
        $parts[] = '[' . $pseudonym . ']';
      }
    }
  }

  $result = implode(' ', $parts);

  // Aggiungi ruoli
  $roles = [];
  if ($is_translator) {
    $roles[] = 'Translator';
  }
  if ($is_editor) {
    $roles[] = 'Editor';
  }

  if (!empty($roles)) {
    $result .= ' (' . implode(', ', $roles) . ')';
  }

  return !empty($result) ? $result : NULL;
}
/**
 * TYPE: bibliography_editors
 * Renderizza il campo paragraph field_editors (Editors).
 * Usa la stessa logica di bibliography_authors ma per il paragraph Editor.
 */
function teasearch_render_field_bibliography_editors($node, $field_config, $field_identifier)
{
  $field_name = $field_config['field'] ?? 'field_editors';
  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  // Check se il nodo è anonimo
  $is_anonymous_node = FALSE;
  if ($node->hasField('field_is_anonymous')) {
    $is_anonymous_field = $node->get('field_is_anonymous');
    if (!$is_anonymous_field->isEmpty()) {
      $is_anonymous_node = $is_anonymous_field->value;
    }
  }

  // Se il nodo è anonimo, restituisci "Anonymous" o "Anonimo" in base alla lingua
  if ($is_anonymous_node) {
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    return $current_language === 'it' ? 'Anonimo' : 'Anonymous';
  }

  $editors = [];

  // Itera su ogni elemento del paragraph
  foreach ($field as $paragraph_item) {
    $paragraph = $paragraph_item->entity;

    if (!$paragraph) {
      continue;
    }

    // Riusa la stessa funzione di formattazione
    $editor_string = teasearch_format_single_author($paragraph);
    if ($editor_string) {
      $editors[] = $editor_string;
    }
  }

  if (empty($editors)) {
    return NULL;
  }

  // Unisci gli editors con ";"
  return implode('; ', $editors);
}

/**
 * TYPE: people_reference_list
 * Renderizza una lista di riferimenti a nodi People (es. field_authors_ref, field_curators_ref, etc.)
 * Mostra i nomi come link ai profili.
 * 
 * Options:
 * - 'show_link' (default true): Se true, mostra link, altrimenti solo testo
 */
function teasearch_render_field_people_reference_list($node, $field_config, $field_identifier)
{
  $field_name = $field_config['field'] ?? $field_identifier;
  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  $show_link = $field_config['options']['show_link'] ?? TRUE;
  $items = [];

  // Itera su ogni nodo People referenziato
  foreach ($field as $item) {
    if ($item->isEmpty()) {
      continue;
    }

    $person_node = $item->entity;

    if (!$person_node || !$person_node->access('view')) {
      continue;
    }

    // Ottieni il nome usando la stessa logica di people_name
    $name = teasearch_get_person_display_name($person_node);

    if ($show_link) {
      $url = $person_node->toUrl()->toString();
      $items[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($name) . '</a>';
    } else {
      $items[] = htmlspecialchars($name);
    }
  }

  if (empty($items)) {
    return NULL;
  }

  // Unisci con virgola
  return implode(', ', $items);
}


/**
 * TYPE: people_roles
 * Renderizza un campo combinato che mostra tutti i riferimenti a nodi People con i loro ruoli.
 * 
 * Combina:
 * - field_authors_ref → "Publication author" / "Autore della pubblicazione"
 * - field_featured_ref → "Person featured" / "Persona trattata"
 * - field_curators_ref → "Entry curator" / "Curatore della scheda"
 * - field_reviewers_ref → "Reviewer" / "Revisore"
 * 
 * Formato: field AUTHOR followed by: (role); field PERSON_FEATURED followed by: (role); etc.
 * Tutti separati da virgole e punto e virgola, con link alle pagine People.
 */
function teasearch_render_field_people_roles($node, $field_config, $field_identifier)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Definizione dei campi e delle loro label tradotte
  $fields_config = [
    'field_authors_ref' => [
      'en' => 'Publication author',
      'it' => 'Autore della pubblicazione',
    ],
    'field_featured_ref' => [
      'en' => 'Person featured',
      'it' => 'Persona trattata',
    ],
    'field_curators_ref' => [
      'en' => 'Entry curator',
      'it' => 'Curatore della scheda',
    ],
    'field_reviewers_ref' => [
      'en' => 'Reviewer',
      'it' => 'Revisore',
    ],
  ];

  $all_items = [];

  // Itera su ogni campo configurato
  foreach ($fields_config as $field_name => $role_labels) {
    if (!$node->hasField($field_name)) {
      continue;
    }

    $field = $node->get($field_name);

    if ($field->isEmpty()) {
      continue;
    }

    // Label del ruolo nella lingua corrente
    $role_label = $role_labels[$current_language] ?? $role_labels['en'];

    // Raccogli tutti i nomi delle persone per questo campo
    $people_names = [];

    foreach ($field as $item) {
      if ($item->isEmpty()) {
        continue;
      }

      $person_node = $item->entity;

      if (!$person_node || !$person_node->access('view')) {
        continue;
      }

      // Ottieni traduzione nella lingua corrente
      if ($person_node->hasTranslation($current_language)) {
        $person_node = $person_node->getTranslation($current_language);
      }

      // Ottieni il nome display della persona
      $name = teasearch_get_person_display_name($person_node);
      $url = $person_node->toUrl()->toString();

      // Crea il link
      $people_names[] = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($name) . '</a>';
    }

    // Se ci sono persone per questo ruolo, aggiungi alla lista
    if (!empty($people_names)) {
      // Formato: field AUTHOR followed by: (role)
      $people_list = implode(', ', $people_names);
      $all_items[] = $people_list . ' <em>(' . htmlspecialchars($role_label) . ')</em>';
    }
  }

  if (empty($all_items)) {
    return NULL;
  }

  // Unisci tutti gli items con "; " (punto e virgola + spazio)
  return implode(', ', $all_items);
}




/**
 * TYPE: referenced_in
 * Campo automatico "CITATO IN" / "REFERENCED IN".
 * Mostra tutti i content types dove questo nodo viene referenziato.
 * 
 * Cerca in:
 * - first_reference (Essentials) → field_teasearch_references → BOX-4
 * - primary_sources (Texts) → field_teasearch_references → BOX-4
 * - images (Images) → field_teasearch_references → BOX-4
 * - videos (Videos) → field_teasearch_references → BOX-4
 * - bibliography (Bibliography) → field_curated_references → BOX-3
 * 
 * Raggruppa per categoria e ordina come BOX-4.
 */
function teasearch_render_field_referenced_in($node, $field_config, $field_identifier)
{
  $current_node_id = $node->id();
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Configurazione content types da cercare
  $content_types_config = [
    'first_reference' => [
      'field' => 'field_teasearch_references',
      'template' => 'box4', // BOX-4 con categoria
    ],
    'primary_sources' => [
      'field' => 'field_teasearch_references',
      'template' => 'box4',
    ],
    'images' => [
      'field' => 'field_teasearch_references',
      'template' => 'box4',
    ],
    'videos' => [
      'field' => 'field_teasearch_references',
      'template' => 'box4',
    ],
    'bibliography' => [
      'field' => 'field_curated_references',
      'template' => 'box3', // BOX-3 (come BOX-1 ma con categoria)
    ],
  ];

  // Raggruppa risultati per content type
  $grouped_by_category = [
    'first_reference' => [],
    'primary_sources' => [],
    'images' => [],
    'videos' => [],
    'bibliography' => [],
  ];

  $seen_surnames = [];

  // Per ogni content type, cerca dove il nodo corrente è referenziato
  foreach ($content_types_config as $content_type => $config) {
    $field_name = $config['field'];
    $template = $config['template'];

    // Query per trovare tutti i nodi di questo content type che referenziano il nodo corrente
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->condition('type', $content_type)
      ->condition('status', 1)
      ->condition($field_name, $current_node_id)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      continue;
    }

    // Carica i nodi
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $ref_node) {
      // Ottieni traduzione nella lingua corrente
      if ($ref_node->hasTranslation($current_language)) {
        $ref_node = $ref_node->getTranslation($current_language);
      }

      // Costruisci la citazione secondo il template
      if ($template === 'box4') {
        // BOX-2 + categoria
        $citation_data = teasearch_build_box2_citation($ref_node, $content_type, $current_language, $seen_surnames, true);
      } else {
        // BOX-3: come BOX-1 + categoria (per bibliography)
        $citation_data = teasearch_build_box1_citation($ref_node, $current_language, $seen_surnames, true);
      }

      if ($citation_data) {
        $grouped_by_category[$content_type][] = $citation_data;
      }
    }
  }

  // Ordina all'interno di ogni categoria
  foreach ($grouped_by_category as $content_type => &$items) {
    if (!empty($items)) {
      usort($items, function ($a, $b) {
        $title_compare = strcasecmp($a['title_sort'], $b['title_sort']);
        if ($title_compare !== 0) {
          return $title_compare;
        }
        return strcmp($a['year'], $b['year']);
      });
    }
  }
  unset($items);

  // Costruisci l'output finale con intestazioni di categoria
  $category_labels = [
    'first_reference' => [
      'en' => 'Essentials',
      'it' => 'Fondamenti',
    ],
    'primary_sources' => [
      'en' => 'Texts',
      'it' => 'Testi',
    ],
    'images' => [
      'en' => 'Images',
      'it' => 'Immagini',
    ],
    'videos' => [
      'en' => 'Videos',
      'it' => 'Video',
    ],
    'bibliography' => [
      'en' => 'Bibliography',
      'it' => 'Bibliografia',
    ],
  ];

  $output_sections = [];

  foreach ($grouped_by_category as $content_type => $items) {
    if (empty($items)) {
      continue;
    }

    // Intestazione categoria
    $category_label = $category_labels[$content_type][$current_language] ?? $category_labels[$content_type]['en'];

    // Citazioni
    $citations = array_map(function ($item) {
      return $item['citation'];
    }, $items);

    // Formato: List of Chinese essays on tea (Essentials)
    // Senza intestazione h3, solo lista inline
    $section_html = '<div class="referenced-in-category">';
    $section_html .= implode('<br>', $citations);
    $section_html .= '</div>';

    $output_sections[] = $section_html;
  }

  if (empty($output_sections)) {
    return NULL;
  }

  return implode("\n", $output_sections);
}



/**
 * Helper: Ottiene il nome display di un nodo People.
 * Riusa la stessa logica di teasearch_render_field_people_name
 */
function teasearch_get_person_display_name($node)
{
  $surname_first = teasearch_get_field_value($node, 'field_surname_first');
  $lastname = teasearch_get_field_value($node, 'field_lastname');
  $firstname = teasearch_get_field_value($node, 'field_firstname');
  $fullname = teasearch_get_field_value($node, 'field_fullname');

  $surname_first_value = $surname_first && !$surname_first->isEmpty() ? $surname_first->value : FALSE;
  $lastname_value = $lastname && !$lastname->isEmpty() ? $lastname->value : '';
  $firstname_value = $firstname && !$firstname->isEmpty() ? $firstname->value : '';
  $fullname_value = $fullname && !$fullname->isEmpty() ? $fullname->value : '';

  $parts = [];

  if ($surname_first_value) {
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

  return !empty($parts) ? implode(' ', $parts) : t('Unknown');
}


/**
 * TYPE: journal_name
 * Combina field_journal e field_journal_latin con puntino catalano.
 */
function teasearch_render_field_journal_name($node, $field_config, $field_identifier)
{
  $journal = teasearch_get_field_value($node, 'field_journal');
  $journal_latin = teasearch_get_field_value($node, 'field_journal_latin');

  $journal_value = $journal && !$journal->isEmpty() ? $journal->value : '';
  $journal_latin_value = $journal_latin && !$journal_latin->isEmpty() ? $journal_latin->value : '';

  // Entrambi presenti: unisci con puntino catalano
  if (!empty($journal_value) && !empty($journal_latin_value)) {
    return $journal_value . ' · ' . $journal_latin_value;
  }

  // Solo uno dei due
  if (!empty($journal_value)) {
    return $journal_value;
  }

  if (!empty($journal_latin_value)) {
    return $journal_latin_value;
  }

  return NULL;
}

/**
 * TYPE: vol_issue
 * Combina field_volume e field_issues.
 */
function teasearch_render_field_vol_issue($node, $field_config, $field_identifier)
{
  $volume = teasearch_get_field_value($node, 'field_volume');
  $issues = teasearch_get_field_value($node, 'field_issues');

  $volume_value = $volume && !$volume->isEmpty() ? $volume->value : '';
  $issues_value = $issues && !$issues->isEmpty() ? $issues->value : '';

  $parts = [];

  if (!empty($volume_value)) {
    $parts[] = $volume_value;
  }

  if (!empty($issues_value)) {
    $parts[] = $issues_value;
  }

  return !empty($parts) ? implode(' ', $parts) : NULL;
}


/**
 * TYPE: curated_references
 * Renderizza field_curated_references (entity reference a Bibliography) secondo BOX-1 template.
 * 
 * BOX-1 template:
 * - Formato: AUTHOR_NAME + (YEAR) + TITLE
 * - Author name format:
 *   - if SURNAME_FIRST=true: SURNAME (LATIN) + GIVEN_NAME (LATIN) + FULL_NAME (ORIGINAL NON-LATIN)
 *   - else: SURNAME (LATIN) + comma + GIVEN_NAME (LATIN) + FULL_NAME (ORIGINAL NON-LATIN)
 * - Se SURNAME duplicato: aggiungi ", et al."
 * - Sorting: per SURNAME (LATIN) primo autore, poi per YEAR
 * - Display: hanging indent
 * - Linked to related pages
 */
function teasearch_render_field_curated_references($node, $field_config, $field_identifier)
{
  $field_name = $field_config['field'] ?? 'field_curated_references';
  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  $references_data = [];
  $seen_surnames = [];

  // Itera su ogni nodo Bibliography referenziato
  foreach ($field as $item) {
    if ($item->isEmpty()) {
      continue;
    }

    $bib_node = $item->entity;

    if (!$bib_node || !$bib_node->access('view')) {
      continue;
    }

    // Ottieni traduzione nella lingua corrente
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($bib_node->hasTranslation($current_language)) {
      $bib_node = $bib_node->getTranslation($current_language);
    }

    // Costruisci la citazione secondo BOX-1
    $citation_parts = [];

    // 1. AUTHOR NAME (dal primo autore di field_responsibles)
    $first_author_data = teasearch_get_first_author_for_citation($bib_node);
    if ($first_author_data) {
      $author_string = $first_author_data['formatted_name'];
      $surname_latin = $first_author_data['surname_latin'];

      // Check se il cognome è duplicato
      if (isset($seen_surnames[$surname_latin])) {
        $author_string .= ', et al.';
      } else {
        $seen_surnames[$surname_latin] = true;
      }

      $citation_parts[] = $author_string;
    }

    // 2. (YEAR)
    $year_field = $bib_node->get('field_year');
    $year = '9999';
    if (!$year_field->isEmpty()) {
      $year = $year_field->value;
      $citation_parts[] = '(' . $year . ')';
    }

    // 3. TITLE (main title + field_title_original)
    $title_parts = [];
    $main_title = $bib_node->getTitle();
    if (!empty($main_title)) {
      $title_parts[] = $main_title;
    }

    $title_original_field = $bib_node->get('field_title_original');
    if (!$title_original_field->isEmpty()) {
      $title_parts[] = $title_original_field->value;
    }

    $full_title = '';
    if (!empty($title_parts)) {
      $full_title = implode(' ', $title_parts);
      $citation_parts[] = $full_title;
    }

    // Costruisci la citazione completa (link)
    if (!empty($citation_parts)) {
      $citation_text = implode(' ', $citation_parts);

      // Link alla pagina del nodo
      $url = $bib_node->toUrl()->toString();
      $citation_html = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($citation_text) . '</a>';

      // 4. (Book/Article/etc.) FUORI dal link
      $pubtype_label = teasearch_get_bibliography_pubtype_label($bib_node, $current_language);
      if ($pubtype_label) {
        $citation_html .= ' <span class="citation-type">(' . htmlspecialchars($pubtype_label) . ')</span>';
      }

      // Salva per sorting
      $references_data[] = [
        'citation' => $citation_html,
        'surname' => $first_author_data['surname_latin'] ?? '',
        'year' => $year,
      ];
    }
  }

  if (empty($references_data)) {
    return NULL;
  }

  // Ordina per cognome, poi per anno
  usort($references_data, function ($a, $b) {
    $surname_compare = strcasecmp($a['surname'], $b['surname']);
    if ($surname_compare !== 0) {
      return $surname_compare;
    }
    return strcmp($a['year'], $b['year']);
  });

  // Estrai le citazioni ordinate
  $citations = array_map(function ($item) {
    return $item['citation'];
  }, $references_data);

  // Restituisci con <br> e aggiungi classe CSS per hanging indent
  return '<div>' . implode('<br>', $citations) . '</div>';
}

/**
 * Helper: Ottiene la label del pubtype per bibliography.
 */
function teasearch_get_bibliography_pubtype_label($bib_node, $language)
{
  if (!$bib_node->hasField('field_pubtype')) {
    return NULL;
  }

  $pubtype_field = $bib_node->get('field_pubtype');
  if ($pubtype_field->isEmpty()) {
    return NULL;
  }

  $pubtype_term = $pubtype_field->entity;
  if (!$pubtype_term) {
    return NULL;
  }

  // Ottieni la traduzione del termine se esiste
  if ($pubtype_term->hasTranslation($language)) {
    $pubtype_term = $pubtype_term->getTranslation($language);
  }

  return $pubtype_term->getName();
}

/**
 * Helper: Ottiene i dati del primo autore per la citazione BOX-1.
 * 
 * @return array|null
 *   Array con 'formatted_name' e 'surname_latin', oppure NULL
 */
function teasearch_get_first_author_for_citation($bib_node)
{
  if (!$bib_node->hasField('field_responsibles')) {
    return NULL;
  }

  $authors_field = $bib_node->get('field_responsibles');

  if ($authors_field->isEmpty()) {
    return NULL;
  }

  // Prendo il primo autore
  $first_author_item = $authors_field->first();
  if (!$first_author_item) {
    return NULL;
  }

  $paragraph = $first_author_item->entity;
  if (!$paragraph) {
    return NULL;
  }

  // Recupero i campi necessari
  $surname_first = $paragraph->get('field_surname_first')->value ?? FALSE;
  $lastname_latin = $paragraph->get('field_lastname')->value ?? '';
  $firstname_latin = $paragraph->get('field_firstname')->value ?? '';
  $fullname = $paragraph->get('field_fullname')->value ?? '';

  $parts = [];

  if ($surname_first) {
    // SURNAME_FIRST = true: SURNAME (LATIN) + GIVEN_NAME (LATIN) + FULL_NAME (ORIGINAL NON-LATIN)
    if (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    }
    if (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }
    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  } else {
    // SURNAME_FIRST = false: SURNAME (LATIN) + comma + GIVEN_NAME (LATIN) + FULL_NAME (ORIGINAL NON-LATIN)
    if (!empty($lastname_latin) && !empty($firstname_latin)) {
      $parts[] = $lastname_latin . ',';
      $parts[] = $firstname_latin;
    } elseif (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    } elseif (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }

    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  }

  if (empty($parts)) {
    return NULL;
  }

  return [
    'formatted_name' => implode(' ', $parts),
    'surname_latin' => $lastname_latin,
  ];
}



/**
 * TYPE: teasearch_references
 * Renderizza field_teasearch_references (entity reference multiplo) secondo BOX-4 template.
 * 
 * BOX-4 template:
 * - Identico a BOX-2, ma termina con + (Category) [category en/it in round brackets]
 * - Prima raggruppa per categoria (Essentials, Text, Images, Videos)
 * - Poi ordina all'interno di ogni categoria per TITLE, poi YEAR
 * 
 * BOX-2 template:
 * - TITLE + dash + AUTHOR_NAME + (PSEUDONYM) + YEAR + TITLE_LATIN + TITLE_ORIGINAL
 * - If SURNAME duplicated: + ", et al."
 * - Sorted by TITLE (LATIN) or TRANSLATED_TITLE, then by YEAR
 * 
 * Field references: Essentials, Images, Texts, Videos
 */
function teasearch_render_field_teasearch_references($node, $field_config, $field_identifier)
{
  $field_name = $field_config['field'] ?? 'field_teasearch_references';
  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return NULL;
  }

  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // Raggruppa per content type (categoria)
  $grouped_by_category = [
    'first_reference' => [], // Essentials
    'primary_sources' => [],  // Text
    'images' => [],           // Images
    'videos' => [],           // Videos
  ];

  $seen_surnames = [];

  // Itera su ogni nodo referenziato
  foreach ($field as $item) {
    if ($item->isEmpty()) {
      continue;
    }

    $ref_node = $item->entity;

    if (!$ref_node || !$ref_node->access('view')) {
      continue;
    }

    // Ottieni traduzione nella lingua corrente
    if ($ref_node->hasTranslation($current_language)) {
      $ref_node = $ref_node->getTranslation($current_language);
    }

    // Ottieni il content type
    $content_type = $ref_node->bundle();

    // Ignora se non è uno dei content type supportati
    if (!isset($grouped_by_category[$content_type])) {
      continue;
    }

    // Costruisci la citazione secondo BOX-2/BOX-4
    $citation_data = teasearch_build_box2_citation($ref_node, $content_type, $current_language, $seen_surnames, false); // ❗ false = no category nel link

    if ($citation_data) {
      // Aggiungi la categoria FUORI dal link
      $category_label = teasearch_get_category_label_for_box4($content_type, $current_language);
      $citation_data['citation'] .= ' <span class="citation-type">(' . htmlspecialchars($category_label) . ')</span>';

      $grouped_by_category[$content_type][] = $citation_data;
    }
  }

  // Ordina all'interno di ogni categoria per title, poi year
  foreach ($grouped_by_category as $content_type => &$items) {
    if (!empty($items)) {
      usort($items, function ($a, $b) {
        $title_compare = strcasecmp($a['title_sort'], $b['title_sort']);
        if ($title_compare !== 0) {
          return $title_compare;
        }
        return strcmp($a['year'], $b['year']);
      });
    }
  }
  unset($items);

  // Costruisci l'output finale con intestazioni di categoria
  $category_labels = [
    'first_reference' => [
      'en' => 'ESSENTIALS',
      'it' => 'FONDAMENTI',
    ],
    'primary_sources' => [
      'en' => 'TEXTS',
      'it' => 'TESTI',
    ],
    'images' => [
      'en' => 'IMAGES',
      'it' => 'IMMAGINI',
    ],
    'videos' => [
      'en' => 'VIDEOS',
      'it' => 'VIDEO',
    ],
  ];

  $output_sections = [];

  foreach ($grouped_by_category as $content_type => $items) {
    if (empty($items)) {
      continue;
    }

    // Intestazione categoria (maiuscolo, più piccola)
    $category_label = $category_labels[$content_type][$current_language] ?? $category_labels[$content_type]['en'];
    $section_html = '<div >';
    

    // Citazioni
    $citations = array_map(function ($item) {
      return $item['citation'];
    }, $items);

    $section_html .= '<div >' . implode('<br>', $citations) . '</div>';
    $section_html .= '</div>';

    $output_sections[] = $section_html;
  }

  if (empty($output_sections)) {
    return NULL;
  }

  return implode("\n", $output_sections);
}


/**
 * Helper: Costruisce una citazione secondo BOX-2 template.
 * 
 * @param object $node
 *   Il nodo da citare.
 * @param string $content_type
 *   Il content type del nodo.
 * @param string $current_language
 *   La lingua corrente.
 * @param array &$seen_surnames
 *   Array dei cognomi già visti (per ", et al.").
 * @param bool $add_category
 *   Se TRUE, aggiunge (Category) alla fine (BOX-4). Se FALSE, è BOX-2 puro.
 * 
 * @return array|null
 *   Array con 'citation', 'title_sort', 'year', oppure NULL.
 */
function teasearch_build_box2_citation($node, $content_type, $current_language, &$seen_surnames, $add_category = false)
{
  $citation_parts = [];

  // 1. TITLE
  // Priority: TITLE_(LATIN) se presente, altrimenti TRANSLATED_TITLE
  $title_latin = '';
  $title_original = '';
  $title_translated = '';

  // Per alcuni content types il titolo principale è in field_title_latin
  if ($node->hasField('field_title_latin') && !$node->get('field_title_latin')->isEmpty()) {
    $title_latin = $node->get('field_title_latin')->value;
  }

  // Titolo originale
  if ($node->hasField('field_title_original') && !$node->get('field_title_original')->isEmpty()) {
    $title_original = $node->get('field_title_original')->value;
  }

  // Titolo tradotto
  if ($node->hasField('field_title_translated') && !$node->get('field_title_translated')->isEmpty()) {
    $title_translated = $node->get('field_title_translated')->value;
  }

  // Logica BOX-2: if field TITLE_(LATIN) HAS VALUE: TITLE_(LATIN) + TITLE_(ORIG_NON-LATIN) + dash
  //                else: field TRANSLATED_TITLE + dash
  $title_for_display = '';
  $title_for_sort = '';

  if (!empty($title_latin)) {
    $title_parts_display = [$title_latin];
    if (!empty($title_original)) {
      $title_parts_display[] = $title_original;
    }
    $title_for_display = implode(' ', $title_parts_display);
    $title_for_sort = $title_latin;
  } elseif (!empty($title_translated)) {
    $title_for_display = $title_translated;
    $title_for_sort = $title_translated;
  } else {
    // Fallback: usa il titolo del nodo
    $title_for_display = $node->getTitle();
    $title_for_sort = $node->getTitle();
  }

  if (!empty($title_for_display)) {
    $citation_parts[] = $title_for_display;
  }

  // 2. AUTHOR NAME
  // Logica BOX-2:
  // if SURNAME_FIRST=true: SURNAME_(LATIN) + GIVEN_NAME_(LATIN) + FULL_NAME_(ORIG_NON-LATIN)
  // else: GIVEN_NAME_(LATIN) + SURNAME_(LATIN) + FULL_NAME_(ORIG_NON-LATIN)
  // + (PSEUDONYM)[in round brackets]
  // if SURNAME_(LATIN) is duplicated: + ", et al."
  $first_author_data = teasearch_get_first_author_for_box2_citation($node, $content_type);
  if ($first_author_data) {
    $author_string = $first_author_data['formatted_name'];
    $surname_latin = $first_author_data['surname_latin'];

    // Check se il cognome è duplicato
    if (isset($seen_surnames[$surname_latin])) {
      $author_string .= ', et al.';
    } else {
      $seen_surnames[$surname_latin] = true;
    }

    $citation_parts[] = $author_string;
  }

  // 3. YEAR
  $year_field_name = teasearch_get_year_field_for_content_type($content_type);
  $year = '9999'; // Default per sorting
  if ($node->hasField($year_field_name)) {
    $year_field = $node->get($year_field_name);
    if (!$year_field->isEmpty()) {
      $year = $year_field->value;
      $citation_parts[] = $year;
    }
  }

  // 4. TITLE_(LATIN) + TITLE_(ORIGINAL_NON-LATIN) (già inclusi nel punto 1)

  // 5. (Category) - solo per BOX-4
  if ($add_category) {
    $category_label = teasearch_get_category_label_for_box4($content_type, $current_language);
    $citation_parts[] = '(' . $category_label . ')';
  }

  if (empty($citation_parts)) {
    return NULL;
  }

  // Costruisci la citazione completa
  $citation_text = implode(' – ', $citation_parts);

  // Link alla pagina del nodo
  $url = $node->toUrl()->toString();
  $citation_html = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($citation_text) . '</a>';

  return [
    'citation' => $citation_html,
    'title_sort' => $title_for_sort,
    'year' => $year,
  ];
}

/**
 * Helper: Ottiene i dati del primo autore per la citazione BOX-2.
 * 
 * @return array|null
 *   Array con 'formatted_name' e 'surname_latin', oppure NULL.
 */
function teasearch_get_first_author_for_box2_citation($node, $content_type)
{
  // Mappa content_type -> campo paragraph autori
  $author_field_map = [
    'first_reference' => 'field_texts_authors',
    'images' => 'field_texts_authors',
    'primary_sources' => 'field_texts_authors',
    'videos' => 'field_creators',
  ];

  $author_field_name = $author_field_map[$content_type] ?? NULL;

  if (!$author_field_name || !$node->hasField($author_field_name)) {
    return NULL;
  }

  $authors_field = $node->get($author_field_name);

  if ($authors_field->isEmpty()) {
    return NULL;
  }

  // Prendo il primo autore
  $first_author_item = $authors_field->first();
  if (!$first_author_item) {
    return NULL;
  }

  $paragraph = $first_author_item->entity;
  if (!$paragraph) {
    return NULL;
  }

  // Recupero i campi necessari
  $surname_first = $paragraph->get('field_surname_first')->value ?? FALSE;

  // Cognome: prova field_lastname_latin, poi field_lastname
  $lastname_latin = '';
  if ($paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname')->isEmpty()) {
    $lastname_latin = $paragraph->get('field_lastname')->value;
  } elseif ($paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname')->isEmpty()) {
    $lastname_latin = $paragraph->get('field_lastname')->value;
  }

  // Nome: prova field_firstname_latin, poi field_firstname
  $firstname_latin = '';
  if ($paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname_latin')->isEmpty()) {
    $firstname_latin = $paragraph->get('field_firstname')->value;
  } elseif ($paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname')->isEmpty()) {
    $firstname_latin = $paragraph->get('field_firstname')->value;
  }

  $fullname = $paragraph->hasField('field_fullname') && !$paragraph->get('field_fullname')->isEmpty()
    ? $paragraph->get('field_fullname')->value
    : '';

  $pseudonym = $paragraph->hasField('field_pseudonym') && !$paragraph->get('field_pseudonym')->isEmpty()
    ? $paragraph->get('field_pseudonym')->value
    : '';

  $parts = [];

  if ($surname_first) {
    // SURNAME_FIRST = true: SURNAME_(LATIN) + GIVEN_NAME_(LATIN) + FULL_NAME_(ORIG_NON-LATIN)
    if (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    }
    if (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }
    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  } else {
    // SURNAME_FIRST = false: GIVEN_NAME_(LATIN) + SURNAME_(LATIN) + FULL_NAME_(ORIG_NON-LATIN)
    if (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }
    if (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    }
    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  }

  // Aggiungi PSEUDONYM in round brackets
  if (!empty($pseudonym)) {
    $parts[] = '(' . $pseudonym . ')';
  }

  if (empty($parts)) {
    return NULL;
  }

  return [
    'formatted_name' => implode(' ', $parts),
    'surname_latin' => $lastname_latin,
  ];
}


/**
 * Helper: Ottiene la label della categoria per BOX-4.
 */
function teasearch_get_category_label_for_box4($content_type, $language)
{
  $labels = [
    'first_reference' => [
      'en' => 'Essential',
      'it' => 'Fondamenti',
    ],
    'primary_sources' => [
      'en' => 'Text',
      'it' => 'Testi',
    ],
    'images' => [
      'en' => 'Image',
      'it' => 'Immagini',
    ],
    'videos' => [
      'en' => 'Video',
      'it' => 'Video',
    ],
  ];

  return $labels[$content_type][$language] ?? ucfirst($content_type);
}




/**
 * Helper: Costruisce una citazione secondo BOX-1/BOX-3 template (per bibliography).
 * 
 * BOX-1/BOX-3:
 * - AUTHOR_NAME + (YEAR) + TITLE
 * - Se $add_category = true, aggiunge (Bibliography) alla fine
 * 
 * @param object $node
 *   Il nodo bibliography da citare.
 * @param string $current_language
 *   La lingua corrente.
 * @param array &$seen_surnames
 *   Array dei cognomi già visti (per ", et al.").
 * @param bool $add_category
 *   Se TRUE, aggiunge (Category) alla fine (BOX-3).
 * 
 * @return array|null
 *   Array con 'citation', 'title_sort', 'year', oppure NULL.
 */
function teasearch_build_box1_citation($node, $current_language, &$seen_surnames, $add_category = false)
{
  $citation_parts = [];

  // 1. AUTHOR NAME (dal primo autore di field_responsibles)
  $first_author_data = teasearch_get_first_author_for_citation($node);
  if ($first_author_data) {
    $author_string = $first_author_data['formatted_name'];
    $surname_latin = $first_author_data['surname_latin'];

    // Check se il cognome è duplicato
    if (isset($seen_surnames[$surname_latin])) {
      $author_string .= ', et al.';
    } else {
      $seen_surnames[$surname_latin] = true;
    }

    $citation_parts[] = $author_string;
  }

  // 2. (YEAR)
  $year_field = $node->get('field_year');
  $year = '9999';
  if (!$year_field->isEmpty()) {
    $year = $year_field->value;
    $citation_parts[] = '(' . $year . ')';
  }

  // 3. TITLE (LATIN) + TITLE (ORIGINAL NON-LATIN)
  $title_latin_field = $node->get('field_pub_title_latin');
  $title_original_field = $node->get('field_pub_title');

  $title_parts = [];
  $title_for_sort = '';

  if (!$title_latin_field->isEmpty()) {
    $title_parts[] = $title_latin_field->value;
    $title_for_sort = $title_latin_field->value;
  }
  if (!$title_original_field->isEmpty()) {
    $title_parts[] = $title_original_field->value;
    if (empty($title_for_sort)) {
      $title_for_sort = $title_original_field->value;
    }
  }

  if (!empty($title_parts)) {
    $citation_parts[] = implode(' ', $title_parts);
  }

  // 4. (Category) - solo se richiesto (BOX-3)
  if ($add_category) {
    $category_label = $current_language === 'it' ? 'Bibliografia' : 'Bibliography';
    $citation_parts[] = '(' . $category_label . ')';
  }

  if (empty($citation_parts)) {
    return NULL;
  }

  // Costruisci la citazione completa
  $citation_text = implode(' ', $citation_parts);

  // Link alla pagina del nodo
  $url = $node->toUrl()->toString();
  $citation_html = '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($citation_text) . '</a>';

  return [
    'citation' => $citation_html,
    'title_sort' => $title_for_sort,
    'year' => $year,
  ];
}

/**
 * Helper: Ottiene il display name del content type per BOX-3.
 */
function teasearch_get_content_type_display_name($content_type)
{
  $map = [
    'first_reference' => 'Essential',
    'images' => 'Image',
    'primary_sources' => 'Text',
    'videos' => 'Video',
  ];

  return $map[$content_type] ?? ucfirst($content_type);
}

/**
 * Helper: Ottiene il nome del campo year per diversi content types.
 */
function teasearch_get_year_field_for_content_type($content_type)
{
  $map = [
    'first_reference' => 'field_year_from',
    'images' => 'field_year_from',
    'primary_sources' => 'field_year_from',
    'videos' => 'field_year_from',
    'bibliography' => 'field_year',
  ];

  return $map[$content_type] ?? 'field_year';
}

/**
 * Helper: Ottiene i dati del primo autore per la citazione BOX-3 (universal).
 * Supporta diversi content types con campi paragraph differenti.
 */
function teasearch_get_first_author_for_citation_universal($node, $content_type)
{
  // Mappa content_type -> campo paragraph autori
  $author_field_map = [
    'first_reference' => 'field_texts_authors',
    'images' => 'field_texts_authors',
    'primary_sources' => 'field_texts_authors',
    'videos' => 'field_creators',
    'bibliography' => 'field_responsibles',
  ];

  $author_field_name = $author_field_map[$content_type] ?? NULL;

  if (!$author_field_name || !$node->hasField($author_field_name)) {
    return NULL;
  }

  $authors_field = $node->get($author_field_name);

  if ($authors_field->isEmpty()) {
    return NULL;
  }

  // Prendo il primo autore
  $first_author_item = $authors_field->first();
  if (!$first_author_item) {
    return NULL;
  }

  $paragraph = $first_author_item->entity;
  if (!$paragraph) {
    return NULL;
  }

  // Recupero i campi necessari (con fallback per nomi diversi)
  $surname_first = $paragraph->get('field_surname_first')->value ?? FALSE;

  $lastname_latin = '';
  if ($paragraph->hasField('field_lastname') && !$paragraph->get('field_lastname_latin')->isEmpty()) {
    $lastname_latin = $paragraph->get('field_lastname')->value;
  } 

  // Nome: prova field_firstname_latin, poi field_firstname
  $firstname_latin = '';
  if ($paragraph->hasField('field_firstname_latin') && !$paragraph->get('field_firstname_latin')->isEmpty()) {
    $firstname_latin = $paragraph->get('field_firstname_latin')->value;
  } elseif ($paragraph->hasField('field_firstname') && !$paragraph->get('field_firstname')->isEmpty()) {
    $firstname_latin = $paragraph->get('field_firstname')->value;
  }

  $fullname = $paragraph->hasField('field_fullname') && !$paragraph->get('field_fullname')->isEmpty()
    ? $paragraph->get('field_fullname')->value
    : '';

  $parts = [];

  if ($surname_first) {
    // SURNAME_FIRST = true
    if (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    }
    if (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }
    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  } else {
    // SURNAME_FIRST = false
    if (!empty($lastname_latin) && !empty($firstname_latin)) {
      $parts[] = $lastname_latin . ',';
      $parts[] = $firstname_latin;
    } elseif (!empty($lastname_latin)) {
      $parts[] = $lastname_latin;
    } elseif (!empty($firstname_latin)) {
      $parts[] = $firstname_latin;
    }

    if (!empty($fullname)) {
      $parts[] = $fullname;
    }
  }

  if (empty($parts)) {
    return NULL;
  }

  return [
    'formatted_name' => implode(' ', $parts),
    'surname_latin' => $lastname_latin,
  ];
}

/**
 * TYPE: square_or_main
 * Renderizza l'icona per primary_sources (Texts) e Images.
 * Priorità: field_square_thumbnail, se vuoto usa field_main_image.
 * 
 * IMPORTANTE: Questa funzione non richiede 'field' nella configurazione.
 * Cerca automaticamente i campi field_square_thumbnail e field_main_image.
 */
function teasearch_render_field_square_or_main($node, $field_config, $field_identifier)
{
  // Prova prima field_square_thumbnail
  $square_thumb = teasearch_get_field_value($node, 'field_square_thumbnail');
  if ($square_thumb && !$square_thumb->isEmpty()) {
    return teasearch_render_image_field_square($square_thumb, 'Square thumbnail');
  }

  // Se vuoto, usa field_main_image
  $main_image = teasearch_get_field_value($node, 'field_main_image');
  if ($main_image && !$main_image->isEmpty()) {
    return teasearch_render_image_field_square($main_image, 'Main image');
  }

  return NULL;
}

/**
 * Helper: Renderizza un campo immagine QUADRATA/RETTANGOLARE (NON tonda).
 * Usa classe specifica 'detail-icon-image-square' invece di 'detail-icon-image'.
 */
function teasearch_render_image_field_square($field, $alt_text = 'Image')
{
  $image = $field->first();
  if (!$image || !$image->entity) {
    return NULL;
  }

  $file = $image->entity;
  $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
  $alt = $image->alt ?: $alt_text;

  // IMPORTANTE: Usa classe 'detail-icon-image-square' per evitare border-radius
  return '<img src="' . htmlspecialchars($image_url) . '" alt="' . htmlspecialchars($alt) . '" class="detail-icon-image-square" />';
}



/**
 * TYPE: texts_title
 * Renderizza il titolo per primary_sources (Texts).
 * 
 * Subtype 'main' (per maintitle nell'header verde):
 * - Se title NOT NULL: title + field_title_original
 * - Altrimenti: field_title_translated
 * 
 * Subtype 'title' (per il campo nella tabella):
 * - title + " – " + field_title_original
 * 
 * Note: field_title_translated è multilingua
 */
function teasearch_render_field_texts_title($node, $field_config, $field_identifier)
{
  $subtype = $field_config['subtype'] ?? 'main';

  // Ottieni i campi
  $title = $node->getTitle(); // Titolo principale del nodo
  $title_original_field = teasearch_get_field_value($node, 'field_title_original');
  $title_translated_field = teasearch_get_field_value($node, 'field_title_translated');

  $title_original = $title_original_field && !$title_original_field->isEmpty()
    ? $title_original_field->value
    : ''; 

  $title_translated = $title_translated_field && !$title_translated_field->isEmpty()
    ? $title_translated_field->value
    : '';

  if ($subtype === 'main') {
    // Logica per maintitle
    if (!empty($title)) {
      // title + field_title_original
      $parts = [$title];
      if (!empty($title_original)) {
        $parts[] = $title_original;
      }
      return implode(' ', $parts);
    } else {
      // field_title_translated
      return !empty($title_translated) ? $title_translated : NULL;
    }
  } elseif ($subtype === 'title') {
    // Logica per title nella tabella
    // title + " – " + field_title_original
    $parts = [];
    if (!empty($title)) {
      $parts[] = $title;
    }
    if (!empty($title_original)) {
      $parts[] = $title_original;
    }

    return !empty($parts) ? implode(' – ', $parts) : NULL;
  }

  return NULL;
}

function teasearch_render_field_bibliography_icon($node, $field_config, $field_identifier)
{
  $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // 1) Term del pubtype
  $pubtype = teasearch_get_field_value($node, 'field_pubtype');
  if (!$pubtype || $pubtype->isEmpty() || !$pubtype->entity) {
    return NULL;
  }
  $term = $pubtype->entity;
  if ($term->hasTranslation($lang)) {
    $term = $term->getTranslation($lang);
  }

  // 2) Nome icona dal term (es. "Article-chapter-general")
  $icon_name = '';
  if ($term->hasField('field_icon_name') && !$term->get('field_icon_name')->isEmpty()) {
    $icon_name = (string) $term->get('field_icon_name')->value;
  }
  if ($icon_name === '') {
    // Niente icona configurata → non rendere nulla (o potresti ritornare un placeholder).
    if (!empty($field_config['debug'])) {
      \Drupal::logger('teasearch_filter')->notice('BIB ICON: nessun field_icon_name sul term @tid.', ['@tid' => $term->id()]);
    }
    return NULL;
  }

  // 3) Path nel tema attivo
  $active_theme_path = \Drupal::theme()->getActiveTheme()->getPath(); // es. 'themes/custom/teasearch'
  $icons_dir_rel    = $active_theme_path . '/images/bibicons';
  $icon_file        = $icon_name . '.png';

  // 4) Verifica esistenza file e fallback a default.png
  $fs_icon  = DRUPAL_ROOT . '/' . $icons_dir_rel . '/' . $icon_file;
  $fs_def   = DRUPAL_ROOT . '/' . $icons_dir_rel . '/default.png';
  $use_file = file_exists($fs_icon) ? $icon_file : (file_exists($fs_def) ? 'default.png' : $icon_file);

  $web_icon = base_path() . $icons_dir_rel . '/' . $use_file;

  // 5) Placeholder finale lato client (se anche il web path fallisce)
  $placeholder = base_path() . 'modules/custom/teasearch_filter/images/placeholder.png';

  if (!empty($field_config['debug'])) {
    \Drupal::logger('teasearch_filter')->notice(
      'BIB ICON DEBUG → term: @tid (@name) | icon_name: @icon | fs: @fs | exists: @ex | web: @web',
      [
        '@tid'  => $term->id(),
        '@name' => $term->label(),
        '@icon' => $icon_name,
        '@fs'   => $fs_icon,
        '@ex'   => file_exists($fs_icon) ? 'YES' : 'NO',
        '@web'  => $web_icon,
      ]
    );
  }

  // 6) Output <img>
  $alt   = 'Bibliography icon';
  $title = 'Using: bibliography_icon';

  return '<img src="' . htmlspecialchars($web_icon, ENT_QUOTES, 'UTF-8') . '"'
    . ' alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '"'
    . ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"'
    . ' class="entity-image bibliography-icon rounded" width="96" height="96" loading="lazy"'
    . ' onerror="this.src=\'' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '\'; this.onerror=null;"'
    . ' data-field-used="bibliography_icon">';
}




function teasearch_format_single_author_from_general($paragraph, $language)
{
  // Recupera i campi dal paragraph
  $is_org = $paragraph->get('field_is_org')->value ?? false;
  $surname_first = $paragraph->get('field_surname_first')->value ?? false;
  $lastname = $paragraph->get('field_lastname')->value ?? '';
  $firstname = $paragraph->get('field_firstname')->value ?? '';
  $fullname = $paragraph->get('field_fullname')->value ?? '';
  $pseudonym = $paragraph->get('field_pseudonym')->value ?? '';
  $dates = $paragraph->get('field_dates')->value ?? '';

  $parts = [];
  $middle_dot = ' · '; // U+00B7

  // ========================================================================
  // CASO 1: ORGANIZZAZIONE
  // ========================================================================
  if ($is_org) {
    // Formato: SURNAME_(LATIN) · FULL_NAME_(ORIG_NON_LATIN)
    if (!empty($lastname)) {
      $parts[] = $lastname;
    }
    if (!empty($fullname)) {
      if (!empty($parts)) {
        $parts[] = $middle_dot . $fullname;
      } else {
        $parts[] = $fullname;
      }
    }

    // Per organizzazioni: NO pseudonym, NO dates
    if (empty($parts)) {
      return null;
    }

    return implode('', $parts);
  }

  // ========================================================================
  // CASO 2: PERSONA
  // ========================================================================

  // Costruisce la parte "nome latino"
  $latin_name = '';

  if ($surname_first) {
    // SURNAME_FIRST = true
    // Formato: GIVEN_NAME_(LATIN) + SURNAME_(LATIN) [CAPS]
    if (!empty($firstname)) {
      $latin_name .= $firstname . ' ';
    }
    if (!empty($lastname)) {
      $latin_name .= mb_strtoupper($lastname, 'UTF-8');
    }
  } else {
    // SURNAME_FIRST = false
    // Formato: SURNAME_(LATIN) [CAPS] + GIVEN_NAME_(LATIN)
    if (!empty($lastname)) {
      $latin_name .= mb_strtoupper($lastname, 'UTF-8');
    }
    if (!empty($firstname)) {
      $latin_name .= ' ' . $firstname;
    }
  }

  // Trim spazi extra
  $latin_name = trim($latin_name);

  // Aggiungi nome latino se presente
  if (!empty($latin_name)) {
    $parts[] = $latin_name;
  }

  // Aggiungi fullname (nome originale) se presente
  if (!empty($fullname)) {
    if (!empty($parts)) {
      $parts[] = $middle_dot . $fullname;
    } else {
      $parts[] = $fullname;
    }
  }

  // Se non abbiamo né nome latino né fullname, non possiamo formattare
  if (empty($parts)) {
    return null;
  }

  // Costruisci stringa base
  $result = implode('', $parts);

  // Aggiungi PSEUDONYM in parentesi se presente
  if (!empty($pseudonym)) {
    $result .= ' (' . $pseudonym . ')';
  }

  // Aggiungi DATE in parentesi se presente
  if (!empty($dates)) {
    $result .= ' (' . $dates . ')';
  }

  return $result;
}

// ============================================================================
// TYPE: authors_general
// ============================================================================

/**
 * TYPE: authors_general
 * Renderizza lista autori da paragraph unificato authors_general.
 * 
 * WORKFLOW:
 * 
 * 1. Controlla field_anonymous (a livello nodo):
 *    - Se true → return "Anonimo" / "Anonymous"
 * 
 * 2. Itera sui paragraph nel campo specificato (es. field_responsibles):
 *    - Per ogni paragraph, formatta l'autore usando teasearch_format_single_author_from_general()
 * 
 * 3. Controlla field_attributed_authorship (a livello nodo):
 *    - Se true → aggiungi "(Attribuito a)" / "(Attributed to)" dopo OGNI autore
 * 
 * 4. Join tutti gli autori con ", " (virgola spazio)
 * 
 * USO NELLO SCHEMA:
 * 
 * 'authors' => [
 *   'type' => 'authors_general',
 *   'field' => 'field_responsibles',
 *   'label' => 'Authors',
 * ],
 * 
 * 'editors' => [
 *   'type' => 'authors_general',
 *   'field' => 'field_editors',
 *   'label' => 'Editors',
 * ],
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo
 * @param array $field_config
 *   La configurazione del campo dallo schema
 * @param string $field_identifier
 *   L'identificativo del campo (nome del campo paragraph, es. 'field_responsibles')
 * 
 * @return string|null
 *   La lista di autori formattata, NULL se nessun autore
 */
function teasearch_render_field_authors_general($node, $field_config, $field_identifier)
{
  $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

  // ========================================================================
  // STEP 1: Controlla field_anonymous (a livello nodo)
  // ========================================================================
  if ($node->hasField('field_anonymous')) {
    $anonymous_field = $node->get('field_anonymous');
    if (!$anonymous_field->isEmpty() && $anonymous_field->value) {
      // Return "Anonimo" / "Anonymous" in base alla lingua
      return $current_language === 'it' ? 'Anonimo' : 'Anonymous';
    }
  }

  // ========================================================================
  // STEP 2: Recupera il campo paragraph (es. field_responsibles)
  // ========================================================================
  $field_name = $field_config['field'] ?? $field_identifier;
  $authors_field = teasearch_get_field_value($node, $field_name);

  if (!$authors_field || $authors_field->isEmpty()) {
    return null;
  }

  // ========================================================================
  // STEP 3: Controlla field_attributed_authorship (a livello nodo)
  // ========================================================================
  $is_attributed = false;
  if ($node->hasField('field_attributed_authorship')) {
    $attributed_field = $node->get('field_attributed_authorship');
    if (!$attributed_field->isEmpty() && $attributed_field->value) {
      $is_attributed = true;
    }
  }

  // Testo "(Attributed to)" / "(Attribuito a)"
  $attributed_text = $current_language === 'it' ? ' (Attribuito a)' : ' (Attributed to)';

  // ========================================================================
  // STEP 4: Itera sui paragraph e formatta ogni autore
  // ========================================================================
  $formatted_authors = [];

  foreach ($authors_field as $item) {
    if ($item->isEmpty()) {
      continue;
    }

    $paragraph = $item->entity;
    if (!$paragraph) {
      continue;
    }

    // Formatta l'autore usando l'helper
    $formatted_author = teasearch_format_single_author_from_general($paragraph, $current_language);

    if ($formatted_author) {
      // Se attributed = true, aggiungi il testo dopo ogni autore
      if ($is_attributed) {
        $formatted_author .= $attributed_text;
      }

      $formatted_authors[] = $formatted_author;
    }
  }

  // ========================================================================
  // STEP 5: Join con ", " e return
  // ========================================================================
  if (empty($formatted_authors)) {
    return null;
  }

  return implode(', ', $formatted_authors);
}

// ============================================================================
// TYPE: unified_title
// ============================================================================

/**
 * TYPE: unified_title
 * Renderizza titoli unificati per tutti i content types.
 * 
 * Campi utilizzati:
 * - title (titolo del nodo)
 * - field_title_original
 * - field_title_translated (multilingua)
 * 
 * LOGICA:
 * 
 * Subtype 'main' (per maintitle nell'header verde):
 * - Se title NOT NULL: title + field_title_original
 * - Altrimenti: field_title_translated
 * 
 * Subtype 'row' (per il campo nella tabella):
 * - title + " – " + field_title_original
 * 
 * USO NELLO SCHEMA:
 * 
 * 'maintitle' => [
 *   'type' => 'unified_title',
 *   'options' => [
 *     'subtype' => 'main',
 *   ],
 * ],
 * 
 * 'title' => [
 *   'type' => 'unified_title',
 *   'label' => 'Title',
 *   'options' => [
 *     'subtype' => 'row',
 *   ],
 * ],
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo
 * @param array $field_config
 *   La configurazione del campo dallo schema
 * @param string $field_identifier
 *   L'identificativo del campo
 * 
 * @return string|null
 *   Il titolo formattato, NULL se nessun dato disponibile
 */
function teasearch_render_field_unified_title($node, $field_config, $field_identifier)
{
  $subtype = $field_config['options']['subtype'] ?? 'base';
  $separator = ' &middot; '; // Puntino catalano (U+00B7)

  // ========================================================================
  // STEP 1: Recupera i campi (tutti opzionali)
  // ========================================================================
  $title = trim($node->getTitle() ?? '');

  $title_original_field = teasearch_get_field_value($node, 'field_title_original');
  $title_original = $title_original_field && !$title_original_field->isEmpty()
    ? trim($title_original_field->value)
    : '';

  $title_translated_field = teasearch_get_field_value($node, 'field_title_translated');
  $title_translated = $title_translated_field && !$title_translated_field->isEmpty()
    ? trim($title_translated_field->value)
    : '';

  // ========================================================================
  // STEP 2: Costruisci l'array di parts
  // ========================================================================
  $parts = [];

  // Aggiungi title se presente
  if (!empty($title)) {
    $parts[] = $title;
  }

  // Aggiungi field_title_original se presente
  if (!empty($title_original)) {
    $parts[] = $title_original;
  }

  // ========================================================================
  // STEP 3: Gestisci field_title_translated
  // ========================================================================
  if (!empty($title_translated)) {
    // Se ci sono altri campi prima, aggiungi con []
    // Altrimenti aggiungi senza []
    if (count($parts) > 0) {
      $parts[] = '[' . $title_translated . ']';
    } else {
      $parts[] = $title_translated;
    }
  }

  // ========================================================================
  // STEP 4: Dates
  // ========================================================================

  $field_year_range = teasearch_get_field_value($node, 'field_year_range');

  $century_label = '';
  if ($field_year_range && !$field_year_range->isEmpty()) {
    $first_item = $field_year_range->first();
    if ($first_item && isset($first_item->century_label)) {
      $century_label = $first_item->century_label;
      $parts[] = $century_label;
    }
  }

  // ========================================================================
  // STEP 5: Join e return
  // ========================================================================
  if (empty($parts)) {
    return null;
  }

  // Per ora 'base' e 'main' hanno lo stesso comportamento
  return implode($separator, $parts);
}

function teasearch_render_field_date_label($node, $field_config, $field_identifier)
{
  // Recupera il campo field_year_range
  $field_year_range = teasearch_get_field_value($node, 'field_year_range');

  // Estrai century_label se presente
  if ($field_year_range && !$field_year_range->isEmpty()) {
    $first_item = $field_year_range->first();
    if ($first_item && isset($first_item->century_label)) {
      return $first_item->century_label;
    }
  }

  return NULL;
}



/**
 * TYPE: image
 * Renderizza un'immagine con lightbox popup.
 * 
 * OPZIONI:
 * - 'icon' (bool): Se true, mostra icona lente in basso a destra
 * - 'field' (string): Campo immagine da usare (default: field_main_image)
 * 
 * FUNZIONALITÀ:
 * - Click sull'immagine → apre popup lightbox full-size
 * - Usa il sistema teasearch-lightbox esistente
 * 
 * USO NELLO SCHEMA:
 * 
 * 'image' => [
 *   'type' => 'image',
 *   'label' => 'Image',
 *   'options' => [
 *     'icon' => true,  // Mostra lente
 *     'field' => 'field_main_image',  // Campo immagine (opzionale)
 *   ],
 * ],
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo
 * @param array $field_config
 *   La configurazione del campo dallo schema
 * @param string $field_identifier
 *   L'identificativo del campo
 * 
 * @return string|null
 *   L'HTML dell'immagine con lightbox, NULL se nessuna immagine
 */

function teasearch_render_field_image($node, $field_config, $field_identifier)
{
  // Recupera il campo immagine principale (thumbnail)
  $field_name = $field_config['options']['field'] ?? $field_config['field'] ?? 'field_main_image';
  $large_field_name = $field_config['options']['large_field'] ?? 'field_large_image';
  $show_icon = $field_config['options']['icon'] ?? false;

  $image_field = teasearch_get_field_value($node, $field_name);

  if (!$image_field || $image_field->isEmpty()) {
    return NULL;
  }

  // Ottieni il file immagine principale (thumbnail)
  $image_item = $image_field->first();
  if (!$image_item || !$image_item->entity) {
    return NULL;
  }

  $file = $image_item->entity;
  $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
  $alt = $image_item->alt ?: 'Image';

  // ========================================================================
  // Controlla se esiste field_large_image per il lightbox
  // ========================================================================
  $large_image_url = NULL;
  $large_field = teasearch_get_field_value($node, $large_field_name);

  if ($large_field && !$large_field->isEmpty()) {
    $large_item = $large_field->first();
    if ($large_item && $large_item->entity) {
      $large_file = $large_item->entity;
      $large_image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($large_file->getFileUri());
    }
  }

  // Se non esiste large image, usa la main image anche per il lightbox
  if (empty($large_image_url)) {
    $large_image_url = $image_url;
  }

  // ========================================================================
  // Costruisci HTML con lightbox
  // ========================================================================
  $html = '<div class="detail-image-container" data-teasearch-lightbox';

  // Aggiungi attributo data-large-src se diverso dalla thumbnail
  if ($large_image_url !== $image_url) {
    $html .= ' data-large-src="' . htmlspecialchars($large_image_url) . '"';
  }

  $html .= '>';
  $html .= '<img src="' . htmlspecialchars($image_url) . '" ';
  $html .= 'alt="' . htmlspecialchars($alt) . '" ';
  $html .= 'class="detail-image img-fluid" />';

  // Aggiungi icona lente se richiesta
  if ($show_icon) {
    $theme_path = \Drupal::service('extension.list.theme')->getPath('teasearch');
    $lente_url = base_path() . $theme_path . '/images/lente.png';

    $html .= '<div class="detail-image-icon"><img  src="'.$lente_url.'"></div>';
  }

  $html .= '</div>';

  // Allega la libreria lightbox
  $attach_library = [
    '#attached' => [
      'library' => ['teasearch/image-lightbox'],
    ],
  ];

  return [
    '#markup' => $html,
    '#attached' => $attach_library['#attached'],
  ];
}



// ============================================================================
// TYPE: video
// ============================================================================

/**
 * TYPE: video
 * Renderizza un video YouTube con thumbnail e play button.
 * 
 * OPZIONI:
 * - 'mode' (string): 'inline' (default) o 'popup'
 * - 'thumbnail_field' (string): Campo per thumbnail (default: field_main_image)
 * - 'code_field' (string): Campo codice YouTube (default: field_youtube_code)
 * 
 * FUNZIONALITÀ:
 * - Mostra thumbnail da field_main_image croppata 16:9
 * - Overlay play button 50x50 centrato con 80% opacity
 * - field_youtube_code contiene solo il codice (es. "dQw4w9WgXcQ")
 * - Mode 'inline': mostra iframe YouTube nel campo
 * - Mode 'popup': apre modal con iframe YouTube
 * 
 * USO NELLO SCHEMA:
 * 
 * 'video' => [
 *   'type' => 'video',
 *   'label' => 'Video',
 *   'options' => [
 *     'mode' => 'inline',  // o 'popup'
 *   ],
 * ],
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo
 * @param array $field_config
 *   La configurazione del campo dallo schema
 * @param string $field_identifier
 *   L'identificativo del campo
 * 
 * @return string|null
 *   L'HTML del video player, NULL se nessun video
 */
function teasearch_render_field_video($node, $field_config, $field_identifier)
{
  $svg = '<svg width="68" height="48" viewBox="0 0 68 48">';
  $svg .= '<path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"/>';
  $svg .= '<path d="M 45,24 27,14 27,34" fill="#fff"/>';
  $svg .= '</svg>';

  // Recupera le opzioni
  $mode = $field_config['options']['mode'] ?? 'inline';
  $thumbnail_field_name = $field_config['options']['thumbnail_field'] ?? 'field_main_image';
  $code_field_name = $field_config['options']['code_field'] ?? 'field_youtube_code';

  // Recupera il codice YouTube
  $youtube_code_field = teasearch_get_field_value($node, $code_field_name);
  if (!$youtube_code_field || $youtube_code_field->isEmpty()) {
    return NULL;
  }

  $youtube_code = trim($youtube_code_field->value);
  if (empty($youtube_code)) {
    return NULL;
  }

  // Recupera la thumbnail
  $thumbnail_field = teasearch_get_field_value($node, $thumbnail_field_name);
  $thumbnail_url = '';

  if ($thumbnail_field && !$thumbnail_field->isEmpty()) {
    $thumbnail_item = $thumbnail_field->first();
    if ($thumbnail_item && $thumbnail_item->entity) {
      $file = $thumbnail_item->entity;
      $thumbnail_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }
  }

  // Fallback: usa thumbnail di YouTube
  if (empty($thumbnail_url)) {
    $thumbnail_url = 'https://img.youtube.com/vi/' . $youtube_code . '/maxresdefault.jpg';
  }

  // URL iframe YouTube
  $youtube_embed_url = 'https://www.youtube.com/embed/' . $youtube_code . '?autoplay=1';

  // ID univoco per il video
  $video_id = 'video-' . $node->id() . '-' . md5($youtube_code);

  // ========================================================================
  // MODE: INLINE
  // ========================================================================


  if ($mode === 'inline') {
    $html = '<div class="detail-video-container detail-video-inline" id="' . $video_id . '">';
    
    // Thumbnail con play button
    $html .= '<div class="detail-video-thumbnail" data-video-id="' . $video_id . '" data-youtube-code="' . htmlspecialchars($youtube_code) . '">';
    $html .= '<img src="' . htmlspecialchars($thumbnail_url) . '" alt="Video thumbnail" class="detail-video-thumbnail-img" />';
    $html .= '<div class="detail-video-play-button">' . $svg . '</div>';
    $html .= '</div>';
    
    // Placeholder per iframe (nascosto inizialmente)
    $html .= '<div class="detail-video-player detail-video-hidden" ></div>';
    
    $html .= '</div>';

    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => ['teasearch/video-player'],
      ],
    ];
  }

  // ========================================================================
  // MODE: POPUP
  // ========================================================================
  else {
    $html = '<div class="detail-video-container detail-video-popup" id="' . $video_id . '">';
    
    // Thumbnail con play button
    $html .= '<div class="detail-video-thumbnail" data-video-popup="' . $video_id . '" data-youtube-code="' . htmlspecialchars($youtube_code) . '">';
    $html .= '<img src="' . htmlspecialchars($thumbnail_url) . '" alt="Video thumbnail" class="detail-video-thumbnail-img" />';
    $html .= '<div class="detail-video-play-button">';
    $html .= '<svg width="50" height="50" viewBox="0 0 68 48" fill="none">';
    $html .= '<path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path>';
    $html .= '<path d="M 45,24 27,14 27,34" fill="#fff"></path>';
    $html .= '</svg>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';

    return [
      '#markup' => $html,
      '#attached' => [
        'library' => ['teasearch/video-player'],
      ],
    ];
  }
}


// ============================================================================
// TYPE: pdf
// ============================================================================

/**
 * TYPE: pdf
 * Renderizza un'immagine con overlay icona PDF e link al file PDF.
 * 
 * FUNZIONALITÀ:
 * - Mostra field_main_image come thumbnail
 * - Overlay icona PDF 50x50 in basso a sinistra con 80% opacità
 * - Click apre PDF in nuovo tab
 * - Se field_portion_of_work ha valore:
 *   - 'selected' → mostra "(Selected pages)" / "(Pagine selezionate)"
 *   - 'excerpt' → mostra "(Excerpt)" / "(Estratto)"
 * 
 * OPZIONI:
 * - 'file' (string): Nome del campo che contiene il file PDF (REQUIRED)
 * - 'image_field' (string): Campo immagine (default: field_main_image)
 * - 'portion_field' (string): Campo portion (default: field_portion_of_work)
 * 
 * USO NELLO SCHEMA:
 * 
 * 'pdf' => [
 *   'label' => 'PDF',
 *   'type' => 'pdf',
 *   'file' => 'field_pdf_file',  // REQUIRED
 * ],
 * 
 * @param \Drupal\node\Entity\Node $node
 *   Il nodo
 * @param array $field_config
 *   La configurazione del campo dallo schema
 * @param string $field_identifier
 *   L'identificativo del campo
 * 
 * @return array|null
 *   Render array con HTML e librerie, NULL se manca PDF o immagine
 */
function teasearch_render_field_pdf($node, $field_config, $field_identifier)
{
  // Recupera i nomi dei campi dalle opzioni
  $pdf_field_name = $field_config['file'] ?? null;
  $image_field_name = $field_config['image_field'] ?? 'field_main_image';
  $portion_field_name = $field_config['portion_field'] ?? 'field_portion_of_work';

  // REQUIRED: il campo PDF deve essere specificato
  if (empty($pdf_field_name)) {
    \Drupal::logger('teasearch')->error('PDF field type requires "file" option');
    return NULL;
  }

  // ========================================================================
  // Recupera il file PDF
  // ========================================================================
  $pdf_field = teasearch_get_field_value($node, $pdf_field_name);
  if (!$pdf_field || $pdf_field->isEmpty()) {
    return NULL;
  }

  $pdf_item = $pdf_field->first();
  if (!$pdf_item || !$pdf_item->entity) {
    return NULL;
  }

  $pdf_file = $pdf_item->entity;
  $pdf_url = \Drupal::service('file_url_generator')->generateAbsoluteString($pdf_file->getFileUri());

  // ========================================================================
  // Recupera l'immagine thumbnail
  // ========================================================================
  $image_field = teasearch_get_field_value($node, $image_field_name);
  if (!$image_field || $image_field->isEmpty()) {
    return NULL;
  }

  $image_item = $image_field->first();
  if (!$image_item || !$image_item->entity) {
    return NULL;
  }

  $image_file = $image_item->entity;
  $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_file->getFileUri());
  $image_alt = $image_item->alt ?: 'PDF Document';

  // ========================================================================
  // Recupera field_portion_of_work (opzionale)
  // ========================================================================
  $portion_text = '';
  $portion_field = teasearch_get_field_value($node, $portion_field_name);

  if ($portion_field && !$portion_field->isEmpty()) {
    $portion_value = strtolower(trim($portion_field->value));
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    if ($portion_value === 'selected') {
      $portion_text = $current_language === 'it'
        ? '(Pagine selezionate)'
        : '(Selected pages)';
    } elseif ($portion_value === 'excerpt') {
      $portion_text = $current_language === 'it'
        ? '(Estratto)'
        : '(Excerpt)';
    }
  }

  // ========================================================================
  // Icona PDF SVG
  // ========================================================================
  $pdf_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="50" height="50">
    <path fill="#82c341" d="M369.9 97.9L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM332.1 128H256V51.9l76.1 76.1zM48 464V48h160v104c0 13.3 10.7 24 24 24h104v288H48zm250.2-143.7c-12.2-12-47-8.7-64.4-6.5-17.2-10.5-28.7-25-36.8-46.3 3.9-16.1 10.1-40.6 5.4-56-4.2-26.2-37.8-23.6-42.6-5.9-4.4 16.1-.4 38.5 7 67.1-10 23.9-24.9 56-35.4 74.4-20 10.3-47 26.2-51 46.2-3.3 15.8 26 55.2 76.1-31.2 22.4-7.4 46.8-16.5 68.4-20.1 18.9 10.2 41 17 55.8 17 25.5 0 28-28.2 17.5-38.7zm-198.1 77.8c5.1-13.7 24.5-29.5 30.4-35-19 30.3-24.2 31.8-30.4 35zm81.6-190.6c7.4 0 6.7 32.1 1.8 40.8-4.4-13.9-4.3-40.8-1.8-40.8zm-24.4 136.6c9.7-16.9 18-37 24.7-54.7 8.3 15.1 18.9 27.2 30.1 35.5-20.8 4.3-38.9 13.1-54.8 19.2zm131.6-5s-5 6-37.3-7.8c35.1-2.6 40.9 5.4 37.3 7.8z"/>
  </svg>';

  // ========================================================================
  // Costruisce HTML
  // ========================================================================
  $html = '<div class="detail-pdf-wrapper">';
  $html.= '<div class="detail-pdf-container">';

  // Link wrapper che apre il PDF
  $html .= '<a href="' . htmlspecialchars($pdf_url) . '" target="_blank" rel="noopener" class="detail-pdf-link">';

  // Immagine
  $html .= '<div class="detail-pdf-image-wrapper">';
  $html .= '<img src="' . htmlspecialchars($image_url) . '" ';
  $html .= 'alt="' . htmlspecialchars($image_alt) . '" ';
  $html .= 'class="detail-pdf-image img-fluid" />';

  // Icona PDF overlay
  $html .= '<div class="detail-pdf-icon">';
  $html .= $pdf_icon_svg;
  $html .= '</div>';

  $html .= '</div>'; // close detail-pdf-image-wrapper

  $html .= '</a>'; // close link

  // Testo portion (se presente)
  if (!empty($portion_text)) {
    $html .= '<div class="detail-pdf-portion-text">' . htmlspecialchars($portion_text) . '</div>';
  }

  $html .= '</div>'; // close detail-pdf-container
  $html .= '</div>'; // close detail-pdf-wrapper

  // ========================================================================
  // CSS inline per lo styling
  // ========================================================================

  return [
    '#markup' => Markup::create( $html),
  ];
}