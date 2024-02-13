import {
	RainbowKitProvider,
	lightTheme,
	darkTheme,
	midnightTheme,
	connectorsForWallets,
} from '@rainbow-me/rainbowkit';
import {
	metaMaskWallet,
	rainbowWallet,
	walletConnectWallet,
	injectedWallet,
	coinbaseWallet,
} from '@rainbow-me/rainbowkit/wallets';
import { createConfig, configureChains, WagmiConfig } from 'wagmi';
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
} from 'wagmi/chains';
import { infuraProvider } from 'wagmi/providers/infura';
import stylePropType from 'react-style-proptype';
import { publicProvider } from 'wagmi/providers/public';

import PropTypes from 'prop-types';
import { WPRainbowConnect } from './connect';

const {
	COMPACT_MODAL,
	COOL_MODE,
	INFURA_ID,
	LOGGED_IN,
	NETWORK,
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
};

const { chains, publicClient } = configureChains(
	[
		...( NETWORK && allChains[ NETWORK ] ? [ allChains[ NETWORK ] ] : [] ),
		allChains.mainnet,
	],
	[ infuraProvider( { apiKey: INFURA_ID } ), publicProvider() ]
);

const wallets = [
	{
		groupName: 'Popular',
		wallets: [
			injectedWallet( { chains } ),
			rainbowWallet( { chains, projectId: WALLETCONNECT_PROJECT_ID } ),
			coinbaseWallet( { appName: SITE_TITLE, chains } ),
			metaMaskWallet( { chains, projectId: WALLETCONNECT_PROJECT_ID } ),
			walletConnectWallet( {
				chains,
				projectId: WALLETCONNECT_PROJECT_ID,
			} ),
		],
	},
];

const connectors = connectorsForWallets( wallets );

const wagmiConfig = createConfig( {
	autoConnect: LOGGED_IN === '1',
	connectors,
	publicClient,
} );

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
		<WagmiConfig config={ wagmiConfig }>
			<RainbowKitProvider
				chains={ chains }
				coolMode={ COOL_MODE === 'on' }
				modalSize={ COMPACT_MODAL === 'on' ? 'compact' : 'large' }
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
		</WagmiConfig>
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
