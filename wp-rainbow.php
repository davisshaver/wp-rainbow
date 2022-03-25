<?php
/**
 * Plugin Name: WP Rainbow
 * Version: 0.0.9
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

/**
 * WP Rainbow version number.
 *
 * @var string
 */
define( 'WP_RAINBOW_ASSETS_VERSION', '0.0.1' );

// Include the autoloader.
add_action(
	'plugins_loaded',
	function () {
		if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
			include __DIR__ . '/vendor/autoload.php';
		}
	},
	1
);

// Require the main class.
require_once __DIR__ . '/inc/class-wp-rainbow.php';

WP_Rainbow::instance();
