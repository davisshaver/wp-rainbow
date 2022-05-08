# WP Rainbow

Plugin URI: <https://wp-rainbow.davisshaver.com/>  
Description: WP Rainbow allows WordPress users to log in with Ethereum using the Sign-In With Ethereum standard, powered by RainbowKit.  
Version: 0.1.2  
Author: Davis Shaver  
Author URI: <https://davisshaver.com>  
License: GPL v2 or later  
License URI: <https://www.gnu.org/licenses/gpl-2.0.html>  
Text Domain: wp-rainbow  
Update URI: <https://github.com/davisshaver/wp-rainbow>  
Tags: WordPress, web3, SIWE, Ethereum, RainbowKit, Sign-In With Ethereum  
Contributors: davisshaver

![WP Rainbow Plugin Banner](.wordpress-org/banner-1544x500.png)

Providing a [Sign-In With Ethereum](https://login.xyz/) experience for [WordPress](https://wordpress.org/) using [RainbowKit](https://www.npmjs.com/package/@rainbow-me/rainbowkit).

[![PHPUnit Tests](https://github.com/davisshaver/wp-rainbow/actions/workflows/phpunit-tests.yml/badge.svg)](https://github.com/davisshaver/wp-rainbow/actions/workflows/phpunit-tests.yml)

## Description

Providing a [Sign-In With Ethereum](https://login.xyz/) experience for [WordPress](https://wordpress.org/) using [RainbowKit](https://www.npmjs.com/package/@rainbow-me/rainbowkit).

_Want to try it out? [Head here.](https://wp-rainbow.davisshaver.com/wp-login.php)_

### Features

* Allow users to log in with Ethereum
* Set required token balance for login

### Installation

The recommended way to install WP Rainbow is downloading the plugin from WordPress.org. Alternatively, download the ZIP file from [the most recent release](https://github.com/davisshaver/wp-rainbow/releases).

### Development

Before you begin, make sure you have [Composer](https://getcomposer.org/) and [Yarn](https://yarnpkg.com/) available.

1. Clone the repository.

   `git clone https://github.com/davisshaver/wp-rainbow.git`

2. Change into the directory.

   `cd wp-rainbow`

3. Install Composer dependencies.

   `composer install`

4. Install Node dependencies with Yarn.

   `yarn install`

5. Build the JS files.

   `yarn build`

_Note: This plugin requires [GMP](https://www.php.net/manual/en/book.gmp.php) to be available on the server._

### Actions

**`wp_rainbow_validation_failed`** - Fires when validation fails.

**`wp_rainbow_user_created`** - Fires when user created.

**`wp_rainbow_user_updated`** - Fires when user updated.

**`wp_rainbow_user_login`** - Fires when user logs in.

### Filters

Find reference implementations of all filters in [example plugin here](https://github.com/davisshaver/wp-rainbow/blob/main/wp-rainbow-filter-examples.php).

**`wp_rainbow_nonce_life`** - Filter amount of time before nonce expires.

**`wp_rainbow_role_for_address`** - Filter role granted to a specific address on sign-in.

**`wp_rainbow_infura_id`** - Filter Infura ID to override settings value.

**`wp_rainbow_redirect_url`** - Filter login redirect URL.
