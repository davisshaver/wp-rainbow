<?php
/**
 * Uninstall logic for WP Rainbow
 *
 * @package WP_Rainbow
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

$wp_rainbow_option_name = 'wp_rainbow_options';

delete_option( $wp_rainbow_option_name );

delete_site_option( $wp_rainbow_option_name );
