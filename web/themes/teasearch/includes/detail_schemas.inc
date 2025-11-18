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
      "paragraph1" => "PROFILO",
      "paragraph2" => "Informazioni di contatto",
      "paragraph5" => "Dettagli",
      "field_name" => "Nome",
      "field_known_as" => "Presudonomo",
      "field_title" => "Posizione",
      "field_affiliation" => "Affiliazione",
      "field_activities" => "Attività correlate al Te",
      "field_subjects" => "Competenze",
      "field_country2" => "Focus Geografico",
      "field_specific_subjects" => "Competenze specifiche",
      "field_language" => "Lingue",
      "field_memberships" => "Associazioni",
      "field_orcid" => "Orcid",
      "location" => "Città e provincia",
      "type_country" => "Paese",
      "type_email" => "Email",
      "type_online_links" => "Presenza Online",
      "systemdate" => "Dati sistema"
    ]
  ];
  $bibliography = [
    "it" => [
      "paragraph1" => "INFORMAZIONI PUBBLICAZIONE",
      "paragraph2" => "AUTORI E CONTRIBUTORI",
      "paragraph3" => "DETTAGLI PUBBLICAZIONE",
      "paragraph4" => "IDENTIFICATORI",
      "paragraph5" => "ACCESSO E DISPONIBILITÀ",
      "paragraph6" => "CONTENUTO",
      "paragraph7" => "INFORMAZIONI AGGIUNTIVE",
      "paragraph8" => "RISORSE CORRELATE",
      "paragraph9" => "Dettagli",
      "field_responsibles" => "Autori",
      "field_editors" => "Curatori (solo per capitoli di libro)",
      "field_authors_ref" => "Riferimenti autori",
      "field_curators_ref" => "Riferimenti curatori",
      "field_reviewers_ref" => "Riferimenti revisori",
      "field_featured_ref" => "Persona ritratta",
      "field_pubtype" => "Tipo",
      "field_project_type" => "Libro curato / Rivista",
      "field_year" => "Anno",
      "field_date" => "Data",
      "field_publisher" => "Editore",
      "field_pub_place" => "Luogo di pubblicazione",
      "field_edition" => "Edizione",
      "field_volume" => "Volume",
      "field_issues" => "Fascicolo",
      "field_pages" => "Pagine",
      "field_journal" => "Rivista",
      "field_journal_latin" => "Rivista (caratteri latini)",
      "field_frequency" => "Frequenza",
      "field_university" => "Università",
      "field_degree" => "Titolo accademico",
      "field_doi" => "DOI",
      "field_isbn" => "ISBN",
      "field_issn" => "ISSN",
      "field_dewey" => "Classificazione Dewey",
      "field_license" => "Licenza",
      "field_access_type" => "Tipo di accesso",
      "field_online_links" => "Accesso online",
      "field_online_access2" => "Accesso online aggiuntivo",
      "field_library_links" => "Record bibliotecari",
      "field_texts_reference" => "PDF nella sezione testi",
      "field_title_original" => "Titolo in lingua originale",
      "field_title_translated" => "Titolo tradotto",
      "field_language" => "Lingua",
      "field_country" => "Focus geografico",
      "field_subjects" => "Soggetti",
      "field_keywords" => "Parole chiave",
      "field_original_abstract" => "Abstract originale",
      "field_curated_abstract" => "Abstract curato da Teasearch",
      "field_table_of_content" => "Indice",
      "field_notes" => "Note",
      "field_status" => "Stato",
      "field_links" => "Risorse esterne",
      "field_curated_references" => "Riferimenti curati",
      "field_teasearch_references" => "Risorse correlate Teasearch",
      "systemdate" => "Dati sistema"
    ]
  ];

  return ["people" => $people, "bibliography" => $bibliography];
}
function teasearch_get_detail_schemas()
{
  $people =  [

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
        'label' => 'Pseudonym',
      ],
      'field_title' => [
        'label' => 'Title',
      ],
      'field_affiliation' => [
        'label' => 'Affiliation',
        //multilanguage
      ],
      "paragraph1" => "PROFILE",
      "field_bio" => [
        "label" => "About",
      ],
      "field_activities" => [
        "label" => "Tea Related Activites"
        //taxonomy
      ],
      "field_subjects" => [
        "label" => "Subject Expertise"
        //taxonomy
      ],
      "field_country2" => [
        "label" => "Geographical Focus"
        //taxonomy
      ],
      "field_specific_subjects" => [
        "label" => "Specific Topics"
        //text multivalues
      ],
      "field_language" => [
        "label" => "Languages"
      ],
      "field_memberships" => [
        "label" => "Memberships"
      ],
      "field_orcid" => [
        "label" => "ORCID"
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
      "paragraph3" => "Tea Related Publications",
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
        "label" => "Standard",
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

      ],
      "texts" => [
        "label" => "Texts",
        "type" => "contrib",
        "content_type" => "primary_sources",
      ],
      "videos" => [
        "label" => "Videos",
        "type" => "contrib",
        "content_type" => "videos",
      ],
      "images" => [
        "label" => "Images",
        "type" => "contrib",
        "content_type" => "images",
      ],

      "bibliography" => [
        "label" => "Bibliography",
        "type" => "contrib",
        "content_type" => "biblography",
      ],

      "paragraph5" => "Details",
      "field_license" => [
        "label" => "License",
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ]
    ],
  ];


  // Aggiungi altri content type qui...

  $bibliography = [
    'icon' => [
      'type' => 'bibliography_icon',
      'field' => 'field_pubtype',
      'debug'  => TRUE
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
      "field_doi" => [
        "label" => "DOI",
      ],
      "field_dewey" => [
        "label" => "Dewey",
      ],
      "field_isbn" => [
        "label" => "ISBN",
      ],
      "field_pub_title" => [
        "label" => "Book Title",
      ],
      "editors" => [
        "label" => "Editors",
        "type" => "authors_general",
        "field" => "field_editors",

      ],
      "field_edition" => [
        "label" => "Edition",
      ],
      "journal_name" => [
        "label" => "Journal",
        "type" => "journal_name",
      ],
      "field_issn" => [
        "label" => "issn",
      ],
      "volissue" => [
        "label" => "Vol (Issue)",
        "type" => "vol_issue",
      ],
      "field_pages" => [
        "label" => "Pages",
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
      "field_notes" => [
        "label" => "Degree",
      ],
      "paragraph2" => "Access Information",

      "field_texts_reference" => [
        "label" => "PDF in texts session",
      ],
      "field_online_links" => [
        "label" => "Online Access",
      ],
      "field_online_links2" => [
        "label" => "Additional Online Access",
      ],
      "field_library_links" => [
        "label" => "Libraries",
      ],

      "paragraph3" => "Description",

      "field_original_abstract" => [
        "label" => "Original Abstract",
      ],
      "field_table_of_content" => [
        "label" => "Table of content",
      ],
      "field_curated_abstract" => [
        "label" => "Teasearch curated abstract",
      ],
      "field_keywords" => [
        "label" => "Keywords",
      ],

      "paragraph4" => "References",
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Teasearch related resources",
        "type" => "teasearch_references"
      ],
      "field_links" => [
        "label" => "External Resources",
      ],
      "paragraph5" => "Entry Details",
      "field_subjects" => [
        "label" => "Subjects"
      ],
      "field_country" => [
        "label" => "Geographic focus"
      ],
      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "License"
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Referenced in",
      ],

    ],
  ];


  $primary_sources = [
    'icon' => [
      'type' => 'square_or_main',
    ],
    'fields' => [
      // Titolo principale (mostrato nell'header verde)
      'maintitle' => [
        'type' => 'unified_title',
      ],
      'field_text_genders' => [
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

      'field_year_label' => [
        'label' => 'Date',
      ],
      'field_language' => [
        'label' => 'Language',
      ],
      'field_country' => [
        'label' => 'Place of Creation',
      ],

      'paragraph1' => 'Digital Resources',

      'pdf' => [
        'label' => 'PDF',
        'type' => 'pdf',
        'file' => 'field_pdf_file'
      ],

      'field_source' => [
        'label' => 'Source',
      ],

      'field_usage_rights' => [
        'label' => 'usage rights',
      ],
      'field_notes' => [
        'label' => 'Notes',
      ],
      'paragraph2' => 'Description',

      'field_text_types' => [
        'label' => 'Format',
      ],

      'field_archive' => [
        'label' => 'Location / Collection',
      ],

      'field_catalogue_number' => [
        'label' => 'Catalogue number',
      ],

      'field_description' => [
        'label' => 'Content Description',
      ],

      'field_text_excerpts' => [
        'label' => 'Text Excerpts',
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],
      'paragraph3' => 'References',
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Teasearch related resources",
        "type" => "teasearch_references"
      ],
      "field_links" => [
        "label" => "External Resources",
      ],
      'paragraph4' => 'Entry Details',
      "field_subjects" => [
        "label" => "Subjects",
      ],
      "field_country2" => [
        "label" => "Geographic Focus",
      ],
      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "License"
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Referenced in",
      ],
    ]
  ];

  $first_reference = [
    'icon' => [
      'type' => 'square_or_main',
    ],
    "fields" => [
      "title" => [
        "label" => "Title"
      ],
      "field_title_original" => [
        "label" => "Original Title"
      ],
      "field_title_translated" => [
        "label" => "Translated Title"
      ],
      "paragraph1" => "Content",
      "field_content" => [
        "label" => "Main Text"
      ],
      "field_keywords" => [
        "label" => "Keywords"
      ],
      "paragraph2" => "References",
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Teasearch related resources",
        "type" => "teasearch_references"
      ],
      "field_links" => [
        "label" => "External Resources",
      ],
      "paragraph3" => "Entry Details",
      "field_subjects" => [
        "label" => "Subjects",
      ],
      "field_country2" => [
        "label" => "Geographic Focus",
      ],
      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "License"
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Referenced in",
      ],
    ]
  ];

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
        'label' => 'Country of Creation',
      ],


      'paragraph1' => "Digital Resources",
      'image' => [
        'label' => 'Image',
        'type' => 'image',
        'options' => [
          'icon' => true
        ]
      ],
      'field_source' => [
        'label' => 'Source',
      ],
      'field_usage_right' => [
        'label' => 'Usage Right',
      ],
      'field_notes' => [
        'label' => 'Usage Right',
      ],

      'paragraph2' => "Description",
      'field_medium' => [
        'label' => 'Medium',
      ],
      'field_dimensions' => [
        'label' => 'Dimensions',
      ],
      'field_location' => [
        'label' => 'Location',
      ],
      'field_catalogue_number' => [
        'label' => 'Catalogue Number',
      ],
      'field_description' => [
        'label' => 'Content Description',
      ],
      'field_details' => [
        'label' => 'Image Details',
      ],
      'field_textual_elements' => [
        'label' => 'Textual Elements',
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],
      "paragraph3" => "References",
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Teasearch related resources",
        "type" => "teasearch_references"
      ],
      "field_links" => [
        "label" => "External Resources",
      ],
      "paragraph4" => "Entry Details",
      "field_subjects" => [
        "label" => "Subjects"
      ],
      "field_country" => [
        "label" => "Geographic focus"
      ],
      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "License"
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Referenced in",
      ],
    ]
  ];


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
      'field_country' => [
        'label' => 'Location',
      ],
      "paragraph2" => "Digital Resources",
      'video' => [
        'label' => 'Video',
        'type' => 'video'
      ],
      'field_source' => [
        'label' => 'Source',
      ],
      'field_creator_details' => [
        'label' => 'Video Creator',
      ],

      'field_usage_right' => [
        'label' => 'Usage Right',
      ],
      'field_notes' => [
        'label' => 'Usage Right',
      ],
      "paragraph3" => "Description",
      'field_duration' => [
        'label' => 'Duration',
      ],
      'field_duration' => [
        'label' => 'Duration',
      ],
      'field_event' => [
        'label' => 'Event / Context',
      ],
      'field_recording_date' => [
        'label' => 'Recording Date',
      ],
      'field_original_release_date' => [
        'label' => 'Release Date',
      ],
      'field_description' => [
        'label' => 'Description',
      ],
      'field_subtitles' => [
        'label' => 'Subtitles',
      ],
      'field_keywords' => [
        'label' => 'Keywords',
      ],
      "paragraph4" => "References",
      "curated_references" => [
        "label" => "Bibliography",
        "type" => "curated_references"
      ],
      "related resources" => [
        "label" => "Teasearch related resources",
        "type" => "teasearch_references"
      ],
      "field_links" => [
        "label" => "External Resources",
      ],
      "paragraph5" => "Entry Details",
      "field_subjects" => [
        "label" => "Subjects"
      ],
      "field_country" => [
        "label" => "Geographic focus"
      ],
      "people" => [
        "label" => "People",
        "type" => "people_roles"
      ],
      "field_license" => [
        "label" => "License"
      ],
      "systemdate" => [
        "type" => "systemdate",
        "label" => "Dates",
      ],
      "referenced_in" => [
        "type" => "referenced_in",
        "label" => "Referenced in",
      ],
    ]
  ];



  return [
    "people" => $people,
    "bibliography" => $bibliography,
    "primary_sources" => $primary_sources,
    "first_reference" => $first_reference,
    "images" => $images,
    "videos" => $videos
  ];
}
