<?php
/**
 * WP_Rainbow class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

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
		add_action( 'login_enqueue_scripts', [ self::$instance, 'action_login_enqueue_scripts' ] );
		add_action(
			'admin_init',
			function() {
				if ( is_plugin_active( 'wp-rainbow-customizations/wp-rainbow-customizations.php' ) ) {
					// Also update option to apply roles on login.
					$options                                       = get_option(
						'wp_rainbow_options',
						[
							'wp_rainbow_field_set_user_roles' => 'off',
							'wp_rainbow_field_default_user_role' => '',
						] 
					);
					$options['wp_rainbow_field_set_user_roles']    = 'on';
					$options['wp_rainbow_field_default_user_role'] = 'visitor';
					update_option( 'wp_rainbow_options', $options );
					deactivate_plugins( 'wp-rainbow-customizations/wp-rainbow-customizations.php' );
				}
			} 
		);
	}

	// FILTERED VALUES.

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
	 * Provide filter for RPC URL. Defaults to settings page value.
	 *
	 * @return mixed|void Filtered RPC URL.
	 */
	public function get_rpc_url() {
		$options = get_option(
			'wp_rainbow_options',
			[
				'wp_rainbow_field_provider' => '',
				'wp_rainbow_field_rpc_url'  => '',
			] 
		);
		$rpc_url = '';
		if (
			! empty( $options['wp_rainbow_field_provider'] ) &&
			'other' === $options['wp_rainbow_field_provider'] &&
			! empty( $options['wp_rainbow_field_rpc_url'] )
		) {
			$rpc_url = $options['wp_rainbow_field_rpc_url'];
		} elseif (
			empty( $options['wp_rainbow_field_provider'] ) ||
			'infura' === $options['wp_rainbow_field_provider']
		) {
			$infura_network = $this->get_infura_network_filtered();
			$infura_id      = $this->get_infura_id_filtered();
			$rpc_url        = 'https://' . wp_rainbow_map_filtered_network_to_infura_endpoint( $infura_network ) . '.infura.io/v3/' . $infura_id;
		}
		
		/**
		 * Filter the RPC URL used for WP Rainbow integration.
		 *
		 * @param string $default RPC URL as set in WP Rainbow options or derived from Infura ID.
		 */
		return apply_filters( 'wp_rainbow_rpc_url', $rpc_url );
	}

	/**
	 * Provide filter for mainnet RPC URL. Defaults to settings page value.
	 *
	 * @return mixed|void Filtered RPC URL for mainnet.
	 */
	public function get_rpc_url_mainnet() {
		$options         = get_option(
			'wp_rainbow_options',
			[
				'wp_rainbow_field_provider'        => '',
				'wp_rainbow_field_rpc_url_mainnet' => '',
			] 
		);
		$rpc_url_mainnet = '';
		if (
			! empty( $options['wp_rainbow_field_provider'] ) &&
			'other' === $options['wp_rainbow_field_provider'] &&
			! empty( $options['wp_rainbow_field_rpc_url_mainnet'] )
		) {
			$rpc_url_mainnet = $options['wp_rainbow_field_rpc_url_mainnet'];
		} elseif (
			empty( $options['wp_rainbow_field_provider'] ) ||
			'infura' === $options['wp_rainbow_field_provider']
		) {
			$infura_id       = $this->get_infura_id_filtered();
			$rpc_url_mainnet = 'https://mainnet.infura.io/v3/' . $infura_id;
		}
		
		/**
		 * Filter the mainnet RPC URL used for WP Rainbow integration.
		 *
		 * @param string $default Mainnet RPC URL as set in WP Rainbow options or derived from Infura ID.
		 */
		return apply_filters( 'wp_rainbow_rpc_url_mainnet', $rpc_url_mainnet );
	}

	/**
	 * Provide filter for Infura ID. Defaults to settings page value.
	 *
	 * @return mixed|void Filtered Infura ID.
	 */
	public function get_infura_id_filtered() {
		$options   = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_infura_id' => '' ] );
		$infura_id = $options['wp_rainbow_field_infura_id'] ?? '';
		/**
		 * Filter the Infura ID used for WP Rainbow integration.
		 *
		 * @param string $default Infura ID as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_infura_id', $infura_id );
	}

	/**
	 * Provide filter for WalletConnect Project ID. Defaults to RainbowKit Login global value.
	 *
	 * @return mixed|void Filtered WalletConnect Project ID network.
	 */
	public function get_walletconnect_project_id_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_walletconnect_project_id' => '' ] );
		$default = ! empty( $options['wp_rainbow_field_walletconnect_project_id'] ) ? $options['wp_rainbow_field_walletconnect_project_id'] : 'fd1a095387aaa4bfc4cc1437ec3caac3';

		/**
		 * Filter the WalletConnect Project ID used for WP Rainbow integration.
		 *
		 * @param string $default WalletConnect Project ID as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_walletconnect_project_id', $default );
	}

	/**
	 * Provide filter for Infura network. Defaults to settings page value.
	 *
	 * @return mixed|void Filtered Infura network.
	 */
	public function get_infura_network_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_infura_network' => '' ] );
		$default = ! empty( $options['wp_rainbow_field_infura_network'] ) ? $options['wp_rainbow_field_infura_network'] : 'mainnet';

		/**
		 * Filter the Infura network used for WP Rainbow integration.
		 *
		 * @param string $default Infura network as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_infura_network', $default );
	}

	/**
	 * Provide filter for RainbowKit theme. Defaults to settings page value.
	 *
	 * @return string|void Filtered RainbowKit theme.
	 */
	public function get_theme_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_rainbowkit_theme' => 'lightTheme' ] );

		$default_theme = ! empty( $options['wp_rainbow_field_rainbowkit_theme'] ) && in_array(
			$options['wp_rainbow_field_rainbowkit_theme'],
			[
				'lightTheme',
				'darkTheme',
				'midnightTheme',
			],
			true 
		) ? $options['wp_rainbow_field_rainbowkit_theme'] : 'lightTheme';

		/**
		 * Filter the theme used for WP Rainbow integration.
		 *
		 * @param string $default Theme as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_theme', $default_theme );
	}

	/**
	 * Provide filter for compact modal setting. Defaults to settings page value.
	 *
	 * @return string|void Filtered compact modal status.
	 */
	public function get_compact_modal_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_compact_modal' => 'off' ] );

		/**
		 * Filter the compact modal status used for WP Rainbow integration.
		 *
		 * @param string $default Compact modal status as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_compact_modal', $options['wp_rainbow_field_compact_modal'] ?? 'off' );
	}

	/**
	 * Provide filter for cool mode. Defaults to settings page value.
	 *
	 * @return string|void Filtered cool mode status.
	 */
	public function get_cool_mode_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_cool_mode' => 'off' ] );

		/**
		 * Filter the cool mode status used for WP Rainbow integration.
		 *
		 * @param string $default Cool mode status as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_cool_mode', $options['wp_rainbow_field_cool_mode'] ?? 'off' );
	}

	// BLOCK SCRIPTS.

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
			[],
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
				'ADMIN_URL'                => get_admin_url(),
				'RPC_URL'                  => esc_textarea( $this->get_rpc_url() ),
				'RPC_URL_MAINNET'          => esc_textarea( $this->get_rpc_url_mainnet() ),
				'LOGIN_API'                => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'                => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL'             => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'               => get_bloginfo( 'name' ),
				'COOL_MODE'                => $this->get_cool_mode_filtered(),
				'THEME'                    => $this->get_theme_filtered(),
				'COMPACT_MODAL'            => $this->get_compact_modal_filtered(),
				'NETWORK'                  => esc_textarea( $this->get_infura_network_filtered() ),
				'ATTRIBUTES'               => $this->get_frontend_attributes(),
				'WALLETCONNECT_PROJECT_ID' => esc_textarea( $this->get_walletconnect_project_id_filtered() ),
			]
		);
		wp_localize_script(
			'login-block-frontend',
			'wpRainbowData',
			[
				'ADMIN_URL'                => get_admin_url(),
				'RPC_URL'                  => esc_textarea( $this->get_rpc_url() ),
				'RPC_URL_MAINNET'          => esc_textarea( $this->get_rpc_url_mainnet() ),
				'LOGIN_API'                => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'LOGGED_IN'                => is_user_logged_in(),
				'NONCE_API'                => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL'             => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'               => get_bloginfo( 'name' ),
				'LOGOUT_URL'               => wp_logout_url(),
				'COOL_MODE'                => $this->get_cool_mode_filtered(),
				'THEME'                    => $this->get_theme_filtered(),
				'COMPACT_MODAL'            => $this->get_compact_modal_filtered(),
				'NETWORK'                  => esc_textarea( $this->get_infura_network_filtered() ),
				'ATTRIBUTES'               => $this->get_frontend_attributes(),
				'WALLETCONNECT_PROJECT_ID' => esc_textarea( $this->get_walletconnect_project_id_filtered() ),
			]
		);
	}

	/**
	 * Get a parsed version of filtered user attributes mapping.
	 */
	public function get_parsed_user_attributes_mapping() {
		$csv = array_map( 'trim', explode( "\n", $this->get_user_attributes_mapping_filtered() ) );

		return array_map(
			function ( $row ) {
				return array_map( 'trim', explode( ',', $row ) );
			},
			$csv
		);
	}

	/**
	 * Get and parse use attributes for frontend.
	 *
	 * @return array Attributes for frontend
	 */
	public function get_frontend_attributes() {
		return array_reduce(
			$this->get_parsed_user_attributes_mapping(),
			function ( $agg, $item ) {
				if ( ! empty( $item ) && ! empty( $item[0] ) ) {
					$agg[] = $item[0];
				}

				return $agg;
			},
			[]
		);
	}

	/**
	 * Provide filter for user attributes mapping.
	 *
	 * @return string Filtered user attributes mapping
	 */
	public function get_user_attributes_mapping_filtered() {
		$options = get_option(
			'wp_rainbow_options',
			[
				'wp_rainbow_field_user_attributes_mapping' =>
					'url,user_url',
			]
		);

		/**
		 * Filter the user attributes mapping for WP Rainbow users.
		 *
		 * @param string $default Default user attributes mapping.
		 */
		return apply_filters( 'wp_rainbow_user_attributes_mapping', $options['wp_rainbow_field_user_attributes_mapping'] ?? '' );
	}

	// LOGIN SCRIPTS.

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
				'ADMIN_URL'                => get_admin_url(),
				'RPC_URL'                  => esc_textarea( $this->get_rpc_url() ),
				'RPC_URL_MAINNET'          => esc_textarea( $this->get_rpc_url_mainnet() ),
				'LOGIN_API'                => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'                => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL'             => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'               => get_bloginfo( 'name' ),
				'COOL_MODE'                => $this->get_cool_mode_filtered(),
				'NETWORK'                  => esc_textarea( $this->get_infura_network_filtered() ),
				'ATTRIBUTES'               => $this->get_frontend_attributes(),
				'THEME'                    => $this->get_theme_filtered(),
				'COMPACT_MODAL'            => $this->get_compact_modal_filtered(),
				'WALLETCONNECT_PROJECT_ID' => esc_textarea( $this->get_walletconnect_project_id_filtered() ),
			]
		);
	}
}
