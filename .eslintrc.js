module.exports = {
	env: {
		browser: true,
		es2021: true,
	},
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:react/recommended',
		'airbnb',
		'prettier',
	],
	globals: {
		React: true,
		ReactDOM: true,
		wp: true,
		wpRainbowData: true,
	},
	ignorePatterns: [ 'build/', 'vendor/' ],
	parserOptions: {
		ecmaFeatures: {
			jsx: true,
		},
		ecmaVersion: 'latest',
		sourceType: 'module',
	},
	plugins: [ 'react' ],
	rules: {
		'react/react-in-jsx-scope': 'off',
	},
	settings: {
		react: {
			version: '16.13.1',
		},
	},
};
