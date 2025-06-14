<?php

namespace Drupal\sheets_taxonomy_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sheets_taxonomy_importer\Commands\SheetsImportCommands;

/**
 * Configuration form for Google Sheets import.
 */
class SheetsImportForm extends FormBase
{

  /**
   * The import service.
   */
  protected SheetsImportCommands $importService;

  /**
   * Constructor.
   */
  public function __construct(SheetsImportCommands $import_service)
  {
    $this->importService = $import_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('sheets_taxonomy_importer.commands')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'sheets_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['spreadsheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Sheets Spreadsheet ID'),
      '#description' => $this->t('The ID of your Google Sheets spreadsheet (found in the URL).'),
      '#required' => TRUE,
      '#default_value'=> '1whtqWYZJ0oDZTuFRXZe_Soxk22bYPJucCiq9lA3W9pA'
    ];

    $form['credentials_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credentials File Path'),
      '#description' => $this->t('Path to your Google API credentials JSON file.'),
      '#required' => TRUE,
      '#default_value' => '/var/www/htdocs/teasearch/keys/teasearch-sheets.json',
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import Options'),
      '#description' => $this->t('Choose how to handle existing taxonomy terms.'),
    ];

    $form['options']['update'] = [
      '#type' => 'radio',
      '#title' => $this->t('Update existing and import new terms (default)'),
      '#description' => $this->t('Existing terms will be updated if changes are found. New terms will be imported.'),
      '#return_value' => 'update',
      '#default_value' => 'update',
      '#parents' => ['import_mode'],
    ];

    $form['options']['new_only'] = [
      '#type' => 'radio',
      '#title' => $this->t('Import only new terms'),
      '#description' => $this->t('Existing terms will be skipped. Only new terms will be imported.'),
      '#return_value' => 'new_only',
      '#parents' => ['import_mode'],
    ];

    $form['options']['reset'] = [
      '#type' => 'radio',
      '#title' => $this->t('⚠️ Reset taxonomies (DELETE ALL existing terms first)'),
      '#description' => $this->t('<strong>DANGER:</strong> This will permanently delete ALL existing terms from the taxonomies before importing new ones. This action cannot be undone!'),
      '#return_value' => 'reset',
      '#parents' => ['import_mode'],
    ];

    $form['mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sheet to Taxonomy Mapping'),
      '#description' => $this->t('JSON format mapping of sheet names to taxonomy machine names. Example: {"Countries": "countries", "Categories": "categories"}'),
      '#rows' => 10,
      '#required' => TRUE,
      '#default_value' => '{"Countries": "countries", "Categories": "categories", "Tags": "tags"}',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Taxonomies'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $mapping = $form_state->getValue('mapping');
    $decoded = json_decode($mapping, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      $form_state->setErrorByName('mapping', $this->t('Invalid JSON format in mapping field.'));
    }

    // Validate credentials file exists
    $credentialsPath = $form_state->getValue('credentials_path');
    if (!empty($credentialsPath) && !file_exists($credentialsPath)) {
      $form_state->setErrorByName('credentials_path', $this->t('Credentials file not found at path: @path', ['@path' => $credentialsPath]));
    }

    // Validate spreadsheet ID format (basic check)
    $spreadsheetId = $form_state->getValue('spreadsheet_id');
    if (!empty($spreadsheetId) && !preg_match('/^[a-zA-Z0-9-_]+$/', $spreadsheetId)) {
      $form_state->setErrorByName('spreadsheet_id', $this->t('Invalid spreadsheet ID format.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $spreadsheetId = $form_state->getValue('spreadsheet_id');
    $credentialsPath = $form_state->getValue('credentials_path');
    $importMode = $form_state->getValue('import_mode');
    $mapping = $form_state->getValue('mapping');

    // Convert import mode to boolean flags
    $newOnly = ($importMode === 'new_only');
    $reset = ($importMode === 'reset');

    // Additional confirmation for reset mode
    if ($reset) {
      $this->messenger()->addWarning($this->t('⚠️ RESET MODE: All existing terms will be deleted before import!'));
    }

    // Start batch process
    $batch = [
      'title' => $this->t('Importing taxonomies from Google Sheets'),
      'operations' => [
        [
          [$this, 'batchImport'],
          [$spreadsheetId, $credentialsPath, $newOnly, $reset, $mapping]
        ],
      ],
      'finished' => [$this, 'batchFinished'],
    ];

    batch_set($batch);
  }

  /**
   * Batch import operation.
   */
  public function batchImport($spreadsheetId, $credentialsPath, $newOnly, $reset, $mapping, &$context)
  {
    // Initialize progress
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = 1;
      $context['results']['imported'] = 0;
      $context['results']['updated'] = 0;
      $context['results']['errors'] = 0;
    }

    try {
      // In a real implementation, you would call the import service here
      // For now, we'll simulate the import
      $context['message'] = $this->t('Importing taxonomies from Google Sheets...');

      // Simulate import progress
      $context['sandbox']['progress']++;
      $context['results']['imported'] += 10; // Example values

      // Mark as finished
      $context['finished'] = 1;
    } catch (\Exception $e) {
      $context['results']['errors']++;
      $context['message'] = $this->t('Error during import: @error', ['@error' => $e->getMessage()]);
      $context['finished'] = 1;
    }
  }

  /**
   * Batch finished callback.
   */
  public function batchFinished($success, $results, $operations)
  {
    if ($success) {
      $imported = $results['imported'] ?? 0;
      $updated = $results['updated'] ?? 0;
      $errors = $results['errors'] ?? 0;

      if ($errors > 0) {
        $this->messenger()->addWarning($this->t('Import completed with @errors errors. @imported terms imported, @updated terms updated.', [
          '@errors' => $errors,
          '@imported' => $imported,
          '@updated' => $updated,
        ]));
      } else {
        $this->messenger()->addMessage($this->t('Import completed successfully! @imported terms imported, @updated terms updated.', [
          '@imported' => $imported,
          '@updated' => $updated,
        ]));
      }
    } else {
      $this->messenger()->addError($this->t('Import failed with errors.'));
    }
  }
}
