<?php
/**
 * WP_Rainbow class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

/**
 * WP Rainbow
 */
class WP_Rainbow {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static $instance;

	/**
	 * Get the instance of this singleton
	 *
	 * @return static
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}
