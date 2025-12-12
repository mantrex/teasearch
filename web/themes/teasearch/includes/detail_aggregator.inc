<?php

/**
 * TYPE: aggregator
 * Renderizza più campi combinati secondo un template con separatori intelligenti.
 * 
 * SEPARATORI SPECIALI:
 * - {break}   → Separatore di BLOCCO. Divide il template in blocchi indipendenti.
 *               Se un blocco non ha campi valorizzati, viene omesso.
 *               Renderizzato come <br>
 * - {hyphen}  → Separatore CONDIZIONALE " - ". Mostrato solo se ci sono valori sia prima che dopo.
 * - {comma}   → Separatore CONDIZIONALE ", "
 * - {slash}   → Separatore CONDIZIONALE " / "
 * - {pipe}    → Separatore CONDIZIONALE " | "
 * - {space}   → Separatore CONDIZIONALE " "
 * - {dot}     → Separatore CONDIZIONALE ". "
 * - {colon}   → Separatore CONDIZIONALE ": "
 * - {paren}   → Parentesi condizionale - avvolge in () solo se c'è contenuto dopo
 * 
 * CONFIGURAZIONE SCHEMA:
 * 
 * "date" => [
 *     "type" => "aggregator",
 *     "label" => "Date",
 *     "template" => "{field_start_date} {hyphen} {field_end_date}",
 * ],
 * 
 * "contacts" => [
 *     "type" => "aggregator",
 *     "label" => "Contacts",
 *     "template" => "Website: {field_links} {break} Email: {field_email} {break} Tel: {field_telephone}",
 *     "options" => [
 *         "field_types" => [
 *             "field_links" => "link",
 *         ],
 *     ],
 * ],
 * 
 * LOGICA:
 * 1. Il template viene diviso in BLOCCHI usando {break}
 * 2. Ogni blocco viene processato:
 *    - I placeholder {field_*} vengono sostituiti con i valori
 *    - I separatori condizionali vengono mostrati solo se necessario
 *    - Se il blocco non ha alcun campo valorizzato, viene scartato
 * 3. I blocchi valorizzati vengono uniti con <br>
 * 4. Se nessun blocco ha valore, il campo non viene mostrato
 *
 * @param \Drupal\node\NodeInterface $node
 * @param array $field_config
 * @param string $field_identifier
 *
 * @return string|null
 */
function teasearch_render_field_aggregator($node, $field_config, $field_identifier)
{
  $template = $field_config['template'] ?? '';

  if (empty($template)) {
    return NULL;
  }

  // Opzioni
  $options = $field_config['options'] ?? [];
  $field_types = $options['field_types'] ?? [];
  $block_separator_output = $options['block_separator'] ?? '<br>'; // Output HTML per {break}

  // Mappa dei separatori condizionali
  $conditional_separators = [
    '{hyphen}' => ' - ',
    '{comma}' => ', ',
    '{slash}' => ' / ',
    '{pipe}' => ' | ',
    '{space}' => ' ',
    '{dot}' => '. ',
    '{colon}' => ': ',
  ];

  // Step 1: Dividi il template in blocchi usando {break}
  $blocks = preg_split('/\s*\{break\}\s*/', $template);

  // Step 2: Processa ogni blocco
  $rendered_blocks = [];

  foreach ($blocks as $block) {
    $rendered_block = teasearch_aggregator_process_block(
      $node,
      $block,
      $field_types,
      $conditional_separators,
      $options
    );

    if ($rendered_block !== NULL && $rendered_block !== '') {
      $rendered_blocks[] = $rendered_block;
    }
  }

  // Step 3: Se nessun blocco ha contenuto, ritorna NULL
  if (empty($rendered_blocks)) {
    return NULL;
  }

  // Step 4: Unisci i blocchi con il separatore
  return implode($block_separator_output, $rendered_blocks);
}

