<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Bootstrap Drupal
$autoloader = require_once 'autoload.php';
$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

// Disattiva il modulo
$module_handler = $container->get('module_handler');
$module_installer = $container->get('module_installer');

try {
  echo "Tentativo di disinstallazione del modulo teasearch_filter...<br>";
  $module_installer->uninstall(['teasearch_filter']);
  echo "Modulo disinstallato con successo!<br>";
} catch (Exception $e) {
  echo "Errore durante la disinstallazione: " . $e->getMessage() . "<br>";

  // Forza la rimozione dal database
  echo "Tentativo di rimozione forzata dalla configurazione...<br>";
  $database = $container->get('database');
  $database->update('config')
    ->fields([
      'data' => str_replace('s:17:"teasearch_filter";', 's:0:"";', $database->select('config', 'c')
        ->fields('c', ['data'])
        ->condition('name', 'core.extension')
        ->execute()
        ->fetchField())
    ])
    ->condition('name', 'core.extension')
    ->execute();
  echo "Rimozione dalla configurazione completata.<br>";
}

// Pulizia cache
echo "Pulizia cache...<br>";
$container->get('cache.render')->deleteAll();
$container->get('cache.discovery')->deleteAll();
$container->get('cache.bootstrap')->deleteAll();
echo "Cache pulita.<br>";

echo "Completato. <a href='/'>Torna alla home</a>";
