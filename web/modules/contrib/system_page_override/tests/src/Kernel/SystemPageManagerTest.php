<?php

namespace Drupal\Tests\system_page_override\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\system_page_override\SystemPageManager
 * @group system_page_override
 */
class SystemPageManagerTest extends KernelTestBase {

  public static $modules = ['node', 'system_page_override'];

  /**
   * The messenger under test.
   *
   * @var \Drupal\system_page_override\SystemPageManager
   */
  protected $systemPageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->systemPageManager = $this->container->get('system_page_override.manager');
  }

  /**
   * Tests revert.
   *
   * @covers ::revert
   */
  public function testRevert() {
    $this->assertNull($this->systemPageManager->getOverride('front', 'en'), "There is no override");
    $this->assertFalse($this->systemPageManager->isOverridden('front', 'en'), "The front page is not overridden");

    $this->systemPageManager->override('front', 'en', '/node/1');

    $this->assertEquals('/node/1', $this->systemPageManager->getOverride('front', 'en'), "There is no override");
    $this->assertTrue($this->systemPageManager->isOverridden('front', 'en'), "The front page is not overridden");

    $this->systemPageManager->revert('front', 'en');

    $this->assertNull($this->systemPageManager->getOverride('front', 'en'), "The override was reverted");
  }

}
