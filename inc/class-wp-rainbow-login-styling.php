<?php
/**
 * WP_Rainbow_Login_Styling class file
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow;

/**
 * WP Rainbow Login Styling Customizations
 */
class WP_Rainbow_Login_Styling {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow_Login_Styling $instance;

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow_Login_Styling {
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
		add_action( 'login_head', [ self::$instance, 'action_login_head' ] );
		add_action( 'login_form', [ self::$instance, 'action_login_form' ] );
	}

	/**
	 * Adds supplemental styles to the login page header.
	 */
	public function action_login_head() {
		?>
		<style>
			.js.login-action-login form {
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
				padding: 0 18px;
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
}
