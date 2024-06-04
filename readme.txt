=== RainbowKit Login (Web3 Integration for Sign-In With Ethereum) ===
Contributors: davisshaver
Tags: WordPress, web3, SIWE, Ethereum, RainbowKit, Sign-In With Ethereum
Tested up to: 6.5.3
Requires at least: 5.9
Requires PHP: 7.0
Stable tag: 0.5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sign-In With Ethereum for WordPress, powered by RainbowKit.

== Description ==

Providing a [Sign-In With Ethereum](https://login.xyz/) experience for [WordPress](https://wordpress.org/) using [RainbowKit](https://www.npmjs.com/package/@rainbow-me/rainbowkit).

- Allow users to log in with Ethereum
- Style the login form to match your site
- Insert a RainbowKit Login block anywhere you can insert a block
- Enable cool mode for a fun experience 😎
- Set required token balance for login
- Sync ENS text records to WordPress user profile
- Assign roles to users based on ERC-1155 token ownership

_Want to try it out? [Head here.](https://wp-rainbow.davisshaver.com/wp-login.php)_

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-rainbow` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin through the 'RainbowKit Login' screen in the WordPress admin.

Using an Infura ID/API key is required to use most features in this plugin, including token-gating and role mapping. You can [sign up for a free account here](https://infura.io/). The free tier is sufficient for most use cases. If you're using this plugin for a production site, you'll want to [sign up for a paid account](https://infura.io/pricing) to avoid rate limiting.

Supplying your own WalletConnect Project ID is also highly recommended. You can [sign up for a free project ID from WallectConnect Cloud](https://cloud.walletconnect.com/).

== Frequently Asked Questions ==

= What filters are included? =

Find reference implementations of all filters in [example plugin here](https://github.com/davisshaver/wp-rainbow/blob/main/wp-rainbow-filter-examples.php).

**`wp_rainbow_nonce_life`** - Filter amount of time before nonce expires.

**`wp_rainbow_role_for_address`** - Filter role granted to a specific address on sign-in.

**`wp_rainbow_should_update_roles`** - Filter whether roles should be set.

**`wp_rainbow_infura_id`** - Filter Infura ID/API key to override settings value.

**`wp_rainbow_walletconnect_project_id`** - Filter WalletConnect project ID to override settings value.

**`wp_rainbow_infura_network`** - Filter Infura network to override settings value.

**`wp_rainbow_redirect_url`** - Filter login redirect URL.

**`wp_rainbow_should_update_roles`** - Filter whether roles should be set.

**`wp_rainbow_should_disable_user_role_updates_on_login`** - Filter whether roles should be updated on login.

= What actions are included? =

**`wp_rainbow_validation_failed`** - Fires when validation fails.

**`wp_rainbow_user_created`** - Fires when user created.

**`wp_rainbow_user_updated`** - Fires when user updated.

**`wp_rainbow_user_login`** - Fires when user logs in.

== Changelog ==

= 0.5.4 =
* Update Viem to fix SIWE bug

= 0.5.3 =
* Update dependencies

= 0.5.2 =
* Switch from simple-siwe to viem/siwe

= 0.5.1 =
* Add default helper classname for button

= 0.5.0 =
* Add support for custom RPC URLs and Base/Zora networks
* Add helper classnames to buttons for additional style customization
* Switch from SIWE to simple-siwe for smaller bundle size
* Add basic validations of settings before save

= 0.4.6 =
* Fix bug with Infura endpoint mapping

= 0.4.5 =
* Fix bug with Polygon network, add Polygon Mumbai

= 0.4.4 =
* Update dependencies/support WordPress 6.4

= 0.4.3 =
* Update dependencies/support WordPress 6.3

= 0.4.2 =
* Update supported chains

= 0.4.1 =
* Version bump only for release

= 0.4.0 =
* Update WAGMI, RainbowKit, and web3 dependencies

= 0.3.4 =
* Use patched version of web3 library to fix BigNumber issue

= 0.3.3 =
* Update readme and messaging for Infura usage

= 0.3.2 =
* Add support for Optimism network

= 0.3.1 =
* Bug fixes

= 0.3.0 =
* Major refactor with new React-powered admin and additional token-gating functionality

= 0.2.20 =
* Bug fix to only set user role if featured is enabled via filter

= 0.2.19 =
* Bump version to 0.2.19

= 0.2.18 =
* Fix bug with login block CSS dependencies

= 0.2.17 =
* Fix bug with user_email and user_url fields user meta

= 0.2.16 =
* Add support for mapping ENS text records to user fields

= 0.2.15 =
* Fix bug with override redirect URL on login block

= 0.2.14 =
* Use alternate network for connection request too

= 0.2.13 =
* Add documentation for network filter

= 0.2.12 =
* Allow network to be specified for contract validation

= 0.2.11 =
* Enhance options for filtering roles

= 0.2.10 =
* Add RainbowKit Cool Mode support

= 0.2.9 =
* Update WAGMI and RainbowKit to latest versions

= 0.2.8 =
* Improved consistency around RainbowKit Login name

= 0.2.7 =
* Initial plugin release to WordPress.org

== Screenshots =

1. RainbowKit Login allows users to log in using their Ethereum wallet
2. RainbowKit Login can be styled to match your site
3. RainbowKit Login uses the Sign-In With Ethereum protocol
4. RainbowKit Login uses the user's address as their username, and an ENS address if available as their display name
5. RainbowKit Login includes a login block that can be customized
6. RainbowKit Login can be customized with a variety of settings
7. RainbowKit Login can sync ENS text records to WordPress user profile fields
8. RainbowKit Login can be used to apply specific roles to users based on ERC-1155 token ownership
