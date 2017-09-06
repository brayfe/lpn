<?php

namespace Drupal\Tests\layout_per_node\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests routes info pages and links.
 *
 * @group layout_per_node
 */
class BasicAccessTest extends BrowserTestBase {

  /**
   * Use the 'standard' installation profile to verify this works with
   * Drupal-provided article/page node types.
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

  }

  /**
   * Tests Anonymous access to routes provided by the module
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

  /**
   * Tests route detail page.
   */
  public function testRouteDetail() {
/*    $expected_title = 'Route detail';
    $xpath_warning_messages = '//div[contains(@class, "messages--warning")]';

    // Ensures that devel route detail link in the menu works properly.
    $url = $this->develUser->toUrl();
    $path = '/' . $url->getInternalPath();

    $this->drupalGet($url);
    $this->clickLink('Current route info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);
    $expected_url = Url::fromRoute('devel.route_info.item', [], ['query' => ['path' => $path]]);
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->elementNotExists('xpath', $xpath_warning_messages);

    // Ensures that devel route detail works properly even when dynamic cache
    // is enabled.
    $url = Url::fromRoute('devel.simple_page');
    $path = '/' . $url->getInternalPath();

    $this->drupalGet($url);
    $this->clickLink('Current route info');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);
    $expected_url = Url::fromRoute('devel.route_info.item', [], ['query' => ['path' => $path]]);
    $this->assertSession()->addressEquals($expected_url);
    $this->assertSession()->elementNotExists('xpath', $xpath_warning_messages);

    // Ensures that if a non existent path is passed as input, a warning
    // message is shown.
    $this->drupalGet('devel/routes/item', ['query' => ['path' => '/undefined']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);
    $this->assertSession()->elementExists('xpath', $xpath_warning_messages);

    // Ensures that the route detail page works properly when a valid route
    // name input is passed.
    $this->drupalGet('devel/routes/item', ['query' => ['route_name' => 'devel.simple_page']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);
    $this->assertSession()->elementNotExists('xpath', $xpath_warning_messages);

    // Ensures that if a non existent route name is passed as input a warning
    // message is shown.
    $this->drupalGet('devel/routes/item', ['query' => ['route_name' => 'not.exists']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);
    $this->assertSession()->elementExists('xpath', $xpath_warning_messages);

    // Ensures that if no 'path' nor 'name' query string is passed as input,
    // devel route detail page does not return errors.
    $this->drupalGet('devel/routes/item');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($expected_title);

    // Ensures that the page is accessible ony to the users with the adequate
    // permissions.
    $this->drupalLogout();
    $this->drupalGet('devel/routes/item');
    $this->assertSession()->statusCodeEquals(403);*/
  }

}
