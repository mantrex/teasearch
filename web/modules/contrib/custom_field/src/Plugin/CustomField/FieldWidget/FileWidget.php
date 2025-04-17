<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'file_generic' widget.
 */
#[CustomFieldWidget(
  id: 'file_generic',
  label: new TranslatableMarkup('File'),
  category: new TranslatableMarkup('General'),
  field_types: [
    'file',
  ],
)]
class FileWidget extends CustomFieldWidgetBase {

  /**
   * Collects available render array element types.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->elementInfo = $container->get('element_info');
    $instance->renderer = $container->get('renderer');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'file_extensions' => 'txt',
        'file_directory' => '[date:custom:Y]-[date:custom:m]',
        'max_filesize' => '',
        'progress_indicator' => 'throbber',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $bytes = ByteSizeMarkup::create(Environment::getUploadMaxSize());
    $element['settings']['file_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => $this->t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[static::class, 'validateDirectory']],
      '#weight' => 3,
    ];
    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['file_extensions']);
    $element['settings']['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => $this->t("Separate extensions with a comma or space. Each extension can contain alphanumeric characters, '.', and '_', and should start and end with an alphanumeric character."),
      '#element_validate' => [[static::class, 'validateExtensions']],
      '#weight' => 1,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    ];
    $element['settings']['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes could be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => $bytes]),
      '#size' => 10,
      '#element_validate' => [[static::class, 'validateMaxFilesize']],
      '#weight' => 5,
    ];
    $element['settings']['progress_indicator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Progress indicator'),
      '#options' => [
        'throbber' => $this->t('Throbber'),
        'bar' => $this->t('Bar with progress meter'),
      ],
      '#default_value' => $settings['progress_indicator'],
      '#description' => $this->t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
      '#weight' => 16,
      '#access' => extension_loaded('uploadprogress'),
    ];

    return $element;
  }

  /**
   * Form API callback.
   *
   * Removes slashes from the beginning and end of the destination value and
   * ensures that the file directory path is not included at the beginning of
   * the value.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   *
   * This doubles as a convenience clean-up function and a validation routine.
   * Commas are allowed by the end-user, but ultimately the value will be stored
   * as space-separated list for compatibility with file_validate_extensions().
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
      $extension_array = array_unique(array_filter(explode(' ', $extensions)));
      $extensions = implode(' ', $extension_array);
      if (!preg_match('/^([a-z0-9]+([._][a-z0-9])* ?)+$/', $extensions)) {
        $form_state->setError($element, new TranslatableMarkup("The list of allowed extensions is not valid. Allowed characters are a-z, 0-9, '.', and '_'. The first and last characters cannot be '.' or '_', and these two characters cannot appear next to each other. Separate extensions with a comma or space."));
      }
      else {
        $form_state->setValueForElement($element, $extensions);
      }

      // If insecure uploads are not allowed and txt is not in the list of
      // allowed extensions, ensure that no insecure extensions are allowed.
      if (!in_array('txt', $extension_array, TRUE) && !\Drupal::config('system.file')->get('allow_insecure_uploads')) {
        foreach ($extension_array as $extension) {
          if (preg_match(FileSystemInterface::INSECURE_EXTENSION_REGEX, 'test.' . $extension)) {
            $form_state->setError($element, new TranslatableMarkup('Add %txt_extension to the list of allowed extensions to securely upload files with a %extension extension. The %txt_extension extension will then be added automatically.', [
              '%extension' => $extension,
              '%txt_extension' => 'txt',
            ]));

            break;
          }
        }
      }
    }
  }

  /**
   * Form API callback.
   *
   * Ensures that a size has been entered and that it can be parsed by
   * \Drupal\Component\Utility\Bytes::toNumber().
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    $element['#value'] = trim($element['#value']);
    $form_state->setValue($element['#parents'], $element['#value']);
    if (!empty($element['#value']) && !Bytes::validate($element['#value'])) {
      $form_state->setError($element, new TranslatableMarkup('The "@name" option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).', ['@name' => $element['#title']]));
    }
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators(array $settings) {
    $validators = [];

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toNumber(Environment::getUploadMaxSize());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toNumber($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['FileSizeLimit'] = ['fileLimit' => $max_filesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['FileExtension'] = ['extensions' => $settings['file_extensions']];
    }

    return $validators;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
    $item = $items[$delta];
    $fid = $item->{$field->getName()};
    // Account for temporary storage settings.
    $current_settings = $form_state->get('current_settings');
    if (!empty($current_settings)) {
      $uri_scheme = $current_settings['columns'][$field->getName()]['uri_scheme'] ?? 'public';
    }
    else {
      $uri_scheme = $item->getFieldDefinition()->getSetting('columns')[$field->getName()]['uri_scheme'];
    }
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $settings['uri_scheme'] = $uri_scheme;
    $defaults = [
      'fids' => [],
    ];
    if (!empty($fid)) {
      if (is_array($fid) && isset($fid['fids'])) {
        $fid = reset($fid['fids']);
      }
      $defaults['fids'][] = $fid;
    }

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('managed_file');

    $element += [
      '#type' => 'managed_file',
      '#upload_location' => $field->getUploadLocation($settings),
      '#upload_validators' => self::getUploadValidators($settings),
      '#value_callback' => [static::class, 'value'],
      '#process' => array_merge($element_info['#process'], [[static::class, 'process']]),
      '#progress_indicator' => $settings['progress_indicator'],
      '#extended' => TRUE,
      '#field_name' => $item->getFieldDefinition()->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#multiple' => FALSE,
      '#cardinality' => 1,
    ];
    $element['#default_value'] = $defaults;

    if (empty($fid)) {
      $file_upload_help = [
        '#theme' => 'file_upload_help',
        '#description' => $element['#description'],
        '#upload_validators' => $element['#upload_validators'],
        '#cardinality' => 1,
      ];
    }
    $element['#description'] = $this->renderer->renderPlain($file_upload_help);

    return $element;
  }

  /**
   * Form API callback: Processes a file_generic field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    return $element;
  }

  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    // Account for field config default values form initial state.
    if ($input == "") {
      return $element['#default_value'];
    }
    // We depend on the managed file element to handle uploads.
    $return = ManagedFile::valueCallback($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += [
      'fids' => [],
    ];

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, array $column): mixed {
    $fids = $value['fids'] ?? NULL;
    if (empty($fids)) {
      return NULL;
    }
    $fid = reset($fids);
    $value['target_id'] = $fid;
    unset($value['fids']);

    return $value;
  }

}
