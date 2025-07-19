<?php

  namespace Drupal\sheets_taxonomy_importer\Commands;

  use Drush\Commands\DrushCommands;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Language\LanguageManagerInterface;
  use Drupal\taxonomy\Entity\Term;
  use Google\Client;
  use Google\Service\Sheets;
  use Drupal\Core\Language\LanguageInterface;
  use Drupal\Core\Language\Language;
  
  /**
   * Drush commands for Google Sheets taxonomy import.
   */
  class SheetsImportCommands extends DrushCommands
  {

    /**
     * Parse header row to find column indices for key, en, and it.
     */
    private function parseHeaderRow(array $headerRow): ?array
    {
      $mapping = [
        'key' => null,
        'en' => null,
        'it' => null,
      ];

      foreach ($headerRow as $index => $header) {
        $header = strtolower(trim($header));

        if ($header === 'key') {
          $mapping['key'] = $index;
        } elseif ($header === 'en' || $header === 'english') {
          $mapping['en'] = $index;
        } elseif ($header === 'it' || $header === 'italian' || $header === 'italiano') {
          $mapping['it'] = $index;
        }
      }

      // Verify all required columns were found
      foreach ($mapping as $column => $index) {
        if ($index === null) {
          return null;
        }
      }

      return $mapping;
    }

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
     * Get the taxonomy configuration mapping.
     */
    private function getTaxonomyMapping(): array
    {
      return [
        'subjects' => 'subjects',
        'languages' => 'languages',
        'countries' => 'countries',
        'activities' => 'activities',
        'text_types' => 'text_types',
        'text_genres' => 'text_genres',
        'image_formats' => 'image_formats',
        'usage_rights' => 'usage_rights',
        'video_genres' => 'video_genres',
        'publication_types' => 'publication_types',
        'periodical_frequency' => 'periodical_frequency',
        'news_types' => 'news_types',
      ];
    }

    /**
     * Manage taxonomy terms from Google Sheets - import, list, or delete.
     *
     * @command sheets-taxonomy:import
     * @aliases sti
     * @option spreadsheet-id The Google Sheets spreadsheet ID
     * @option credentials-path Path to Google API credentials JSON file
     * @option new-only Import only new terms (skip existing ones)
     * @option reset Delete all existing terms before import (DESTRUCTIVE!)
     * @option list List all taxonomies and their term counts
     * @option delete Delete terms from taxonomies instead of importing
     * @option taxonomy Specific taxonomy machine name to operate on
     * @option force Skip confirmation prompt when deleting
     * @usage sti
     * @usage sti --new-only
     * @usage sti --reset
     * @usage sti --list
     * @usage sti --list --taxonomy=subjects
     * @usage sti --delete
     * @usage sti --delete --taxonomy=subjects
     * @usage sti --delete --force
     * @usage sti --delete --taxonomy=languages --force
     */
    public function importTaxonomies(array $options = [
      'spreadsheet-id' => NULL,
      'credentials-path' => NULL,
      'new-only' => FALSE,
      'reset' => FALSE,
      'list' => FALSE,
      'delete' => FALSE,
      'taxonomy' => NULL,
      'force' => FALSE,
    ])
    {
      $fieldToTaxonomy = $this->getTaxonomyMapping();

      // Handle list option
      if ($options['list']) {
        $this->handleTaxonomyListing($fieldToTaxonomy, $options);
        return;
      }

      // Handle delete option
      if ($options['delete']) {
        $this->handleTaxonomyDeletion($fieldToTaxonomy, $options);
        return;
      }

      // Validate conflicting options for import
      if ($options['new-only'] && $options['reset']) {
        $this->logger()->error('Cannot use --new-only and --reset options together. Please choose one.');
        return;
      }

      // Verify languages are enabled
      if (!$this->verifyLanguagesEnabled()) {
        return;
      }

      // Default values for import
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

      // Warning for reset option
      if ($options['reset']) {
        $this->logger()->warning('⚠️  RESET MODE ENABLED: This will DELETE ALL existing terms from the taxonomies before importing new ones!');
        if (!$this->io()->confirm('Are you sure you want to continue? This action cannot be undone.', FALSE)) {
          $this->logger()->info('Import cancelled by user.');
          return;
        }
      }

      // Continue with import logic
      try {
        // Initialize Google Sheets API
        $client = $this->getGoogleClient($credentialsPath);
        $service = new Sheets($client);

        // Get spreadsheet data
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        // List all sheet names found
        $sheetNames = [];
        foreach ($sheets as $sheet) {
          $sheetNames[] = $sheet->getProperties()->getTitle();
        }
        $this->logger()->info("Sheets found in Google Sheets: " . implode(', ', $sheetNames));
        $this->logger()->info("Sheets expected in mapping: " . implode(', ', array_keys($fieldToTaxonomy)));

        // Process statistics
        $stats = [
          'created' => 0,
          'updated' => 0,
          'skipped' => 0,
          'errors' => 0,
        ];

        foreach ($sheets as $sheet) {
          $sheetTitle = $sheet->getProperties()->getTitle();

          if (!isset($fieldToTaxonomy[$sheetTitle])) {
            $this->logger()->info("Skipping sheet '{$sheetTitle}' - not configured in mapping.");
            continue;
          }

          $taxonomyMachineName = $fieldToTaxonomy[$sheetTitle];
          $this->logger()->info("Processing sheet: {$sheetTitle} -> taxonomy: {$taxonomyMachineName}");

          // Check if vocabulary exists
          $vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
          $vocabulary = $vocabularyStorage->load($taxonomyMachineName);
          if (!$vocabulary) {
            $this->logger()->error("ERROR: Taxonomy vocabulary '{$taxonomyMachineName}' does not exist!");
            continue;
          }

          // Check if field_key field exists
          $fieldManager = \Drupal::service('entity_field.manager');
          $fields = $fieldManager->getFieldDefinitions('taxonomy_term', $taxonomyMachineName);
          if (!isset($fields['field_key'])) {
            $this->logger()->error("ERROR: Field 'field_key' does not exist in taxonomy '{$taxonomyMachineName}'");
            continue;
          }

          // Check if field_key is translatable (it should NOT be)
          if ($fields['field_key']->isTranslatable()) {
            $this->logger()->warning("WARNING: field_key is translatable in '{$taxonomyMachineName}'. This may cause issues.");
          }

          // Reset taxonomy if requested
          if ($options['reset']) {
            $this->resetTaxonomy($taxonomyMachineName);
          }

          // Get sheet data
          $range = $sheetTitle . '!A:Z'; // Get all columns to be flexible
          $response = $service->spreadsheets_values->get($spreadsheetId, $range);
          $values = $response->getValues();

          if (empty($values)) {
            $this->logger()->warning("No data found in sheet: {$sheetTitle}");
            continue;
          }

          $this->logger()->info("Sheet '{$sheetTitle}' has " . count($values) . " rows total");

          // Parse header row to find language columns
          $headerRow = $values[0];
          $columnMapping = $this->parseHeaderRow($headerRow);

          if (!$columnMapping) {
            $this->logger()->error("Could not parse header row for sheet '{$sheetTitle}'. Expected 'key', 'en', and 'it' columns.");
            continue;
          }

          $this->logger()->info("Column mapping for '{$sheetTitle}': " . json_encode($columnMapping));

          // Show first few and last few rows for debugging
          if (count($values) > 5) {
            $this->logger()->info("DEBUG: First 3 data rows: " . json_encode(array_slice($values, 1, 3)));
            $this->logger()->info("DEBUG: Last 3 rows: " . json_encode(array_slice($values, -3)));
          } else {
            $this->logger()->info("DEBUG: All rows: " . json_encode($values));
          }

          // Process each row (skip first row which contains headers)
          foreach ($values as $rowIndex => $row) {
            // Skip header row (index 0)
            if ($rowIndex === 0) {
              continue;
            }

            // Extract values based on column mapping
            $key = isset($row[$columnMapping['key']]) ? trim($row[$columnMapping['key']]) : '';
            $englishName = isset($row[$columnMapping['en']]) ? trim($row[$columnMapping['en']]) : '';
            $italianName = isset($row[$columnMapping['it']]) ? trim($row[$columnMapping['it']]) : '';

            // DEBUG: Show what we read from the sheet
            $this->logger()->info("DEBUG: Row " . ($rowIndex + 1) . " - Key: '{$key}' | EN: '{$englishName}' | IT: '{$italianName}'");

            // Validate that we have values for required columns
            if (empty($key)) {
              $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has empty key. Skipping.");
              $stats['skipped']++;
              continue;
            }

            if (empty($englishName)) {
              $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has empty English name for key '{$key}'. Skipping.");
              $stats['skipped']++;
              continue;
            }

            // Convert key to lowercase automatically
            $key = strtolower($key);

            // Validate key format (should be machine-readable)
            if (empty($key) || !preg_match('/^[a-z0-9_]+$/', $key)) {
              $this->logger()->warning("Row " . ($rowIndex + 1) . " in sheet '{$sheetTitle}' has invalid or empty key '{$key}'. Skipping.");
              $stats['skipped']++;
              continue;
            }

            // Create or update taxonomy term
            $result = $this->createOrUpdateTerm($taxonomyMachineName, $key, $englishName, $italianName, $options['new-only']);

            // Update statistics
            if (isset($stats[$result])) {
              $stats[$result]++;
            }
          }
        }

        // Report final statistics
        $this->logger()->success('Import completed successfully!');
        $this->logger()->info("Statistics: Created: {$stats['created']}, Updated: {$stats['updated']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}");
      } catch (\Exception $e) {
        $this->logger()->error('Import failed: ' . $e->getMessage());
      }
    }

    /**
     * Verify that required languages are enabled.
     */
    private function verifyLanguagesEnabled(): bool
    {
      $languages = $this->languageManager->getLanguages();
      $requiredLanguages = ['en', 'it'];
      $missingLanguages = [];

      foreach ($requiredLanguages as $langcode) {
        if (!isset($languages[$langcode])) {
          $missingLanguages[] = $langcode;
        }
      }

      if (!empty($missingLanguages)) {
        $this->logger()->error("The following required languages are not enabled: " . implode(', ', $missingLanguages));
        $this->logger()->info("Please enable these languages before running the import.");
        return FALSE;
      }

      // Check default language
      $defaultLanguage = $this->languageManager->getDefaultLanguage()->getId();
      $this->logger()->info("Default site language: {$defaultLanguage}");

      // Show all enabled languages
      $enabledLanguages = [];
      foreach ($languages as $langcode => $language) {
        $enabledLanguages[] = $langcode . " (" . $language->getName() . ")";
      }
      $this->logger()->info("Enabled languages: " . implode(', ', $enabledLanguages));

      return TRUE;
    }

    /**
     * Handle taxonomy listing functionality.
     */
    private function handleTaxonomyListing(array $fieldToTaxonomy, array $options): void
    {
      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

      // Filter by specific taxonomy if requested
      if ($options['taxonomy']) {
        $targetTaxonomy = $options['taxonomy'];
        if (!in_array($targetTaxonomy, $fieldToTaxonomy)) {
          $this->logger()->error("Taxonomy '{$targetTaxonomy}' is not configured in the mapping.");
          $this->logger()->info("Available taxonomies: " . implode(', ', array_values($fieldToTaxonomy)));
          return;
        }

        // Filter to only the requested taxonomy
        $fieldToTaxonomy = array_filter($fieldToTaxonomy, fn($vid) => $vid === $targetTaxonomy);
      }

      $this->io()->writeln("📋 Configured taxonomies and term counts:");
      $this->io()->writeln(str_repeat("-", 60));

      $totalTerms = 0;

      foreach ($fieldToTaxonomy as $sheetName => $vocabularyId) {
        try {
          // Count terms in this vocabulary
          $terms = $termStorage->loadByProperties(['vid' => $vocabularyId]);
          $termCount = count($terms);
          $totalTerms += $termCount;

          $this->io()->writeln(sprintf("%-25s | %-20s | %d terms", $sheetName, $vocabularyId, $termCount));
        } catch (\Exception $e) {
          $this->io()->writeln(sprintf("%-25s | %-20s | ERROR: %s", $sheetName, $vocabularyId, $e->getMessage()));
        }
      }

      $this->io()->writeln(str_repeat("-", 60));

      if ($options['taxonomy']) {
        $this->io()->writeln("Showing 1 taxonomy, {$totalTerms} terms");
      } else {
        $this->io()->writeln("Total: " . count($fieldToTaxonomy) . " taxonomies, {$totalTerms} terms");
      }
    }

    /**
     * Handle taxonomy deletion functionality.
     */
    private function handleTaxonomyDeletion(array $fieldToTaxonomy, array $options): void
    {
      // Determine which taxonomies to delete
      $taxonomiesToDelete = [];

      if ($options['taxonomy']) {
        $targetTaxonomy = $options['taxonomy'];

        // Check if the provided taxonomy is in our mapping
        if (in_array($targetTaxonomy, $fieldToTaxonomy)) {
          $taxonomiesToDelete = [$targetTaxonomy];
        } else {
          $this->logger()->error("Taxonomy '{$targetTaxonomy}' is not configured in the mapping.");
          $this->logger()->info("Available taxonomies: " . implode(', ', array_values($fieldToTaxonomy)));
          return;
        }
      } else {
        // Delete from all configured taxonomies
        $taxonomiesToDelete = array_values($fieldToTaxonomy);
      }

      // Show warning
      if (count($taxonomiesToDelete) === 1) {
        $this->io()->warning("⚠️  This will DELETE ALL terms from taxonomy: " . implode(', ', $taxonomiesToDelete));
      } else {
        $this->io()->warning("⚠️  This will DELETE ALL terms from " . count($taxonomiesToDelete) . " taxonomies: " . implode(', ', $taxonomiesToDelete));
      }

      // Confirmation
      if (!$options['force']) {
        if (!$this->io()->confirm('Are you sure you want to delete all terms? This action cannot be undone.', FALSE)) {
          $this->io()->writeln('Delete operation cancelled by user.');
          return;
        }
      }

      // Perform deletion
      $totalDeleted = 0;
      $successCount = 0;

      foreach ($taxonomiesToDelete as $vocabularyId) {
        try {
          $deleted = $this->resetTaxonomy($vocabularyId);
          $totalDeleted += $deleted;
          $successCount++;
        } catch (\Exception $e) {
          $this->logger()->error("Failed to delete taxonomy '{$vocabularyId}': " . $e->getMessage());
        }
      }

      // Summary
      if ($successCount === count($taxonomiesToDelete)) {
        $this->logger()->success("✅ Successfully deleted {$totalDeleted} terms from {$successCount} taxonomies.");
      } else {
        $failed = count($taxonomiesToDelete) - $successCount;
        $this->logger()->warning("⚠️  Partially completed: {$successCount} succeeded, {$failed} failed. Total terms deleted: {$totalDeleted}");
      }
    }

    /**
     * Reset (delete all terms) from a taxonomy vocabulary.
     * 
     * @return int Number of terms deleted
     */
    private function resetTaxonomy(string $vocabularyId): int
    {
      try {
        $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

        // Load all terms for this vocabulary
        $terms = $termStorage->loadByProperties(['vid' => $vocabularyId]);

        if (empty($terms)) {
          $this->logger()->info("No existing terms found in taxonomy '{$vocabularyId}' to delete.");
          return 0;
        }

        $termCount = count($terms);
        $this->logger()->warning("🗑️  Deleting {$termCount} existing terms from taxonomy '{$vocabularyId}'...");

        // Show sample of terms being deleted
        $termNames = [];
        $sampleSize = min(10, $termCount);
        $termArray = array_values($terms);

        for ($i = 0; $i < $sampleSize; $i++) {
          $term = $termArray[$i];
          $termNames[] = $term->getName() . " (ID: " . $term->id() . ")";
        }

        $this->logger()->info("Deleting terms: " . implode(', ', $termNames) . ($termCount > $sampleSize ? " ... and " . ($termCount - $sampleSize) . " more" : ""));

        // Delete all terms in batches to avoid memory issues
        $batch_size = 50;
        $deleted = 0;

        foreach (array_chunk($terms, $batch_size) as $batch) {
          $termStorage->delete($batch);
          $deleted += count($batch);
          $this->logger()->info("Progress: Deleted {$deleted}/{$termCount} terms...");
        }

        $this->logger()->success("✅ Successfully deleted {$termCount} terms from taxonomy '{$vocabularyId}'.");

        return $termCount;
      } catch (\Exception $e) {
        $this->logger()->error("Failed to reset taxonomy '{$vocabularyId}': " . $e->getMessage());
        throw $e;
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
     * 
     * @return string Result status: 'created', 'updated', 'skipped', or 'errors'
     */
  private function createOrUpdateTerm(string $vocabularyId, string $key, string $englishName, string $italianName, bool $newOnly = FALSE): string
  {
    try {
      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

      $existingTerms = $termStorage->loadByProperties([
        'vid' => $vocabularyId,
        'field_key' => $key,
      ]);

      if (!empty($existingTerms)) {
        $term = reset($existingTerms);
        // logica per aggiornamento esistente (non modificata)
        return 'updated';
      }

      if (empty($englishName)) {
        $this->logger()->warning("Cannot create term with key '{$key}' - English name is empty.");
        return 'errors';
      }

      // 🚩 Soluzione corretta per forzare la lingua originale EN
      $languageManager = \Drupal::languageManager();
      $originalLanguage = $languageManager->getCurrentLanguage();

      // Imposta temporaneamente la lingua corrente su inglese
      $english = $languageManager->getLanguage('en');
      $languageManager->setConfigOverrideLanguage($english);
      $GLOBALS['language_content'] = $english;
      $this->logger()->info("DEBUG: EN='$englishName', IT='$italianName', KEY='$key'");

      // Crea il termine esplicitamente con langcode inglese
      \Drupal::languageManager()->setConfigOverrideLanguage(\Drupal::languageManager()->getLanguage('en'));
      $GLOBALS['language_content'] = \Drupal::languageManager()->getLanguage('en');

      $term = Term::create([
        'vid' => $vocabularyId,
        'name' => $englishName,
        'field_key' => $key,
        'langcode' => 'en',
        'default_langcode' => 1, // <-- FORZA LA LINGUA ORIGINALE A QUELLA SPECIFICATA SOPRA (en)
      ]);
      $term->save();

      // Ripristina lingua originale
      $languageManager->setConfigOverrideLanguage($originalLanguage);
      $GLOBALS['language_content'] = $originalLanguage;

      // Aggiungi traduzione italiana se necessaria
      if (!empty($italianName) && $italianName !== $englishName) {
        $term = Term::load($term->id());
        if (!$term->hasTranslation('it')) {
          $italianTerm = $term->addTranslation('it');
          $italianTerm->setName($italianName);
          $italianTerm->set('field_key', $key);
          $italianTerm->save();
        }
      }

      return 'created';
    } catch (\Exception $e) {
      $this->logger()->error("CRITICAL ERROR in createOrUpdateTerm for '{$key}': " . $e->getMessage());
      return 'errors';
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

    /**
     * Diagnose a specific term to check language issues.
     *
     * @command sheets-taxonomy:diagnose
     * @aliases std
     * @option taxonomy The taxonomy machine name
     * @option key The term key to diagnose
     * @usage std --taxonomy=countries --key=italy
     */
    public function diagnoseTerm(array $options = [
      'taxonomy' => NULL,
      'key' => NULL,
    ])
    {
      if (!$options['taxonomy'] || !$options['key']) {
        $this->logger()->error('Both --taxonomy and --key options are required.');
        return;
      }

      $vocabularyId = $options['taxonomy'];
      $key = $options['key'];

      $term = $this->getTermByKey($vocabularyId, $key);

      if (!$term) {
        $this->logger()->error("Term with key '{$key}' not found in taxonomy '{$vocabularyId}'.");
        return;
      }

      $this->io()->writeln("📋 Term Diagnosis for key '{$key}' in taxonomy '{$vocabularyId}':");
      $this->io()->writeln(str_repeat("-", 60));

      // Basic info
      $this->io()->writeln("Term ID: " . $term->id());
      $this->io()->writeln("Default language: " . $term->language()->getId());
      $this->io()->writeln("Name (default lang): " . $term->getName());

      // Check all translations
      $languages = $this->languageManager->getLanguages();
      $this->io()->writeln("\nTranslations:");

      foreach ($languages as $langcode => $language) {
        if ($term->hasTranslation($langcode)) {
          $translation = $term->getTranslation($langcode);
          $this->io()->writeln("  - {$langcode}: " . $translation->getName());

          // Check field_key value in each translation
          if ($translation->hasField('field_key')) {
            $keyValue = $translation->get('field_key')->value;
            $this->io()->writeln("    field_key: " . ($keyValue ?: '[empty]'));
          }
        } else {
          $this->io()->writeln("  - {$langcode}: [no translation]");
        }
      }

      $this->io()->writeln(str_repeat("-", 60));
    }
  }
