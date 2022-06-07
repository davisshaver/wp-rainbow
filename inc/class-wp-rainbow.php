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

	/**
	 * Provide filter for cool mode. Defaults to settings page value.
	 *
	 * @return boolean|void Filtered cool mode status.
	 */
	public function get_cool_mode_filtered() {
		$options = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_cool_mode' => false ] );

		/**
		 * Filter the cool mode status used for WP Rainbow integration.
		 *
		 * @param boolean $default Cool mode status as set in WP Rainbow options.
		 */
		return apply_filters( 'wp_rainbow_infura_id', $options['wp_rainbow_field_cool_mode'] ?? false );
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
				'COOL_MODE'    => (bool) $this->get_cool_mode_filtered(),
			]
		);
		wp_localize_script(
			'login-block-frontend',
			'wpRainbowData',
			[
				'ADMIN_URL'    => get_admin_url(),
				'INFURA_ID'    => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'    => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'LOGGED_IN'    => is_user_logged_in(),
				'NONCE_API'    => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL' => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'   => get_bloginfo( 'name' ),
				'LOGOUT_URL'   => wp_logout_url(),
				'COOL_MODE'    => (bool) $this->get_cool_mode_filtered(),
			]
		);
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
				'ADMIN_URL'    => get_admin_url(),
				'INFURA_ID'    => esc_textarea( $this->get_infura_id_filtered() ),
				'LOGIN_API'    => get_rest_url( null, 'wp-rainbow/v1/login' ),
				'NONCE_API'    => get_rest_url( null, 'wp-rainbow/v1/nonce' ),
				'REDIRECT_URL' => esc_url( $this->get_redirect_url_filtered() ),
				'SITE_TITLE'   => get_bloginfo( 'name' ),
				'COOL_MODE'    => (bool) $this->get_cool_mode_filtered(),
			]
		);
	}
}
