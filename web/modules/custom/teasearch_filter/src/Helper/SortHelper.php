<?php

namespace Drupal\teasearch_filter\Helper;

use Drupal\teasearch_filter\Config\SortConfig;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helper per applicare il sort alle query di ricerca.
 *
 * Gestisce:
 *  - Sort su campi semplici (field_year, field_title, ecc.)
 *  - Sort per last_updated (campo 'changed')
 *  - Sort per internal_references (conta quante volte il nodo è referenziato)
 *  - Sort su campi nested (paragraph > field_lastname) — via PHP post-query
 *  - Tiebreaker multipli
 *
 * USO nel SearchController:
 *   $sort_resolved = SortHelper::resolveFromRequest($content_type, $request);
 *   // In searchNodes(): prima di ->execute(), chiama applyToQuery()
 *   // Per internal_references o nested: applica sortEntitiesInMemory() dopo il load
 */
class SortHelper
{

  /**
   * Legge il parametro ?sort= dalla request e risolve la config.
   *
   * @param string $content_type
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return array|null  NULL se il content_type non ha config sort
   */
  public static function resolveFromRequest(string $content_type, Request $request): ?array
  {
    $requested_sort = $request->query->get('sort');
    return SortConfig::resolve($content_type, $requested_sort);
  }

  /**
   * Applica il sort alla EntityQuery Drupal (per sort su campi DB diretti).
   * NON gestisce 'internal_references' e 'nested' (richiedono PHP post-query).
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   * @param array $resolved  Output di SortConfig::resolve()
   * @param string $entity_type  'node' | 'user'
   */
  public static function applyToQuery($query, array $resolved, string $entity_type = 'node'): void
  {
    $option = $resolved['option'];
    $type = $option['type'];

    // Tipi che richiedono ordinamento PHP — skip qui
    if (in_array($type, ['internal_references', 'nested'])) {
      return;
    }

    // Sort principale
    $field = static::resolveFieldName($option, $entity_type);
    $direction = strtoupper($option['direction'] ?? 'ASC');

    if ($field) {
      $query->sort($field, $direction);
    }

    // Tiebreakers (solo quelli applicabili alla query DB)
    foreach ($option['tiebreakers'] ?? [] as $tb) {
      if (($tb['type'] ?? '') === 'internal_references') {
        continue; // Skip — richiede PHP
      }
      $tb_field = static::resolveTiebreakerField($tb, $entity_type);
      if ($tb_field) {
        $query->sort($tb_field, strtoupper($tb['direction'] ?? 'ASC'));
      }
    }
  }

  /**
   * Ordina un array di entity già caricate in memoria.
   * Usato per:
   *  - internal_references (richiede conteggio query separata)
   *  - nested (sort su campo paragraph)
   *  - qualsiasi sort non gestibile direttamente dalla EntityQuery
   *
   * @param array $entities        Array di nodi/utenti già caricati
   * @param array $resolved        Output di SortConfig::resolve()
   * @param string $entity_type    'node' | 'user'
   * @return array                 Array riordinato
   */
  public static function sortEntitiesInMemory(array $entities, array $resolved, string $entity_type = 'node'): array
  {
    if (empty($entities)) {
      return $entities;
    }

    $option = $resolved['option'];
    $type = $option['type'];

    // Pre-calcola i valori di sort per ogni entity
    $sort_values = [];
    foreach ($entities as $id => $entity) {
      $sort_values[$id] = static::extractSortValue($entity, $option, $entity_type);
    }

    // Calcola tiebreaker values
    $tb_values = [];
    foreach ($option['tiebreakers'] ?? [] as $tb_idx => $tb) {
      $tb_values[$tb_idx] = [];
      foreach ($entities as $id => $entity) {
        $tb_values[$tb_idx][$id] = static::extractTiebreakerValue($entity, $tb, $entity_type);
      }
    }

    $direction = strtoupper($option['direction'] ?? 'ASC');

    uasort($entities, function ($a, $b) use ($sort_values, $tb_values, $option, $direction) {
      $id_a = static::getEntityId($a);
      $id_b = static::getEntityId($b);

      $val_a = $sort_values[$id_a] ?? '';
      $val_b = $sort_values[$id_b] ?? '';

      $cmp = static::compareValues($val_a, $val_b, $direction);
      if ($cmp !== 0) {
        return $cmp;
      }

      // Tiebreakers
      foreach ($option['tiebreakers'] ?? [] as $tb_idx => $tb) {
        $tb_dir = strtoupper($tb['direction'] ?? 'ASC');
        $tb_a = $tb_values[$tb_idx][$id_a] ?? '';
        $tb_b = $tb_values[$tb_idx][$id_b] ?? '';
        $tb_cmp = static::compareValues($tb_a, $tb_b, $tb_dir);
        if ($tb_cmp !== 0) {
          return $tb_cmp;
        }
      }
      return 0;
    });

    return $entities;
  }

  /**
   * Determina se il sort richiede ordinamento in memoria PHP.
   */
  public static function requiresInMemorySort(array $resolved): bool
  {
    $type = $resolved['option']['type'] ?? '';
    return in_array($type, ['internal_references', 'nested']);
  }

