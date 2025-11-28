<?php

namespace Drupal\teasearch_filter\Helper;

use Drupal\Core\Entity\EntityInterface;

/**
 * Helper class for custom field rendering functions.
 */
class CustomFieldHelper
{


  /**
   * Helper: Aggiunge label editor se field_is_editor è true sul NODO principale
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Il nodo principale (non il paragraph)
   * @param string $authors_string
   *   La stringa con tutti gli autori già uniti
   *
   * @return string
   *   La stringa autori con eventuale label editor alla fine
   */
  private static function addEditorLabelIfNeeded(EntityInterface $entity, string $authors_string): string
  {
    // Ottieni lingua corrente
    $current_language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Check field_is_editor sul NODO (non sul paragraph)
    if ($entity->hasField('field_is_editor')) {
      $editor_field = $entity->get('field_is_editor');
      if ($editor_field && !$editor_field->isEmpty() && $editor_field->value) {
        $editor_label = $current_language === 'it' ? ' (a cura di)' : ' (ed.)';
        $authors_string .= $editor_label;
      }
    }

    return $authors_string;
  }

  /**
   * Execute a custom function by name.
   *
   * @param string $function_name
   *   Name of the function to execute.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   *
   * @return string|null
   *   The rendered string or NULL if function doesn't exist.
   */
  public static function execute(string $function_name, EntityInterface $entity)
  {
    $method = 'render_' . $function_name;

    if (method_exists(self::class, $method)) {
      try {
        return self::$method($entity);
      } catch (\Exception $e) {
        \Drupal::logger('teasearch_filter')->error('Error in @method: @message', [
          '@method' => $method,
          '@message' => $e->getMessage(),
        ]);
        return NULL;
      }
    }

    \Drupal::logger('teasearch_filter')->error('Custom function @name not found', ['@name' => $function_name]);
    return NULL;
  }

  /**
   * Render PEOPLE display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Rendered output.
   */
  public static function render_format_people_display(EntityInterface $entity)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $output = [];

    // =============================================================================
    // PARTE 1: NOME COMPLETO
    // =============================================================================

    $name_parts = [];
    $field_surname_first = FALSE;

    // Leggi field_surname_first (boolean)
    if ($entity->hasField('field_surname_first')) {
      $surname_first_field = $entity->get('field_surname_first');
      if ($surname_first_field && !$surname_first_field->isEmpty()) {
        $field_surname_first = (bool) $surname_first_field->value;
      }
    }

    // Estrai i campi nome direttamente dall'entità (NON da paragraph)
    $field_lastname = '';
    $field_firstname = '';
    $field_fullname = '';

    // field_lastname (Surname latin)
    if ($entity->hasField('field_lastname')) {
      $lastname_field = $entity->get('field_lastname');
      if ($lastname_field && !$lastname_field->isEmpty()) {
        $field_lastname = $lastname_field->value;
      }
    }

    // field_firstname (Given Name latin)
    if ($entity->hasField('field_firstname')) {
      $firstname_field = $entity->get('field_firstname');
      if ($firstname_field && !$firstname_field->isEmpty()) {
        $field_firstname = $firstname_field->value;
      }
    }

    // field_fullname (Full Name ORIGINAL non latin)
    if ($entity->hasField('field_fullname')) {
      $fullname_field = $entity->get('field_fullname');
      if ($fullname_field && !$fullname_field->isEmpty()) {
        $field_fullname = $fullname_field->value;
      }
    }

    // Costruisci il nome secondo la logica field_surname_first
    if ($field_surname_first) {
      // Ordine: LASTNAME + FIRSTNAME + FULLNAME
      if (!empty($field_lastname)) {
        $name_parts[] = $field_lastname;
      }
      if (!empty($field_firstname)) {
        $name_parts[] = $field_firstname;
      }
      if (!empty($field_fullname)) {
        $name_parts[] = $field_fullname;
      }
    } else {
      // Ordine: FIRSTNAME + LASTNAME + FULLNAME
      if (!empty($field_firstname)) {
        $name_parts[] = $field_firstname;
      }
      if (!empty($field_lastname)) {
        $name_parts[] = $field_lastname;
      }
      if (!empty($field_fullname)) {
        $name_parts[] = $field_fullname;
      }
    }

    // Aggiungi il nome formattato all'output
    if (!empty($name_parts)) {
      $output[] = '<span class="result-item-title">' . implode(' ', $name_parts) . '</span>';
    }

    // =============================================================================
    // PARTE 2: TITLE/POSITION
    // =============================================================================

    if ($entity->hasField('field_title')) {
      $title_field = $entity->get('field_title');
      if ($title_field && !$title_field->isEmpty()) {
        $field_title = $title_field->value;
        $output[] = $field_title;
      }
    }

    // =============================================================================
    // PARTE 3: AFFILIATION
    // =============================================================================

    if ($entity->hasField('field_affiliation')) {
      $affiliation_field = $entity->get('field_affiliation');
      if ($affiliation_field && !$affiliation_field->isEmpty()) {
        $field_affiliation = $affiliation_field->value;
        $output[] = '' . $field_affiliation . '';
      }
    }

    // =============================================================================
    // PARTE 4: LOCATION - COUNTRY, CITY (REGION)
    // =============================================================================

    $location_parts = [];

    // field_country (TASSONOMIA - Countries)
    if ($entity->hasField('field_country_single')) {
      $country_field = $entity->get('field_country_single');
      if ($country_field && !$country_field->isEmpty()) {
        $field_country = $country_field->entity;
        if ($field_country) {
          $location_parts[] = $field_country->getName();
        }
      }
    }

