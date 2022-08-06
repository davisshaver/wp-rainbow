<?php
/**
 * RainbowKit Login
 *
 * @package           WP_Rainbow
 * @author            Davis Shaver
 * @license:          GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       RainbowKit Login (Web3 Integration for Sign-In With Ethereum)
 * Plugin URI:        https://wp-rainbow.davisshaver.com/
 * Description:       RainbowKit Login allows WordPress users to log in with Ethereum using the Sign-In With Ethereum standard, powered by RainbowKit.
 * Version:           0.2.9
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-rainbow
 * Tags:              WordPress, web3, SIWE, Ethereum, RainbowKit, Sign-In With Ethereum
 * Contributors:      davisshaver
 */

namespace WP_Rainbow;

/**
 * WP Rainbow version number.
 *
 * @var string
 */
define( 'WP_RAINBOW_ASSETS_VERSION', '0.2.8' );

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
