<?php
/**
 * General purpose functions file
 *
 * @package WP_Rainbow
 */

/**
 * Map filtered Infura network to an Infura endpoint value.
 *
 * @param string $filtered_network Filtered Infura network.
 *
 * @return string Infura endpoint or default
 */
function wp_rainbow_map_filtered_network_to_infura_endpoint( string $filtered_network ): string {
	$overrides = [
		'arbitrum'        => 'arbitrum-mainnet',
		'arbitrumGoerli'  => 'arbitrum-goerli',
		'arbitrumSepolia' => 'arbitrum-sepolia',
		'optimism'        => 'optimism-mainnet',
		'optimismGoerli'  => 'optimism-goerli',
		'optimismSepolia' => 'optimism-sepolia',
		'polygon'         => 'polygon-mainnet',
		'polygonMumbai'   => 'polygon-mumbai',
	];
	if ( ! empty( $overrides[ $filtered_network ] ) ) {
		return $overrides[ $filtered_network ];
	}

	return $filtered_network;
}
