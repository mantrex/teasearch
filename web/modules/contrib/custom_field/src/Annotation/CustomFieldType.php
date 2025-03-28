<?php

namespace Drupal\custom_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Custom field Type item annotation object.
 *
 * @see \Drupal\custom_field\Plugin\CustomFieldTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class CustomFieldType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the field type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short human readable description for the field type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The default value for the check empty field setting.
   *
   * @var bool
   */
  public $check_empty = FALSE;

  /**
   * Flag to restrict this type from empty row checking.
   *
   * @var bool
   */
  public $never_check_empty = FALSE;

  /**
   * The category under which the field type should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * The plugin_id of the default widget for this field type.
   *
   * This widget must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  public $default_widget;

  /**
   * The plugin_id of the default formatter for this field type.
   *
   * This formatter must be available whenever the field type is available (i.e.
   * provided by the field type module, or by a module the field type module
   * depends on).
   *
   * @var string
   */
  public $default_formatter;

}