  // ===========================================================================
  // METODI PRIVATI
  // ===========================================================================

  /**
   * Risolve il nome del campo per la EntityQuery.
   */
  private static function resolveFieldName(array $option, string $entity_type): ?string
  {
    $type = $option['type'] ?? 'field';

    if ($type === 'last_updated') {
      return $entity_type === 'user' ? 'changed' : 'changed';
    }

    if ($type === 'field') {
      $field = $option['field'] ?? NULL;
      if (!$field)
        return NULL;

      // Campi speciali senza prefisso field_
      $no_prefix = ['nid', 'uid', 'created', 'changed', 'title', 'status'];
      if (in_array($field, $no_prefix)) {
        return $field;
      }
      // Alcuni campi potrebbero già avere field_ nel config
      return $field;
    }

    return NULL;
  }

  /**
   * Risolve il nome del campo per un tiebreaker.
   */
  private static function resolveTiebreakerField(array $tb, string $entity_type): ?string
  {
    $type = $tb['type'] ?? 'field';

    if ($type === 'last_updated') {
      return 'changed';
    }
    if ($type === 'field') {
      return $tb['field'] ?? NULL;
    }
    return NULL;
  }

  /**
   * Estrae il valore di sort da una entity per ordinamento in memoria.
   */
  private static function extractSortValue($entity, array $option, string $entity_type)
  {
    $type = $option['type'] ?? 'field';

    if ($type === 'last_updated') {
      return (int) $entity->getChangedTime();
    }

    if ($type === 'internal_references') {
      return static::countInternalReferences($entity);
    }

    if ($type === 'nested') {
      return static::extractNestedFieldValue($entity, $option['nested_path'] ?? '');
    }

    if ($type === 'field') {
      return static::extractFieldValue($entity, $option['field'] ?? '');
    }

    return '';
  }

  /**
   * Estrae il valore per un tiebreaker da una entity.
   */
  private static function extractTiebreakerValue($entity, array $tb, string $entity_type)
  {
    $type = $tb['type'] ?? 'field';

    if ($type === 'last_updated') {
      return (int) $entity->getChangedTime();
    }
    if ($type === 'internal_references') {
      return static::countInternalReferences($entity);
    }
    if ($type === 'field') {
      return static::extractFieldValue($entity, $tb['field'] ?? '');
    }
    return '';
  }

  /**
   * Legge il valore di un campo semplice da una entity.
   */
  private static function extractFieldValue($entity, string $field_name)
  {
    if (empty($field_name))
      return '';

    // Campi speciali
    if ($field_name === 'nid')
      return (int) $entity->id();
    if ($field_name === 'uid')
      return (int) $entity->id();
    if ($field_name === 'title')
      return strtolower($entity->label() ?? '');

    // Campo con subproperty es. field_year_range.year_from
    if (str_contains($field_name, '.')) {
      [$field, $property] = explode('.', $field_name, 2);
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        $item = $entity->get($field)->first();
        return $item->get($property)?->getValue() ?? '';
      }
      return '';
    }

    if (!$entity->hasField($field_name))
      return '';
    if ($entity->get($field_name)->isEmpty())
      return '';

    $value = $entity->get($field_name)->value;
    // Tenta la conversione numerica per campi year/number
    if (is_numeric($value))
      return (float) $value;
    return strtolower((string) $value);
  }

  /**
   * Legge il valore da un campo nested (paragraph).
   * nested_path: 'field_responsibles.authors_general.field_lastname'
   */
  private static function extractNestedFieldValue($entity, string $nested_path): string
  {
    if (empty($nested_path))
      return '';

    $parts = explode('.', $nested_path);
    // Atteso: [paragraph_field, paragraph_type, target_field]
    if (count($parts) < 3)
      return '';

    [$paragraph_field, , $target_field] = $parts;

    if (!$entity->hasField($paragraph_field) || $entity->get($paragraph_field)->isEmpty()) {
      return '';
    }

    // Prende il primo item nel campo paragraph
    foreach ($entity->get($paragraph_field) as $item) {
      $referenced = $item->entity;
      if (!$referenced)
        continue;

      if ($referenced->hasField($target_field) && !$referenced->get($target_field)->isEmpty()) {
        $value = $referenced->get($target_field)->value;
        return strtolower((string) $value);
      }
    }

    return '';
  }

  /**
   * Conta quante volte un nodo è referenziato internamente
   * tramite field_teasearch_references.
   */
  private static function countInternalReferences($entity): int
  {
    $nid = $entity->id();
    if (!$nid)
      return 0;

    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('field_teasearch_references', $nid)
      ->accessCheck(FALSE)
      ->count();

    return (int) $query->execute();
  }

  /**
   * Confronta due valori tenendo conto della direzione ASC/DESC.
   */
  private static function compareValues($a, $b, string $direction): int
  {
    if (is_numeric($a) && is_numeric($b)) {
      $cmp = $a <=> $b;
    } else {
      $cmp = strcmp((string) $a, (string) $b);
    }
    return $direction === 'DESC' ? -$cmp : $cmp;
  }

  /**
   * Ottiene l'ID univoco di una entity (nid o uid).
   */
  private static function getEntityId($entity): string
  {
    return (string) $entity->id();
  }

}