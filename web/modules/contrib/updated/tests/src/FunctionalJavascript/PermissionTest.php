<?php

namespace Drupal\Tests\updated\FunctionalJavascript;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;

use Drupal\updated\UpdatedHelper;

/**
 * Tests that the module defined permission does limit user actions.
 *
 * @group updated_visibility
 */
class PermissionTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
  protected $defaultTheme = 'stark';

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

    $account = $this->drupalCreateUser(['administer blocks',
      'access administration pages',
      'administer nodes',
      'administer node last updated date',
      'administer content types',
      'create test content',
      'edit own test content',
    ]);
    $this->drupalLogin($account);

    // Place the block in the content area.
    $block_url = 'admin/structure/block/add/updated_date_block/stark';
    $edit = [
      'region' => 'content',
    ];
    $this->drupalGet($block_url);
    $this->submitForm($edit, 'Save block');
  }

  /**
   * Test the visibility of the updated block on a per-node basis.
   */
  public function testNodeLevelPermission() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    // Go to node creation page.
    $this->drupalGet('node/add/test');
    // Find the v-tab provided by the module and click it.
    $page->findLink('Page display options')->click();
    // Confirm the "display updated" checkbox is unchecked by default.
    $page->hasUncheckedField('#display_updated[value]');
    // Get random title and save the node.
    $title = $this->randomString();
    $edit = [
      'title[0][value]' => $title,
    ];
    $this->submitForm($edit, 'Save');
    // Confirm a new Test type node was created.
    $web_assert->responseContains('has been created.');
    // Verify the updated element exists in the markup.
    $web_assert->elementNotExists('css', '#block-stark-lastupdateddateblock');
    // Grab the node id by its title and load the node edit page.
    $node = $this->drupalGetNodeByTitle($title);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $page->findLink('Page display options')->click();
    $page->checkField('display_updated[value]');
    $this->submitForm([], 'Save');
    $web_assert->elementExists('css', '#block-stark-lastupdateddateblock');

    // Logout.
    $this->drupalLogout();
    // Create a new user without the permission to edit display updated
    // visibility.
    $account = $this->drupalCreateUser([
      'create test content',
      'edit any test content',
      'administer nodes',
    ]);
    // Login with the new user.
    $this->drupalLogin($account);
    // Edit the same existing node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Go to the Page Display Options v-tab section.
    $page->findLink('Page display options')->click();
    // Confirm the checkbox is disabled with a descriptive message.
    $web_assert->elementAttributeContains('css', '#edit-display-updated-value', 'disabled', 'disabled');
    $web_assert->elementTextContains('css', '#edit-display-updated-value--description', UpdatedHelper::PERMISSION_DENIED_MESSAGE);
    // Logout to end test.
    $this->drupalLogout();
  }

  /**
   * Test access to the updated block content type setting.
   */
  public function testContentTypeLevelPermission() {
    $session = $this->getSession();
    $web_assert = $this->assertSession();
    $page = $session->getPage();

    // Demonstrate a user with the permission to edit content type setting.
    $this->drupalGet('admin/structure/types/manage/test');
    $page->findLink('Page display defaults')->click();
    $page->hasUnCheckedField('#edit-display-updated');
    $page->checkField('edit-display-updated');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/add/test');
    $page->findLink('Page display options')->click();
    $page->hasCheckedField('#display_updated[value]');
    $this->drupalLogout();

    // Demonstrate a user without the permission to edit content type setting.
    $account = $this->drupalCreateUser([
      'administer content types',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/types/manage/test');
    $page->findLink('Page display defaults')->click();
    $web_assert->elementAttributeContains('css', '#edit-display-updated', 'disabled', 'disabled');
    $web_assert->elementTextContains('css', '#edit-display-updated--description', UpdatedHelper::PERMISSION_DENIED_MESSAGE);
    $this->drupalLogout();
  }

}
