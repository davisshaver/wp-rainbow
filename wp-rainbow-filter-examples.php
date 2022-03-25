<?php
/**
 * Plugin Name: WP Rainbow Filter Examples
 * Version: 0.0.1
 *
 * @package WP_Rainbow_Filter_Examples
 */

// Filter nonce lifespan to 5 minutes.
add_filter(
	'wp_rainbow_nonce_life',
	function () {
		return 300;
	} 
);

// Use contributor role for new accounts.
add_filter(
	'wp_rainbow_role_for_address',
	function () {
		return 'contributor';
	}
);

// Use editor role for a specific new account.
add_filter(
	'wp_rainbow_role_for_address',
	function ( $default_role, $address ) {
		if ( '0xaf045Cb0dBC1225948482e4692Ec9dC7Bb3cD48b' === $address ) {
			return 'editor';
		}
		return $default_role;
	},
	99,
	2
);

// Set an Infura ID without using settings.
add_filter(
	'wp_rainbow_infura_id',
	function () {
		return 'INFURA_ID_HERE';
	}
);
