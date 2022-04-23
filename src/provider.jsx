import {
	RainbowKitProvider,
	connectorsForWallets,
	getDefaultWallets,
} from '@rainbow-me/rainbowkit';
import { WagmiProvider, chain } from 'wagmi';
import { providers } from 'ethers';
import stylePropType from 'react-style-proptype';

import PropTypes from 'prop-types';
import { WPRainbowConnect } from './connect';

const { INFURA_ID, SITE_TITLE } = wpRainbowData;

const provider = ( { chainId } ) =>
	new providers.InfuraProvider( chainId, INFURA_ID );

const chains = [ { ...chain.mainnet, name: 'Ethereum' } ];

const wallets = getDefaultWallets( {
	appName: SITE_TITLE,
	chains,
	infuraId: INFURA_ID,
	jsonRpcUrl: ( { chainId } ) =>
		chains.find( ( x ) => x.id === chainId )?.rpcUrls?.[ 0 ] ??
		chain.mainnet.rpcUrls[ 0 ],
} );

const connectors = connectorsForWallets( wallets );

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
 * @param {string}   props.redirectURL             Redirect URL override.
 * @param {boolean}  props.loggedIn                Enabled logged in functionality.
 */
function WPRainbow( {
	buttonClassName,
	checkWalletText,
	containerClassName,
	containers,
	errorText,
	loggedIn,
	loginText,
	mockLogin,
	onError,
	onLogin,
	onLogout,
	outerContainerClassName,
	redirectURL,
	style,
} ) {
	return (
		<RainbowKitProvider chains={ chains }>
			<WagmiProvider
				autoConnect={ loggedIn }
				connectors={ connectors }
				provider={ provider }
			>
				<WPRainbowConnect
					buttonClassName={ buttonClassName }
					checkWalletText={ checkWalletText }
					containerClassName={ containerClassName }
					containers={ containers }
					errorText={ errorText }
					loggedIn={ loggedIn }
					loginText={ loginText }
					mockLogin={ mockLogin }
					onError={ onError }
					onLogin={ onLogin }
					onLogout={ onLogout }
					outerContainerClassName={ outerContainerClassName }
					redirectURL={ redirectURL }
					style={ style }
				/>
			</WagmiProvider>
		</RainbowKitProvider>
	);
}

WPRainbow.defaultProps = {
	buttonClassName: '',
	checkWalletText: '',
	containerClassName: '',
	containers: false,
	errorText: '',
	loggedIn: false,
	loginText: '',
	mockLogin: false,
	onError: () => {},
	onLogin: () => {},
	onLogout: () => {},
	outerContainerClassName: '',
	redirectURL: '',
	style: {},
};

WPRainbow.propTypes = {
	buttonClassName: PropTypes.string,
	checkWalletText: PropTypes.string,
	containerClassName: PropTypes.string,
	containers: PropTypes.bool,
	errorText: PropTypes.string,
	loggedIn: PropTypes.bool,
	loginText: PropTypes.string,
	mockLogin: PropTypes.bool,
	onError: PropTypes.func,
	onLogin: PropTypes.func,
	onLogout: PropTypes.func,
	outerContainerClassName: PropTypes.string,
	redirectURL: PropTypes.string,
	style: stylePropType,
};

export default WPRainbow;
