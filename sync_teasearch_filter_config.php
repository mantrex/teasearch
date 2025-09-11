<?php

/**
 * @file
 * Script per sincronizzare teasearch_filter.settings.yml con la configurazione database.
 * 
 * Uso: 
 * 1. Modifica config/install/teasearch_filter.settings.yml
 * 2. Esegui: drush php:script sync_teasearch_filter_config.php
 */

use Drupal\Component\Serialization\Yaml;

// Path al file di configurazione
$module_path = \Drupal::service('extension.list.module')->getPath('teasearch_filter');
$config_file = DRUPAL_ROOT . '/' . $module_path . '/config/install/teasearch_filter.settings.yml';

if (!file_exists($config_file)) {
  echo "❌ File non trovato: $config_file\n";
  exit(1);
}

try {
  // Leggi il file YAML
  $yaml_content = file_get_contents($config_file);
  $yaml_data = Yaml::decode($yaml_content);

  if (empty($yaml_data)) {
    echo "❌ File YAML vuoto o malformato\n";
    exit(1);
  }

  // Ottieni la configurazione esistente
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('teasearch_filter.settings');

  echo "📋 Backup configurazione attuale...\n";

  // Applica tutti i dati dal file YAML
  foreach ($yaml_data as $key => $value) {
    $config->set($key, $value);
    echo "✅ Aggiornato: $key\n";
  }

  // Salva la configurazione
  $config->save(TRUE);

  echo "\n🎉 Configurazione sincronizzata con successo!\n";
  echo "📁 File sorgente: $config_file\n";
  echo "💾 Database aggiornato: teasearch_filter.settings\n\n";

  // Mostra le chiavi principali aggiornate
  echo "🔍 Configurazione attuale:\n";
  echo "   - Content Types: " . count($config->get('content_types') ?: []) . "\n";
  echo "   - Content Fields: " . count($config->get('content_fields') ?: []) . "\n";
  echo "   - Legacy Mappings: " . count($config->get('legacy_mappings') ?: []) . "\n";
} catch (\Exception $e) {
  echo "❌ Errore durante la sincronizzazione: " . $e->getMessage() . "\n";
  exit(1);
}

echo "\n💡 Per verificare: drush config:get teasearch_filter.settings\n";
