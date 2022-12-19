<?php
/**
 * WP_Rainbow_Plugins_ERC_1155_Roles class file
 *
 * @package WP_Rainbow_Plugins
 */

namespace WP_Rainbow_Plugins;

use Exception;
use Web3\Contract;
use WP_User;

/**
 * WP Rainbow Plugins ERC-1115 Role
 */
class WP_Rainbow_Plugins_ERC_1155_Roles {

	/**
	 * Instance of the singleton.
	 *
	 * @var static
	 */
	protected static WP_Rainbow_Plugins_ERC_1155_Roles $instance;

	/**
	 * Get the instance of this singleton.
	 *
	 * @return static
	 */
	public static function instance(): WP_Rainbow_Plugins_ERC_1155_Roles {
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
		add_filter( 'wp_rainbow_role_for_address', [ self::$instance, 'filter_wp_rainbow_role_for_address' ], 10, 5 );
	}

	/**
	 * Filter WP Rainbow role for address based on ERC-1155 holdings.
	 *
	 * @param string        $default_role Role used as fallback.
	 * @param string        $address Address for determining role.
	 * @param string        $filtered_infura_id Infura ID to use for contract calls.
	 * @param string        $filtered_infura_network Infura network to use for contract calls.
	 * @param WP_User|false $user WordPress user if available.
	 *
	 * @return string Filtered WP Rainbow role.
	 * @throws Exception Throws if user does not hold valid tokens.
	 */
	public function filter_wp_rainbow_role_for_address( string $default_role, string $address, string $filtered_infura_id, string $filtered_infura_network, $user ): string {
		$erc1155_abi        = '[{"inputs":[{"internalType":"address","name":"","type":"address"},{"internalType":"uint256","name":"","type":"uint256"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
		$erc1155_role       = '';
		$contract           = new Contract( 'https://' . $filtered_infura_network . '.infura.io/v3/' . $filtered_infura_id, $erc1155_abi );
		$wp_rainbow_options = get_option(
			'wp_rainbow_options',
			[
				'wp_rainbow_redirect_url_field'       => '',
				'wp_rainbow_role_to_id_mapping_field' => '',
				'wp_rainbow_customizations_erc_1155_contract_field' => '',
			]
		);

		if ( empty( $wp_rainbow_options['wp_rainbow_customizations_erc_1155_contract_field'] ) ) {
			return $default_role;
		}

		$erc1155_token_ids_raw = ! empty( $wp_rainbow_options['wp_rainbow_role_to_id_mapping_field'] ) && is_string( $wp_rainbow_options['wp_rainbow_role_to_id_mapping_field'] )
			?
			preg_split( '/\n|\r\n?/', $wp_rainbow_options['wp_rainbow_role_to_id_mapping_field'] )
			:
			[];
		$erc1155_token_ids     = array_reduce(
			$erc1155_token_ids_raw,
			function ( $all, $item ) {
				$split_item = explode( ',', $item );
				if ( count( $split_item ) < 2 ) {
					return $all;
				}
				if ( ! is_string( $split_item[0] ) || ! is_numeric( $split_item[1] ) ) {
					return $all;
				}
				$all[ trim( $split_item[0] ) ] = trim( $split_item[1] );

				return $all;
			},
			[]
		);

		foreach ( $erc1155_token_ids as $role => $id ) {
			$contract->at( $wp_rainbow_options['wp_rainbow_customizations_erc_1155_contract_field'] )->call(
				'balanceOf',
				$address,
				$id,
				function ( $err, $balance ) use ( &$erc1155_role, $role ) {
					if ( hexdec( $balance[0]->value ) >= 1 ) {
						$erc1155_role = $role;
					}
				}
			);
		}

		if ( empty( $erc1155_role ) ) {
			// Update user role before redirecting so they can't sneak in.
			if ( $user ) {
				$user->set_role( $default_role );
			}
			if ( ! empty( $wp_rainbow_options['wp_rainbow_redirect_url_field'] ) ) {
				throw new Exception(
					wp_json_encode(
						[
							'type'        => 'redirect',
							'redirectURL' => esc_url( $wp_rainbow_options['wp_rainbow_redirect_url_field'] ),
						]
					)
				);
			}

			return $default_role;
		}

		return $erc1155_role;
	}
}
