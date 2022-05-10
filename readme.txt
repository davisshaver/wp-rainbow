=== RainbowKit Login (Web3 Integration for Sign-In With Ethereum) ===
Contributors: davisshaver
Tags: WordPress, web3, SIWE, Ethereum, RainbowKit, Sign-In With Ethereum
Tested up to: 6.0
Requires at least: 5.9
Requires PHP: 7.0
Stable tag: 0.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WP Rainbow allows WordPress users to log in with Ethereum using the Sign-In With Ethereum standard, powered by RainbowKit.

== Description ==

Providing a [Sign-In With Ethereum](https://login.xyz/) experience for [WordPress](https://wordpress.org/) using [RainbowKit](https://www.npmjs.com/package/@rainbow-me/rainbowkit).

- Allow users to log in with Ethereum
- Set required token balance for login

_Want to try it out? [Head here.](https://wp-rainbow.davisshaver.com/wp-login.php)_

== Frequently Asked Questions ==

= What filters are included? =

Find reference implementations of all filters in [example plugin here](https://github.com/davisshaver/wp-rainbow/blob/main/wp-rainbow-filter-examples.php).

**`wp_rainbow_nonce_life`** - Filter amount of time before nonce expires.

**`wp_rainbow_role_for_address`** - Filter role granted to a specific address on sign-in.

**`wp_rainbow_infura_id`** - Filter Infura ID to override settings value.

**`wp_rainbow_redirect_url`** - Filter login redirect URL.

= What actions are included? =

**`wp_rainbow_validation_failed`** - Fires when validation fails.

**`wp_rainbow_user_created`** - Fires when user created.

**`wp_rainbow_user_updated`** - Fires when user updated.

**`wp_rainbow_user_login`** - Fires when user logs in.

== Changelog ==

= 0.2.3 =
* Initial plugin release to WordPress.org

== Screenshots =

1. RainbowKit Login allows users to log in using their Ethereum wallet
2. RainbowKit Login includes a login block that can be customized
