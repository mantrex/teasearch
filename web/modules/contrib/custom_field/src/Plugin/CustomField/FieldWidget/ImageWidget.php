<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomField\FieldType\ImageType;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_image' widget.
 */
#[CustomFieldWidget(
  id: 'image_image',
  label: new TranslatableMarkup('Image'),
  category: new TranslatableMarkup('General'),
  field_types: [
    'image',
  ],
)]
class ImageWidget extends FileWidget {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->imageFactory = $container->get('image.factory');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'file_extensions' => 'png gif jpg jpeg',
        'alt_field' => 1,
        'alt_field_required' => 1,
        'title_field' => 0,
        'title_field_required' => 0,
        'progress_indicator' => 'throbber',
        'max_resolution' => '',
        'min_resolution' => '',
        'preview_image_style' => ImageStyle::load('thumbnail') ? 'thumbnail' : '',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    assert($field instanceof ImageType);

    // Add maximum and minimum resolution settings.
    $max_resolution = explode('x', $settings['max_resolution']) + ['', ''];
    $element['settings']['max_resolution'] = [
      '#type' => 'item',
      '#title' => $this->t('Maximum image resolution'),
      '#element_validate' => [[static::class, 'validateResolution']],
      '#weight' => 4.1,
      '#description' => $this->t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="http://wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image.'),
    ];
    $element['settings']['max_resolution']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum width'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
      '#prefix' => '<div class="form--inline clearfix">',
    ];
    $element['settings']['max_resolution']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum height'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#suffix' => '</div>',
    ];

    $min_resolution = explode('x', $settings['min_resolution']) + ['', ''];
    $element['settings']['min_resolution'] = [
      '#type' => 'item',
      '#title' => $this->t('Minimum image resolution'),
      '#element_validate' => [[static::class, 'validateResolution']],
      '#weight' => 4.2,
      '#description' => $this->t('The minimum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
    ];
    $element['settings']['min_resolution']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum width'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
      '#prefix' => '<div class="form--inline clearfix">',
    ];
    $element['settings']['min_resolution']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum height'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#suffix' => '</div>',
    ];
    // Add title and alt configuration options.
    $element['settings']['alt_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Alt</em> field'),
      '#default_value' => $settings['alt_field'],
      '#description' => $this->t('Short description of the image used by screen readers and displayed when the image is not loaded. Enabling this field is recommended.'),
      '#weight' => 9,
    ];
    $element['settings']['alt_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<em>Alt</em> field required'),
      '#default_value' => $settings['alt_field_required'],
      '#description' => $this->t('Making this field required is recommended.'),
      '#weight' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][ ' . $field->getName() . '][widget_settings][settings][alt_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['title_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <em>Title</em> field'),
      '#default_value' => $settings['title_field'],
      '#description' => $this->t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
      '#weight' => 11,
    ];
    $element['settings']['title_field_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<em>Title</em> field required'),
      '#default_value' => $settings['title_field_required'],
      '#weight' => 12,
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $field->getName() . '][widget_settings][settings][title_field]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['settings']['preview_image_style'] = [
      '#title' => $this->t('Preview image style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#empty_option' => '<' . $this->t('no preview') . '>',
      '#default_value' => $settings['preview_image_style'],
      '#description' => $this->t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    /** @var \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item */
    $item = $items[$delta];
    $name = $field->getName();
    $fid = $item->{$name};
    // Account for temporary storage settings.
    $current_settings = $form_state->get('current_settings');
    if (!empty($current_settings)) {
      $uri_scheme = $current_settings['columns'][$name]['uri_scheme'] ?? 'public';
    }
    else {
      $uri_scheme = $item->getFieldDefinition()->getSetting('columns')[$name]['uri_scheme'];
    }
    $settings = $field->getWidgetSetting('settings') + static::defaultSettings()['settings'];
    $settings['uri_scheme'] = $uri_scheme;
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';

    // Add image validation.
    $element['#upload_validators']['file_validate_is_image'] = [];

    // Add upload resolution validation.
    if ($settings['max_resolution'] || $settings['min_resolution']) {
      $element['#upload_validators']['file_validate_image_resolution'] = [
        $settings['max_resolution'],
        $settings['min_resolution'],
      ];
    }

    $extensions = $settings['file_extensions'];
    $supported_extensions = $this->imageFactory->getSupportedExtensions();

    // If using custom extension validation, ensure that the extensions are
    // supported by the current image toolkit. Otherwise, validate against all
    // toolkit supported extensions.
    $extensions = !empty($extensions) ? array_intersect(explode(' ', $extensions), $supported_extensions) : $supported_extensions;
    $element['#upload_validators']['file_validate_extensions'][0] = implode(' ', $extensions);

    // Add mobile device image capture acceptance.
    $element['#accept'] = 'image/*';

    // Add properties needed by process() method.
    $element['#image_width'] = $item->{$name . '__width'} ?? NULL;
    $element['#image_height'] = $item->{$name . '__height'} ?? NULL;
    $element['#title_field'] = $settings['title_field'];
    $element['#title_field_required'] = !$is_config_form && $settings['title_field_required'];
    $element['#alt_field'] = $settings['alt_field'];
    $element['#alt_field_required'] = !$is_config_form && $settings['alt_field_required'];
    $element['#preview_image_style'] = $settings['preview_image_style'];
    $element['#default_value'] = [
      'fids' => [],
      'alt' => $item->{$name . '__alt'} ?? NULL,
      'title' => $item->{$name . '__title'} ?? NULL,
    ];

    if (!empty($fid)) {
      if (is_array($fid) && isset($fid['fids'])) {
        $fid = reset($fid['fids']);
      }
      $element['#default_value']['fids'] = [$fid];
    }

    return parent::process($element, $form_state, $form);
  }

  /**
   * Form API callback: Processes an image_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];

    $element['#theme'] = 'image_widget';

    // Add the image preview.
    if (!empty($element['#files']) && $element['#preview_image_style']) {
      $file = reset($element['#files']);
      $variables = [
        'style_name' => $element['#preview_image_style'],
        'uri' => $file->getFileUri(),
      ];

      $dimension_key = $variables['uri'] . '.image_preview_dimensions';
      // Determine image dimensions.
      if (isset($element['#image_width']) && isset($element['#image_height'])) {
        $variables['width'] = $element['#image_width'];
        $variables['height'] = $element['#image_height'];
      }
      elseif ($form_state->has($dimension_key)) {
        $variables += $form_state->get($dimension_key);
      }
      else {
        $image = \Drupal::service('image.factory')->get($file->getFileUri());
        if ($image->isValid()) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }
      }

      $element['preview'] = [
        '#weight' => -10,
        '#theme' => 'image_style',
        '#width' => $variables['width'],
        '#height' => $variables['height'],
        '#style_name' => $variables['style_name'],
        '#uri' => $variables['uri'],
      ];

      // Store the dimensions in the form so the file doesn't have to be
      // accessed again. This is important for remote files.
      $form_state->set($dimension_key, [
        'width' => $variables['width'],
        'height' => $variables['height'],
      ]);
    }

    // Add the additional alt and title fields.
    $element['alt'] = [
      '#title' => new TranslatableMarkup('Alternative text'),
      '#type' => 'textfield',
      '#default_value' => $item['alt'] ?? '',
      '#description' => new TranslatableMarkup('Short description of the image used by screen readers and displayed when the image is not loaded. This is important for accessibility.'),
      // @see https://www.drupal.org/node/465106#alt-text
      '#maxlength' => 512,
      '#weight' => -12,
      '#access' => (bool) $element['#files'] && $element['#alt_field'],
      '#required' => $element['#alt_field_required'],
      '#element_validate' => $element['#alt_field_required'] == 1 ? [[static::class, 'validateRequiredFields']] : [],
    ];
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => new TranslatableMarkup('Title'),
      '#default_value' => $item['title'] ?? '',
      '#description' => new TranslatableMarkup('The title is used as a tool tip when the user hovers the mouse over the image.'),
      '#maxlength' => 1024,
      '#weight' => -11,
      '#access' => (bool) $element['#files'] && $element['#title_field'],
      '#required' => $element['#title_field_required'],
      '#element_validate' => $element['#title_field_required'] == 1 ? [[static::class, 'validateRequiredFields']] : [],
    ];

    return $element;
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, FormStateInterface $form_state) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (['x', 'y'] as $dimension) {
        if (!$element[$dimension]['#value']) {
          // We expect the field name placeholder value to be wrapped in
          // $this->t() here, so it won't be escaped again as it's already
          // marked safe.
          $form_state->setError($element[$dimension], new TranslatableMarkup('Both a height and width value must be specified in the @name field.', ['@name' => $element['#title']]));
          return;
        }
      }
      $form_state->setValueForElement($element, $element['x']['#value'] . 'x' . $element['y']['#value']);
    }
    else {
      $form_state->setValueForElement($element, '');
    }
  }

  /**
   * Validate callback for alt and title field, if the user wants them required.
   *
   * This is separated in a validate function instead of a #required flag to
   * avoid being validated on the process callback.
   */
  public static function validateRequiredFields($element, FormStateInterface $form_state) {
    // Only do validation if the function is triggered from other places than
    // the image process form.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#submit']) && in_array('file_managed_file_submit', $triggering_element['#submit'], TRUE)) {
      $form_state->setLimitValidationErrors([]);
    }
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

    return $return;
  }

}
