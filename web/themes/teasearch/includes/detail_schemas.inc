<?php

/**
 * @file
 * Schema di configurazione per le schede di dettaglio dei content type.
 *
 * Questo file definisce come devono essere renderizzati i campi nelle pagine di dettaglio.
 * 
 * STRUTTURA DELLO SCHEMA:
 * 
 * [
 *   'machine_name_content_type' => [
 *     'icon' => [
 *       'type' => 'tipo_renderer',     // Type che definisce come renderizzare l'icona
 *       'field' => 'field_name',       // Campo da cui prendere l'immagine
 *       'options' => [],               // Opzioni aggiuntive per il type
 *     ],
 *     'fields' => [
 *       'identificativo_campo' => [    // Può essere un field_name o un identificativo custom
 *         'type' => 'tipo_renderer',   // Type che definisce come renderizzare
 *         'field' => 'field_name',     // Campo principale (opzionale, alcuni type lo richiedono)
 *         'label' => 'Etichetta',      // Label da mostrare (opzionale)
 *         'wrapper_class' => 'class',  // Classe CSS per il wrapper (opzionale)
 *         'options' => [               // Opzioni specifiche per il type (opzionale)
 *           'campo_aggiuntivo' => 'field_name',
 *         ],
 *       ],
 *     ],
 *   ],
 * ]
 *
 * ORDINE DI VISUALIZZAZIONE:
 * L'ordine dei campi nell'array determina l'ordine di visualizzazione.
 * Non serve 'weight', basta spostare le righe su/giù nello schema.
 *
 * TYPE DISPONIBILI:
 * 
 * TYPE SEMPLICI (mostrano il valore del campo):
 * - 'text': Mostra il contenuto del campo (plain text o rich text)
 * - 'taxonomy': Mostra i termini della tassonomia separati da virgola (multilingua)
 * - 'media_image': Mostra un'immagine da campo media
 * - 'media_image_round': Mostra un'immagine tonda (per icone/avatar)
 * 
 * TYPE COMPLESSI (eseguono logica custom):
 * - 'people_main_title': Logica specifica per il titolo principale di people
 * - 'people_name': Logica specifica per il nome di people
 * - 'video': Mostra immagine con overlay play button e modal video
 * - 'record_data': Mostra data creazione e ultimo aggiornamento
 * 
 * Per aggiungere nuovi type, definire la funzione render in detail_field_types.inc
 *
 * MULTILINGUA:
 * I campi vengono automaticamente recuperati nella lingua corrente del nodo.
 *
 * LABEL:
 * - Se 'label' non è specificato, non viene mostrata alcuna etichetta
 * - Se 'label' è una stringa, viene usata quella (con traduzione t())
 * - Per label dinamiche, creare un type custom
 */

