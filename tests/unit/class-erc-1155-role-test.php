<?php
/**
 * Verify ERC-1155 logic.
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow\Tests;

use PHPUnit\Framework\TestCase;
use Web3\Contract;
use Web3\Formatters\BigNumberFormatter;

/**
 * These tests check ERC-1155 logic.
 */
class Erc_1155_Role_Test extends TestCase {
	/**
	 * Formatter
	 *
	 * @var BigNumberFormatter
	 */
	protected BigNumberFormatter $formatter;

	/**
	 * Setup
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->formatter = new BigNumberFormatter();
	}

	/**
	 * Verify ERC-1155 role logic with a big number.
	 */
	public function testVerifyERC1155RoleLogic() {
		$erc1155_abi   = '[{"inputs":[{"internalType":"address","name":"","type":"address"},{"internalType":"uint256","name":"","type":"uint256"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
		$contract      = new Contract( 'https://mainnet.infura.io/v3/9861d9fc6201412a801f7a2380fb2f0c', $erc1155_abi );
		$erc1155_check = false;
		$contract->at( '0x988a1ff39cFe1deC263D301a95cf0C6F7719e057' )->call(
			'balanceOf',
			'0xa26a2851b9Dfa218392eDE0714BfF5775f2a6076',
			'114478551006842822869844276849349299855804958670084906297703580153042624915906',
			function ( $err, $balance ) use ( &$erc1155_check ) {
				if ( hexdec( $balance[0]->value ) >= 1 ) {
					$erc1155_check = true;
				}
			}
		);
		$this->assertTrue( $erc1155_check );
	}

	/**
	 * Verify ERC-1155 role logic on Polygon.
	 */
	public function testVerifyERC1155Polygon() {
		$erc1155_abi   = '[{"inputs":[{"internalType":"address","name":"","type":"address"},{"internalType":"uint256","name":"","type":"uint256"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
		$contract      = new Contract( 'https://polygon-mainnet.infura.io/v3/9861d9fc6201412a801f7a2380fb2f0c', $erc1155_abi );
		$erc1155_check = false;
		$contract->at( '0xB5Cd475c41b7c59646Fa2779Dd5fA54765B61039' )->call(
			'balanceOf',
			'0x7929DA8e397aE58Be05ee8ffe00a0Da2E58D9271',
			'0',
			function ( $err, $balance ) use ( &$erc1155_check ) {
				if ( hexdec( $balance[0]->value ) >= 1 ) {
					$erc1155_check = true;
				}
			}
		);
		$this->assertTrue( $erc1155_check );
	}

	/**
	 * Verify ERC-1155 role logic on Base.
	 */
	public function testVerifyERC1155Base() {
		$erc1155_abi   = '[{"inputs":[{"internalType":"address","name":"","type":"address"},{"internalType":"uint256","name":"","type":"uint256"}],"name":"balanceOf","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],"stateMutability":"view","type":"function"}]';
		$contract      = new Contract( 'https://divine-practical-aura.base-mainnet.quiknode.pro/3546a321b96b62c1edcdc8df17978d6ffdf09024/', $erc1155_abi );
		$erc1155_check = false;
		$contract->at( '0x73b2bdee3c89ff52e3ddb398d740dc0968c1f1b4' )->call(
			'balanceOf',
			'0x0F9Bd2a9E0D30f121c525DB5419A07b08Fce8440',
			'3',
			function ( $err, $balance ) use ( &$erc1155_check ) {
				if ( hexdec( $balance[0]->value ) >= 1 ) {
					$erc1155_check = true;
				}
			}
		);
		$this->assertTrue( $erc1155_check );
	}
}
