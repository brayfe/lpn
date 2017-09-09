<?php

namespace Drupal\Tests\layout_per_node\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests routes info pages and links.
 *
 * @group layout_per_node
 */
class BasicAccessTest extends BrowserTestBase {

  /**
   * Use the 'standard' installation for Drupal-provided node types.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_per_node',
    'field_layout',
    'layout_discovery',
  ];

  /**
   * A node ID for use across multiple tests.
   *
   * @var string
   */
  protected $articleID;

  /**
   * The user for the test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $articleUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->articleUser = $this->drupalCreateUser([
      'use article layout per node',
      'create article content',
      'edit own article content',
    ]);
    $this->drupalLogin($this->articleUser);
  }

  /**
   * Tests routes info.
   */
  public function testArticleUserAccess() {

    // Test /node/add page with only one content type.
    $this->drupalGet('node/add');
    $this->assertResponse(200);
    $this->assertUrl('node/add/article');
    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->articleID = $node->id();

    $this->drupalGet('admin/layout-per-node/add');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('layout-editor/switch-layouts');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/layout-per-node/set');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/layout-per-node/get');
    $this->assertSession()->statusCodeEquals(403);

    //$edit_tab = $this->xpath("//a[@data-drupal-link-system-path]");
    //$edit_tab = $this->xpath("//a[contains(@href,'/node')]/@href");
    //$this->assertEqual(count($edit_tab), 1, 'Ensure that the user does have access to edit the node');

  }

  /**
   * Tests Anonymous access to routes provided by the module.
   */
  public function testAnonymousAccess() {
    // Behave as an anonymous user.
    $this->drupalLogout();

    $this->drupalGet('admin/layout-per-node/add');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('layout-editor/switch-layouts');
    $this->assertSession()->statusCodeEquals(403);

    // GET requests should be not found.
    $this->drupalGet('admin/layout-per-node/set');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/layout-per-node/get');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that pages do not show submitted information by default.
    $this->drupalGet('node/' . $this->articleID);
    $this->assertSession()->statusCodeEquals(200);


  }

}
