<?php

namespace Drupal\Tests\updated\Functional;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests routes info pages and links.
 *
 * @group updated
 */
class EntityTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'updated',
  ];

  /**
   * Specify the theme to be used in testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stable9';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $nodeType = NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ]);
    $nodeType->save();
    $entity = BaseFieldOverride::create([
      'field_name' => 'status',
      'entity_type' => 'node',
      'bundle' => 'test',
    ]);
    $entity->setDefaultValue(TRUE)->save();

    $account = $this->drupalCreateUser([
      'create test content',
      'edit own test content',
      'administer nodes',
      'administer node last updated date',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test that the display_updated checkbox works.
   */
  public function testNodeForm() {
    $title = $this->randomString();
    // When initially installed, a node's updated display will default to "off".
    $this->drupalGet(Url::fromRoute('node.add', ['node_type' => 'test']));
    $this->assertSession()->fieldValueEquals('display_updated[value]', '');
    // User sets the display to "on".
    $this->getSession()->getPage()->checkField('display_updated[value]');
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->findButton('Save')->submit();
    $node = $this->getNodeByTitle($title);

    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    // Upon return to the node, the user-defined value persists.
    $this->assertSession()->fieldValueEquals('display_updated[value]', '1');
    // User sets the display to "off".
    $this->getSession()->getPage()->unCheckField('display_updated[value]');
    $this->getSession()->getPage()->findButton('Save')->submit();

    $this->drupalGet(Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]));
    // Upon return to the node, the user-defined value persists.
    $this->assertSession()->fieldValueEquals('display_updated[value]', '');
  }

}