    // field_city (TESTO SEMPLICE)
    if ($entity->hasField('field_city')) {
      $city_field = $entity->get('field_city');
      if ($city_field && !$city_field->isEmpty()) {
        $field_city = $city_field->value;
        $location_parts[] = $field_city;
      }
    }

    // Costruisci la stringa location
    if (!empty($location_parts)) {
      $location_string = implode(', ', $location_parts);

      // Aggiungi field_region in parentesi se presente (TESTO SEMPLICE)
      if ($entity->hasField('field_region')) {
        $region_field = $entity->get('field_region');
        if ($region_field && !$region_field->isEmpty()) {
          $field_region = $region_field->value;
          $location_string .= ' (' . $field_region . ')';
        }
      }

      $output[] = '<span class="location">' . $location_string . '</span>';
    }

    // =============================================================================
    // OUTPUT FINALE
    // =============================================================================

    return implode('<br>', $output);
  }


  /**
   * Render BIBLIOGRAPHY display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Rendered output.
   */
  public static function render_format_bibliography_display(EntityInterface $entity)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $output = [];

    // =============================================================================
    // PARTE 1: TITOLO
    // =============================================================================

    $title_parts = [];

    // title (TITLE_LATIN) - titolo del nodo
    $title_latin = $entity->label();
    if (!empty($title_latin)) {
      $title_parts[] = $title_latin;
    }

    // field_title_original (TITLE_ORIGINAL_NON_LATIN)
    if ($entity->hasField('field_title_original')) {
      $title_original_field = $entity->get('field_title_original');
      if ($title_original_field && !$title_original_field->isEmpty()) {
        $field_title_original = $title_original_field->value;
        if (!empty($field_title_original)) {
          $title_parts[] = $field_title_original;
        }
      }
    }

    // Aggiungi titolo principale
    if (!empty($title_parts)) {
      $output[] = '<span class="result-item-title">' . implode(' ', $title_parts) . '</span>';
    }

    // field_title_translated (TRANSLATED TITLE) - in parentesi quadre
    if ($entity->hasField('field_title_translated')) {
      $title_translated_field = $entity->get('field_title_translated');
      if ($title_translated_field && !$title_translated_field->isEmpty()) {
        $field_title_translated = $title_translated_field->value;
        if (!empty($field_title_translated)) {
          $output[] = '[' . $field_title_translated . ']';
        }
      }
    }

    // =============================================================================
    // PARTE 2: AUTORI (da paragraph field_responsibles)
    // =============================================================================

    $author_line_parts = [];

    // Check se è anonimo
    $field_is_anonymous = FALSE;
    if ($entity->hasField('field_is_anonymous')) {
      $anonymous_field = $entity->get('field_is_anonymous');
      if ($anonymous_field && !$anonymous_field->isEmpty()) {
        $field_is_anonymous = (bool) $anonymous_field->value;
      }
    }

    if ($field_is_anonymous) {
      // Se anonimo, scrivi "Anonymous"
      $author_line_parts[] = 'Anonymous';
    } else {
      // Altrimenti processa gli autori dal paragraph
      if ($entity->hasField('field_responsibles')) {
        $field_responsibles = $entity->get('field_responsibles');

        if ($field_responsibles && !$field_responsibles->isEmpty()) {
          $authors = [];
          $max_authors = 2; // Mostra max 3 autori, poi "et al."

          foreach ($field_responsibles as $index => $item) {
            if ($index >= $max_authors) {
              break; // Interrompi dopo 3 autori
            }

            if ($item && $item->entity) {
              $author_paragraph = $item->entity;

              // Estrai campi dal paragraph
              $field_lastname_latin = '';
              $field_firstname_latin = '';
              $field_fullname = '';
              $field_surname_first = FALSE;
              $field_is_org = FALSE;
              $field_is_translator = FALSE;
              $field_is_editor = FALSE;

              // field_lastname_latin (Surname latin)
              if ($author_paragraph->hasField('field_lastname')) {
                $lastname_field = $author_paragraph->get('field_lastname');
                if ($lastname_field && !$lastname_field->isEmpty()) {
                  $field_lastname_latin = $lastname_field->value;
                }
              }

              // field_firstname_latin (Given Name latin)
              if ($author_paragraph->hasField('field_firstname')) {
                $firstname_field = $author_paragraph->get('field_firstname');
                if ($firstname_field && !$firstname_field->isEmpty()) {
                  $field_firstname_latin = $firstname_field->value;
                }
              }

              // field_fullname (Full Name Original non Latin)
              if ($author_paragraph->hasField('field_fullname')) {
                $fullname_field = $author_paragraph->get('field_fullname');
                if ($fullname_field && !$fullname_field->isEmpty()) {
                  $field_fullname = $fullname_field->value;
                }
              }

              // field_surname_first
              if ($author_paragraph->hasField('field_surname_first')) {
                $surname_first_field = $author_paragraph->get('field_surname_first');
                if ($surname_first_field && !$surname_first_field->isEmpty()) {
                  $field_surname_first = (bool) $surname_first_field->value;
                }
              }

              // field_is_org (Is Organization)
              if ($author_paragraph->hasField('field_is_org')) {
                $is_org_field = $author_paragraph->get('field_is_org');
                if ($is_org_field && !$is_org_field->isEmpty()) {
                  $field_is_org = (bool) $is_org_field->value;
                }
              }

              // field_is_translator
              if ($author_paragraph->hasField('field_is_translator')) {
                $is_translator_field = $author_paragraph->get('field_is_translator');
                if ($is_translator_field && !$is_translator_field->isEmpty()) {
                  $field_is_translator = (bool) $is_translator_field->value;
                }
              }

              // field_is_editor
              if ($author_paragraph->hasField('field_is_editor')) {
                $is_editor_field = $author_paragraph->get('field_is_editor');
                if ($is_editor_field && !$is_editor_field->isEmpty()) {
                  $field_is_editor = (bool) $is_editor_field->value;
                }
              }

              // Costruisci nome autore secondo logica PO
              $author_name_parts = [];

              if ($field_is_org) {
                // ORGANIZATION: SURNAME + FULLNAME
                if (!empty($field_lastname_latin)) {
                  $author_name_parts[] = $field_lastname_latin;
                }
                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              } else {
                // PERSON
                if ($field_surname_first) {
                  // SURNAME_FIRST: SURNAME + GIVEN_NAME + FULLNAME
                  if (!empty($field_lastname_latin)) {
                    $author_name_parts[] = $field_lastname_latin;
                  }
                  if (!empty($field_firstname_latin)) {
                    $author_name_parts[] = $field_firstname_latin;
                  }
                  if (!empty($field_fullname)) {
                    $author_name_parts[] = $field_fullname;
                  }
                } else {
                  // NOT SURNAME_FIRST: SURNAME, GIVEN_NAME + FULLNAME
                  $name_with_comma = [];
                  if (!empty($field_lastname_latin)) {
                    $name_with_comma[] = $field_lastname_latin;
                  }
                  if (!empty($field_firstname_latin)) {
                    $name_with_comma[] = $field_firstname_latin;
                  }

                  if (!empty($name_with_comma)) {
                    $author_name_parts[] = implode(', ', $name_with_comma);
                  }

                  if (!empty($field_fullname)) {
                    $author_name_parts[] = $field_fullname;
                  }
                }
              }

              // Costruisci stringa autore con ruoli
              $author_string = implode(' ', $author_name_parts);

              // Aggiungi ruoli
              $roles = [];
              if ($field_is_translator) {
                $roles[] = 'Translator';
              }
              if ($field_is_editor) {
                $roles[] = 'Editor';
              }
              /*
              if (!empty($roles)) {
                $author_string .= ' (' . implode(', ', $roles) . ')';
              }*/


              $authors[] = $author_string;
            }
          }

          // Se ci sono più di max_authors, aggiungi "et al."
          if (count($field_responsibles) > $max_authors) {
            $authors[] = 'et al.';
          }

          // Unisci autori
          if (!empty($authors)) {
            $authors_string = implode('; ', $authors);

            // Aggiungi label editor se necessario (una volta sola alla fine)
            $authors_string = self::addEditorLabelIfNeeded($entity, $authors_string);

            $author_line_parts[] = $authors_string;

          }
        }
      }
    }

    // Aggiungi YEAR con dash
    if ($entity->hasField('field_year')) {
      $year_field = $entity->get('field_year');
      if ($year_field && !$year_field->isEmpty()) {
        $field_year = $year_field->value;
        if (!empty($field_year)) {
          $author_line_parts[] = ' / ' . $field_year . '';
        }
      }
    }

    // Aggiungi linea autori all'output
    if (!empty($author_line_parts)) {
      $output[] = implode(' ', $author_line_parts);
    }

    // =============================================================================
    // PARTE 3: TYPE + LANGUAGE + ICONA BIBLIOGRAFIA
    // =============================================================================

    $type_lang_parts = [];

    // field_pubtype (TYPE - Tassonomia)
    if ($entity->hasField('field_pubtype')) {
      $pubtype_field = $entity->get('field_pubtype');
      if ($pubtype_field && !$pubtype_field->isEmpty()) {
        $field_pubtype = $pubtype_field->entity;
        if ($field_pubtype) {
          $type_lang_parts[] = $field_pubtype->getName();

          // =============================================================================
          // GESTIONE ICONA BIBLIOGRAFIA
          // =============================================================================
          // Estrai field_icon_name dalla taxonomy term publication_types
          if ($field_pubtype->hasField('field_icon_name') && !$field_pubtype->get('field_icon_name')->isEmpty()) {
            $icon_name_value = $field_pubtype->get('field_icon_name')->value;

            if (!empty($icon_name_value)) {
              // Costruisci il path completo dell'icona
              $theme_path = \Drupal::service('extension.list.theme')->getPath('teasearch');
              $icon_path = '../../' . $theme_path . '/images/bibicons/' . $icon_name_value . ".png";

              // Aggiungi la proprietà all'entità per l'uso nel template
              $entity->teasearch_bibliography_icon = $icon_path;
            }
          }
        }
      }
    }

    // field_language (LANGUAGE - Tassonomia)
    if ($entity->hasField('field_language')) {
      $language_field = $entity->get('field_language');
      if ($language_field && !$language_field->isEmpty()) {
        $languages = [];

        // Itera su tutti i valori (supporta sia singolo che multivalore)
        foreach ($language_field as $language_item) {
          if ($language_item->entity) {
            $languages[] = $language_item->entity->getName();
          }
        }

        // Aggiungi tutte le lingue separate da virgola (o altro separatore)
        if (!empty($languages)) {
          $type_lang_parts[] = implode(', ', $languages);
        }
      }
    }
    
    // Aggiungi TYPE - LANGUAGE
    if (!empty($type_lang_parts)) {
      $output[] = '' . implode(' / ', $type_lang_parts) . '';
    }

    // =============================================================================
    // OUTPUT FINALE
    // =============================================================================

    return implode('<br>', $output);
  }


  /**
   * Render TEXTS display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Rendered output.
   */
  public static function render_format_texts_display(EntityInterface $entity)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */

    $output = [];

    // =============================================================================
    // PARTE 1: TITOLO
    // =============================================================================

    $title_latin = $entity->label(); // title del nodo
    $field_title_original = '';
    $field_title_translated = '';

    // field_title_original (TITLE_ORIG_NON-LATIN)
    if ($entity->hasField('field_title_original')) {
      $title_original_field = $entity->get('field_title_original');
      if ($title_original_field && !$title_original_field->isEmpty()) {
        $field_title_original = $title_original_field->value;
      }
    }

    // field_title_translated (TRANSLATED TITLE)
    if ($entity->hasField('field_title_translated')) {
      $title_translated_field = $entity->get('field_title_translated');
      if ($title_translated_field && !$title_translated_field->isEmpty()) {
        $field_title_translated = $title_translated_field->value;
      }
    }

    // Logica titolo secondo PO
    if (!empty($title_latin)) {
      // Se TITLE_(LATIN) ha valore
      $title_parts = [];
      $title_parts[] = $title_latin;

      if (!empty($field_title_original)) {
        $title_parts[] = $field_title_original;
      }

      $output[] = '<span class="result-item-title">' . implode(' ', $title_parts) . '</span>';

      // Translated title in square brackets
      if (!empty($field_title_translated)) {
        $output[] = '[' . $field_title_translated . ']';
      }
    } else {
      // Altrimenti usa TRANSLATED_TITLE
      if (!empty($field_title_translated)) {
        $output[] = '<span class="result-item-title">' . $field_title_translated . '</span>';
      }
    }

    // =============================================================================
    // PARTE 2: AUTORI (da paragraph field_texts_authors)
    // =============================================================================

    $author_line_parts = [];

    // Check se è anonimo
    $field_is_anonymous = FALSE;
    if ($entity->hasField('field_is_anonymous')) {
      $anonymous_field = $entity->get('field_is_anonymous');
      if ($anonymous_field && !$anonymous_field->isEmpty()) {
        $field_is_anonymous = (bool) $anonymous_field->value;
      }
    }

    if ($field_is_anonymous) {
      // Se anonimo, scrivi "Anonymous"
      $author_line_parts[] = 'Anonymous';
    } else {
      // Altrimenti processa gli autori dal paragraph
      if ($entity->hasField('field_responsibles')) {
        $field_texts_authors = $entity->get('field_responsibles');

        if ($field_texts_authors && !$field_texts_authors->isEmpty()) {
          $authors = [];
          $max_authors = 2; // Mostra max 3 autori, poi "et al."

          foreach ($field_texts_authors as $index => $item) {
            if ($index >= $max_authors) {
              break; // Interrompi dopo 3 autori
            }

            if ($item && $item->entity) {
              $author_paragraph = $item->entity;

              // Estrai campi dal paragraph
              $field_lastname_latin = '';
              $field_firstname_latin = '';
              $field_fullname = '';
              $field_surname_first = FALSE;
              $field_attributed_authorship = FALSE;

              // field_lastname_latin (Surname latin)
              if ($author_paragraph->hasField('field_lastname')) {
                $lastname_field = $author_paragraph->get('field_lastname');
                if ($lastname_field && !$lastname_field->isEmpty()) {
                  $field_lastname_latin = $lastname_field->value;
                }
              }

              // field_firstname_latin (Given Name latin)
              if ($author_paragraph->hasField('field_firstname')) {
                $firstname_field = $author_paragraph->get('field_firstname');
                if ($firstname_field && !$firstname_field->isEmpty()) {
                  $field_firstname_latin = $firstname_field->value;
                }
              }

              // field_fullname (Full Name Original non Latin)
              if ($author_paragraph->hasField('field_fullname')) {
                $fullname_field = $author_paragraph->get('field_fullname');
                if ($fullname_field && !$fullname_field->isEmpty()) {
                  $field_fullname = $fullname_field->value;
                }
              }

              // field_surname_first
              if ($author_paragraph->hasField('field_surname_first')) {
                $surname_first_field = $author_paragraph->get('field_surname_first');
                if ($surname_first_field && !$surname_first_field->isEmpty()) {
                  $field_surname_first = (bool) $surname_first_field->value;
                }
              }

              // field_attributed_authorship
              if ($author_paragraph->hasField('field_attributed_authorship')) {
                $attributed_field = $author_paragraph->get('field_attributed_authorship');
                if ($attributed_field && !$attributed_field->isEmpty()) {
                  $field_attributed_authorship = (bool) $attributed_field->value;
                }
              }

              // Costruisci nome autore secondo logica PO
              $author_name_parts = [];

              if ($field_surname_first) {
                // SURNAME_FIRST: SURNAME + GIVEN_NAME + FULLNAME
                if (!empty($field_lastname_latin)) {
                  $author_name_parts[] = $field_lastname_latin;
                }
                if (!empty($field_firstname_latin)) {
                  $author_name_parts[] = $field_firstname_latin;
                }
                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              } else {
                // NOT SURNAME_FIRST: SURNAME, GIVEN_NAME + FULLNAME
                $name_with_comma = [];
                if (!empty($field_lastname_latin)) {
                  $name_with_comma[] = $field_lastname_latin;
                }
                if (!empty($field_firstname_latin)) {
                  $name_with_comma[] = $field_firstname_latin;
                }

                if (!empty($name_with_comma)) {
                  $author_name_parts[] = implode(', ', $name_with_comma);
                }

                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              }

              // Costruisci stringa autore
              $author_string = implode(' ', $author_name_parts);

              // Aggiungi (Attributed) se presente
              if ($field_attributed_authorship) {
                $author_string .= ' (Attributed)';
              }

              $authors[] = $author_string;
            }
          }

          // Se ci sono più di max_authors, aggiungi "et al."
          if (count($field_texts_authors) > $max_authors) {
            $authors[] = 'et al.';
          }

          // Unisci autori
          if (!empty($authors)) {
            $authors_string = implode('; ', $authors);

            // Aggiungi label editor se necessario (una volta sola alla fine)
            $authors_string = self::addEditorLabelIfNeeded($entity, $authors_string);

            $author_line_parts[] = $authors_string;
          }
        }
      }
    }

    // Aggiungi YEAR_LABEL (da field_year_range)
    // Nota: field_year_range è un custom field, probabilmente ha subfields
    // Assumo che abbia un subfield 'label' o simile per il display

    if ($entity->hasField('field_year_range')) {

      $year_range_field = $entity->get('field_year_range');
      if ($year_range_field && !$year_range_field->isEmpty()) {
        /** @var \Drupal\Core\Field\FieldItemInterface $year_range_item */
        $year_range_item = $year_range_field->first();

        if ($year_range_item) {
          $year_from = NULL;
          $year_to = NULL;
          $century_label = NULL;

          // Accedi ai subfields del custom field
          if ($year_range_item->__isset('year_from')) {
            $year_from = $year_range_item->year_from;
          }
          if ($year_range_item->__isset('year_to')) {
            $year_to = $year_range_item->year_to;
          }
          if ($year_range_item->__isset('century_label')) {
            $century_label = $year_range_item->century_label;
          }

          // Priorità: usa century_label se presente, altrimenti costruisci da year_from/year_to
          if (!empty($century_label)) {
            $author_line_parts[] = ' / ' . $century_label . '';
          }

        }
      }
    }


    // Aggiungi linea autori all'output
    if (!empty($author_line_parts)) {
      $output[] = implode(' ', $author_line_parts);
    }

    // =============================================================================
    // PARTE 3: GENRE + LANGUAGE
    // =============================================================================

    $genre_lang_parts = [];

    // field_text_genres (GENRE - Tassonomia)
    if ($entity->hasField('field_text_genres')) {
      $genre_field = $entity->get('field_text_genres');
      if ($genre_field && !$genre_field->isEmpty()) {
        $field_text_genres = $genre_field->entity;
        if ($field_text_genres) {
          $genre_lang_parts[] = $field_text_genres->getName();
        }
      }
    }

    // field_language (LANGUAGE - Tassonomia)
    if ($entity->hasField('field_language')) {
      $language_field = $entity->get('field_language');
      if ($language_field && !$language_field->isEmpty()) {
        $field_language = $language_field->entity;
        if ($field_language) {
          $genre_lang_parts[] = $field_language->getName();
        }
      }
    }

    // Aggiungi GENRE – LANGUAGE
    if (!empty($genre_lang_parts)) {
      $output[] = '' . implode(' / ', $genre_lang_parts) . '';
    }

    // =============================================================================
    // OUTPUT FINALE
    // =============================================================================

    return implode('<br>', $output);
  }

  /**
   * Render IMAGES display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Rendered output.
   */
  public static function render_format_images_display(EntityInterface $entity)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $output = [];

    // =============================================================================
    // PARTE 1: TITOLO
    // =============================================================================

    $title_latin = $entity->label(); // title del nodo
    $field_title_original = '';
    $field_title_translated = '';

    // field_title_original (TITLE_ORIG_NON-LATIN)
    if ($entity->hasField('field_title_original')) {
      $title_original_field = $entity->get('field_title_original');
      if ($title_original_field && !$title_original_field->isEmpty()) {
        $field_title_original = $title_original_field->value;
      }
    }

    // field_title_translated (TRANSLATED TITLE)
    if ($entity->hasField('field_title_translated')) {
      $title_translated_field = $entity->get('field_title_translated');
      if ($title_translated_field && !$title_translated_field->isEmpty()) {
        $field_title_translated = $title_translated_field->value;
      }
    }

    // Logica titolo secondo PO
    if (!empty($title_latin)) {
      // Se TITLE_(LATIN) ha valore
      $title_parts = [];
      $title_parts[] = $title_latin;

      if (!empty($field_title_original)) {
        $title_parts[] = $field_title_original;
      }

      $output[] = '<span class="result-item-title">' . implode(' ', $title_parts) . '</span>';

      // Translated title in square brackets
      if (!empty($field_title_translated)) {
        $output[] = '[' . $field_title_translated . ']';
      }
    } else {
      // Altrimenti usa TRANSLATED_TITLE
      if (!empty($field_title_translated)) {
        $output[] = '<span class="result-item-title">' . $field_title_translated . '</span>';
      }
    }

    // =============================================================================
    // PARTE 2: AUTORI (da paragraph field_texts_authors)
    // =============================================================================

    $author_line_parts = [];

    // Check se è anonimo
    $field_is_anonymous = FALSE;
    if ($entity->hasField('field_is_anonymous')) {
      $anonymous_field = $entity->get('field_is_anonymous');
      if ($anonymous_field && !$anonymous_field->isEmpty()) {
        $field_is_anonymous = (bool) $anonymous_field->value;
      }
    }

    if ($field_is_anonymous) {
      // Se anonimo, scrivi "Anonymous"
      $author_line_parts[] = 'Anonymous';
    } else {
      // Altrimenti processa gli autori dal paragraph
      if ($entity->hasField('field_responsibles')) {
        $field_texts_authors = $entity->get('field_responsibles');

        if ($field_texts_authors && !$field_texts_authors->isEmpty()) {
          $authors = [];
          $max_authors = 2; // Mostra max 3 autori, poi "et al."

          foreach ($field_texts_authors as $index => $item) {
            if ($index >= $max_authors) {
              break; // Interrompi dopo 3 autori
            }

            if ($item && $item->entity) {
              $author_paragraph = $item->entity;

              // Estrai campi dal paragraph
              $field_lastname_latin = '';
              $field_firstname_latin = '';
              $field_fullname = '';
              $field_surname_first = FALSE;
              $field_attributed_authorship = FALSE;

              // field_lastname_latin (Surname latin)
              if ($author_paragraph->hasField('field_lastname')) {
                $lastname_field = $author_paragraph->get('field_lastname');
                if ($lastname_field && !$lastname_field->isEmpty()) {
                  $field_lastname_latin = $lastname_field->value;
                }
              }

              // field_firstname_latin (Given Name latin)
              if ($author_paragraph->hasField('field_firstname')) {
                $firstname_field = $author_paragraph->get('field_firstname');
                if ($firstname_field && !$firstname_field->isEmpty()) {
                  $field_firstname_latin = $firstname_field->value;
                }
              }

              // field_fullname (Full Name Original non Latin)
              if ($author_paragraph->hasField('field_fullname')) {
                $fullname_field = $author_paragraph->get('field_fullname');
                if ($fullname_field && !$fullname_field->isEmpty()) {
                  $field_fullname = $fullname_field->value;
                }
              }

              // field_surname_first
              if ($author_paragraph->hasField('field_surname_first')) {
                $surname_first_field = $author_paragraph->get('field_surname_first');
                if ($surname_first_field && !$surname_first_field->isEmpty()) {
                  $field_surname_first = (bool) $surname_first_field->value;
                }
              }

              // field_attributed_authorship
              if ($author_paragraph->hasField('field_attributed_authorship')) {
                $attributed_field = $author_paragraph->get('field_attributed_authorship');
                if ($attributed_field && !$attributed_field->isEmpty()) {
                  $field_attributed_authorship = (bool) $attributed_field->value;
                }
              }

              // Costruisci nome autore secondo logica PO
              $author_name_parts = [];

              if ($field_surname_first) {
                // SURNAME_FIRST: SURNAME + GIVEN_NAME + FULLNAME
                if (!empty($field_lastname_latin)) {
                  $author_name_parts[] = $field_lastname_latin;
                }
                if (!empty($field_firstname_latin)) {
                  $author_name_parts[] = $field_firstname_latin;
                }
                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              } else {
                // NOT SURNAME_FIRST: SURNAME, GIVEN_NAME + FULLNAME
                $name_with_comma = [];
                if (!empty($field_lastname_latin)) {
                  $name_with_comma[] = $field_lastname_latin;
                }
                if (!empty($field_firstname_latin)) {
                  $name_with_comma[] = $field_firstname_latin;
                }

                if (!empty($name_with_comma)) {
                  $author_name_parts[] = implode(', ', $name_with_comma);
                }

                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              }

              // Costruisci stringa autore
              $author_string = implode(' ', $author_name_parts);

              // Aggiungi (Attributed) se presente
              if ($field_attributed_authorship) {
                $author_string .= ' (Attributed)';
              }

              $authors[] = $author_string;
            }
          }

          // Se ci sono più di max_authors, aggiungi "et al."
          if (count($field_texts_authors) > $max_authors) {
            $authors[] = 'et al.';
          }

          // Unisci autori
          if (!empty($authors)) {
            $authors_string = implode('; ', $authors);

            // Aggiungi label editor se necessario (una volta sola alla fine)
            $authors_string = self::addEditorLabelIfNeeded($entity, $authors_string);

            $author_line_parts[] = $authors_string;

          }
        }
      }
    }


    // Aggiungi YEAR_LABEL (da field_year_range)
    if ($entity->hasField('field_year_range')) {

      $year_range_field = $entity->get('field_year_range');
      if ($year_range_field && !$year_range_field->isEmpty()) {
        /** @var \Drupal\Core\Field\FieldItemInterface $year_range_item */
        $year_range_item = $year_range_field->first();

        if ($year_range_item) {
          $year_from = NULL;
          $year_to = NULL;
          $century_label = NULL;

          // Accedi ai subfields del custom field
          if ($year_range_item->__isset('year_from')) {
            $year_from = $year_range_item->year_from;
          }
          if ($year_range_item->__isset('year_to')) {
            $year_to = $year_range_item->year_to;
          }
          if ($year_range_item->__isset('century_label')) {
            $century_label = $year_range_item->century_label;
          }

          // Priorità: usa century_label se presente, altrimenti costruisci da year_from/year_to
          if (!empty($century_label)) {
            $author_line_parts[] = ' / ' . $century_label . '';
          }

        }
      }
    }

    // Aggiungi linea autori all'output
    if (!empty($author_line_parts)) {
      $output[] = implode(' ', $author_line_parts);
    }

    // =============================================================================
    // PARTE 3: CATEGORY + COUNTRY
    // =============================================================================

    $category_country_parts = [];

    // field_image_category (CATEGORY - Tassonomia Image Formats)
    if ($entity->hasField('field_image_category')) {
      $category_field = $entity->get('field_image_category');
      if ($category_field && !$category_field->isEmpty()) {
        $field_image_category = $category_field->entity;
        if ($field_image_category) {
          $category_country_parts[] = $field_image_category->getName();
        }
      }
    }

    // field_country (COUNTRY - Tassonomia Countries)
    if ($entity->hasField('field_country')) {
      $country_field = $entity->get('field_country');
      if ($country_field && !$country_field->isEmpty()) {
        $field_country = $country_field->entity;
        if ($field_country) {
          $category_country_parts[] = $field_country->getName();
        }
      }
    }

    // Aggiungi CATEGORY – COUNTRY
    if (!empty($category_country_parts)) {
      $output[] = '' . implode(' / ', $category_country_parts) . '';
    }

    // =============================================================================
    // OUTPUT FINALE
    // =============================================================================

    return implode('<br>', $output);
  }


  /**
   * Render VIDEOS display.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Rendered output.
   */
  public static function render_format_videos_display(EntityInterface $entity)
  {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $output = [];

    // =============================================================================
    // PARTE 1: TITOLO
    // =============================================================================

    $title_latin = $entity->label(); // title del nodo
    $field_title_original = '';
    $field_title_translated = '';

    // field_title_original (TITLE_ORIG_NON-LATIN)
    if ($entity->hasField('field_title_original')) {
      $title_original_field = $entity->get('field_title_original');
      if ($title_original_field && !$title_original_field->isEmpty()) {
        $field_title_original = $title_original_field->value;
      }
    }

    // field_title_translated (TRANSLATED TITLE)
    if ($entity->hasField('field_title_translated')) {
      $title_translated_field = $entity->get('field_title_translated');
      if ($title_translated_field && !$title_translated_field->isEmpty()) {
        $field_title_translated = $title_translated_field->value;
      }
    }

    // Logica titolo secondo PO
    if (!empty($title_latin)) {
      // Se TITLE_(LATIN) ha valore
      $title_parts = [];
      $title_parts[] = $title_latin;

      if (!empty($field_title_original)) {
        $title_parts[] = $field_title_original;
      }

      $output[] = '<span class="result-item-title">' . implode(' ', $title_parts) . '</span>';

      // Translated title in square brackets
      if (!empty($field_title_translated)) {
        $output[] = '[' . $field_title_translated . ']';
      }
    } else {
      // Altrimenti usa TRANSLATED_TITLE
      if (!empty($field_title_translated)) {
        $output[] = '<span class="result-item-title">' . $field_title_translated . '</span>';
      }
    }

    // =============================================================================
    // PARTE 2: AUTORI
    // IMPORTANTE: Questo content type usa il paragraph "texts_authors" (field_responsibles)
    // Se in futuro viene creato un nuovo paragraph con campi diversi, modificare qui:
    // - Nome del field paragraph
    // - Nomi dei subfields nel paragraph
    // =============================================================================

    $author_line_parts = [];

    // Check se è anonimo
    $field_is_anonymous = FALSE;
    if ($entity->hasField('field_is_anonymous')) {
      $anonymous_field = $entity->get('field_is_anonymous');
      if ($anonymous_field && !$anonymous_field->isEmpty()) {
        $field_is_anonymous = (bool) $anonymous_field->value;
      }
    }

    if ($field_is_anonymous) {
      // Se anonimo, scrivi "Anonymous"
      $author_line_parts[] = 'Anonymous';
    } else {
      // Altrimenti processa gli autori dal paragraph
      // PARAGRAPH UTILIZZATO: field_texts_authors (tipo: texts_authors)
      if ($entity->hasField('field_responsibles')) {
        $field_texts_authors = $entity->get('field_responsibles');

        if ($field_texts_authors && !$field_texts_authors->isEmpty()) {
          $authors = [];
          $max_authors = 2; // Mostra max 3 autori, poi "et al."

          foreach ($field_texts_authors as $index => $item) {
            if ($index >= $max_authors) {
              break; // Interrompi dopo 3 autori
            }

            if ($item && $item->entity) {
              $author_paragraph = $item->entity;

              // Estrai campi dal paragraph texts_authors
              $field_lastname = '';
              $field_firstname = '';
              $field_fullname = '';
              $field_pseudonym = '';
              $field_surname_first = FALSE;
              $field_is_organization = FALSE;
              $field_attributed_authorship = FALSE;

              // field_lastname_latin (Surname latin)
              if ($author_paragraph->hasField('field_lastname')) {
                $lastname_field = $author_paragraph->get('field_lastname');
                if ($lastname_field && !$lastname_field->isEmpty()) {
                  $field_lastname = $lastname_field->value;
                }
              }

              // field_firstname_latin (Given Name latin)
              if ($author_paragraph->hasField('field_firstname')) {
                $firstname_field = $author_paragraph->get('field_firstname');
                if ($firstname_field && !$firstname_field->isEmpty()) {
                  $field_firstname = $firstname_field->value;
                }
              }

              // field_fullname (Full Name Original non Latin)
              if ($author_paragraph->hasField('field_fullname')) {
                $fullname_field = $author_paragraph->get('field_fullname');
                if ($fullname_field && !$fullname_field->isEmpty()) {
                  $field_fullname = $fullname_field->value;
                }
              }

              // field_pseudonym (Pseudonym) - potrebbe non esistere
              if ($author_paragraph->hasField('field_pseudonym')) {
                $pseudonym_field = $author_paragraph->get('field_pseudonym');
                if ($pseudonym_field && !$pseudonym_field->isEmpty()) {
                  $field_pseudonym = $pseudonym_field->value;
                }
              }

              // field_surname_first
              if ($author_paragraph->hasField('field_surname_first')) {
                $surname_first_field = $author_paragraph->get('field_surname_first');
                if ($surname_first_field && !$surname_first_field->isEmpty()) {
                  $field_surname_first = (bool) $surname_first_field->value;
                }
              }

              // field_is_organization (Is Organization) - potrebbe non esistere
              if ($author_paragraph->hasField('field_is_organization')) {
                $is_org_field = $author_paragraph->get('field_is_organization');
                if ($is_org_field && !$is_org_field->isEmpty()) {
                  $field_is_organization = (bool) $is_org_field->value;
                }
              }

              // field_attributed_authorship
              if ($author_paragraph->hasField('field_attributed_authorship')) {
                $attributed_field = $author_paragraph->get('field_attributed_authorship');
                if ($attributed_field && !$attributed_field->isEmpty()) {
                  $field_attributed_authorship = (bool) $attributed_field->value;
                }
              }

              // Costruisci nome autore secondo logica PO
              $author_name_parts = [];

              if ($field_is_organization) {
                // ORGANIZATION: SURNAME + FULLNAME
                if (!empty($field_lastname)) {
                  $author_name_parts[] = $field_lastname;
                }
                if (!empty($field_fullname)) {
                  $author_name_parts[] = $field_fullname;
                }
              } else {
                // PERSON
                if ($field_surname_first) {
                  // SURNAME_FIRST: SURNAME + GIVEN_NAME + FULLNAME
                  if (!empty($field_lastname)) {
                    $author_name_parts[] = $field_lastname;
                  }
                  if (!empty($field_firstname)) {
                    $author_name_parts[] = $field_firstname;
                  }
                  if (!empty($field_fullname)) {
                    $author_name_parts[] = $field_fullname;
                  }
                } else {
                  // NOT SURNAME_FIRST: SURNAME, GIVEN_NAME + FULLNAME
                  $name_with_comma = [];
                  if (!empty($field_lastname)) {
                    $name_with_comma[] = $field_lastname;
                  }
                  if (!empty($field_firstname)) {
                    $name_with_comma[] = $field_firstname;
                  }

                  if (!empty($name_with_comma)) {
                    $author_name_parts[] = implode(', ', $name_with_comma);
                  }

                  if (!empty($field_fullname)) {
                    $author_name_parts[] = $field_fullname;
                  }
                }
              }

              // Costruisci stringa autore
              $author_string = implode(' ', $author_name_parts);

              // Aggiungi (PSEUDONYM) in round brackets se presente
              if (!empty($field_pseudonym)) {
                $author_string .= ' (' . $field_pseudonym . ')';
              }

              // Aggiungi (Attributed) se presente
              if ($field_attributed_authorship) {
                $author_string .= ' (Attributed)';
              }

              $authors[] = $author_string;
            }
          }

          // Se ci sono più di max_authors, aggiungi "et al."
          if (count($field_texts_authors) > $max_authors) {
            $authors[] = 'et al.';
          }

          // Unisci autori
          if (!empty($authors)) {
            $authors_string = implode('; ', $authors);

            // Aggiungi label editor se necessario (una volta sola alla fine)
            $authors_string = self::addEditorLabelIfNeeded($entity, $authors_string);

            $author_line_parts[] = $authors_string;
          }
        }
      }
    }

    // Aggiungi PRODUCTION YEAR con dash
    if ($entity->hasField('field_production_year')) {
      $production_year_field = $entity->get('field_production_year');
      if ($production_year_field && !$production_year_field->isEmpty()) {
        $field_production_year = $production_year_field->value;
        if (!empty($field_production_year)) {
          $author_line_parts[] = '/ ' . $field_production_year . '';
        }
      }
    }

    // Aggiungi linea autori all'output
    if (!empty($author_line_parts)) {
      $output[] = implode(' ', $author_line_parts);
    }

    // =============================================================================
    // PARTE 3: GENRE + LANGUAGE
    // =============================================================================

    $genre_lang_parts = [];

    // field_text_genres (GENRE - Tassonomia)
    if ($entity->hasField('field_text_genres')) {
      $genre_field = $entity->get('field_text_genres');
      if ($genre_field && !$genre_field->isEmpty()) {
        $field_text_genres = $genre_field->entity;
        if ($field_text_genres) {
          $genre_lang_parts[] = $field_text_genres->getName();
        }
      }
    }

    // field_language (LANGUAGE - Tassonomia)
    if ($entity->hasField('field_language')) {
      $language_field = $entity->get('field_language');
      if ($language_field && !$language_field->isEmpty()) {
        $field_language = $language_field->entity;
        if ($field_language) {
          $genre_lang_parts[] = $field_language->getName();
        }
      }
    }

    // Aggiungi GENRE – LANGUAGE
    if (!empty($genre_lang_parts)) {
      $output[] = '' . implode(' / ', $genre_lang_parts) . '';
    }

    // =============================================================================
    // OUTPUT FINALE
    // =============================================================================

    return implode('<br>', $output);
  }
}