<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "uri_link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "uri",
 *   }
 * )
 */
class UriLinkFormatter extends CustomFieldFormatterBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityRepository = $container->get('entity.repository');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'trim_length' => '80',
      'url_plain' => FALSE,
      'rel' => '',
      'target' => '',
      'title' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Leave blank to render the url.'),
      '#default_value' => $this->getSetting('title'),
    ];
    $elements['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim link text length'),
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => $this->t('Leave blank to allow unlimited link text lengths.'),
    ];
    $elements['url_plain'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show URL as plain text'),
      '#default_value' => $this->getSetting('url_plain'),
    ];
    $elements['rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
    ];
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    ];

    return $elements;
  }

  /**
   * Builds the \Drupal\Core\Url object for a link field item.
   *
   * @param string $value
   *   The raw value to build the url from.
   *
   * @return \Drupal\Core\Url
   *   A Url object.
   */
  protected function buildUrl($value) {
    try {
      $url = $this->getUrl($value);
    }
    catch (\InvalidArgumentException $e) {
      // @todo Add logging here in https://www.drupal.org/project/drupal/issues/3348020
      $url = Url::fromRoute('<none>');
    }

    $options = $url->getOptions();

    // Add optional 'rel' attribute to link options.
    if (!empty($this->getSetting('rel'))) {
      $options['attributes']['rel'] = $this->getSetting('rel');
    }
    // Add optional 'target' attribute to link options.
    if (!empty($this->getSetting('target')) && $url->isExternal()) {
      $options['attributes']['target'] = $this->getSetting('target');
    }
    $url->setOptions($options);

    return $url;
  }

  /**
   * Helper function to get a Url from given string value.
   *
   * @param string $value
   *   The field value.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  protected function getUrl(string $value) {
    return Url::fromUri($value);
  }

  /**
   * Helper function to determine if Url is external.
   *
   * @param string $value
   *   The uri value to test.
   *
   * @return bool
   *   The boolean value for if the url is external.
   */
  protected function isExternal(string $value) {
    return $this->getUrl($value)->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, $value) {
    $langcode = $item->getEntity()->language()->getId();
    $url = $this->buildUrl($value);
    // Use the full URL as the link title by default.
    $link_title = $url->toString();
    $link_entity = NULL;
    if ($url->isRouted() && preg_match('/^entity\.(\w+)\.canonical$/', $url->getRouteName(), $matches)) {
      // Check access to the canonical entity route.
      $link_entity_type = $matches[1];
      if (!empty($url->getRouteParameters()[$link_entity_type])) {
        $link_entity_param = $url->getRouteParameters()[$link_entity_type];
        if ($link_entity_param instanceof EntityInterface) {
          $link_entity = $link_entity_param;
        }
        elseif (is_string($link_entity_param) || is_numeric($link_entity_param)) {
          try {
            $link_entity_type_storage = $this->entityTypeManager->getStorage($link_entity_type);
            $link_entity = $link_entity_type_storage->load($link_entity_param);
          }
          catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          }
        }
        // Set the entity in the correct language for display.
        if ($link_entity instanceof TranslatableInterface) {
          $link_entity = $this->entityRepository->getTranslationFromContext($link_entity, $langcode);
        }
        if ($link_entity instanceof EntityInterface) {
          $access = $link_entity->access('view', NULL, TRUE);
          if (!$access->isAllowed()) {
            return NULL;
          }
          $link_title = $link_entity->label();
        }
      }
    }

    // Trim the link text to the desired length.
    if (!empty($this->getSetting('trim_length'))) {
      $link_title = Unicode::truncate($link_title, $this->getSetting('trim_length'), FALSE, TRUE);
    }

    // If the title field value is available, use it for the link text.
    if (!empty($this->getSetting('title'))) {
      $link_title = $this->getSetting('title');
    }
    if ($this->getSetting('url_plain')) {
      $build = [
        '#plain_text' => $value,
      ];
    }
    else {
      $build = [
        '#type' => 'link',
        '#title' => $link_title,
        '#options' => $url->getOptions(),
        '#url' => $url,
      ];
    }

    return $build;
  }

}
