import {
	RainbowKitProvider,
	lightTheme,
	darkTheme,
	midnightTheme,
} from '@rainbow-me/rainbowkit';
import { coinbaseWallet, injected, walletConnect } from 'wagmi/connectors';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createConfig, http, WagmiProvider } from 'wagmi';
import {
	arbitrum,
	arbitrumGoerli,
	arbitrumSepolia,
	goerli,
	mainnet,
	optimism,
	optimismGoerli,
	optimismSepolia,
	polygon,
	polygonMumbai,
	sepolia,
	base,
	baseSepolia,
	zora,
	zoraSepolia,
} from 'wagmi/chains';
import stylePropType from 'react-style-proptype';

import PropTypes from 'prop-types';
import { WPRainbowConnect } from './connect';

const {
	COMPACT_MODAL,
	COOL_MODE,
	LOGGED_IN,
	NETWORK,
	RPC_URL,
	RPC_URL_MAINNET,
	SITE_TITLE,
	THEME,
	WALLETCONNECT_PROJECT_ID,
} = wpRainbowData;

const themes = {
	lightTheme,
	darkTheme,
	midnightTheme,
};

const allChains = {
	arbitrum,
	arbitrumGoerli,
	arbitrumSepolia,
	goerli,
	mainnet,
	optimism,
	optimismGoerli,
	optimismSepolia,
	polygon,
	polygonMumbai,
	sepolia,
	base,
	baseSepolia,
	zora,
	zoraSepolia,
};

const chains =
	NETWORK && allChains[ NETWORK ] && NETWORK !== 'mainnet'
		? [ allChains[ NETWORK ], mainnet ]
		: [ mainnet ];

const transports =
	NETWORK && allChains[ NETWORK ] && NETWORK !== 'mainnet'
		? {
				[ allChains[ NETWORK ] ]: http( RPC_URL ),
				[ mainnet.id ]: http( RPC_URL_MAINNET ),
		  }
		: {
				[ mainnet.id ]: http( RPC_URL_MAINNET ),
		  };
const wagmiConfig = createConfig( {
	chains,
	connectors: [
		coinbaseWallet( {
			appName: SITE_TITLE,
			chainId: allChains[ NETWORK ].id,
		} ),
		injected(),
		walletConnect( {
			projectId: WALLETCONNECT_PROJECT_ID,
		} ),
	],
	transports,
} );

const queryClient = new QueryClient();

/**
 * WP Rainbow Provider.
 *
 * @param {Object}   props                         Props for WP Rainbow Provider.
 * @param {string}   props.buttonClassName         Class for WP Rainbow button.
 * @param {boolean}  props.mockLogin               Whether to skip the login redirect.
 * @param {Function} props.onError                 Callback for error handling.
 * @param {Function} props.onLogin                 Callback for login.
 * @param {Function} props.onLogout                Callback for logout.
 * @param {string}   props.containerClassName      Container classname.
 * @param {Object}   props.style                   Button style.
 * @param {boolean}  props.containers              Use container elements.
 * @param {string}   props.outerContainerClassName Outer container classname.
 * @param {string}   props.loginText               Login text override.
 * @param {string}   props.checkWalletText         Check wallet text override.
 * @param {string}   props.errorText               Error text override.
 * @param {boolean}  props.redirectBoomerang       Enable redirecting to current page.
 * @param {string}   props.redirectURL             Redirect URL override.
 */
function WPRainbow( {
	buttonClassName,
	checkWalletText,
	containerClassName,
	containers,
	errorText,
	loginText,
	mockLogin,
	onError,
	onLogin,
	onLogout,
	outerContainerClassName,
	redirectBoomerang,
	redirectURL,
	style,
} ) {
	return (
		<WagmiProvider
			config={ wagmiConfig }
			reconnectOnMount={ LOGGED_IN === '1' }
		>
			<QueryClientProvider client={ queryClient }>
				<RainbowKitProvider
					coolMode={ COOL_MODE === 'on' }
					modalSize={ COMPACT_MODAL === 'on' ? 'compact' : 'wide' }
					theme={ themes[ THEME ]() }
				>
					<WPRainbowConnect
						buttonClassName={ buttonClassName }
						checkWalletText={ checkWalletText }
						containerClassName={ containerClassName }
						containers={ containers }
						errorText={ errorText }
						loginText={ loginText }
						mockLogin={ mockLogin }
						onError={ onError }
						onLogin={ onLogin }
						onLogout={ onLogout }
						outerContainerClassName={ outerContainerClassName }
						redirectBoomerang={ redirectBoomerang }
						redirectURL={ redirectURL }
						style={ style }
					/>
				</RainbowKitProvider>
			</QueryClientProvider>
		</WagmiProvider>
	);
}

WPRainbow.defaultProps = {
	buttonClassName: '',
	checkWalletText: '',
	containerClassName: '',
	containers: false,
	errorText: '',
	loginText: '',
	mockLogin: false,
	onError: () => {},
	onLogin: () => {},
	onLogout: () => {},
	outerContainerClassName: '',
	redirectBoomerang: false,
	redirectURL: '',
	style: {},
};

WPRainbow.propTypes = {
	buttonClassName: PropTypes.string,
	checkWalletText: PropTypes.string,
	containerClassName: PropTypes.string,
	containers: PropTypes.bool,
	errorText: PropTypes.string,
	loginText: PropTypes.string,
	mockLogin: PropTypes.bool,
	onError: PropTypes.func,
	onLogin: PropTypes.func,
	onLogout: PropTypes.func,
	outerContainerClassName: PropTypes.string,
	redirectBoomerang: PropTypes.bool,
	redirectURL: PropTypes.string,
	style: stylePropType,
};

export default WPRainbow;
