<?php
/**
 * WP_Rainbow_Login_Functionality class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

use Exception;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Elliptic\EC;
use kornrunner\Keccak;

/**
 * WP Rainbow Login Functionality
 */
class WP_Rainbow_Login_Functionality {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow_Login_Functionality $instance;

	const WP_RAINBOW_NONCE_KEY = 'wp-rainbow-nonce';

	const WP_RAINBOW_REQUIRED_KEYS = [
		'address',
		'chainId',
		'domain',
		'issuedAt',
		'nonce',
		'statement',
		'uri',
		'version',
	];

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow_Login_Functionality {
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
		add_action( 'rest_api_init', [ self::$instance, 'action_rest_api_init' ] );
	}

	// FILTERS.

	/**
	 * Filter nonce lifespan with filtered valued. Defaults to 10 mins.
	 *
	 * @return int Nonce lifespan.
	 */
	public function filter_nonce_life_filtered(): int {
		/**
		 * Filter the nonce lifespan for WP Rainbow login.
		 *
		 * @param int $default Default lifespan (ten minutes).
		 */
		return apply_filters( 'wp_rainbow_nonce_life', 6000 );
	}

	/**
	 * Provide filter for address roles. Defaults to subscriber.
	 *
	 * @param string $address Address for user.
	 *
	 * @return mixed|void Filtered role for a given address.
	 */
	public function get_role_for_address_filtered( string $address ) {
		/**
		 * Filter the default role for WP Rainbow users.
		 *
		 * @param string $default Default role for new users.
		 * @param string $address Address of user being added.
		 */
		return apply_filters( 'wp_rainbow_role_for_address', 'subscriber', $address );
	}

	// API ROUTES.

