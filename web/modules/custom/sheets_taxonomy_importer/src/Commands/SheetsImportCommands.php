<?php

namespace Drupal\sheets_taxonomy_importer\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Google\Client;
use Google\Service\Sheets;

/**
 * Drush commands for Google Sheets taxonomy import.
 */
class SheetsImportCommands extends DrushCommands
{

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Import taxonomy terms from Google Sheets.
   *
   * @command sheets-taxonomy:import
   * @aliases sti
   * @option spreadsheet-id The Google Sheets spreadsheet ID
   * @option credentials-path Path to Google API credentials JSON file
   * @option new-only Import only new terms (skip existing ones)
   * @option reset Delete all existing terms before import (DESTRUCTIVE!)
   * @usage sheets-taxonomy:import
   * @usage sheets-taxonomy:import --new-only
   * @usage sheets-taxonomy:import --reset
   */
  public function importTaxonomies(array $options = [
    'spreadsheet-id' => NULL,
    'credentials-path' => NULL,
    'new-only' => FALSE,
    'reset' => FALSE,
  ])
  {

    // Configuration mapping: sheet name => taxonomy machine name
    $fieldToTaxonomy = [
      'subjects' => 'subjects',
      'languages' => 'languages',
      'countries' => 'countries',
      'activities' => 'activities',
      'text_formats' => 'text_formats',
      'text_genres' => 'text_genres',
      'image_formats' => 'image_formats',
      'usage_rights' => 'usage_rights',
      'video_genres' => 'video_genres',
      'types' => 'types',
      'news_types' => 'news_types',
    ];

    // Default values - same as in the web form
    $defaultSpreadsheetId = '1whtqWYZJ0oDZTuFRXZe_Soxk22bYPJucCiq9lA3W9pA';
    $defaultCredentialsPath = '/var/www/htdocs/teasearch/keys/teasearch-sheets.json';

    // Use provided values or defaults
    $spreadsheetId = $options['spreadsheet-id'] ?: $defaultSpreadsheetId;
    $credentialsPath = $options['credentials-path'] ?: $defaultCredentialsPath;

    // Log what we're using
    $this->logger()->info("Using Spreadsheet ID: {$spreadsheetId}");
    $this->logger()->info("Using Credentials Path: {$credentialsPath}");

    // Validate files exist
    if (!file_exists($credentialsPath)) {
      $this->logger()->error("Credentials file not found at: {$credentialsPath}");
      return;
    }

    // Validate conflicting options
    if ($options['new-only'] && $options['reset']) {
      $this->logger()->error('Cannot use --new-only and --reset options together. Please choose one.');
      return;
    }

    // Warning for reset option
    if ($options['reset']) {
      $this->logger()->warning('⚠️  RESET MODE ENABLED: This will DELETE ALL existing terms from the taxonomies before importing new ones!');
      if (!$this->io()->confirm('Are you sure you want to continue? This action cannot be undone.', FALSE)) {
        $this->logger()->info('Import cancelled by user.');
        return;
      }
    }

    try {
      // Initialize Google Sheets API
      $client = $this->getGoogleClient($credentialsPath);
      $service = new Sheets($client);

      // Get spreadsheet data
      $spreadsheet = $service->spreadsheets->get($spreadsheetId);
      $sheets = $spreadsheet->getSheets();

      foreach ($sheets as $sheet) {
        $sheetTitle = $sheet->getProperties()->getTitle();

        if (!isset($fieldToTaxonomy[$sheetTitle])) {
          $this->logger()->info("Skipping sheet '{$sheetTitle}' - not configured in mapping.");
          continue;
        }

        $taxonomyMachineName = $fieldToTaxonomy[$sheetTitle];
        $this->logger()->info("Processing sheet: {$sheetTitle} -> taxonomy: {$taxonomyMachineName}");

        // Reset taxonomy if requested
        if ($options['reset']) {
          $this->resetTaxonomy($taxonomyMachineName);
        }

        // Get sheet data (A=key, B=english, C=italian)
        $range = $sheetTitle . '!A:C';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        if (empty($values)) {
          $this->logger()->warning("No data found in sheet: {$sheetTitle}");
          continue;
        }

        // Process each row (skip first row which contains headers)
        foreach ($values as $rowIndex => $row) {
          // Skip header row (index 0)
          if ($rowIndex === 0) {
            $this->logger()->info("Skipping header row in sheet '{$sheetTitle}': " . implode(' | ', $row));
            continue;
          }

          if (count($row) < 3) {
            $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' does not have all 3 columns (key, english, italian). Skipping.");
            continue;
          }


          $key = trim($row[0]);
          $englishName = trim($row[1]);
          $italianName = trim($row[2]);

          if (empty($key)) {
            $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has empty key. Skipping.");
            continue;
          }

          // Convert key to lowercase automatically
          $key = strtolower($key);

          // Validate key format (should be machine-readable)
          if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has invalid key format '{$key}'. Keys should contain only lowercase letters, numbers, and underscores. Skipping.");
            continue;
          }

          if (empty($key)) {
            $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has empty key. Skipping.");
            continue;
          }

          // Validate key format (should be machine-readable)
          if (!preg_match('/^[a-z0-9_]+$/', $key)) {
            $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has invalid key format '{$key}'. Keys should contain only lowercase letters, numbers, and underscores. Skipping.");
            continue;
          }

          // Create or update taxonomy term
          $this->createOrUpdateTerm($taxonomyMachineName, $key, $englishName, $italianName, $options['new-only']);
        }
      }

      $this->logger()->success('Import completed successfully!');
    } catch (\Exception $e) {
      $this->logger()->error('Import failed: ' . $e->getMessage());
    }
  }

  /**
   * Reset (delete all terms) from a taxonomy vocabulary.
   */
  private function resetTaxonomy(string $vocabularyId): void
  {
    try {
      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

      // Load all terms for this vocabulary
      $terms = $termStorage->loadByProperties(['vid' => $vocabularyId]);

      if (empty($terms)) {
        $this->logger()->info("No existing terms found in taxonomy '{$vocabularyId}' to delete.");
        return;
      }

      $termCount = count($terms);
      $this->logger()->warning("🗑️  Deleting {$termCount} existing terms from taxonomy '{$vocabularyId}'...");

      // Delete all terms
      $termStorage->delete($terms);

      $this->logger()->success("✅ Successfully deleted {$termCount} terms from taxonomy '{$vocabularyId}'.");
    } catch (\Exception $e) {
      $this->logger()->error("Failed to reset taxonomy '{$vocabularyId}': " . $e->getMessage());
      throw $e; // Re-throw to stop the import process
    }
  }

  /**
   * Initialize Google API client.
   */
  private function getGoogleClient(string $credentialsPath): Client
  {
    $client = new Client();
    $client->setApplicationName('Drupal Taxonomy Importer');
    $client->setScopes(Sheets::SPREADSHEETS_READONLY);
    $client->setAuthConfig($credentialsPath);
    $client->setAccessType('offline');

    return $client;
  }

  /**
   * Create or update taxonomy term with smart updating.
   */
  private function createOrUpdateTerm(string $vocabularyId, string $key, string $englishName, string $italianName, bool $newOnly = FALSE): void
  {
    try {
      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

      // Check if term with this key already exists
      $existingTerms = $termStorage->loadByProperties([
        'vid' => $vocabularyId,
        'field_key' => $key,
      ]);

      if (!empty($existingTerms)) {
        // Term exists
        if ($newOnly) {
          $term = reset($existingTerms);
          $this->logger()->info("Skipping existing term '{$key}' (TID: {$term->id()}) - new-only mode enabled.");
          return;
        }

        // Update existing term
        $term = reset($existingTerms);
        $updated = FALSE;
        $changes = [];

        // Check English name
        $currentEnglishName = $term->getName();
        if ($currentEnglishName !== $englishName && !empty($englishName)) {
          $term->setName($englishName);
          $updated = TRUE;
          $changes[] = "EN: '{$currentEnglishName}' → '{$englishName}'";
        }

        // Check Italian translation
        $needsItalianUpdate = FALSE;
        $currentItalianName = '';

        if ($term->hasTranslation('it')) {
          $italianTerm = $term->getTranslation('it');
          $currentItalianName = $italianTerm->getName();
          if ($currentItalianName !== $italianName && !empty($italianName)) {
            $italianTerm->setName($italianName);
            $needsItalianUpdate = TRUE;
            $changes[] = "IT: '{$currentItalianName}' → '{$italianName}'";
          }
        } else if (!empty($italianName)) {
          // Create Italian translation if it doesn't exist
          $italianTerm = $term->addTranslation('it');
          $italianTerm->setName($italianName);
          $needsItalianUpdate = TRUE;
          $changes[] = "IT: Added translation '{$italianName}'";
        }

        // Save only if something changed
        if ($updated) {
          $term->save();
        }
        if ($needsItalianUpdate) {
          $italianTerm->save();
        }

        if (!empty($changes)) {
          $this->logger()->info("Updated term '{$key}' (TID: {$term->id()}): " . implode(', ', $changes));
        } else {
          $this->logger()->info("Term '{$key}' (TID: {$term->id()}) is up to date, no changes needed.");
        }
      } else {
        // Term doesn't exist - create new one
        if (empty($englishName)) {
          $this->logger()->warning("Cannot create term with key '{$key}' - English name is empty.");
          return;
        }

        $term = Term::create([
          'vid' => $vocabularyId,
          'name' => $englishName,
          'field_key' => $key,
          'langcode' => 'en',
        ]);
        $term->save();

        $this->logger()->info("Created new term '{$key}' (TID: {$term->id()}) - EN: '{$englishName}'");

        // Add Italian translation if provided
        if (!empty($italianName)) {
          $italianTerm = $term->addTranslation('it');
          $italianTerm->setName($italianName);
          $italianTerm->save();
          $this->logger()->info("Added Italian translation for '{$key}': '{$italianName}'");
        }
      }
    } catch (\Exception $e) {
      $this->logger()->error("Failed to create/update term '{$key}': " . $e->getMessage());
    }
  }

  /**
   * Helper function to get term by key.
   */
  public function getTermByKey(string $vocabularyId, string $key): ?Term
  {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $termStorage->loadByProperties([
      'vid' => $vocabularyId,
      'field_key' => $key,
    ]);

    return !empty($terms) ? reset($terms) : NULL;
  }
}
