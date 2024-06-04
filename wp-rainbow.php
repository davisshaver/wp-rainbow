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
 * Version:           0.5.4
 * Author:            Davis Shaver
 * Author URI:        https://davisshaver.com/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-rainbow
 * Tags:              WordPress, web3, SIWE, Ethereum, RainbowKit, Sign-In With Ethereum
 * Contributors:      davisshaver
 */

namespace WP_Rainbow;

use WP_Rainbow_Plugins\WP_Rainbow_Plugins_ERC_1155_Roles;

/**
 * WP Rainbow version number
 */
define( 'WP_RAINBOW_ASSETS_VERSION', '0.5.4' );

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

// Require the helper functions.
require_once __DIR__ . '/inc/functions.php';

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

// Add-on functionality.
require_once __DIR__ . '/plugins/class-wp-rainbow-plugins-erc-1155-roles.php';
WP_Rainbow_Plugins_ERC_1155_Roles::instance();
