<?php
/**
 * WP_Rainbow_Settings class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

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
		add_action( 'admin_init', [ self::$instance, 'action_admin_init' ] );
	}

	/**
	 * Add menu page for plugin.
	 */
	public function action_admin_menu() {
		add_menu_page(
			'RainbowKit Login Settings',
			'RainbowKit Login',
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
		register_setting(
			'wp_rainbow',
			'wp_rainbow_options',
			[
				'sanitize_callback' => [ self::$instance, 'wp_rainbow_sanitize_callback' ],
			]
		);

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
			'wp_rainbow_field_infura_network',
			__( 'Infura Network', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_infura_network_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_infura_network',
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

		add_settings_field(
			'wp_rainbow_field_required_token',
			__( 'Required Token Contract', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_required_token_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_required_token',
			],
		);

		add_settings_field(
			'wp_rainbow_field_required_token_quantity',
			__( 'Required Token Quantity', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_required_token_quantity_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_required_token_quantity',
			],
		);

		add_settings_field(
			'wp_rainbow_field_force_logout',
			__( 'Clear Existing Sessions', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_force_logout_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_force_logout',
			],
		);

		add_settings_field(
			'wp_rainbow_field_cool_mode',
			__( 'Enable Cool Mode', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_cool_mode_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_cool_mode',
			]
		);

		add_settings_field(
			'wp_rainbow_field_user_attributes_mapping',
			__( 'ENS Text Records to User Meta Mapping', 'wp-rainbow' ),
			[ self::$instance, 'wp_rainbow_field_user_attributes_mapping_callback' ],
			'wp_rainbow',
			'wp_rainbow_connection_options',
			[
				'label_for' => 'wp_rainbow_field_user_attributes_mapping',
			],
		);
	}

	/**
	 * Sanitize WP Rainbow options.
	 *
	 * @param array $input WP Rainbow options.
	 *
	 * @return array Sanitized WP Rainbow options.
	 */
	public function wp_rainbow_sanitize_callback( $input ) {
		if ( ! empty( $input['wp_rainbow_field_force_logout'] ) ) {
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
			$input['wp_rainbow_field_force_logout'] = null;
		}

		return $input;
	}

	/**
	 * Print field for Forcing Logout of Existing Sessions.
	 */
	public function wp_rainbow_force_logout_callback() {
		?>
		<input
			id='wp_rainbow_field_force_logout'
			name='wp_rainbow_options[wp_rainbow_field_force_logout]'
			type='checkbox'
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If checked, existing sessions will be logged out on save.', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
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
					esc_html_e( 'If enabled, non-admin RainbowKit Login users will be passwordless.', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for cool mode option.
	 */
	public function wp_rainbow_cool_mode_callback() {
		$options   = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_cool_mode' => false ] );
		$cool_mode = ! empty( $options['wp_rainbow_field_cool_mode'] );
		?>
		<input
			id='wp_rainbow_field_cool_mode'
			name='wp_rainbow_options[wp_rainbow_field_cool_mode]'
			type='checkbox'
			<?php
			if ( $cool_mode ) {
				echo 'checked';
			}
			?>
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If enabled, RainbowKit will use "Cool Mode" effects.', 'wp-rainbow' );
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
	 * Print field for Required NFT option.
	 */
	public function wp_rainbow_required_token_callback() {
		$options        = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_required_token' => '' ] );
		$required_token = ! empty( $options['wp_rainbow_field_required_token'] ) ? $options['wp_rainbow_field_required_token'] : '';
		?>
		<input
			id='wp_rainbow_field_required_token'
			name='wp_rainbow_options[wp_rainbow_field_required_token]'
			size='40'
			type='text'
			value='<?php echo esc_attr( $required_token ); ?>'
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'If set, users will be required to own an NFT from this contract', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for Required NFT Quantity option.
	 */
	public function wp_rainbow_required_token_quantity_callback() {
		$options                 = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_required_token_quantity' => '' ] );
		$required_token_quantity = ! empty( $options['wp_rainbow_field_required_token_quantity'] ) ? $options['wp_rainbow_field_required_token_quantity'] : '0';
		?>
		<input
			id='wp_rainbow_field_required_token_quantity'
			name='wp_rainbow_options[wp_rainbow_field_required_token_quantity]'
			size='40'
			type='number'
			value='<?php echo esc_attr( $required_token_quantity ? $required_token_quantity : 1 ); ?>'
		/>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'Optionally set the number of tokens required to be held by user', 'wp-rainbow' );
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
	 * Print field for Infura network option.
	 */
	public function wp_rainbow_infura_network_callback() {
		$options        = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_infura_network' => '' ] );
		$infura_network = ! empty( $options['wp_rainbow_field_infura_network'] ) ? $options['wp_rainbow_field_infura_network'] : '';
		$networks       = [ 'Mainnet', 'Ropsten', 'Kovan', 'Rinkeby', 'Goerli' ];
		?>
		<select
			id='wp_rainbow_field_infura_network'
			name='wp_rainbow_options[wp_rainbow_field_infura_network]'
		>
			<?php
			foreach ( $networks as $network ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( strtolower( $network ) ),
					strtolower( $network ) === $infura_network ? 'selected' : '',
					esc_html( $network )
				);
			}
			?>
		</select>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'All contract validation will be performed on this network.', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
		<?php
	}

	/**
	 * Print field for ENS text attributes to WP user meta option.
	 */
	public function wp_rainbow_field_user_attributes_mapping_callback() {
		$options       = get_option( 'wp_rainbow_options', [ 'wp_rainbow_field_user_attributes_mapping' => '' ] );
		$mapping_field = ! empty( $options['wp_rainbow_field_user_attributes_mapping'] ) ? $options['wp_rainbow_field_user_attributes_mapping'] : '';
		?>
		<textarea
			id='wp_rainbow_field_user_attributes_mapping'
			name='wp_rainbow_options[wp_rainbow_field_user_attributes_mapping]'
			rows='5'
			type='textarea'
		><?php echo esc_textarea( $mapping_field ); ?></textarea>
		<p>
			<em>
				<small>
					<?php
					esc_html_e( 'Enter a mapping of ENS text attributes to WordPress user meta, one on each line. Example: url,user_url', 'wp-rainbow' );
					?>
				</small>
			</em>
		</p>
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
}
