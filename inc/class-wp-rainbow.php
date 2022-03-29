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
		add_action( 'login_form', [ self::$instance, 'action_login_form' ] );
		add_action( 'login_head', [ self::$instance, 'action_login_head' ] );
		add_action( 'login_enqueue_scripts', [ self::$instance, 'action_login_enqueue_scripts' ] );
		add_action( 'rest_api_init', [ self::$instance, 'action_rest_api_init' ] );
		add_action( 'admin_menu', [ self::$instance, 'action_admin_menu' ] );
		add_action( 'admin_init', [ self::$instance, 'action_admin_init' ] );
	}

	// FILTERED VALUES.

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

	/**
	 * Provide filter for Infura ID. Defaults to settings page value.
	 *
	 * @return mixed|void Filtered Infura ID.
	 */
	public function get_infura_id_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_infura_id' => '' ] );

		/**
		 * Filter the Infura ID used for WP Rainbow integration.
		 *
		 * @param string $default Infura ID as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_infura_id', $options['wp_rainbow_field_infura_id'] );
	}

	// ADMIN PAGE.

	/**
	 * Add menu page for plugin.
	 */
	public function action_admin_menu() {
		add_menu_page(
			'WP Rainbow Settings',
			'WP Rainbow',
			'manage_options',
			'wp_rainbow',
			[ self::$instance, 'wp_rainbow_settings_page_html' ]
		);
	}

	/**
	 * Register settings for plugin.
	 */
	public function action_admin_init() {
		register_setting( 'wp_rainbow', 'wp_rainbow_options' );

		add_settings_section(
			'wp_rainbow_connection_options',
			__( 'Connection Options', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_connection_options_callback' ],
			'wp_rainbow'
		);

		add_settings_field(
			'wp_rainbow_field_infura_id',
			__( 'Infura ID', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_infura_id_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_infura_id',
			],
		);
	}

	/**
	 * Print field for Infura ID.
	 */
	public function wp_rainbow_infura_id_callback() {
		$options   = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_infura_id' => '' ] );
		$infura_id = ! empty( $options['wp_rainbow_field_infura_id'] ) ? $options['wp_rainbow_field_infura_id'] : '';
		?>
		<input
			id='wp_rainbow_field_infura_id'
			name='wp_rainbow_options[wp_rainbow_field_infura_id]'
			size='40'
			type='text'
			value='<?php echo esc_textarea( $infura_id ); ?>'
		/>
		<?php
	}

	/**
	 * Print header for connection options.
	 *
	 * @param array $args Settings section attributes.
	 */
	public function wp_rainbow_connection_options_callback( array $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php esc_html_e( 'Customize your connection settings below.', 'wp-rainbow' ); ?>
		</p>
		<?php
	}

	/**
	 * Print WP Rainbow settings page.
	 */
	public function wp_rainbow_settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// phpcs:ignore
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'wp_rainbow_messages', 'wp_rainbow_message', __( 'Settings Saved', 'wp-rainbow' ), 'updated' );
		}
		settings_errors( 'wp_rainbow_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form
				action="options.php"
				method="post"
			>
				<?php
				settings_fields( 'wp_rainbow' );
				do_settings_sections( 'wp_rainbow' );
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	// FRONTEND INTEGRATION.

	/**
	 * Adds supplemental scripts to login page.
	 */
	public function action_login_enqueue_scripts() {
		wp_enqueue_script(
			'wp-rainbow-login',
			plugins_url( '/build/js/index.js', dirname( __FILE__ ) ),
			[ 'react', 'react-dom', 'wp-i18n' ],
			WP_RAINBOW_ASSETS_VERSION,
			true
		);
		wp_localize_script(
			'wp-rainbow-login',
			'wpRainbowData',
			[
				'ADMIN_URL'  => get_admin_url(),
				'INFURA_ID'  => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'  => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'  => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'SITE_TITLE' => get_bloginfo( 'name' ),
			]
		);
	}

	/**
	 * Adds supplemental styles to the login page header.
	 */
	public function action_login_head() {
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

			#wp-rainbow-button {
				order: -2;
				width: 100%;
			}

			#loginform.logged-in > :not(div#wp-rainbow-button) {
				display: none;
			}

			#loginform.logged-in .wp-rainbow.help-text {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Adds the Log In with Ethereum button.
	 */
	public function action_login_form() {
		?>
		<div id="wp-rainbow-button"></div>
		<?php
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
			return new WP_REST_Response( 'error', 500 );
		}

		// Make sure that SIWE payload contains all required keys.
		$address      = $request->get_param( 'address' );
		$signature    = $request->get_param( 'signature' );
		$display_name = $request->get_param( 'displayName' );
		$siwe_payload = $request->get_param( 'siwePayload' );
		if ( empty( $address ) || empty( $signature ) ) {
			return new WP_REST_Response( 'error', 500 );
		}
		foreach ( self::WP_RAINBOW_REQUIRED_KEYS as $key ) {
			if ( empty( $siwe_payload[ $key ] ) ) {
				return new WP_REST_Response( 'error', 500 );
			}
		}

		// Make sure that nonce in message matches top-level nonce.
		if ( $siwe_payload['nonce'] !== $nonce ) {
			return new WP_REST_Response( 'error', 500 );
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

			return new WP_REST_Response( 'error', 500 );
		}

		// Lookup or generate user and then sign them in.
		$user                   = get_user_by( 'login', $address );
		$sanitized_display_name = sanitize_text_field( $display_name );
		if ( ! $user ) {
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
