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
      "field_activities"=>"Attività correlate al Te",
      "field_subjects"=>"Competenze",
      "field_country2"=>"Focus Geografico",
      "field_specific_subjects"=>"Competenze specifiche",
      "field_language"=>"Lingue",
      "field_memberships" => "Associazioni",
      "field_orcid" => "Orcid",
      "location" => "Città e provincia",
      "type_country" => "Paese",
      "type_email" => "Email",
      "type_online_links"=>"Presenza Online",
      "systemdate" => "Dati sistema"
    ]
  ];


  return ["people" => $people];
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
        'label' => 'Position',
        //taxonomy
        //multilanguage
      ],
      'field_affiliation' => [
        'label' => 'Affiliation',
        //multilanguage
      ],
      "paragraph1" => "PROFILE",
      "field_bio" => [
        "label"=>"About",
      ],
      "field_activities"=>[
        "label"=>"Tea Related Activites"
        //taxonomy
      ],
      "field_subjects"=>[
        "label"=>"Subject Expertise"
        //taxonomy
      ],
      "field_country2"=>[
        "label"=>"Geographical Focus"
        //taxonomy
      ],
      "field_specific_subjects"=>[
        "label"=>"Specific Topics"
        //text multivalues
      ],
      "field_language"=>[
        "label"=>"Languages"
      ],
      "field_memberships" => [
        "label" => "Memberships"
      ],
      "field_orcid"=>[
        "label"=>"ORCID"
      ],
      "paragraph2" => "Contact Information",
      "location"=>[
        "type"=>"location",
        "label"=>"Location",
      ],
      "field_country"=>[
        "label"=>"Country",
      ],
      "field_email"=>[
        "label"=>"Email",
      ],
      "field_online_links"=>[
        "label" => "Online Presence",
      ],
      "paragraph5"=> "Dettagli",
      "systemdate"=> [
        "type"=>"systemdate",
        "label" => "Dates",
      ]


    ],
  ];

  // Aggiungi altri content type qui...

  return [
    "people" => $people
  ];
}
