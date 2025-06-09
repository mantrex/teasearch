<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field_graphql\Functional;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Tests\graphql_compose\Functional\GraphQLComposeBrowserTestBase;
use Drupal\custom_field\CustomFieldGenerateDataInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\NodeInterface;

/**
 * Test the Custom Field integration.
 *
 * @group legacy
 */
class CustomFieldGraphqlComposeTest extends GraphQLComposeBrowserTestBase {

  /**
   * We aren't concerned with strict config schema for contrib.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE; // @phpcs:ignore

  /**
   * The test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'custom_field',
    'custom_field_test',
    'custom_field_graphql_test',
    'custom_field_viewfield',
    'custom_field_graphql',
    'graphql_compose',
    'graphql_compose_views',
    'node',
    'user',
  ];

  /**
   * The custom field generate data service.
   *
   * @var \Drupal\custom_field\CustomFieldGenerateDataInterface
   */
  protected CustomFieldGenerateDataInterface $customFieldDataGenerator;

  /**
   * File URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->customFieldDataGenerator = $this->container->get('custom_field.generate_data');
    $this->fileUrlGenerator = $this->container->get('file_url_generator');

    $field_config = FieldConfig::loadByName(
      'node',
      'custom_field_entity_test',
      'field_test'
    );

    // Set the graphql views in the widget settings.
    $field_settings = $field_config->getSetting('field_settings');
    $field_settings['viewfield_test']['widget_settings']['settings']['allowed_views'] = [
      'custom_field_graphql_test' => [
        'graphql_1' => 'graphql_1',
      ],
      'custom_field_graphql_test2' => [
        'graphql_1' => 'graphql_1',
      ],
    ];
    $field_config->setSetting('field_settings', $field_settings);
    $field_config->save();

    $field_data = $this->customFieldDataGenerator->generateFieldData($field_config->getSettings(), $field_config->getTargetEntityTypeId());

    // Set a graphql view display.
    $field_data['viewfield_test'] = 'custom_field_graphql_test';
    $field_data['viewfield_test__display'] = 'graphql_1';

    $this->node = $this->createNode([
      'type' => 'custom_field_entity_test',
      'title' => 'Test',
      'body' => [
        'value' => 'Test content',
        'format' => 'plain_text',
      ],
      'status' => 1,
      'field_test' => $field_data,
    ]);

    // Create some article nodes.
    $article_titles = ['Article 1', 'Article 2', 'Article 3'];
    foreach ($article_titles as $article_title) {
      $this->createNode([
        'type' => 'article',
        'title' => $article_title,
        'status' => 1,
      ]);
    }

    $this->setEntityConfig('node', 'custom_field_entity_test', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);
    $this->setEntityConfig('node', 'article', [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);

    $this->setFieldConfig('node', 'custom_field_entity_test', 'field_test', [
      'enabled' => TRUE,
    ]);
  }

  /**
   * Test load entity by id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Throwable
   */
  public function testCustomField(): void {
    $node = $this->node;
    $entity_type_id = $node->getEntityTypeId();
    $bundle = $node->bundle();
    $field_name = 'field_test';
    $field_config = FieldConfig::loadByName(
      'node',
      'custom_field_entity_test',
      'field_test'
    );
    $columns = $field_config->getSetting('columns');

    $config = $this->config('graphql_compose.settings');
    $config_path = "field_config.$entity_type_id.$bundle.$field_name.subfields";
    $settings = $config->get($config_path);
    // Test backwards compatibility to ensure names with underscores continue to
    // resolve.
    foreach ($columns as $name => $column) {
      $settings[(string) $name] = [
        'enabled' => TRUE,
        'name_sdl' => (string) $name,
      ];
    }
    $config->set($config_path, $settings);
    $config->save();

    $query = <<<GQL
      query {
        node(id: "{$this->node->uuid()}") {
          ... on NodeCustomFieldEntityTest {
            test {
              boolean_test
              color_test
              datetime_test {
                offset
                time
                timestamp
                timezone
              }
              decimal_test
              email_test
              entity_reference_test {
                __typename
                ... on NodeArticle {
                  id
                }
              }
              file_test {
                description
                mime
                name
                size
                url
              }
              float_test
              image_test {
                alt
                height
                mime
                size
                title
                url
                width
              }
              integer_test
              link_test {
                internal
                title
                url
                attributes {
                  accesskey
                  ariaLabel
                  class
                  id
                  name
                  rel
                  target
                  title
                }
              }
              map_test
              map_string_test
              string_test
              string_long_test {
                value
                processed
                format
              }
              telephone_test
              time_test
              uri_test {
                internal
                title
                url
              }
              viewfield_test {
                views {
                  __typename
                  ... on CustomFieldTestGraphqlResult {
                    results {
                      __typename
                      ... on NodeArticle {
                        id
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);
    $this->assertNotNull($content['data']['node']['test'] ?? NULL);

    $custom_field = $content['data']['node']['test'];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $node->get('field_test')->first();

    $this->assertEquals($item->get('boolean_test')->getValue(), $custom_field['boolean_test']);
    $this->assertEquals($item->get('color_test')->getValue(), $custom_field['color_test']);
    $this->assertEquals($item->get('decimal_test')->getValue(), $custom_field['decimal_test']);
    $this->assertEquals($item->get('email_test')->getValue(), $custom_field['email_test']);

    // Entity reference type.
    $reference = $custom_field['entity_reference_test'];
    /** @var \Drupal\node\NodeInterface $reference_entity */
    $reference_entity = $item->get('entity_reference_test__entity')->getValue();
    $this->assertEquals('NodeArticle', $reference['__typename']);
    $this->assertEquals($reference_entity->uuid(), $reference['id']);

    // Datetime type.
    $date_value = $item->get('datetime_test')->getValue();
    $date_time = is_numeric($date_value)
      ? DrupalDateTime::createFromTimestamp($date_value)
      : new DrupalDateTime($date_value, new \DateTimeZone('UTC'));
    $this->assertEquals($date_time->getTimestamp(), $custom_field['datetime_test']['timestamp']);
    $this->assertEquals($date_time->getTimezone()->getName(), $custom_field['datetime_test']['timezone']);
    $this->assertEquals($date_time->format('P'), $custom_field['datetime_test']['offset']);
    $this->assertEquals($date_time->format(\DateTime::RFC3339), $custom_field['datetime_test']['time']);

    // File.
    /** @var \Drupal\file\FileInterface $file */
    $file = $item->get('file_test__entity')->getValue();
    $file_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    $this->assertEquals($file->getMimeType(), $custom_field['file_test']['mime']);
    $this->assertEquals($file->getSize(), $custom_field['file_test']['size']);
    $this->assertEquals($file->getFilename(), $custom_field['file_test']['name']);
    $this->assertEquals($file_url, $custom_field['file_test']['url']);

    // Why are float values slightly off in comparison?
    $this->assertTrue(is_numeric($custom_field['float_test']));

    // Image type.
    /** @var \Drupal\file\FileInterface $image */
    $image = $item->get('image_test__entity')->getValue();
    $image_url = $this->fileUrlGenerator->generateAbsoluteString($image->getFileUri());
    $this->assertEquals($item->get('image_test__alt')->getValue(), $custom_field['image_test']['alt']);
    $this->assertEquals($item->get('image_test__height')->getValue(), $custom_field['image_test']['height']);
    $this->assertEquals($image->getMimeType(), $custom_field['image_test']['mime']);
    $this->assertEquals($image->getSize(), $custom_field['image_test']['size']);
    $this->assertEquals($item->get('image_test__title')->getValue(), $custom_field['image_test']['title']);
    $this->assertEquals($item->get('image_test__width')->getValue(), $custom_field['image_test']['width']);
    $this->assertEquals($image_url, $custom_field['image_test']['url']);

    // Link type.
    $link_url = Url::fromUri($item->get('link_test')->getValue());
    $this->assertEquals($link_url->toString(), $custom_field['link_test']['url']);
    $this->assertEquals(!$link_url->isExternal(), (bool) $custom_field['link_test']['internal']);
    $this->assertEquals($item->get('link_test__title')->getValue(), (bool) $custom_field['link_test']['title']);

    $this->assertEquals($item->get('map_string_test')->getValue(), $custom_field['map_string_test']);
    $this->assertEquals($item->get('map_test')->getValue(), $custom_field['map_test']);
    $this->assertEquals($item->get('integer_test')->getValue(), $custom_field['integer_test']);
    $this->assertEquals($item->get('string_test')->getValue(), $custom_field['string_test']);

    // String long type.
    $this->assertEquals($item->get('string_long_test')->getValue(), $custom_field['string_long_test']['value']);
    $this->assertNotEmpty($custom_field['string_long_test']['format']);
    $this->assertNotEmpty($custom_field['string_long_test']['processed']);

    $this->assertEquals($item->get('telephone_test')->getValue(), $custom_field['telephone_test']);
    $this->assertEquals($item->get('time_test')->getValue(), $custom_field['time_test']);

    // Uri type.
    $uri_url = Url::fromUri($item->get('uri_test')->getValue());
    $this->assertEquals($uri_url->toString(), $custom_field['uri_test']['url']);
    $this->assertEquals(!$uri_url->isExternal(), (bool) $custom_field['uri_test']['internal']);

    // Viewfield type.
    $viewfield = $custom_field['viewfield_test'];
    $this->assertEquals('CustomFieldTestGraphqlResult', $viewfield['views']['__typename']);
    $this->assertEquals('NodeArticle', $viewfield['views']['results'][0]['__typename']);
  }

  /**
   * Test load entity by id with advanced settings.
   *
   * @throws \Throwable
   */
  public function testCustomFieldAdvancedSettings(): void {
    $node = $this->node;
    $entity_type_id = $node->getEntityTypeId();
    $bundle = $node->bundle();
    $field_name = 'field_test';

    $config = $this->config('graphql_compose.settings');
    $config_path = "field_config.$entity_type_id.$bundle.$field_name.subfields";
    $settings = $config->get($config_path);
    // Set a field's name_sdl and verify it works.
    $settings['string_test'] = [
      'enabled' => TRUE,
      'name_sdl' => 'someCustomName',
    ];
    $config->set($config_path, $settings);
    $config->save();

    $query = <<<GQL
      query {
        node(id: "{$this->node->uuid()}") {
          ... on NodeCustomFieldEntityTest {
            test {
              someCustomName
            }
          }
        }
      }
    GQL;

    $content = $this->executeQuery($query);
    $this->assertNotNull($content['data']['node']['test'] ?? NULL);

    $custom_field = $content['data']['node']['test'];
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $node->get('field_test')->first();

    $this->assertEquals($item->get('string_test')->getValue(), $custom_field['someCustomName']);
  }

}
