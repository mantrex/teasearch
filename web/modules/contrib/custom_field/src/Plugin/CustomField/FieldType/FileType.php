<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;

/**
 * Plugin implementation of the 'file' field type.
 */
#[CustomFieldType(
  id: 'file',
  label: new TranslatableMarkup('File'),
  description: [
    new TranslatableMarkup('For uploading files'),
    new TranslatableMarkup('Can be configured with options such as allowed file extensions and maximum upload size'),
  ],
  category: new TranslatableMarkup('File upload'),
  default_widget: 'file_generic',
  default_formatter: 'file_default',
  constraints: [
    'ReferenceAccess' => [],
    'FileValidation' => [],
  ],
)]
class FileType extends CustomFieldTypeBase {

  /**
   * The default file directory for generateSampleValue().
   *
   * @var string
   */
  const DEFAULT_FILE_DIRECTORY = '[date:custom:Y]-[date:custom:m]';

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $columns[$name] = [
      'description' => 'The ID of the file entity.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name, 'target_type' => $target_type] = $settings;
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_entity_reference')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $name]))
      ->setSetting('unsigned', TRUE)
      ->setSetting('target_type', $target_type)
      ->setRequired(FALSE);

    $properties[$name . self::SEPARATOR . 'entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      ->setComputed(TRUE)
      ->setSettings(['target_id' => $name, 'target_type' => $target_type])
      ->setClass('\Drupal\custom_field\Plugin\CustomField\EntityReferenceComputed')
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($target_type))
      ->addConstraint('EntityType', $target_type);

    return $properties;
  }

  /**
   * Determines the URI for a file field.
   *
   * @param array<string, mixed> $settings
   *   An array of settings to pass to the function.
   * @param array<string, mixed> $data
   *   An array of token objects to pass to Token::replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function getUploadLocation(array $settings, array $data = []): string {
    return static::doGetUploadLocation($settings, $data);
  }

  /**
   * Determines the URI for a file field.
   *
   * @param array<string, mixed> $settings
   *   The array of field settings.
   * @param array<string, mixed> $data
   *   An array of token objects to pass to Token::replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  protected static function doGetUploadLocation(array $settings, array $data = []): string {
    $file_directory = $settings['file_directory'] ?? self::DEFAULT_FILE_DIRECTORY;
    $destination = trim($file_directory, '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed {
    $random = new Random();
    $settings = $field->getWidgetSetting('settings');
    $settings['uri_scheme'] = $field->getSetting('uri_scheme');

    // Prepare destination.
    $dirname = static::doGetUploadLocation($settings);
    \Drupal::service('file_system')->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);

    // Generate a file entity.
    $destination = $dirname . '/' . $random->name(10, TRUE) . '.txt';
    $data = $random->paragraphs(3);
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($data, $destination, FileExists::Error);

    return $file->id();
  }

}
