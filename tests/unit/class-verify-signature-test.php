<?php
/**
 * Verify signature checks.
 *
 * @package WP_Rainbow
 */

namespace WP_Rainbow\Tests;

require __DIR__ . '/../../inc/class-wp-rainbow.php';

use Exception;
use PHPUnit\Framework\TestCase;
use Elliptic\EC;
use WP_Rainbow\WP_Rainbow;

/**
 * These tests check signature verification.
 */
class Verify_Signature_Test extends TestCase {

	/**
	 * Verify a generated message using library only.
	 */
	public function testVerificationLibraryOnly() {
		$ec        = new EC( 'secp256k1' );
		$key       = $ec->genKeyPair();
		$msg       = 'ab4c3451';
		$signature = $key->sign( $msg );
		$der_sign  = $signature->toDER( 'hex' );
		$this->assertTrue( $key->verify( $msg, $der_sign ) );
	}

	/**
	 * Verify a generated message using WP_Rainbow class.
	 *
	 * @throws Exception Throws if unsupported Keccak Hash output size.
	 */
	public function testVerificationWithClass() {
		$this_plugin        = new WP_Rainbow();
		$msg_payload        = [
			'address'   => '0xfe15a1eC58947149F81c33d5f5B6D74d952bc0F2',
			'domain'    => 'wp-rainbow.test',
			'chainId'   => 1,
			'issuedAt'  => '2022-03-22T22:52:03.693Z',
			'nonce'     => '5761ec5dfe',
			'statement' => 'Log In with Ethereum to WP Rainbow',
			'uri'       => 'https://wp-rainbow.test',
			'version'   => 1,
		];
		$signature          = '0x649726d97a8ebd2b67fcf867a08e504a8fbd7c9fe3af582f8b3f05dffdda6375717e1a69e7eddd9af2c5b6e92ad40402a2361b19652e1264d146c65e1110b6761c';
		$address            = '0xfe15a1eC58947149F81c33d5f5B6D74d952bc0F2';
		$generated_message  = $this_plugin->generate_message( $msg_payload );
		$signature_verified = $this_plugin->verify_signature(
			$generated_message,
			$signature,
			$address
		);
		$this->assertTrue( $signature_verified );
	}
}
