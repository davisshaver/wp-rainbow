<?php
/**
 * Environment sanity checks.
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow\Tests;

use PHPUnit\Framework\TestCase;
use WPRainbow\Plugin;

/**
 * These tests prove test setup works.
 *
 * They are useful for debugging.
 */
class Environment_Test extends TestCase {

	/**
	 * Most basic test possible.
	 */
	public function testSomething() {
		$this->assertIsBool( true );
	}
}
