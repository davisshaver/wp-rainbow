# WP Rainbow

[![PHPUnit Tests](https://github.com/davisshaver/wp-rainbow/actions/workflows/phpunit-tests.yml/badge.svg)](https://github.com/davisshaver/wp-rainbow/actions/workflows/phpunit-tests.yml)

Providing a [Sign-In With Ethereum](https://login.xyz/) experience for [WordPress](https://wordpress.org/) using [RainbowKit](https://www.npmjs.com/package/@rainbow-me/rainbowkit).

_Want to try it out? [Head here.](https://wp-rainbow.davisshaver.com/wp-login.php)_

## Installation

The recommended way to install WP Rainbow is downloading the ZIP file from [the most recent release](https://github.com/davisshaver/wp-rainbow/releases).

## Development

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

## Filters

Find reference implementations of all filters in [example plugin here](https://github.com/davisshaver/wp-rainbow/blob/main/wp-rainbow-filter-examples.php).

**`wp_rainbow_nonce_life`** - Filter amount of time before nonce expires.

**`wp_rainbow_role_for_address`** - Filter role granted to a specific address on sign-in.

**`wp_rainbow_infura_id`** - Filter Infura ID to override settings value.
