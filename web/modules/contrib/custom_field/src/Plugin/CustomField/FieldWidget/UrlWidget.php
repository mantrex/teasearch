<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'url' custom field widget.
 *
 * @FieldWidget(
 *   id = "url",
 *   label = @Translation("Url"),
 *   category = @Translation("Url"),
 *   data_types = {
 *     "uri",
 *   }
 * )
 */
class UrlWidget extends CustomFieldWidgetBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'size' => 60,
        'placeholder' => '',
        'link_type' => CustomFieldTypeInterface::LINK_GENERIC,
        'field_prefix' => 'default',
        'field_prefix_custom' => '',
      ] + parent::defaultSettings()['settings'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $field_name = $field->getName();

    $element['settings']['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allowed link type'),
      '#default_value' => $settings['link_type'],
      '#options' => [
        CustomFieldTypeInterface::LINK_INTERNAL => $this->t('Internal links only'),
        CustomFieldTypeInterface::LINK_EXTERNAL => $this->t('External links only'),
        CustomFieldTypeInterface::LINK_GENERIC => $this->t('Both internal and external links'),
      ],
    ];
    $element['settings']['field_prefix'] = [
      '#type' => 'radios',
      '#title' => $this->t('Field prefix'),
      '#description' => $this->t('Controls the field prefix for internal links.'),
      '#options' => [
        'default' => $this->t('Default'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $settings['field_prefix'],
      '#states' => [
        'visible' => [
          'input[name="settings[field_settings][' . $field_name . '][widget_settings][settings][link_type]"]' => ['value' => CustomFieldTypeInterface::LINK_INTERNAL],
        ],
      ],
    ];
    $element['settings']['field_prefix_custom'] = [
      '#type' => 'url',
      '#title' => $this->t('Custom field prefix'),
      '#description' => $this->t('Leave empty to not show a prefix.'),
      '#default_value' => $settings['field_prefix_custom'],
      '#attributes' => ['placeholder' => 'https://www.mycustomdomain.com'],
      '#states' => [
        'visible' => [
          ':input[name="settings[field_settings][' . $field_name . '][widget_settings][settings][link_type]"]' => ['value' => CustomFieldTypeInterface::LINK_INTERNAL],
          0 => 'AND',
          ':input[name="settings[field_settings][' . $field_name . '][widget_settings][settings][field_prefix]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $element['settings']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $item = $items[$delta];
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $link = [
      '#type' => 'url',
      '#placeholder' => $settings['placeholder'] ?? NULL,
      '#size' => $settings['size'] ?? NULL,
      // The current field value could have been entered by a different user.
      // However, if it is inaccessible to the current user, do not display it
      // to them.
      '#default_value' => (!empty($item->{$field->getName()}) && ($this->currentUser->hasPermission('link to any page') || $field->getUrl($item)->access())) ? static::getUriAsDisplayableString($item->{$field->getName()}) : NULL,
      '#element_validate' => [[static::class, 'validateUriElement']],
      '#link_type' => $settings['link_type'] ?? CustomFieldTypeInterface::LINK_GENERIC,
    ];

    // If the field is configured to support internal links, it cannot use the
    // 'url' form element and we have to do the validation ourselves.
    if ($this->supportsInternalLinks($settings)) {
      $link['#type'] = 'entity_autocomplete';
      // @todo The user should be able to select an entity type. Will be fixed
      //   in https://www.drupal.org/node/2423093.
      $link['#target_type'] = 'node';
      // Disable autocompletion when the first character is '/', '#' or '?'.
      $link['#attributes']['data-autocomplete-first-character-blacklist'] = '/#?';
      // The link widget is doing its own processing in
      // static::getUriAsDisplayableString().
      $link['#process_default_value'] = FALSE;
    }

    // If the field is configured to allow only internal links, add a useful
    // element prefix and description.
    if (!$this->supportsExternalLinks($settings)) {
      $default_prefix = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $link_prefix = $settings['field_prefix'] == 'custom' ? $settings['field_prefix_custom'] : $default_prefix;
      if (!empty($link_prefix)) {
        $link['#field_prefix'] = rtrim($link_prefix, '/');
      }
      $link['#description'] = $this->t('This must be an internal path such as %add-node. You can also start typing the title of a piece of content to select it. Enter %front to link to the front page. Enter %nolink to display link text only. Enter %button to display keyboard-accessible link text only.', [
        '%add-node' => '/node/add',
        '%front' => '<front>',
        '%nolink' => '<nolink>',
        '%button' => '<button>',
      ]);
    }
    // If the field is configured to allow both internal and external links,
    // show a useful description.
    elseif ($this->supportsExternalLinks($settings) && $this->supportsInternalLinks($settings)) {
      $link['#description'] = $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page. Enter %nolink to display link text only. Enter %button to display keyboard-accessible link text only.', [
        '%front' => '<front>',
        '%add-node' => '/node/add',
        '%url' => 'http://example.com',
        '%nolink' => '<nolink>',
        '%button' => '<button>',
      ]);
    }
    // If the field is configured to allow only external links, show a useful
    // description.
    elseif ($this->supportsExternalLinks($settings) && !$this->supportsInternalLinks($settings)) {
      $link['#description'] = $this->t('This must be an external URL such as %url.', ['%url' => 'http://example.com']);
    }

    return $link + $element;
  }

  /**
   * Indicates enabled support for link to routes.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return bool
   *   Returns TRUE if the Url field is configured to support links to
   *   routes, otherwise FALSE.
   */
  protected function supportsInternalLinks(array $settings) {
    $link_type = $settings['link_type'];
    return (bool) ($link_type & CustomFieldTypeInterface::LINK_INTERNAL);
  }

  /**
   * Indicates enabled support for link to external URLs.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return bool
   *   Returns TRUE if the LinkItem field is configured to support links to
   *   external URLs, otherwise FALSE.
   */
  protected function supportsExternalLinks(array $settings) {
    $link_type = $settings['link_type'];
    return (bool) ($link_type & CustomFieldTypeInterface::LINK_EXTERNAL);
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *   The displayable string value.
   *
   * @see static::getUserEnteredStringAsUri()
   */
  protected static function getUriAsDisplayableString(string $uri): string {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      [$entity_type, $entity_id] = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      if ($entity_type == 'node' && $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }
    elseif ($scheme === 'route') {
      $displayable_string = ltrim($displayable_string, 'route:');
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri(string $string): string {
    // By default, assume the entered string is a URI.
    $uri = trim($string);

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      //   https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Support linking to nothing.
    elseif (in_array($string, ['<nolink>', '<none>', '<button>'], TRUE)) {
      $uri = 'route:' . $string;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($uri, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to an 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (parse_url($uri, PHP_URL_SCHEME) === 'internal'
      && !in_array($element['#value'][0], ['/', '?', '#'], TRUE)
      && substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, new TranslatableMarkup('Manually entered paths should start with one of the following characters: / ? #'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): ?string {
    $uri = !empty($value) ? trim($value) : NULL;
    if (empty($uri)) {
      return NULL;
    }

    return $uri;
  }

}
