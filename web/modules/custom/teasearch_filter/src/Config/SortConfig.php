<?php

namespace Drupal\teasearch_filter\Config;

/**
 * Configurazione statica delle opzioni di sort per ogni content type.
 *
 * Modificare questo file non richiede il reload del modulo.
 * Ogni content_type ha:
 *   - 'default': chiave dell'opzione di sort predefinita
 *   - 'options': array di opzioni, ognuna con:
 *       - 'label': etichetta in inglese
 *       - 'label_it': etichetta in italiano
 *       - 'type': 'field' | 'last_updated' | 'internal_references' | 'nested'
 *       - 'field': nome del campo Drupal (per type=field)
 *       - 'direction': 'ASC' | 'DESC'
 *       - 'nested_path': per campi nested (es. paragraph > field_lastname)
 *       - 'tiebreakers': array di sort secondari [{field, direction}]
 */
class SortConfig
{

  /**
   * Restituisce la configurazione sort per tutti i content type.
   *
   * @return array
   */
  public static function getConfig(): array
  {
    return [

      // =====================================================================
      // PEOPLE (entity type: user)
      // =====================================================================
      'people' => [
        'default' => 'editorial_order',
        'options' => [
          'editorial_order' => [
            'label' => 'Editorial order',
            'label_it' => 'Ordine editoriale',
            'type' => 'field',
            'field' => 'field_number_of_contributions',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'field_lastname', 'direction' => 'ASC'],
              ['field' => 'field_firstname', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'uid', 'direction' => 'ASC'],
            ],
          ],
          'lastname_asc' => [
            'label' => 'Surname (A-Z)',
            'label_it' => 'Cognome (A–Z)',
            'type' => 'field',
            'field' => 'field_lastname',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'field_firstname', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'uid', 'direction' => 'ASC'],
            ],
          ],
          'lastname_desc' => [
            'label' => 'Surname (Z-A)',
            'label_it' => 'Cognome (Z-A)',
            'type' => 'field',
            'field' => 'field_lastname',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'field_firstname', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'uid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'uid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

      // =====================================================================
      // BIBLIOGRAPHY
      // =====================================================================
      'bibliography' => [
        'default' => 'year_desc',
        'options' => [
          'year_desc' => [
            'label' => 'Year (newest first)',
            'label_it' => 'Anno (più recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'title', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'year_asc' => [
            'label' => 'Year (oldest first)',
            'label_it' => 'Anno (meno recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'title', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'title_asc' => [
            'label' => 'Title (A-Z)',
            'label_it' => 'Titolo (A–Z)',
            'type' => 'field',
            'field' => 'title',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'author_asc' => [
            'label' => 'Author (A-Z)',
            'label_it' => 'Autore (A-Z)',
            'type' => 'nested',
            'nested_path' => 'field_responsibles.authors_general.field_lastname',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_desc' => [
            'label' => 'Internal references (High to Low)',
            'label_it' => 'Riferimenti interni (decrescente)',
            'type' => 'internal_references',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_asc' => [
            'label' => 'Internal references (Low to High)',
            'label_it' => 'Riferimenti interni (crescente)',
            'type' => 'internal_references',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

      // =====================================================================
      // TEXTS (machine_name: primary_sources)
      // =====================================================================
      'texts' => [
        'default' => 'date_earliest',
        'options' => [
          'date_earliest' => [
            'label' => 'Date (earliest first)',
            'label_it' => 'Data (meno recenti)',
            'type' => 'field',
            'field' => 'field_year_range.year_from',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'field_year_range.year_to', 'direction' => 'ASC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'date_latest' => [
            'label' => 'Date (latest first)',
            'label_it' => 'Data (più recenti)',
            'type' => 'field',
            'field' => 'field_year_range.year_to',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'field_year_range.year_from', 'direction' => 'ASC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'title_asc' => [
            'label' => 'Title (A-Z)',
            'label_it' => 'Titolo (A–Z)',
            'type' => 'field',
            'field' => 'title',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'author_asc' => [
            'label' => 'Author (A-Z)',
            'label_it' => 'Autore (A-Z)',
            'type' => 'nested',
            'nested_path' => 'field_responsibles.authors_general.field_lastname',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_desc' => [
            'label' => 'Internal references (High to Low)',
            'label_it' => 'Riferimenti interni (decrescente)',
            'type' => 'internal_references',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_asc' => [
            'label' => 'Internal references (Low to High)',
            'label_it' => 'Riferimenti interni (crescente)',
            'type' => 'internal_references',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

      // =====================================================================
      // IMAGES
      // =====================================================================
      'images' => [
        'default' => 'year_desc',
        'options' => [
          'year_desc' => [
            'label' => 'Year (newest first)',
            'label_it' => 'Anno (più recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'year_asc' => [
            'label' => 'Year (oldest first)',
            'label_it' => 'Anno (meno recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'title_asc' => [
            'label' => 'Title (A-Z)',
            'label_it' => 'Titolo (A–Z)',
            'type' => 'field',
            'field' => 'title',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'author_asc' => [
            'label' => 'Author (A-Z)',
            'label_it' => 'Autore (A-Z)',
            'type' => 'nested',
            'nested_path' => 'field_responsibles.authors_general.field_lastname',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_desc' => [
            'label' => 'Internal references (High to Low)',
            'label_it' => 'Riferimenti interni (decrescente)',
            'type' => 'internal_references',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_asc' => [
            'label' => 'Internal references (Low to High)',
            'label_it' => 'Riferimenti interni (crescente)',
            'type' => 'internal_references',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

      // =====================================================================
      // VIDEO (machine_name: videos)
      // =====================================================================
      'video' => [
        'default' => 'year_desc',
        'options' => [
          'year_desc' => [
            'label' => 'Year (newest first)',
            'label_it' => 'Anno (più recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'year_asc' => [
            'label' => 'Year (oldest first)',
            'label_it' => 'Anno (meno recenti)',
            'type' => 'field',
            'field' => 'field_year',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'title_asc' => [
            'label' => 'Title (A-Z)',
            'label_it' => 'Titolo (A–Z)',
            'type' => 'field',
            'field' => 'title',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'author_asc' => [
            'label' => 'Author (A-Z)',
            'label_it' => 'Autore (A-Z)',
            'type' => 'nested',
            'nested_path' => 'field_responsibles.authors_general.field_lastname',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_desc' => [
            'label' => 'Internal references (High to Low)',
            'label_it' => 'Riferimenti interni (decrescente)',
            'type' => 'internal_references',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_asc' => [
            'label' => 'Internal references (Low to High)',
            'label_it' => 'Riferimenti interni (crescente)',
            'type' => 'internal_references',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

      // =====================================================================
      // ESSENTIALS (machine_name: first_reference)
      // =====================================================================
      'essentials' => [
        'default' => 'editorial_order',
        'options' => [
          'editorial_order' => [
            'label' => 'Editorial order',
            'label_it' => 'Ordine editoriale',
            'type' => 'field',
            'field' => 'field_relevance',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'title', 'direction' => 'ASC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'recently_updated' => [
            'label' => 'Recently updated',
            'label_it' => 'Aggiornati di recente',
            'type' => 'last_updated',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'title_asc' => [
            'label' => 'Title (A-Z)',
            'label_it' => 'Titolo (A–Z)',
            'type' => 'field',
            'field' => 'title',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['type' => 'last_updated', 'direction' => 'DESC'],
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_desc' => [
            'label' => 'Internal references (High to Low)',
            'label_it' => 'Riferimenti interni (decrescente)',
            'type' => 'internal_references',
            'direction' => 'DESC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
          'internal_refs_asc' => [
            'label' => 'Internal references (Low to High)',
            'label_it' => 'Riferimenti interni (crescente)',
            'type' => 'internal_references',
            'direction' => 'ASC',
            'tiebreakers' => [
              ['field' => 'nid', 'direction' => 'ASC'],
            ],
          ],
        ],
      ],

    ];
  }
  
  /**
   * Restituisce la config sort per un singolo content_type.
   *
   * @param string $content_type
   * @return array|null
   */
  public static function getForContentType(string $content_type): ?array
  {
    $config = static::getConfig();
    return $config[$content_type] ?? NULL;
  }

  /**
   * Risolve la chiave sort attiva dalla Request.
   * Ritorna ['key' => string, 'option' => array].
   *
   * @param string $content_type
   * @param string|null $requested_sort  Valore del parametro ?sort= dalla URL
   * @return array|null  NULL se il content_type non ha config sort
   */
  public static function resolve(string $content_type, ?string $requested_sort): ?array
  {
    $type_config = static::getForContentType($content_type);
    if (!$type_config) {
      return NULL;
    }

    $default_key = $type_config['default'];
    $options = $type_config['options'];

    // Usa il sort richiesto solo se esiste tra le opzioni valide
    $active_key = ($requested_sort && isset($options[$requested_sort]))
      ? $requested_sort
      : $default_key;

    return [
      'key' => $active_key,
      'option' => $options[$active_key],
      'options' => $options,
      'default' => $default_key,
    ];
  }

}