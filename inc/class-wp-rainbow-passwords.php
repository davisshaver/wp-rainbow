<?php
/**
 * WP_Rainbow_Passwords class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

use WP_Error;
use WP_User;

/**
 * WP Rainbow Password Customizations
 */
class WP_Rainbow_Passwords {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow_Passwords $instance;

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow_Passwords {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
			static::$instance->setup();
		}

		return static::$instance;
	}

	/**
	 * Setup instance.
	 */
	protected function setup() {
		add_filter( 'show_password_fields', [ self::$instance, 'filter_show_password_fields' ], 10, 2 );
		add_filter( 'allow_password_reset', [ self::$instance, 'filter_allow_password_reset' ], 10, 2 );
		add_filter( 'wp_authenticate_user', [ self::$instance, 'filter_wp_authenticate_user' ] );
	}

	// FILTERS.

	/**
	 * Maybe disallow password login for WP Rainbow user.
	 *
	 * @param WP_User $user User logging in.
	 *
	 * @return WP_User|WP_Error User if allowed, error if not.
	 */
	public function filter_wp_authenticate_user( $user ) {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_disable_passwords_for_wp_users' => false ] );
		if ( empty( $options['wp_rainbow_field_disable_passwords_for_wp_users'] ) ) {
			return $user;
		}

		if ( $user->has_cap( 'manage_options' ) ) {
			return $user;
		}

		$is_wp_rainbow_user = get_user_meta( $user->ID, 'wp_rainbow_user', true );
		if ( ! $is_wp_rainbow_user ) {
			return $user;
		}

		return new WP_Error(
			'wp_rainbow_password_login_disabled',
			esc_html__( 'Password login is not allowed for this user', 'wp-rainbow' )
		);
	}

	/**
	 * Maybe disallow password reset for WP Rainbow users.
	 *
	 * @param bool $allow_password_reset Default value for allow password reset.
	 * @param int  $user_id ID of current user.
	 *
	 * @return bool Filtered value for allow password reset.
	 */
	public function filter_allow_password_reset( $allow_password_reset, $user_id ) {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_disable_passwords_for_wp_users' => false ] );
		if ( empty( $options['wp_rainbow_field_disable_passwords_for_wp_users'] ) ) {
			return $allow_password_reset;
		}

		$user = get_user_by( 'id', $user_id );
		if ( $user->has_cap( 'manage_options' ) ) {
			return $allow_password_reset;
		}

		$is_wp_rainbow_user = get_user_meta( $user->ID, 'wp_rainbow_user', true );
		if ( ! $is_wp_rainbow_user ) {
			return $allow_password_reset;
		}

		return false;
	}

	/**
	 * Maybe hide password fields for WP Rainbow users.
	 *
	 * @param bool    $show_password_fields Default value for show password fields.
	 * @param WP_User $user Current user.
	 *
	 * @return bool Filtered value for show password fields.
	 */
	public function filter_show_password_fields( $show_password_fields, $user ) {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_disable_passwords_for_wp_users' => false ] );
		if ( empty( $options['wp_rainbow_field_disable_passwords_for_wp_users'] ) ) {
			return $show_password_fields;
		}

		if ( $user->has_cap( 'manage_options' ) ) {
			return $show_password_fields;
		}

		$is_wp_rainbow_user = get_user_meta( $user->ID, 'wp_rainbow_user', true );
		if ( ! $is_wp_rainbow_user ) {
			return $show_password_fields;
		}

		return false;
	}
}
