<?php

namespace Drupal\Tests\system_page_override\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the config UI for adding and editing entity browsers.
 *
 * @group system_page_override
 */
class ConfigOverrideTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system_page_override',
    'user',
    'node',
  ];

  /**
   * The administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The content manager user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $contentUser;

  /**
   * An article node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

  }

  /**
   * Tests enabling a content entity type and configuring a node as front page.
   */
  public function testArticleNodeOverride() {
    $this->adminUser = $this->drupalCreateUser([
      'administer system page override settings',
      'administer system page overrides',
    ]);

    $this->contentUser = $this->drupalCreateUser([
      'administer node as system page',
      'edit any article content',
    ]);

    $this->node = $this->createNode([
      'type' => 'article',
      'title' => 'Daddy Shark',
    ]);

    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains('Daddy Shark');

    $this->drupalGet('/node/' . $this->node->id());
    $this->assertSession()
      ->pageTextContains('Daddy Shark');

    $this->drupalLogin($this->contentUser);

    $this->drupalGet('/node/' . $this->node->id() . '/edit');

    $this->assertSession()
      ->fieldNotExists('edit-system-page-override');

    $this->drupalLogout();

    $this->drupalGet('/admin/config/system/system-page-override/settings');
    $this->assertSession()
      ->statusCodeEquals(403, "Anonymous user can't access the system page override configuration");

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('/admin/config/system/system-page-override/settings');
    $this->assertSession()
      ->statusCodeEquals(200, 'Admin user is able to navigate to the system page override configuration');

    $this->assertSession()->responseContains('Article');
    $this->assertSession()
      ->fieldExists('edit-front-article');
    $page = $this->getSession()->getPage();

    $page->checkField('edit-front-article');

    $this->assertSession()->buttonExists('edit-submit')->press();
    $page = $this->getSession()->getPage();
    $this->assertEquals(TRUE, $page->findField('edit-front-article')->isChecked(), 'The article content type checkbox is checked');

    $this->drupalLogout();
    $this->drupalLogin($this->contentUser);

    $this->drupalGet('/node/' . $this->node->id() . '/edit');

    $this->assertSession()
      ->pageTextContains('Systempage settings');

    $this->assertSession()
      ->fieldExists('edit-system-page-override-front-en');

    $page = $this->getSession()->getPage();
    $page->checkField('edit-system-page-override-front-en');

    $this->assertSession()->buttonExists('edit-submit')->press();

    $this->drupalGet('/node/' . $this->node->id() . '/edit');

    $page = $this->getSession()->getPage();
    $this->assertEquals(TRUE, $page->findField('edit-system-page-override-front-en')->isChecked(), 'The frontpage checkbox is checked');

    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Daddy Shark');
  }

}
