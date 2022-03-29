<?php
/**
 * WP Rainbow
 *
 * @package           WP_Rainbow
 * @author            Davis Shaver
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name:       WP Rainbow
 * Plugin URI:        https://wp-rainbow.davisshaver.com/
 * Description:       WP Rainbow is a plugin that allows WordPress users to log in with Ethereum using the Sign-In With Ethereum standard, powered by RainbowKit.
 * Version:           0.0.3
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * Text Domain:       wp-rainbow
 * Update URI:        https://github.com/davisshaver/wp-rainbow
 */

namespace WP_Rainbow;

/**
 * WP Rainbow version number.
 *
 * @var string
 */
define( 'WP_RAINBOW_ASSETS_VERSION', '0.0.3' );

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
