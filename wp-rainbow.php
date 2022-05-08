<?php
/**
 * WP Rainbow
 *
 * @package           WP_Rainbow
 * @author            Davis Shaver
 * @license:          GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WP Rainbow
 * Plugin URI:        https://wp-rainbow.davisshaver.com/
 * Description:       WP Rainbow is a plugin that allows WordPress users to log in with Ethereum using the Sign-In With Ethereum standard, powered by RainbowKit.
 * Version:           0.0.6
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * Text Domain:       wp-rainbow
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/davisshaver/wp-rainbow
 */

namespace WP_Rainbow;

/**
 * WP Rainbow version number.
 *
 * @var string
 */
define( 'WP_RAINBOW_ASSETS_VERSION', '0.1.1' );

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
require_once __DIR__ . '/inc/class-wp-rainbow-login-functionality.php';
require_once __DIR__ . '/inc/class-wp-rainbow-login-styling.php';
require_once __DIR__ . '/inc/class-wp-rainbow-passwords.php';
require_once __DIR__ . '/inc/class-wp-rainbow-settings.php';

WP_Rainbow::instance();
WP_Rainbow_Login_Functionality::instance();
WP_Rainbow_Login_Styling::instance();
WP_Rainbow_Passwords::instance();
WP_Rainbow_Settings::instance();
