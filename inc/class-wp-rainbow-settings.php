<?php
/**
 * WP_Rainbow_Settings class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

use WP_REST_Response;
use WP_REST_Server;
use WP_Session_Tokens;

/**
 * WP Rainbow Settings
 */
class WP_Rainbow_Settings {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow_Settings $instance;

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow_Settings {
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
		add_action( 'admin_menu', [ self::$instance, 'action_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::$instance, 'action_admin_enqueue_scripts' ], 10, 1 );
		add_action( 'rest_api_init', [ self::$instance, 'action_rest_api_init' ] );
	}

	/**
	 * Adds rest routes for settings page.
	 */
	public function action_rest_api_init() {
		register_rest_route(
			'wp-rainbow/v1',
			'/settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'settings_callback_get' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
		register_rest_route(
			'wp-rainbow/v1',
			'/settings',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'settings_callback_edit' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'wp_rainbow_field_cool_mode'          => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether RainbowKit Cool Mode is enabled',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_rainbowkit_theme'   => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'What base theme is used by RainbowKit',
						'enum'        => [
							'lightTheme',
							'darkTheme',
							'midnightTheme',
						],
					],
					'wp_rainbow_field_compact_modal'      => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether RainbowKit should use the compact modal',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_disable_overwriting_user_meta' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether overwriting user meta is disabled',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_disable_passwords_for_wp_users' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether passwords are disabled for non-admins',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_override_users_can_register' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether users can register',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_set_user_roles'     => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether user roles should be set on account creation',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_disable_user_role_updates_on_login' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Whether user role updates should be prevented on subsequent logins',
						'enum'        => [
							'on',
							'off',
						],
					],
					'wp_rainbow_field_default_user_role'  => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'Default RainbowKit Login user role',
					],
					'wp_rainbow_field_infura_id'          => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'Infura ID field',
					],
					'wp_rainbow_field_provider'           => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'RPC provider to use for RainbowKit login',
						'enum'        => [
							'infura',
							'other',
						],
					],
					'wp_rainbow_field_infura_network'     => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'Infura network',
						'enum'        => [
							'mainnet',
							'goerli',
							'sepolia',
							'polygon',
							'polygonMumbai',
							'optimism',
							'optimismGoerli',
							'optimismSepolia',
							'arbitrum',
							'arbitrumGoerli',
							'arbitrumSepolia',
							'base',
							'baseSepolia',
							'zora',
							'zoraSepolia',
						],
					],
					'wp_rainbow_field_rpc_url'            => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'RPC URL',
					],
					'wp_rainbow_field_rpc_url_mainnet'    => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'RPC URL',
					],
					'wp_rainbow_field_walletconnect_project_id' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'WalletConnect Project ID',
					],
					'wp_rainbow_field_redirect_url'       => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Default redirect URL',
					],
					'wp_rainbow_field_required_token'     => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Required token contract',
					],
					'wp_rainbow_field_required_token_quantity' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'Required token quantity',
					],
					'wp_rainbow_field_user_attributes_mapping' => [
						'required'    => true,
						'type'        => 'string',
						'description' => 'User attributes mapping',
					],
					'wp_rainbow_role_to_id_mapping_field' => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'Role to ID mapping',
					],
					'wp_rainbow_redirect_url_field'       => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'ERC-1155 redirect URL',
					],
					'wp_rainbow_customizations_erc_1155_contract_field' => [
						'required'    => false,
						'type'        => 'string',
						'description' => 'ERC-1155 contract address',
					],
				],
			]
		);
	}

	/**
	 * Adds supplemental scripts to settings page.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function action_admin_enqueue_scripts( string $hook ) {
		if ( 'toplevel_page_wp_rainbow_settings' !== $hook ) {
			return;
		}
		$script_path         = '../build/settings';
		$script_dependencies = include __DIR__ . '/../build/settings.asset.php';
		wp_register_script(
			'wp-rainbow-settings',
			plugins_url( $script_path . '.js', __FILE__ ),
			$script_dependencies['dependencies'],
			$script_dependencies['version'],
			true
		);
		wp_localize_script(
			'wp-rainbow-settings',
			'wpRainbowSettings',
			[
				'api'          => [
					'url'   => esc_url_raw( rest_url( 'wp-rainbow/v1/settings' ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				],
				'plugins'      => [
					'erc-1155-roles',
				],
				'default_role' => get_option( 'default_role' ),
			] 
		);
		wp_enqueue_script( 'wp-rainbow-settings' );
	}

	/**
	 * Add menu page for plugin.
	 */
	public function action_admin_menu() {
		add_menu_page(
			'RainbowKit Login Settings',
			'RainbowKit Login',
			'manage_options',
			'wp_rainbow_settings',
			[ self::$instance, 'wp_rainbow_settings_page_html' ],
			'dashicons-money'
		);
	}

	/**
	 * Log out all non-current users.
	 *
	 * @return void
	 */
	private function log_out_users() {
		$current_user    = get_current_user_id();
		$logged_in_users = get_users(
			[
				'meta_key'     => 'session_tokens',
				'meta_compare' => 'EXISTS',
			]
		);
		foreach ( $logged_in_users as $user ) {
			if ( $current_user !== $user->ID ) {
				$sessions = WP_Session_Tokens::get_instance( $user->ID );
				$sessions->destroy_all();
			}
		}
	}

	/**
	 * Print WP Rainbow settings page.
	 */
	public function wp_rainbow_settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div id="wp-rainbow-settings-page">
		</div>
		<?php
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function settings_callback_edit( $request ) {
		$settings = array_filter(
			$request->get_params(),
			function( $key ) {
				return '_locale' !== $key;
			},
			ARRAY_FILTER_USE_KEY
		);
		if ( ! empty( $settings['wp_rainbow_field_force_logout'] ) ) {
			$this->log_out_users();
			$settings['wp_rainbow_field_force_logout'] = false;
		}
		update_option(
			'wp_rainbow_options',
			$settings,
			true
		);
		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update one item from the collection
	 *
	 * @return WP_REST_Response
	 */
	public function settings_callback_get() {
		$saved = get_option( 'wp_rainbow_options', [] );
		return new WP_REST_Response( $saved, 200 );
	}
}
