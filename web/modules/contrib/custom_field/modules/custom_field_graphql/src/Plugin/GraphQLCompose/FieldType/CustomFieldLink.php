<?php

declare(strict_types=1);

namespace Drupal\custom_field_graphql\Plugin\GraphQLCompose\FieldType;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;
use Drupal\link\LinkItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "custom_field_link",
 *   type_sdl = "Link"
 * )
 */
class CustomFieldLink extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

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
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {

    $url = Url::fromUri($item->uri);
    $link_title = $this->getTitle($url);
    $link = $url->toString(TRUE);
    $context->addCacheableDependency($link);

    return [
      'title' => $link_title ?: $link->getGeneratedUrl(),
      'url' => $link->getGeneratedUrl(),
      'internal' => !$url->isExternal(),
    ];
  }

  /**
   * Get the title from a FieldItemInterface.
   *
   * @param \Drupal\Core\Url $url
   *   The Url object.
   *
   * @return string|null
   *   The title.
   */
  protected function getTitle(Url $url): ?string {
    $link_title = NULL;
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
          $link_entity = $this->entityRepository->getTranslationFromContext($link_entity);
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
    return $link_title;
  }

  /**
   * Get the URL from a LinkItemInterface.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link item.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  protected function getUrlFromLink(LinkItemInterface $item): Url {
    return $item->getUrl();
  }

  /**
   * Get the URL from a FieldItemInterface.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  protected function getUrlFromOther(FieldItemInterface $item): Url {
    $path = $item->uri ?? NULL;

    return UrlHelper::isExternal($path)
      ? Url::fromUri($path)
      : Url::fromUserInput($path);
  }

}
