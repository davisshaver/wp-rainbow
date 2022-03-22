<?php
/**
 * WP_Rainbow class file
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
 * WP Rainbow
 */
class WP_Rainbow {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow $instance;

	const WP_RAINBOW_NONCE_KEY = 'wp-rainbow-login';

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow {
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
		add_action( 'login_form', [ self::$instance, 'login_form' ] );
		add_action( 'login_head', [ self::$instance, 'login_head' ] );
		add_action( 'login_enqueue_scripts', [ self::$instance, 'login_enqueue_scripts' ] );
		add_action( 'rest_api_init', [ self::$instance, 'register_rest_routes' ] );
	}

	/**
	 * Adds rest routes for login page.
	 */
	public function register_rest_routes() {
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
	 * @param WP_REST_Request $request REST request data.
	 *
	 * @return WP_REST_Response REST API response.
	 */
	public function nonce_callback( WP_REST_Request $request ): WP_REST_Response {
		add_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life' ] );
		$nonce = wp_create_nonce( 'wp-rainbow-nonce' );
		remove_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life' ] );

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
		$message = $request->get_param( 'message' );
		$nonce   = $request->get_param( 'nonce' );
		add_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life' ] );
		$nonce_verified = wp_verify_nonce( $nonce, 'wp-rainbow-nonce' );
		remove_filter( 'nonce_life', [ self::$instance, 'filter_nonce_life' ] );
		if ( ! $nonce_verified ) {
			return new WP_REST_Response( 'error', 500 );
		}
		$address      = $request->get_param( 'address' );
		$signature    = $request->get_param( 'signature' );
		$display_name = $request->get_param( 'displayName' );

		$signature_verified = $this->verify_signature( $message, $signature, $address );
		if ( ! $signature_verified ) {
			return new WP_REST_Response( 'error', 500 );
		}

		$user = get_user_by( 'login', $address );
		if ( ! $user ) {
			$password = wp_generate_password();
			$user_id  = wp_create_user( $address, $password );
			$user     = get_user_by( 'ID', $user_id );
			wp_update_user(
				[
					'ID'           => $user->ID,
					'role'         => 'author',
					'display_name' => sanitize_text_field( $display_name ),
				]
			);
		}
		wp_set_auth_cookie( $user->ID, true );

		return new WP_REST_Response( true );
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
			// the nth letter should be uppercase if the nth digit of casemap is 1.
			if ( intval( $address_hash_array[ $i ], 16 ) > 7 ) {
				$ret .= strtoupper( $address_array[ $i ] );
			} elseif ( intval( $address_hash_array[ $i ], 16 ) <= 7 ) {
				$ret .= strtolower( $address_array[ $i ] );
			}
		}

		return '0x' . $ret;

	}

	/**
	 * Adds supplemental scripts to login page.
	 */
	public function login_enqueue_scripts() {
		wp_enqueue_script(
			'wp-rainbow-login',
			plugins_url( '/build/js/index.js', dirname( __FILE__, 1 ) ),
			[ 'react', 'react-dom', 'wp-i18n' ],
			WP_RAINBOW_ASSETS_VERSION,
			true
		);
		wp_localize_script(
			'wp-rainbow-login',
			'wpRainbowData',
			[
				'ADMIN_URL'  => get_admin_url(),
				'INFURA_ID'  => 'd3f47e029c8f4b109d57de3ee40bcf47',
				'LOGIN_API'  => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'  => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'SITE_TITLE' => get_bloginfo( 'name' ),
			]
		);
	}

	/**
	 * Filter nonce lifespan.
	 *
	 * @return int Nonce lifespan.
	 */
	public function filter_nonce_life(): int {
		return 6000; // 10 minutes
	}

	/**
	 * Adds supplemental styles to the login page header.
	 */
	public function login_head() {
		?>
		<style>
			.js.login form {
				display: flex;
				flex-wrap: wrap;
				justify-content: space-between;
			}

			.no-js .wp-rainbow {
				display: none !important;
			}

			.wp-rainbow {
				width: 100%;
			}

			.wp-rainbow.help-text {
				font-size: 12px;
				font-style: italic;
				margin-bottom: 4px !important;
				order: -1;
				text-align: center;
			}

			#wp-rainbow-button {
				order: -2;
				width: 100%;
			}

			#loginform.logged-in > :not(div#wp-rainbow-button) {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Adds the Log In with Ethereum button.
	 */
	public function login_form() {
		?>
		<div id="wp-rainbow-button"></div>
		<?php
	}
}
