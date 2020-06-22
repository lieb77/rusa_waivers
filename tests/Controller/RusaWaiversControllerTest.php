<?php

namespace Drupal\rusa_waivers\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\smartwaiver\ClientInterface;

/**
 * Provides automated tests for the rusa_waivers module.
 */
class RusaWaiversControllerTest extends WebTestBase {

  /**
   * Drupal\smartwaiver\ClientInterface definition.
   *
   * @var \Drupal\smartwaiver\ClientInterface
   */
  protected $smartwaiverClient;


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "rusa_waivers RusaWaiversController's controller functionality",
      'description' => 'Test Unit for module rusa_waivers and controller RusaWaiversController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests rusa_waivers functionality.
   */
  public function testRusaWaiversController() {
    // Check that the basic functions of module rusa_waivers.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