function teasearch_translations()
{
  $people = [
    "it" => [
      "paragraph1" => "Profilo",
      "paragraph2" => "Contatti",
      "paragraph3" => "Pubblicazioni relative al tè",
      "paragraph4" => "Contributi a Teasearch",
      "paragraph5" => "Dettagli",
      "name" => "Nome",
      "field_known_as" => "Altri nomi",
      "field_title" => "Posizione",
      "field_affiliation" => "Affiliazione",
      "field_orcid" => "ORCID",
      "field_bio" => "Presentazione",
      "field_activities" => "Attività legate al tè",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",
      "field_specific_subjects" => "Argomenti di ricerca",
      "field_language" => "Lingue",
      "field_memberships" => "Membership",
      "location" => "Sede",
      "field_country" => "Paese",
      "field_email" => "Email",
      "field_online_links" => "Presenza oline",
      "books" => "Libri",
      "book_chapters" => "Capitoli di libri",
      "journal_articles" => "Articoli di rivista",
      "dissertation" => "Tesi",
      "book_review" => "Recensioni di libro",
      "reviews" => "Recensioni",
      "conference_proceedings" => "Atti di convegno",
      "patents" => "Brevetti",
      "legal_and_policy" => "Documenti legali e normativi",
      "standard" => "Standard",
      "datasets" => "Datasets",
      "periodicals" => "Testate di periodici",
      "newspaper_articles" => "Article di giornale",
      "webpages" => "Pagine web / contenuti online",
      "reference_works" => "Opere di consultazione",
      "other_documents" => "Altri documenti",
      "essentials" => "Fondamenti",
      "texts" => "Testi",
      "videos" => "Video",
      "images" => "Immagini",
      "bibliography" => "Bibliografia",
      "field_license" => "Licenza editoriale",
      "systemdate" => "Date",
    ],
  ];

  $bibliography = [
    "it" => [
      "field_pubtype" => "Tipo",
      "authors" => "Autori",
      "title" => "Titolo",
      "field_title_original" => "Titolo originale",
      "field_title_translated" => "Titolo tradotto",
      "field_year" => "Anno",
      "field_date" => "Data",
      "field_language" => "Lingua",
      "contained_in" => "Contenuto in",
      "field_pub_place" => "Luogo di pubblicazione",
      "field_publisher" => "Editore",
      "field_country" => "Paese",
      "editors" => "Curatori",
      "field_edition" => "Edizione",
      "project_name_journal" => "Rivista",
      "project_name_book" => "Volume",
      "volissue" => "Vol. (N.)",
      "field_pages" => "Pagine",
      "field_issn" => "issn",
      "field_doi" => "DOI",
      "field_dewey" => "Dewey",
      "field_isbn" => "ISBN",
      "field_status" => "Stato",
      "field_frequency" => "Frequenza",
      "field_university" => "Università",
      "field_degree" => "Titolo accademico",

      "paragraph2" => "Informazioni di accesso",
      "field_texts_reference" => "PDF nella sezione Testi",
      "field_online_links" => "Accesso online",
      "field_access_type" => "Tipo di accesso",
      "field_notes" => "Note",
      "field_online_links2" => "Accessi online addizionali",
      "field_library_links" => "Biblioteche",

      "paragraph3" => "Panoramica del contenuto",
      "field_original_abstract" => "Abstract originale",
      "field_translated_abstract" => "Abstract tradotto",
      "field_table_of_content" => "Sommario",
      "field_translated_table_of_conten" => "Sommario tradotto",
      "field_curated_abstract" => "Note del curatore",
      "field_keywords" => "Parole chiave",

      "paragraph4" => "Risorse correlate selezionate",
      "curated_references" => "Bibliografia",
      "related resources" => "Risorse correlate su Teasearch",
      "field_links" => "Risorse esterne",

      "paragraph5" => "Tag",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",

      "paragraph6" => "Dettagli della scheda",
      "people" => "Contributori",
      "field_license" => "Licenza editoriale",
      "systemdate" => "Date",
      "referenced_in" => "Link da",
    ],
  ];

  $primary_sources = [
    "it" => [
      "field_text_genres" => "Genere",
      "authors" => "Autori",
      "title" => "Titolo",
      "field_title_original" => "Titolo originale",
      "field_title_translated" => "Titolo tradotto",
      "date" => "Date",
      "field_language" => "Lingua",
      "field_country" => "Paese",
      "contained_in" => "Raccolto in",
      "collected_in" => "Raccolto in",
      "field_place_of_publication" => "Luogo di pubblicazione",
      "field_publisher" => "Editore",
      "field_text_types" => "Formato",
      "field_collected_in" => "Raccolto in",
      "field_archive" => "Luogo di conservazione",
      "field_catalogue_number" => "Numero di catalogo",

      "paragraph1" => "Risorse digitali",
      "pdf" => "PDF",
      "field_resource_type" => "Tipo di risorsa",
      "field_digital_resource_original" => "Titolo originale risorsa",
      "field_credit_line" => "Crediti",
      "field_source" => "Fonte",
      "field_right_holder" => "Titolare dei diritti",
      "field_usage_rights" => "Tipo di riutilizzo",
      "field_file_licence" => "Licenza di riutilizzo",
      "field_notes" => "Note",
      "field_alternative_links" => "Risorse online aggiuntive",

      "paragraph2" => "Panoramica del contenuto",
      "field_description" => "Descrizione del curatore",
      "field_text_excerpts" => "Estratti del testo",
      "field_keywords" => "Parole chiave",

      "paragraph3" => "Riferimenti e risorse correlate",
      "curated_references" => "Bibliografia",
      "related resources" => "Altre risorse in Teasearch",
      "field_external_references_link" => "Risorse esterne",

      "paragraph4" => "Tag",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",

      "paragraph5" => "Dettagli della scheda",
      "people" => "Contributori",
      "field_license" => "Licenza editoriale",
      "referenced_in" => "Link da",
      "systemdate" => "Date",
    ],
  ];

  $first_reference = [
    "it" => [
      "title" => "Titolo",
      "field_short_description" => "Sintesi",
      "field_original_abstract" => "Abstract",
      "field_content" => "Testo",
      "field_keywords" => "Parole chiave",

      "paragraph3" => "Riferimenti e risorse correlate",
      "curated_references" => "Bibliografia",
      "related resources" => "Altre risorse in Teasearch",
      "field_links" => "Risorse esterne",

      "paragraph4" => "Tag",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",

      "paragraph5" => "Dettagli della scheda",
      "people" => "Contributori",
      "field_license" => "Licenza editoriale",
      "referenced_in" => "Link da",
      "systemdate" => "Date",
    ],
  ];

  $images = [
    "it" => [
      "field_image_category" => "Categoria",
      "authors" => "Autori",
      "title" => "Titolo",
      "field_title_original" => "Titolo originale",
      "field_title_translated" => "Titolo tradotto",
      "date" => "Date",
      "field_language" => "Lingua",
      "field_country" => "Paese",
      "contained_in" => "Contenuta in",
      "collected_in" => "Contenuta in",
      "field_medium" => "Materiale / Tecnica",
      "field_dimensions" => "Dimensioni",
      "field_location" => "Luogo di conservazione",
      "field_catalogue_number" => "Numero di catalogo",

      "paragraph1" => "Risorse digitali",
      "image" => "Immagine",
      "field_resource_type" => "Tipo di risorsa",
      "field_digital_resource_original" => "Titolo originale risorsa",
      "field_credit_line" => "Crediti",
      "field_source" => "Fonte",
      "field_right_holder" => "Titolare dei diritti",
      "field_usage_rights" => "Tipo di riutilizzo",
      "field_file_licence" => "Licenza di riutilizzo",
      "field_notes" => "Note",
      "field_alternative_links" => "Risorse online aggiuntive",

      "paragraph2" => "Panoramica del contenuto",
      "field_description" => "Descrizione del curatore",
      "field_details" => "Dettagli dell'immagine",
      "field_textual_elements" => "Elementi testuali",
      "field_keywords" => "Parole chiave",

      "paragraph3" => "Riferimenti e risorse correlate",
      "curated_references" => "Bibliografia",
      "related resources" => "Altre risorse in Teasearch",
      "field_external_references_link" => "Risorse esterne",

      "paragraph4" => "Tag",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",

      "paragraph5" => "Dettagli della scheda",
      "people" => "Contributori",
      "field_license" => "Licenza editoriale",
      "referenced_in" => "Link da",
      "systemdate" => "Date",
    ],
  ];

  $videos = [
    "it" => [
      "field_image_category" => "Categoria",
      "authors" => "Autori",
      "title" => "Titolo",
      "field_title_original" => "Titolo originale",
      "field_title_translated" => "Titolo tradotto",
      "field_production_year" => "Anno",
      "field_language" => "Lingua",
      "field_subtitles" => "Subtitles",
      "field_event" => "Evento / Contesto",
      "field_location" => "Sede",
      "field_place_of_publication" => "Luogo di creazione",
      "field_country" => "Paese",
      "field_recording_date" => "Data di registrazione",
      "field_original_release_date" => "Data di pubblicazione",
      "field_duration" => "Durata",

      "paragraph2" => "Risorse digitali",
      "video" => "Video",
      "field_resource_type" => "Tipo di risorsa",
      "field_digital_resource_original" => "Titolo originale risorsa",
      "field_source" => "Fonte",
      "field_right_holder" => "Titolare dei diritti",
      "field_usage_rights" => "Tipo di riutilizzo",
      "field_file_licence" => "Licenza di riutilizzo",
      "field_notes" => "Note",
      "field_alternative_links" => "Risorse online aggiuntive",

      "paragraph3" => "Panoramica del contenuto",
      "field_original_abstract" => "Descrizione originale",
      "field_description" => "Descrizione del curatore",
      "field_keywords" => "Parole chiave",

      "paragraph4" => "Riferimenti e risorse correlate",
      "curated_references" => "Bibliografia",
      "related resources" => "Risorse correlate su Teasearch",
      "field_links" => "Risorse esterne",

      "paragraph5" => "Dettagli della scheda",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",

      "paragraph6" => "Dettagli della scheda",
      "people" => "Contributori",
      "field_license" => "Licenza editoriale",
      "systemdate" => "Date",
      "referenced_in" => "Link da",
    ],
  ];

  $news = [
    "it" => [
      "news_type_badge" => "Categoria",

      "date" => "Date",
      "field_deadline" => "Scadenza",
      "time" => "Orario",
      "attendance" => "Attendance",
      "media" => "Media",
      "field_language" => "Lingua",
      "location" => "Luogo",
      "field_country" => "Paese",
      "field_link" => "Link",
      "field_organizer" => "Organizzatore",
      "contacts" => "Contatti",
      "field_related_resources" => "Risorse correlate",

      "image" => "Immagine",
      "field_content" => "Body Texts",

      "paragraph1" => "Dettagli della scheda",
      "field_subjects" => "Ambito tematico",
      "field_country2" => "Ambito geografico",
      "field_keywords" => "Parole chiave",
      "people" => "Persone",
      "field_teasearch_contributors" => "Persone",
      "field_license" => "Licenza editoriale",
      "systemdate" => "Date",
    ],
  ];

  return [
    "people" => $people,
    "bibliography" => $bibliography,
    "primary_sources" => $primary_sources,
    "first_reference" => $first_reference,
    "images" => $images,
    "videos" => $videos,
    "news" => $news,
  ];
}
// Schede
function teasearch_get_detail_schemas()

