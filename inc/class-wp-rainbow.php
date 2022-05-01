<?php
/**
 * WP_Rainbow class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Elliptic\EC;
use kornrunner\Keccak;
use WP_User;

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
		add_action( 'init', [ self::$instance, 'action_init' ] );
		add_action( 'login_form', [ self::$instance, 'action_login_form' ] );
		add_action( 'login_head', [ self::$instance, 'action_login_head' ] );
		add_action( 'login_enqueue_scripts', [ self::$instance, 'action_login_enqueue_scripts' ] );
		add_action( 'rest_api_init', [ self::$instance, 'action_rest_api_init' ] );
		add_action( 'admin_menu', [ self::$instance, 'action_admin_menu' ] );
		add_action( 'admin_init', [ self::$instance, 'action_admin_init' ] );
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
	 * Provide filter for redirect URL. Uses admin URL if not set.
	 */
	public function get_redirect_url_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_redirect_url' => '' ] );

		/**
		 * Filter the post-login redirect URL for WP Rainbow users.
		 *
		 * @param string $default Default redirect URL.
		 */
		return apply_filters( 'wp_rainbow_redirect_url', $options['wp_rainbow_field_redirect_url'] );
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
	 * Register blocks on init.
	 */
	public function action_init() {
		$test_block_path         = '../build/login-block';
		$test_block_dependencies = include __DIR__ . '/../build/login-block.asset.php';
		wp_register_script(
			'login-block',
			plugins_url( $test_block_path . '.js', __FILE__ ),
			$test_block_dependencies['dependencies'],
			$test_block_dependencies['version'],
			false
		);
		wp_register_style(
			'login-block-css',
			plugins_url( $test_block_path . '.css', __FILE__ ),
			[],
			$test_block_dependencies['version']
		);
		$test_block_frontend_path         = '../build/login-block-frontend';
		$test_block_frontend_dependencies = include __DIR__ . '/../build/login-block-frontend.asset.php';
		wp_register_script(
			'login-block-frontend',
			plugins_url( $test_block_frontend_path . '.js', __FILE__ ),
			$test_block_frontend_dependencies['dependencies'],
			$test_block_frontend_dependencies['version'],
			true
		);
		wp_register_style(
			'login-block-frontend-css',
			plugins_url( $test_block_frontend_path . '.css', __FILE__ ),
			[ 'wp-block-button' ],
			$test_block_frontend_dependencies['version']
		);
		register_block_type(
			'wp-rainbow/login',
			[
				'api_version'   => 2,
				'editor_script' => 'login-block',
				'editor_style'  => 'login-block-css',
				'view_script'   => 'login-block-frontend',
				'style'         => 'login-block-frontend-css',
			]
		);
		wp_localize_script(
			'login-block',
			'wpRainbowData',
			[
				'ADMIN_URL'    => get_admin_url(),
				'INFURA_ID'    => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'    => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'    => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL' => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'   => get_bloginfo( 'name' ),
			]
		);
		wp_localize_script(
			'login-block-frontend',
			'wpRainbowData',
			[
				'ADMIN_URL'    => get_admin_url(),
				'INFURA_ID'    => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'    => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'    => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL' => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'   => get_bloginfo( 'name' ),
				'LOGOUT_URL'   => wp_logout_url(),
			]
		);
	}

	/**
	 * Add menu page for plugin.
	 */
	public function action_admin_menu() {
		add_menu_page(
			'WP Rainbow Settings',
			'WP Rainbow',
			'manage_options',
			'wp_rainbow',
			[ self::$instance, 'wp_rainbow_settings_page_html' ],
			'dashicons-money'
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

		add_settings_field(
			'wp_rainbow_field_override_users_can_register',
			__( 'Always Allow Registration', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_override_users_can_register_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_override_users_can_register',
			],
		);

		add_settings_field(
			'wp_rainbow_field_disable_passwords_for_wp_users',
			__( 'Disable Passwords', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_disable_passwords_for_wp_users_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_disable_passwords_for_wp_users',
			],
		);

		add_settings_field(
			'wp_rainbow_field_redirect_url',
			__( 'Redirect URL', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_redirect_url_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_redirect_url',
			],
		);
	}

	/**
	 * Print field for Disable Passwords for WP Rainbow Users option.
	 */
	public function wp_rainbow_disable_passwords_for_wp_users_callback() {
		$options           = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_disable_passwords_for_wp_users' => false ] );
		$disable_passwords = ! empty( $options['wp_rainbow_field_disable_passwords_for_wp_users'] );
		?>
		<input
			id='wp_rainbow_field_disable_passwords_for_wp_users'
			name='wp_rainbow_options[wp_rainbow_field_disable_passwords_for_wp_users]'
			type='checkbox'
			<?php
			if ( $disable_passwords ) {
				echo 'checked';
			}
			?>
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If enabled, non-admin WP Rainbow users will be passwordless.', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for Always Allow Registration option.
	 */
	public function wp_rainbow_override_users_can_register_callback() {
		$options            = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_override_users_can_register' => false ] );
		$users_can_register = ! empty( $options['wp_rainbow_field_override_users_can_register'] );
		?>
		<input
			id='wp_rainbow_field_override_users_can_register'
			name='wp_rainbow_options[wp_rainbow_field_override_users_can_register]'
			type='checkbox'
			<?php
			if ( $users_can_register ) {
				echo 'checked';
			}
			?>
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If enabled, this setting will override the General Settings membership option.', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for Redirect URL option.
	 */
	public function wp_rainbow_redirect_url_callback() {
		$options      = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_redirect_url' => '' ] );
		$redirect_url = ! empty( $options['wp_rainbow_field_redirect_url'] ) ? $options['wp_rainbow_field_redirect_url'] : '';
		?>
		<input
			id='wp_rainbow_field_redirect_url'
			name='wp_rainbow_options[wp_rainbow_field_redirect_url]'
			size='40'
			type='url'
			value='<?php echo esc_url( $redirect_url ); ?>'
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If set, users will be redirected here on login instead of the admin. (Block redirect configuration will take precedent if set.)', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for Infura ID option.
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
		$script_path         = '../build/login';
		$script_dependencies = include __DIR__ . '/../build/login.asset.php';
		wp_register_script(
			'wp-rainbow-login',
			plugins_url( $script_path . '.js', __FILE__ ),
			$script_dependencies['dependencies'],
			$script_dependencies['version'],
			true
		);
		wp_enqueue_script( 'wp-rainbow-login' );
		wp_register_style(
			'wp-rainbow-login-css',
			plugins_url( $script_path . '.css', __FILE__ ),
			[],
			$script_dependencies['version']
		);
		wp_enqueue_style( 'wp-rainbow-login-css' );
		wp_localize_script(
			'wp-rainbow-login',
			'wpRainbowData',
			[
				'ADMIN_URL'    => get_admin_url(),
				'INFURA_ID'    => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'    => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'    => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL' => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'   => get_bloginfo( 'name' ),
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

			/* Compensate for flex styling when Jetpack SSO enabled. */
			.jetpack-sso-clear {
				width: 100%;
			}

			.wp-rainbow-sso-or {
				margin: 16px 0;
				position: relative;
				text-align: center;
			}

			.wp-rainbow-sso-or::before {
				background: #dcdcde;
				content: '';
				height: 1px;
				position: absolute;
				left: 0;
				top: 50%;
				width: 100%;
			}

			.wp-rainbow-sso-or span {
				background: #fff;
				color: #777;
				position: relative;
				padding: 0 8px;
				text-transform: uppercase;
			}

			#wp-rainbow-button {
				order: -2;
				width: 100%;
			}

			#wp-rainbow-button div[role="button"] {
				text-align: center;
				width: 100%;
			}

			#loginform.logged-in > :not(div#wp-rainbow-button) {
				display: none;
			}

			#loginform.logged-in .wp-rainbow-sso-or {
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
