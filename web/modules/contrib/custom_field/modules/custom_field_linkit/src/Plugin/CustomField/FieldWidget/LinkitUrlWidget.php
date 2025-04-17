<?php

namespace Drupal\custom_field_linkit\Plugin\CustomField\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\custom_field\Attribute\CustomFieldWidget;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\custom_field\Plugin\CustomFieldWidgetBase;
use Drupal\file\FileInterface;
use Drupal\linkit\Utility\LinkitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkit_url' widget.
 */
#[CustomFieldWidget(
  id: 'linkit_url',
  label: new TranslatableMarkup('Linkit'),
  category: new TranslatableMarkup('Url'),
  field_types: [
    'uri',
  ],
)]
class LinkitUrlWidget extends CustomFieldWidgetBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fileUrlGenerator = $container->get('file_url_generator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'settings' => [
        'linkit_profile' => 'default',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widgetSettingsForm($form_state, $field);
    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];
    $profile_storage = $this->entityTypeManager->getStorage('linkit_profile');

    $options = array_map(function ($linkit_profile) {
      return $linkit_profile->label();
    }, $profile_storage->loadMultiple());

    $element['settings']['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#options' => $options,
      '#default_value' => $settings['linkit_profile'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state, CustomFieldTypeInterface $field): array {
    $element = parent::widget($items, $delta, $element, $form, $form_state, $field);
    $item = $items[$delta];
    $uri = $item->{$field->getName()} ?? NULL;

    // Try to fetch entity information from the URI.
    $default_allowed = !$item->isEmpty() && ($this->currentUser->hasPermission('link to any page') || $field->getUrl($item)->access());
    $entity = $default_allowed && $uri ? LinkitHelper::getEntityFromUri($uri) : NULL;

    // Display entity URL consistently across all entity types.
    if ($entity instanceof FileInterface) {
      // File entities are anomalies, so we handle them differently.
      $element['#default_value'] = $this->fileUrlGenerator->generateString($entity->getFileUri());
    }
    elseif ($entity instanceof EntityInterface) {
      $uri_parts = parse_url($uri);
      $uri_options = [];
      // Extract query parameters and fragment and merge them into $uri_options.
      if (isset($uri_parts['fragment']) && $uri_parts['fragment'] !== '') {
        $uri_options += ['fragment' => $uri_parts['fragment']];
      }
      if (!empty($uri_parts['query'])) {
        $uri_query = [];
        parse_str($uri_parts['query'], $uri_query);
        $uri_options['query'] = isset($uri_options['query']) ? $uri_options['query'] + $uri_query : $uri_query;
      }
      $element['#default_value'] = $entity->toUrl()->setOptions($uri_options)->toString();
    }

    $settings = $field->getWidgetSetting('settings') + self::defaultSettings()['settings'];

    $element['#type'] = 'linkit';
    $description = $settings['description'] ?: 'Start typing to find content or paste a URL and click on the suggestion below.';
    $element['#description'] = $this->t('@description', ['@description' => $description]);
    $element['#autocomplete_route_name'] = 'linkit.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'linkit_profile_id' => $settings['linkit_profile'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValue(mixed $value, $column): mixed {
    $uri = trim($value);
    if ($uri === '') {
      return NULL;
    }

    return LinkitHelper::uriFromUserInput($uri);
  }

}