// Schede§people
{
  $people = [

    'icon' => [
      'type' => 'image_round',
      'field' => 'field_avatar',
    ],
    'fields' => [
      'maintitle' => [
        'type' => 'people_main_title',

      ],

      'name' => [
        'type' => 'people_name',
        'label' => 'name'
      ],
      'field_known_as' => [
        'label' => 'Other names',
      ],
      'field_title' => [
        'label' => 'Position',
      ],
      'field_affiliation' => [
        'label' => 'Affiliation',
        //multilanguage
      ],
      "field_orcid" => [
        "label" => "ORCID"
      ],

      "paragraph1" => "PROFILE",
      "field_bio" => [
        "label" => "About",
      ],
      "field_activities" => [
        "label" => "Tea-Related Activites"
        //taxonomy
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
        //taxonomy
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
        //taxonomy
      ],
      "field_specific_subjects" => [
        "label" => "Research Topics"
        //text multivalues
      ],
      "field_language" => [
        "label" => "Languages"
      ],
      "field_memberships" => [
        "label" => "Memberships"
      ],
      "paragraph2" => "Contact Information",
      "location" => [
        "type" => "location",
        "label" => "Location",
      ],
      "field_country" => [
        "label" => "Country",
      ],
      "field_email" => [
        "label" => "Email",
      ],
      "field_online_links" => [
        "label" => "Online Presence",
      ],
      "paragraph3" => "Tea-Related Publications",
      "books" => [
        "label" => "books",
        "type" => "publib",
        "pubtype" => "book",
      ],
      "book_chapters" => [
        "label" => "book chapters",
        "type" => "publib",
        "pubtype" => "book_chapters",
      ],
      "journal_articles" => [
        "label" => "Journal Articles",
        "type" => "publib",
        "pubtype" => "journal_articles",
      ],
      "dissertation" => [
        "label" => "dissertation",
        "type" => "publib",
        "pubtype" => "dissertation",
      ],
      "book_review" => [
        "label" => "Book review",
        "type" => "publib",
        "pubtype" => "book_review",
      ],
      "reviews" => [
        "label" => "Reviews",
        "type" => "publib",
        "pubtype" => "reviews",
      ],
      "conference_proceedings" => [
        "label" => "Conference Proceedings",
        "type" => "publib",
        "pubtype" => "conference_proceedings",
      ],
      "patents" => [
        "label" => "Patents",
        "type" => "publib",
        "pubtype" => "patents",
      ],

      "legal_and_policy" => [
        "label" => "Legal & Policy Documents",
        "type" => "publib",
        "pubtype" => "legal_and_policy",
      ],
      "standard" => [
        "label" => "Standards",
        "type" => "publib",
        "pubtype" => "standard",
      ],
      "datasets" => [
        "label" => "Datasets",
        "type" => "publib",
        "pubtype" => "datasets",
      ],
      "periodicals" => [
        "label" => "Periodical Titles",
        "type" => "publib",
        "pubtype" => "periodicals",
      ],
      "newspaper_articles" => [
        "label" => "Newspaper articles",
        "type" => "publib",
        "pubtype" => "newspaper_articles",
      ],
      "webpages" => [
        "label" => "Webpages / online contents",
        "type" => "publib",
        "pubtype" => "webpages",
      ],
      "reference_works" => [
        "label" => "Reference works",
        "type" => "publib",
        "pubtype" => "reference_works",
      ],
      "other_documents" => [
        "label" => "Other",
        "type" => "publib",
        "pubtype" => "other",
      ],
      "paragraph4" => "Teasearch content contributions",
      "essentials" => [
        "label" => "Essentials",
        "type" => "contrib",
        "content_type" => "first_reference",
        "no_author" => true,

      ],
      "texts" => [
        "label" => "Texts",
        "type" => "contrib",
        "content_type" => "primary_sources",
        "no_author" => true,
      ],
      "videos" => [
        "label" => "Videos",
        "type" => "contrib",
        "content_type" => "videos",
        "no_author" => true,
      ],
      "images" => [
        "label" => "Images",
        "type" => "contrib",
        "content_type" => "images",
        "no_author" => true,
      ],


      "bibliography" => [
        "label" => "Bibliography",
        "type" => "contrib",
        "content_type" => "bibliography",
        "no_author" => true,
      ],

      "paragraph5" => "Entry Details",
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked",
        "no_author" => true,
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
        "no_author" => true,
      ]
    ],
  ];


  // Aggiungi altri content type qui...

  // Schede§bibliography

  $bibliography = [
    'icon' => [
      'type' => 'bibliography_icon',
      'field' => 'field_pubtype',
      'debug' => TRUE
    ],

    'fields' => [
      // Titolo principale (mostrato nell'header verde)
      'maintitle' => [
        'type' => 'unified_title',
      ],
      "field_pubtype" => [
        "label" => "Type",
      ],

      "authors" => [
        "type" => "authors_general",
        "field" => "field_responsibles",
        "label" => "Authors",

      ],
      "title" => [
        "label" => "title",
      ],
      "field_title_original" => [
        "label" => "original title",
      ],
      "field_title_translated" => [
        "label" => "translated title",
      ],
      "field_year" => [
        "label" => "Year",
      ],
      "field_date" => [
        "label" => "Date",
      ],
      "field_language" => [
        "label" => "Language",
      ],
      "field_pub_place" => [
        "label" => "Place of Publication",
      ],
      'field_publisher' => [
        'label' => 'Publisher',
      ],
      "field_country" => [
        "label" => "Country"
      ],
      "contained_in" => [
        "label" => "Contained in",
        "type" => "curated_references",
        "field" => "field_contained_in"
      ],
      "project_name_journal" => [
        "label" => "Contained in",
        "type" => "project_name",
        "options" => [
          "filter" => ["field_project_type", "journal"]
        ]
      ],
      "project_name_book" => [
        "label" => "Contained in",
        "type" => "project_name",
        "options" => [
          "filter" => ["field_project_type", "book"]
        ]
      ],
      "editors" => [
        "label" => "Editors",
        "type" => "authors_general",
        "field" => "field_editors",
        "options" => [
          "no_editor_label" => true,
          "is_author" => false,
        ]
      ],
      "authors_general" => [
        "label" => "Authors",
        "type" => "authors_general",
        "field" => "field_editors",
        "options" => [
          "no_editor_label" => true,
          "is_author" => true,
        ]
      ],
      "field_edition" => [
        "label" => "Edition",
      ],
      "volissue" => [
        "label" => "Vol (Issue)",
        "type" => "vol_issue",
      ],
      "field_pages" => [
        "label" => "Pages",
      ],
      "field_issn" => [
        "label" => "issn",
      ],
      "field_doi" => [
        "label" => "DOI",
      ],
      "field_dewey" => [
        "label" => "Dewey",
      ],
      "field_isbn" => [
        "label" => "ISBN",
      ],

      "field_status" => [
        "label" => "Status",
      ],
      "field_frequency" => [
        "label" => "Frequency",
      ],
      "field_university" => [
        "label" => "University",
      ],
      "field_degree" => [
        "label" => "Degree",
      ],

      "paragraph2" => "Access Information",
      "field_texts_reference" => [
        "label" => "PDF in texts session",
      ],
      "field_online_links" => [
        "label" => "Online Access",
      ],
      "field_access_type" => [
        "label" => "Access Type",
      ],
      "field_notes" => [
        "label" => "Notes",
      ],
      "field_online_links2" => [
        "label" => "Additional Online Access",
      ],
      "field_library_links" => [
        "label" => "Libraries",
      ],

      "paragraph3" => "Content overview",
      "field_original_abstract" => [
        "label" => "Original Abstract",
      ],
      "field_translated_abstract" => [
        "label" => "Translated Abstract",
      ],
      "field_table_of_content" => [
        "label" => "Table of content",
      ],
      "field_translated_table_of_conten" => [
        "label" => "Translated Table of content",
      ],
      "field_curated_abstract" => [
        "label" => "Curator's Note",
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],

      'paragraph4' => 'Selected related resources',
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Other Teasearch Resources",
        "type" => "teasearch_references"
      ],
      "field_external_references_link" => [
        "label" => "External Resources",
      ],

      "paragraph6" => "Entry Details",
      "people" => [
        "label" => "Contributori",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"

      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Linked from",
      ],
    ],
  ];

  // Schede§TEXTS = primary_sources

  $primary_sources = [
    'icon' => [
      'type' => 'square_or_main',
    ],
    'fields' => [
      // Titolo principale (mostrato nell'header verde)
      'maintitle' => [
        'type' => 'unified_title',
      ],
      'field_text_genres' => [
        'label' => 'Genre'
      ],
      'authors' => [
        'label' => 'Authors',
        'type' => 'authors_general',
        'field' => 'field_responsibles',
      ],
      'title' => [
        'label' => 'Title',
      ],
      'field_title_original' => [
        'label' => 'Original title',
      ],
      'field_title_translated' => [
        'label' => 'Translated title',
      ],

      'date' => [
        'label' => 'Date',
        'type' => 'date_label'
      ],

      'field_language' => [
        'label' => 'Language',
      ],

      'field_country' => [
        'label' => 'Country',
      ],

      "contained_in" => [
        "label" => "Included in",
        "type" => "teasearch_references",
        "field" => "field_contained_in"
      ],

      "field_collected_in" => [
        "label" => "Included in",
      ],

      'field_text_publisher' => [
        'label' => 'Publisher',
      ],

      'field_place_of_publication' => [
        'label' => 'Place of publication',
      ],

      'field_text_types' => [
        'label' => 'Format',
      ],

      'field_archive' => [
        'label' => 'Repository',
      ],

      'field_catalogue_number' => [
        'label' => 'Catalogue number',
      ],

      'paragraph1' => 'Digital Resources',

      'pdf' => [
        'label' => 'PDF',
        'type' => 'pdf',
        'file' => 'field_pdf_file'
      ],
      'field_resource_type' => [
        'label' => 'Resource type',
      ],
      'field_digital_resource_original' => [
        'label' => 'Original resource title',
      ],
      'field_source' => [
        'label' => 'Source',
      ],
      '	field_file_version' => [
        'label' => 'File version',
      ],
      'field_right_holder' => [
        'label' => 'Right holder',
      ],
      'field_usage_rights' => [
        'label' => 'Reuse Category',
      ],
      'field_file_licence' => [
        "label" => "Licence / Reuse Terms",
        "type" => "taxonomy_linked"

      ],
      'field_credit_line' => [
        'label' => 'Credit Line',
      ],
      'field_notes' => [
        'label' => 'Notes',
      ],
      'field_alternative_links' => [
        'label' => 'Additional Online Resources',
      ],

      'paragraph2' => 'Content overview',
      'field_description' => [
        'label' => 'Curated description',
      ],
      'field_text_excerpts' => [
        'label' => 'Text Excerpts',
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],

      'paragraph3' => 'References and related resources',
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Other Teasearch Resources",
        "type" => "teasearch_references"
      ],
      "field_external_references_link" => [
        "label" => "External Resources",
      ],

      "paragraph5" => "Entry Details",
      "people" => [
        "label" => "Contributori",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"

      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Linked from",
      ],
    ]
  ];

  // Schede§ESSENTIALS = first_reference

  $first_reference = [
    'icon' => [
      'type' => 'square_or_main',
    ],
    "fields" => [
      "maintitle" => [
        "type" => "unified_title"
      ],
      "title" => [
        "label" => "Title"
      ],
      "field_short_description" => [
        "label" => "Short Description"
      ],
      "field_original_abstract" => [
        "label" => "Abstract"
      ],
      "field_content" => [
        "label" => "Main Text"
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],

      'paragraph3' => 'References and related resources',
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Other Teasearch Resources",
        "type" => "teasearch_references"
      ],
      "field_external_references_link" => [
        "label" => "External Resources",
      ],

      "paragraph5" => "Entry Details",
      "people" => [
        "label" => "Contributori",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"

      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Linked from",
      ],
    ]
  ];

  // Schede§IMAGES

  $images = [
    'icon' => [
      'type' => 'square_or_main',
      'field' => 'qualcosa'
    ],
    'fields' => [
      'maintitle' => [
        'type' => 'unified_title',

      ],
      'field_image_category' => [
        'label' => 'Category'
      ],
      'authors' => [
        'label' => 'Authors',
        'type' => 'authors_general',
        'field' => 'field_responsibles',
      ],
      'title' => [
        'label' => 'Title',
      ],
      'field_title_original' => [
        'label' => 'Original title',
      ],
      'field_title_translated' => [
        'label' => 'Translated title',
      ],
      'date' => [
        'label' => 'Date',
        'type' => 'date_label'
      ],
      'field_language' => [
        'label' => 'Language',
      ],

      'field_country' => [
        'label' => 'Country',
      ],

      "contained_in" => [
        "label" => "Contained in",
        "type" => "teasearch_references",
        "field" => "field_contained_in"
      ],

      "field_collected_in" => [
        "label" => "Contained in",
      ],


      'field_medium' => [
        'label' => 'Medium',
      ],
      'field_dimensions' => [
        'label' => 'Dimensions',
      ],
      'field_location' => [
        'label' => 'Repository',
      ],
      'field_catalogue_number' => [
        'label' => 'Catalogue Number',
      ],

      'paragraph1' => "Digital Resources",
      'image' => [
        'label' => 'Image',
        'type' => 'image',
        'options' => [
          'icon' => true
        ]
      ],
      'field_resource_type' => [
        'label' => 'Resource type',
      ],
      'field_digital_resource_original' => [
        'label' => 'Original resource title',
      ],
      'field_source' => [
        'label' => 'Source',
      ],
      '	field_file_version' => [
        'label' => 'File version',
      ],
      'field_right_holder' => [
        'label' => 'Right holder',
      ],
      'field_usage_rights' => [
        'label' => 'Reuse Category',
      ],
      'field_file_licence' => [
        "label" => "Licence / Reuse Terms",
        "type" => "taxonomy_linked"
      ],
      'field_credit_line' => [
        'label' => 'Credit Line',
      ],
      'field_notes' => [
        'label' => 'Notes',
      ],
      'field_alternative_links' => [
        'label' => 'Additional Online Resources',
      ],

      'paragraph2' => "Content overview",
      'field_description' => [
        'label' => 'Curated description',
      ],
      'field_details' => [
        'label' => 'Image Details',
      ],
      'field_textual_elements' => [
        'label' => 'Textual Elements',
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],

      'paragraph3' => 'References and related resources',
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Other Teasearch Resources",
        "type" => "teasearch_references"
      ],
      "field_external_references_link" => [
        "label" => "External Resources",
      ],

      "paragraph5" => "Entry Details",
      "people" => [
        "label" => "Contributori",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"

      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Linked from",
      ],
    ]
  ];

  // Schede§videos

  $videos = [
    'icon' => [
      'type' => 'square_or_main',
    ],
    'fields' => [
      'maintitle' => [
        'type' => 'unified_title',

      ],
      'field_image_category' => [
        'label' => 'Category'
      ],
      'authors' => [
        'label' => 'Authors',
        'type' => 'authors_general',
        'field' => 'field_responsibles',
      ],
      'title' => [
        'label' => 'Title',
      ],
      'field_title_original' => [
        'label' => 'Original title',
      ],
      'field_title_translated' => [
        'label' => 'Translated title',
      ],
      'field_production_year' => [
        'label' => 'Year',
      ],
      'field_language' => [
        'label' => 'Language',
      ],
      'field_subtitles' => [
        'label' => 'Subtitles',
      ],
      'field_country' => [
        'label' => 'Country',
      ],
      'field_event' => [
        'label' => 'Event / Context',
      ],
      'field_location' => [
        'label' => 'Venue',
      ],
      'field_place_of_publication' => [
        'label' => 'Place of creation',
      ],
      'field_recording_date' => [
        'label' => 'Recording Date',
      ],
      'field_original_release_date' => [
        'label' => 'Release Date',
      ],
      'field_duration' => [
        'label' => 'Duration',
      ],

      "paragraph2" => "Digital Resources",
      'video' => [
        'label' => 'Video',
        'type' => 'video'
      ],
      'field_resource_type' => [
        'label' => 'Resource type',
      ],
      'field_digital_resource_original' => [
        'label' => 'Original resource title',
      ],
      'field_source' => [
        'label' => 'Source',
      ],
      'field_right_holder' => [
        'label' => 'Right holder',
      ],
      'field_usage_rights' => [
        'label' => 'Reuse Category',
      ],
      'field_file_licence' => [
        "label" => "Licence / Reuse Terms",
        "type" => "taxonomy_linked"

      ],
      'field_credit_line' => [
        'label' => 'Credit Line',
      ],
      'field_notes' => [
        'label' => 'Notes',
      ],
      'field_alternative_links' => [
        'label' => 'Additional Online Resources',
      ],

      "paragraph3" => "Content overview",
      'field_original_abstract' => [
        'label' => 'Original Description',
      ],
      'field_description' => [
        'label' => 'Curated Description',
      ],
      "field_subjects" => [
        "label" => "Subject Scope"
      ],
      "field_country2" => [
        "label" => "Geographical Scope"
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],

      "paragraph4" => "References and related resources",
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Other Teasearch resources",
        "type" => "teasearch_references"
      ],

      "paragraph6" => "Entry Details",
      "people" => [
        "label" => "Contributori",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"

      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Linked from",
      ],
    ]
  ];



  /**
   * Schema per NEWS - da aggiungere in detail_schemas.inc
   * 
   * Aggiungere alla funzione teasearch_get_detail_schemas():
   * return [
   *   "people" => $people,
   *   "bibliography" => $bibliography,
   *   ...
   *   "news" => $news,  // <-- AGGIUNGERE QUESTA RIGA
   * ];
   */

  $news = [
    'fields' => [
      // Titolo principale (header verde)
      'maintitle' => [
        'type' => 'unified_title',
      ],

      // =======================================================
      // BOX "NEWS TYPE" FLOTTANTE IN ALTO A SINISTRA
      // =======================================================
      'news_type_badge' => [
        'type' => 'news_type_badge',
        'position' => 'left_box',
      ],

      // =======================================================
      // SIDEBAR DESTRA - METADATI EVENTO
      // =======================================================

      // Date (event)
      'date' => [
        'label' => 'Date',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => '{field_event_start_date} {hyphen} {field_event_end_date}',
        "options" => [
          "date_format" => "j F Y"
        ]
      ],
      'field_deadline' => [
        'label' => 'Deadline',
        'position' => 'right',
      ],

      'time' => [
        'label' => 'Time',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => '{field_start_time} {hyphen} {field_end_time}{break}{field_time_zone}'

      ],
      'attendance' => [
        'label' => 'Attendance mode',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => '{field_attendance_mode}{break}{field_fee_cost}{break}{field_notes}'
      ],
      'media' => [
        'label' => 'Media',
        'position' => 'right',
        'type' => 'aggregator',
        "template" => "Type: {field_media_type}{break}{field_pub_title}",
      ],
      'field_language' => [
        'label' => 'Language',
        'position' => 'right',
      ],
      'location' => [
        'label' => 'Location',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => '{field_venue}{break}{field_address}{break}{field_map_view}'
      ],
      'field_country' => [
        'label' => 'Country',
        'position' => 'right',
      ],
      'field_link' => [
        'label' => 'Link',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => '{field_online_event}{break}{field_link_notes}'
      ],
      'field_organizer' => [
        'label' => 'Organiser',
        'position' => 'right',
      ],
      'contacts' => [
        'label' => 'Contacts',
        'position' => 'right',
        'type' => 'aggregator',
        'template' => 'Website:{field_links}{break}Email:{field_email}{break}Tel:{field_telephone}{break} Contact person: {field_contacts}'
      ],
      'field_teaserch_resources' => [
        'label' => 'Related Resources',
        'position' => 'right',
      ],

      // =======================================================
      // CONTENUTO PRINCIPALE (colonna sinistra)
      // =======================================================
      'image' => [
        'type' => 'image',
        'field' => 'field_image',
        'position' => 'main',
      ],

      'field_content' => [
        'label' => 'Body Texts',
        'position' => 'main',
      ],

      // =======================================================
      // SEZIONE BOTTOM (full width sotto le 2 colonne)
      // Usa le stesse classi CSS degli altri content type
      // =======================================================

      "paragraph1" => "Entry Details",

      "field_subjects" => [
        "label" => "Subject Scope",
      ],

      "field_country2" => [
        "label" => "Geographical Scope",
      ],

      "field_keywords" => [
        "label" => "Keywords",
      ],

      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],

      "field_license" => [
        "label" => "Editorial Licence",
        "type" => "taxonomy_linked"
      ],

      "field_teasearch_contributors" => [
        "label" => "People",
        "type" => "people_reference_list"
      ],

      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],

    ],
  ];



  return [
    "people" => $people,
    "bibliography" => $bibliography,
    "primary_sources" => $primary_sources,
    "first_reference" => $first_reference,
    "images" => $images,
    "videos" => $videos,
    "news" => $news,
  ];
}