/**
 * Processa un singolo blocco del template.
 *
 * @param \Drupal\node\NodeInterface $node
 * @param string $block
 * @param array $field_types
 * @param array $conditional_separators
 * @param array $options
 *
 * @return string|null
 */
function teasearch_aggregator_process_block($node, $block, $field_types, $conditional_separators, $options = [])
{
  if (empty(trim($block))) {
    return NULL;
  }

  // Estrai tutti i placeholder campo {field_*}
  preg_match_all('/\{(field_[a-zA-Z0-9_]+)\}/', $block, $matches);
  $field_placeholders = $matches[1] ?? [];

  // Se non ci sono campi in questo blocco, è solo testo statico - scartalo
  if (empty($field_placeholders)) {
    return NULL;
  }

  // Ottieni i valori di tutti i campi
  $field_values = [];
  $has_any_value = false;

  foreach ($field_placeholders as $field_name) {
    $value = teasearch_aggregator_render_single_field($node, $field_name, $field_types, $options);
    $field_values[$field_name] = $value;

    if (!empty($value)) {
      $has_any_value = true;
    }
  }

  // Se nessun campo ha valore, scarta il blocco
  if (!$has_any_value) {
    return NULL;
  }

  // Processa il blocco sostituendo campi e separatori condizionali
  $result = teasearch_aggregator_render_block_content($block, $field_values, $conditional_separators);

  return $result;
}

/**
 * Renderizza il contenuto di un blocco, gestendo separatori condizionali.
 *
 * @param string $block
 * @param array $field_values
 * @param array $conditional_separators
 *
 * @return string|null
 */
