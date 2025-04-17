<?php

namespace Drupal\custom_field\Plugin\CustomField\FeedsType;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldFeedsType;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file' feeds type.
 */
#[CustomFieldFeedsType(
  id: 'file',
  label: new TranslatableMarkup('File'),
  mark_unique: FALSE,
)]
class FileTarget extends EntityReferenceTarget {

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The list of allowed file extensions.
   *
   * @var string[]
   */
  protected $fileExtensions;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The system.file configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $fileConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->client = $container->get('http_client');
    $instance->token = $container->get('token');
    $instance->fileSystem = $container->get('file_system');
    $instance->fileRepository = $container->get('file.repository');
    $instance->fileConfig = $container->get('config.factory')->get('system.file');
    $file_extensions = $configuration['widget_settings']['settings']['file_extensions'] ?? [];
    $instance->fileExtensions = array_keys(explode(' ', $file_extensions));

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['existing' => FileSystemInterface::EXISTS_ERROR] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(int $delta, array $configuration) {
    $form = parent::buildConfigurationForm($delta, $configuration);
    $options = [
      FileSystemInterface::EXISTS_REPLACE => $this->t('Replace'),
      FileSystemInterface::EXISTS_RENAME => $this->t('Rename'),
      FileSystemInterface::EXISTS_ERROR => $this->t('Ignore'),
    ];

    $form['existing'] = [
      '#type' => 'select',
      '#title' => $this->t('Handle existing files'),
      '#options' => $options,
      '#default_value' => $configuration['existing'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $configuration): array {
    $summary = parent::getSummary($configuration);

    switch ($configuration['existing']) {
      case FileSystemInterface::EXISTS_REPLACE:
        $message = 'Replace';
        break;

      case FileSystemInterface::EXISTS_RENAME:
        $message = 'Rename';
        break;

      case FileSystemInterface::EXISTS_ERROR:
        $message = 'Ignore';
        break;

      default:
        $message = '';
    }

    $summary[] = $this->t('Existing files: %existing', ['%existing' => $message]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareValue($value, array $configuration, string $langcode): ?string {
    $name = $this->configuration['name'];
    return !empty($value) ? $this->getFile($value, $configuration[$name], $langcode) : NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Filesize and MIME-type aren't sensible fields to match on so these are
   * filtered out.
   */
  protected function filterFieldTypes(FieldStorageDefinitionInterface $field): bool {
    $ignore_fields = [
      'filesize',
      'filemime',
    ];

    return !in_array($field->getName(), $ignore_fields) && parent::filterFieldTypes($field);
  }

  /**
   * {@inheritdoc}
   *
   * The file entity doesn't support any bundles. Providing an empty array here
   * will prevent the bundle check from being added in the find entity query.
   */
  protected function getBundles(): array {
    return [];
  }

  /**
   * Searches for an entity by entity key.
   *
   * @param string $field
   *   The subfield to search in.
   * @param array $configuration
   *   The feeds configuration array for the custom field.
   * @param string $search
   *   The value to search for.
   * @param string $langcode
   *   The feeds language code.
   *
   * @return int|bool
   *   The entity id, or false, if not found.
   */
  protected function findEntity(string $field, array $configuration, string $search, string $langcode) {
    $entities = $this->findEntities($field, $configuration, $search, $langcode);
    if (!empty($entities)) {
      return reset($entities);
    }
    return FALSE;
  }

  /**
   * Returns a file id given a url.
   *
   * @param string $value
   *   A URL file object.
   * @param array $configuration
   *   The feeds configuration array for the field.
   * @param string $langcode
   *   The feeds language code.
   *
   * @return int
   *   The file id.
   *
   * @throws \Drupal\feeds\Exception\EmptyFeedException
   *   In case an empty file url is given.
   */
  protected function getFile(string $value, array $configuration, string $langcode) {
    if (empty($value)) {
      // No file.
      throw new EmptyFeedException('The given file url is empty.');
    }

    // Perform a lookup against the value using the configured reference method.
    if (FALSE !== ($fid = $this->findEntity($configuration['reference_by'], $configuration, $value, $langcode))) {
      return $fid;
    }

    // Compose file path.
    $filepath = $this->getDestinationDirectory() . 'FileTarget.php/' . $this->getFileName($value);

    switch ($configuration['existing']) {
      case FileSystemInterface::EXISTS_ERROR:
        if (file_exists($filepath) && $fid = $this->findEntity('uri', $configuration, $filepath, $langcode)) {
          return $fid;
        }
        if ($file = $this->writeData($this->getContent($value), $filepath, FileSystemInterface::EXISTS_REPLACE)) {
          return $file->id();
        }
        break;

      default:
        if ($file = $this->writeData($this->getContent($value), $filepath, $configuration['existing'])) {
          return $file->id();
        }
    }

    // Something bad happened while trying to save the file to the database. We
    // need to throw an exception so that we don't save an incomplete field
    // value.
    throw new TargetValidationException($this->t('There was an error saving the file: %file', [
      '%file' => $filepath,
    ]));
  }

  /**
   * Prepares destination directory and returns its path.
   *
   * @return string
   *   The directory to save the file to.
   */
  protected function getDestinationDirectory() {
    $file_directory = $this->configuration['widget_settings']['settings']['file_directory'] ?? '';
    $destination = $this->token->replace($this->configuration['uri_scheme'] . '://' . trim($file_directory, '/'));
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::MODIFY_PERMISSIONS | FileSystemInterface::CREATE_DIRECTORY);

    return $destination;
  }

  /**
   * Extracts the file name from the given url and checks for valid extension.
   *
   * @param string $url
   *   The URL to get the file name for.
   *
   * @return string
   *   The file name.
   *
   * @throws \Drupal\feeds\Exception\TargetValidationException
   *   In case the file extension is not valid.
   */
  protected function getFileName($url) {
    $filename = trim($this->fileSystem->basename($url), " \t\n\r\0\x0B.");
    // Remove query string from file name, if it has one.
    [$filename] = explode('?', $filename);
    $extension = substr($filename, strrpos($filename, '.') + 1);

    if (!preg_grep('/' . $extension . '/i', $this->fileExtensions)) {
      throw new TargetValidationException($this->t('The file, %url, failed to save because the extension, %ext, is invalid.', [
        '%url' => $url,
        '%ext' => $extension,
      ]));
    }

    return $filename;
  }

  /**
   * Attempts to download the file at the given url.
   *
   * @param string $url
   *   The URL to download a file from.
   *
   * @return string
   *   The file contents.
   *
   * @throws \Drupal\feeds\Exception\TargetValidationException
   *   In case the file could not be downloaded.
   */
  protected function getContent($url) {
    $response = $this->client->request('GET', $url);

    if ($response->getStatusCode() >= 400) {
      $args = [
        '%url' => $url,
        '@code' => $response->getStatusCode(),
      ];
      throw new TargetValidationException($this->t('Download of %url failed with code @code.', $args));
    }

    return (string) $response->getBody();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($label, array $configuration, string $feeds_langcode) {
    $target_type = $this->configuration['target_type'];
    if (!strlen(trim($label))) {
      return FALSE;
    }

    $bundles = $this->getBundles();

    $entity = $this->entityTypeManager->getStorage($target_type)->create([
      $this->getLabelKey() => $label,
      $this->getBundleKey() => reset($bundles),
      'uri' => $label,
    ]);
    $entity->save();

    return $entity->id();
  }

  /**
   * Saves a file to the specified destination and creates a database entry.
   *
   * @param string $data
   *   A string containing the contents of the file.
   * @param string|null $destination
   *   (optional) A string containing the destination URI. This must be a stream
   *   wrapper URI. If no value or NULL is provided, a randomized name will be
   *   generated and the file will be saved using Drupal's default files scheme,
   *   usually "public://".
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   A file entity, or FALSE on error.
   */
  protected function writeData($data, $destination = NULL, $replace = FileSystemInterface::EXISTS_RENAME) {
    if (empty($destination)) {
      $destination = $this->fileConfig->get('default_scheme') . '://';
    }
    try {
      return $this->fileRepository->writeData($data, $destination, $replace);
    }
    catch (EntityStorageException | FileException $e) {
      return FALSE;
    }
  }

}
