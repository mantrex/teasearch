<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\custom_field\Attribute\CustomFieldType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\TypedData\CustomFieldDataDefinition;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Plugin implementation of the 'image' field type.
 */
#[CustomFieldType(
  id: 'image',
  label: new TranslatableMarkup('Image'),
  description: [
    new TranslatableMarkup("For uploading images"),
    new TranslatableMarkup("Allows a user to upload an image with configurable extensions, image dimensions, upload size"),
    new TranslatableMarkup(
      "Can be configured with options such as allowed file extensions, maximum upload size and image dimensions minimums/maximums"
    ),
  ],
  category: new TranslatableMarkup('File upload'),
  default_widget: 'image_image',
  default_formatter: 'image',
  constraints: [
    'ReferenceAccess' => [],
    'FileValidation' => [],
  ],
)]
class ImageType extends FileType {

  /**
   * The default file extensions for generateSampleValue().
   *
   * @var
   */
  const DEFAULT_FILE_EXTENSIONS = 'png gif jpg jpeg';

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    ['name' => $name] = $settings;

    $height = $name . self::SEPARATOR . 'height';
    $width = $name . self::SEPARATOR . 'width';
    $alt = $name . self::SEPARATOR . 'alt';
    $title = $name . self::SEPARATOR . 'title';

    $columns[$name] = [
      'description' => 'The ID of the file entity.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $columns[$width] = [
      'description' => 'The width of the image in pixels.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $columns[$height] = [
      'description' => 'The height of the image in pixels.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $columns[$alt] = [
      'description' => "Alternative image text, for the image's 'alt' attribute.",
      'type' => 'varchar',
      'length' => 512,
    ];
    $columns[$title] = [
      'description' => "Image title text, for the image's 'title' attribute.",
      'type' => 'varchar',
      'length' => 1024,
    ];

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): array {
    ['name' => $name, 'target_type' => $target_type] = $settings;
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);

    $height = $name . self::SEPARATOR . 'height';
    $width = $name . self::SEPARATOR . 'width';
    $alt = $name . self::SEPARATOR . 'alt';
    $title = $name . self::SEPARATOR . 'title';

    $properties[$name] = CustomFieldDataDefinition::create('custom_field_image')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $name]))
      ->setSetting('unsigned', TRUE)
      ->setSetting('target_type', $target_type)
      ->setRequired(FALSE);

    $properties[$width] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@label width', ['@label' => $name]))
      ->setDescription(new TranslatableMarkup('The width of the image in pixels.'))
      ->setInternal(TRUE);

    $properties[$height] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@label height', ['@label' => $name]))
      ->setDescription(new TranslatableMarkup('The height of the image in pixels.'))
      ->setInternal(TRUE);

    $properties[$alt] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label alternative text', ['@label' => $name]))
      ->setDescription(new TranslatableMarkup("Alternative image text, for the image's 'alt' attribute."))
      ->setInternal(TRUE);

    $properties[$title] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label title', ['@label' => $name]))
      ->setDescription(new TranslatableMarkup("Image title text, for the image's 'title' attribute."))
      ->setInternal(TRUE);

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
   * {@inheritdoc}
   */
  public static function generateSampleValue(CustomFieldTypeInterface $field, string $target_entity_type): mixed {
    $random = new Random();
    $settings = $field->getWidgetSetting('settings');
    $file_extensions = $settings['file_extensions'] ?? self::DEFAULT_FILE_EXTENSIONS;
    static $images = [];

    $settings['uri_scheme'] = $field->getConfiguration()['uri_scheme'];
    $min_resolution = empty($settings['min_resolution']) ? '100x100' : $settings['min_resolution'];
    $max_resolution = empty($settings['max_resolution']) ? '600x600' : $settings['max_resolution'];
    $extensions = array_intersect(explode(' ', $file_extensions), ['png', 'gif', 'jpg', 'jpeg']);
    $extension = array_rand(array_combine($extensions, $extensions));

    $min = explode('x', $min_resolution);
    $max = explode('x', $max_resolution);
    if (intval($min[0]) > intval($max[0])) {
      $max[0] = $min[0];
    }
    if (intval($min[1]) > intval($max[1])) {
      $max[1] = $min[1];
    }
    $max_resolution = "$max[0]x$max[1]";

    // Generate a max of 5 different images.
    if (!isset($images[$extension][$min_resolution][$max_resolution]) || count($images[$extension][$min_resolution][$max_resolution]) <= 5) {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $tmp_file = $file_system->tempnam('temporary://', 'generateImage_');
      $destination = $tmp_file . '.' . $extension;
      try {
        $file_system->move($tmp_file, $destination);
      }
      catch (FileException $e) {
        // Ignore failed move.
      }
      if ($path = $random->image($file_system->realpath($destination), $min_resolution, $max_resolution)) {
        $image = File::create();
        $image->setFileUri($path);
        $image->setOwnerId(\Drupal::currentUser()->id());
        $guesser = \Drupal::service('file.mime_type.guesser');
        $image->setMimeType($guesser->guessMimeType($path));
        $image->setFileName($file_system->basename($path));
        $destination_dir = static::doGetUploadLocation($settings);
        $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);
        $destination = $destination_dir . '/' . basename($path);
        $file = \Drupal::service('file.repository')->move($image, $destination);
        $images[$extension][$min_resolution][$max_resolution][$file->id()] = $file;
      }
      else {
        return [];
      }
    }
    else {
      // Select one of the images we've already generated for this field.
      $image_index = array_rand($images[$extension][$min_resolution][$max_resolution]);
      $file = $images[$extension][$min_resolution][$max_resolution][$image_index];
    }

    [$width, $height] = getimagesize($file->getFileUri());

    $value = [
      'target_id' => $file->id(),
      'alt' => $random->sentences(4),
      'title' => $random->sentences(4),
      'width' => $width,
      'height' => $height,
    ];

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateDependencies(CustomFieldTypeInterface $item, array $default_value): array {
    $dependencies = parent::calculateDependencies($item, $default_value);
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $style_id = $widget_settings['preview_image_style'] ?? NULL;
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      // If this widget uses a valid image style to display the preview of the
      // uploaded image, add that image style configuration entity as dependency
      // of this widget.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function onDependencyRemoval(CustomFieldTypeInterface $item, array $dependencies): array {
    $widget_settings = $item->getWidgetSetting('settings') ?? [];
    $changed = FALSE;
    $changed_settings = [];
    $style_id = $widget_settings['preview_image_style'] ?? NULL;
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        /** @var \Drupal\image\ImageStyleStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage($style->getEntityTypeId());
        $replacement_id = $storage->getReplacementId($style_id);
        // If a valid replacement has been provided in the storage, replace the
        // preview image style with the replacement.
        if ($replacement_id && ImageStyle::load($replacement_id)) {
          $widget_settings['preview_image_style'] = $replacement_id;
        }
        else {
          $widget_settings['preview_image_style'] = '';
        }
        $changed = TRUE;
      }
    }
    if ($changed) {
      $changed_settings = $widget_settings;
    }

    return $changed_settings;
  }

}
