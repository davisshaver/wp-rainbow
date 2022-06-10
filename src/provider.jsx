import { getDefaultWallets, RainbowKitProvider } from '@rainbow-me/rainbowkit';
import { chain, createClient, configureChains, WagmiConfig } from 'wagmi';
import { infuraProvider } from 'wagmi/providers/infura';
import stylePropType from 'react-style-proptype';

import PropTypes from 'prop-types';
import { WPRainbowConnect } from './connect';

const { INFURA_ID, LOGGED_IN, SITE_TITLE } = wpRainbowData;

const { chains, provider } = configureChains(
	[ chain.mainnet ],
	[ infuraProvider( { infuraId: INFURA_ID } ) ]
);

const { connectors } = getDefaultWallets( {
	appName: SITE_TITLE,
	chains,
} );

const wagmiClient = createClient( {
	autoConnect: LOGGED_IN === '1',
	connectors,
	provider,
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
		<WagmiConfig client={ wagmiClient }>
			<RainbowKitProvider chains={ chains }>
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