function teasearch_aggregator_render_block_content($block, $field_values, $conditional_separators)
{
  // Tokenizza il blocco per gestire i separatori condizionali
  // Pattern: cattura separatori condizionali E placeholder campo E testo statico
  $separator_pattern = implode('|', array_map(function ($s) {
    return preg_quote($s, '/');
  }, array_keys($conditional_separators)));

  // Pattern completo: separatori OPPURE placeholder campo
  $pattern = '/(' . $separator_pattern . '|\{field_[a-zA-Z0-9_]+\})/';

  // Split mantenendo i delimitatori
  $tokens = preg_split($pattern, $block, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

  // Costruisci l'output processando i token
  $output_parts = [];
  $pending_separator = null;
  $last_had_value = false;

  foreach ($tokens as $token) {
    $trimmed_token = trim($token);

    // È un separatore condizionale?
    if (isset($conditional_separators[$trimmed_token])) {
      // Salva il separatore, lo aggiungeremo solo se c'è contenuto dopo
      if ($last_had_value) {
        $pending_separator = $conditional_separators[$trimmed_token];
      }
      continue;
    }

    // È un placeholder campo?
    if (preg_match('/^\{(field_[a-zA-Z0-9_]+)\}$/', $trimmed_token, $m)) {
      $field_name = $m[1];
      $value = $field_values[$field_name] ?? '';

      if (!empty($value)) {
        // Se c'era un separatore pending e c'è già contenuto, aggiungilo
        if ($pending_separator !== null && !empty($output_parts)) {
          $output_parts[] = $pending_separator;
        }
        $output_parts[] = $value;
        $last_had_value = true;
      }
      $pending_separator = null;
      continue;
    }

    // È testo statico (es. "Website: ", "Tel: ", etc.)
    // Lo aggiungiamo solo se il prossimo campo ha valore
    // Per farlo, dobbiamo guardare avanti...
    // Approccio: aggiungiamo il testo, poi lo rimuoviamo se il campo dopo è vuoto

    if (!empty($token)) {
      // Controlla se questo testo statico precede un campo
      $output_parts[] = $token;
    }
  }

  // Unisci e pulisci
  $result = implode('', $output_parts);

  // Pulisci label orfane (es. "Website: " senza valore dopo)
  $result = teasearch_aggregator_clean_orphan_labels($result);

  // Pulisci spazi multipli
  $result = preg_replace('/\s+/', ' ', $result);
  $result = trim($result);

  return !empty($result) ? $result : NULL;
}

/**
 * Helper: Renderizza un singolo campo.
 *
 * @param \Drupal\node\NodeInterface $node
 * @param string $field_name
 * @param array $field_types
 * @param array $options
 *   Opzioni aggiuntive:
 *   - 'date_format': formato PHP per le date (es. 'd/m/Y', 'j F Y')
 *
 * @return string
 */
function teasearch_aggregator_render_single_field($node, $field_name, $field_types = [], $options = [])
{
  // Verifica che il campo esista
  if (!$node->hasField($field_name)) {
    return '';
  }

  $field = teasearch_get_field_value($node, $field_name);

  if (!$field || $field->isEmpty()) {
    return '';
  }

  // Determina il type da usare
  $type = $field_types[$field_name] ?? teasearch_infer_field_type($node, $field_name);

  // Ottieni la funzione render
  $render_function = teasearch_get_type_render_function($type);

  if (!function_exists($render_function)) {
    $render_function = 'teasearch_render_field_text';
  }

  // Chiama la funzione render
  $field_config = ['field' => $field_name];
  $result = $render_function($node, $field_config, $field_name);

  // Gestisce risultati array (render array Drupal)
  if (is_array($result)) {
    if (isset($result['#markup'])) {
      $result = (string) $result['#markup'];
    } else {
      $result = (string) \Drupal::service('renderer')->renderPlain($result);
    }
  }

  $result = $result ?? '';

  // Se è specificato date_format, trasforma le date yyyy-mm-dd
  if (!empty($options['date_format']) && !empty($result)) {
    $result = teasearch_aggregator_format_dates($result, $options['date_format']);
  }

  return $result;
}

/**
 * Helper: Trasforma tutte le date in formato yyyy-mm-dd nel formato specificato.
 *
 * @param string $text
 *   Il testo contenente potenziali date.
 * @param string $format
 *   Il formato PHP per le date (es. 'd/m/Y', 'j F Y', 'd M Y').
 *
 * @return string
 *   Il testo con le date formattate.
 */
function teasearch_aggregator_format_dates($text, $format)
{
  // Pattern per date yyyy-mm-dd (ISO 8601)
  $pattern = '/\b(\d{4})-(\d{2})-(\d{2})\b/';

  return preg_replace_callback($pattern, function ($matches) use ($format) {
    $year = $matches[1];
    $month = $matches[2];
    $day = $matches[3];

    // Valida la data
    if (!checkdate((int) $month, (int) $day, (int) $year)) {
      return $matches[0]; // Ritorna originale se non valida
    }

    // Crea DateTime e formatta
    try {
      $date = new \DateTime($matches[0]);
      return $date->format($format);
    } catch (\Exception $e) {
      return $matches[0]; // Ritorna originale in caso di errore
    }
  }, $text);
}

/**
 * Helper: Pulisce label orfane quando non segue un valore.
 * 
 * Es: "Website: " → "" 
 * Es: "Tel: " → ""
 * Es: "(Contact person: )" → ""
 * Es: "Email: test@email.com" → "Email: test@email.com" (invariato)
 *
 * @param string $text
 *
 * @return string
 */
function teasearch_aggregator_clean_orphan_labels($text)
{
  // Rimuovi "Label: " alla fine della stringa (niente dopo i due punti)
  $text = preg_replace('/[A-Za-z\s]+:\s*$/u', '', $text);

  // Rimuovi "(Label: )" con contenuto vuoto dentro le parentesi
  $text = preg_replace('/\(\s*[A-Za-z\s]+:\s*\)/u', '', $text);

  // Rimuovi parentesi vuote "()" residue  
  $text = preg_replace('/\(\s*\)/', '', $text);

  // Rimuovi "Label: " seguito solo da spazi o fine stringa
  $text = preg_replace('/[A-Za-z]+:\s+(?=[A-Z][a-z]+:|$)/u', '', $text);

  return trim($text);
}