	/**
	 * Adds rest routes for login page.
	 */
	public function action_rest_api_init() {
		register_rest_route(
			'wp-rainbow/v1',
			'/nonce',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'nonce_callback' ],
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			'wp-rainbow/v1',
			'/login',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'login_callback' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	// API CALLBACKS.

	/**
	 * Verify message signature for specific address.
	 *
	 * @param string $message Message string.
	 * @param string $signature Signed messaged.
	 * @param string $address Alleged address.
	 *
	 * @return bool Whether address matches message.
	 * @throws Exception Throws if unsupported Keccak Hash output size.
	 */
	public function verify_signature( string $message, string $signature, string $address ): bool {
		$msglen = strlen( $message );
		$hash   = Keccak::hash( "\x19Ethereum Signed Message:\n{$msglen}{$message}", 256 );
		$sign   = [
			'r' => substr( $signature, 2, 64 ),
			's' => substr( $signature, 66, 64 ),
		];
		$recid  = ord( hex2bin( substr( $signature, 130, 2 ) ) ) - 27;
		if ( ( $recid & 1 ) !== $recid ) {
			return false;
		}
		$ec               = new EC( 'secp256k1' );
		$pubkey           = $ec->recoverPubKey( $hash, $sign, $recid );
		$inferred_address = $this->pub_key_to_address( $pubkey );

		return $address === $inferred_address;
	}

	/**
	 * Generates nonce for Log In with Ethereum request.
	 *
	 * @return WP_REST_Response REST API response.
	 */
	public function nonce_callback(): WP_REST_Response {
		add_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life_filtered' ] );
		$nonce = wp_create_nonce( self::WP_RAINBOW_NONCE_KEY );
		remove_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life_filtered' ] );

		return new WP_REST_Response( $nonce );
	}

	/**
	 * Validates Log In with Ethereum request.
	 *
	 * @param WP_REST_Request $request REST request data.
	 *
	 * @return WP_REST_Response REST API response.
	 * @throws Exception Throws if unsupported Keccak Hash output size.
	 */
	public function login_callback( WP_REST_Request $request ): WP_REST_Response {
		// Make sure that nonce passes WordPress validation.
		$nonce = $request->get_param( 'nonce' );
		add_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life_filtered' ] );
		$nonce_verified = wp_verify_nonce( $nonce, self::WP_RAINBOW_NONCE_KEY );
		remove_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life_filtered' ] );
		if ( ! $nonce_verified ) {
			return new WP_REST_Response( __( 'Nonce verification failed.', 'wp-rainbow' ), 500 );
		}

		// Make sure that SIWE payload contains all required keys.
		$address      = $request->get_param( 'address' );
		$signature    = $request->get_param( 'signature' );
		$display_name = $request->get_param( 'displayName' );
		$siwe_payload = $request->get_param( 'siwePayload' );
		if ( empty( $address ) || empty( $signature ) ) {
			return new WP_REST_Response( __( 'Malformed authentication request.', 'wp-rainbow' ), 500 );
		}
		foreach ( self::WP_RAINBOW_REQUIRED_KEYS as $key ) {
			if ( empty( $siwe_payload[ $key ] ) ) {
				return new WP_REST_Response( __( 'Incomplete authentication request.', 'wp-rainbow' ), 500 );
			}
		}

		// Make sure that nonce in message matches top-level nonce.
		if ( $siwe_payload['nonce'] !== $nonce ) {
			return new WP_REST_Response( __( 'Nonce validation failed.', 'wp-rainbow' ), 500 );
		}

		// Make sure that signature verifies correctly.
		$generated_msg      = $this->generate_message( $siwe_payload );
		$signature_verified = $this->verify_signature( $generated_msg, $signature, $address );
		if ( ! $signature_verified ) {
			/**
			 * Fires when a WP Rainbow user's login attempt doesn't pass validation.
			 *
			 * @param string $generated_msg Generated SIWE message.
			 * @param string $signature Signature passed in login request.
			 * @param string $address Address of user attempting login.
			 */
			do_action( 'wp_rainbow_validation_failed', $generated_msg, $signature, $address );

			return new WP_REST_Response( __( 'Message validation failed.', 'wp-rainbow' ), 500 );
		}

		// Lookup or generate user and then sign them in.
		$user                   = get_user_by( 'login', $address );
		$sanitized_display_name = sanitize_text_field( $display_name );
		if ( ! $user ) {
			// If there's not a user already, double check registration settings.
			$users_can_register = get_option( 'users_can_register', false );
			if ( empty( $users_can_register ) ) {
				$wp_rainbow_options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_override_users_can_register' => false ] );
				if ( empty( $wp_rainbow_options['wp_rainbow_field_override_users_can_register'] ) ) {
					return new WP_REST_Response( __( 'User registration is disabled.', 'wp-rainbow' ), 500 );
				}
			}

			$password = wp_generate_password();
			$user_id  = wp_create_user( $address, $password );
			$user     = get_user_by( 'ID', $user_id );
			wp_update_user(
				[
					'ID'           => $user->ID,
					'role'         => $this->get_role_for_address_filtered( $address ),
					'display_name' => $sanitized_display_name,
				]
			);

			update_user_meta( $user->ID, 'wp_rainbow_user', true );

			/**
			 * Fires when a new user is created via WP Rainbow.
			 *
			 * @param int $user_id ID of new user account.
			 * @param string $address Address of new user.
			 * @param string $sanitized_display_name Display name of new user (either ENS or address).
			 */
			do_action( 'wp_rainbow_user_created', $user->ID, $address, $sanitized_display_name );

		} elseif ( $user->display_name !== $sanitized_display_name ) {
			wp_update_user(
				[
					'ID'           => $user->ID,
					'display_name' => $sanitized_display_name,
				]
			);

			/**
			 * Fires when a WP Rainbow user's display name is updated.
			 *
			 * @param int $user_id ID of new user account.
			 * @param string $address Address of new user.
			 * @param string $sanitized_display_name Display name of new user (either ENS or address).
			 */
			do_action( 'wp_rainbow_user_updated', $user->ID, $address, $sanitized_display_name );
		}
		wp_set_auth_cookie( $user->ID, true );

		/**
		 * Fires when a WP Rainbow user has logged in.
		 *
		 * @param int $user_id ID of new user account.
		 * @param string $address Address of new user.
		 * @param string $sanitized_display_name Display name of new user (either ENS or address).
		 */
		do_action( 'wp_rainbow_user_login', $user->ID, $address, $sanitized_display_name );

		return new WP_REST_Response( true );
	}

	/**
	 * Transform inferred public key to address.
	 *
	 * @param object $pubkey Inferred public key.
	 *
	 * @return string Inferred address.
	 * @throws Exception Throws if unsupported Keccak Hash output size.
	 */
	public function pub_key_to_address( object $pubkey ): string {
		$address = '0x' . substr( Keccak::hash( substr( hex2bin( $pubkey->encode( 'hex' ) ), 1 ), 256 ), 24 );

		return $this::get_checksum_address( $address );
	}

	/**
	 * Generate a SIWE message from provided payload.
	 *
	 * @param array $siwe_payload SIWE Payload.
	 *
	 * @return string Generated message in SIWE template.
	 */
	public function generate_message( array $siwe_payload ): string {
		return "{$siwe_payload['domain']} wants you to sign in with your Ethereum account:
{$siwe_payload['address']}

{$siwe_payload['statement']}

URI: {$siwe_payload['uri']}
Version: {$siwe_payload['version']}
Chain ID: {$siwe_payload['chainId']}
Nonce: {$siwe_payload['nonce']}
Issued At: {$siwe_payload['issuedAt']}";
	}

	/**
	 * Get an address formatted for checksum validation.
	 *
	 * @param string $address Unformatted address.
	 *
	 * @return string Formatted address.
	 * @throws Exception Throws if unsupported Keccak Hash output size.
	 */
	public static function get_checksum_address( string $address ): string {
		$address            = substr( $address, 2 );
		$address_hash       = Keccak::hash( strtolower( $address ), 256 );
		$address_array      = str_split( $address );
		$address_hash_array = str_split( $address_hash );

		$ret = '';
		for ( $i = 0; $i < 40; $i ++ ) {
			// The nth letter should be uppercase if the nth digit of casemap is 1.
			if ( intval( $address_hash_array[ $i ], 16 ) > 7 ) {
				$ret .= strtoupper( $address_array[ $i ] );
			} elseif ( intval( $address_hash_array[ $i ], 16 ) <= 7 ) {
				$ret .= strtolower( $address_array[ $i ] );
			}
		}

		return '0x' . $ret;
	}
}
