import { ConnectButton } from '@rainbow-me/rainbowkit';
import { __ } from '@wordpress/i18n';
import {
	useAccount,
	useDisconnect,
	useEnsName,
	usePublicClient,
	useSignMessage,
	useConnections,
} from 'wagmi';
import stylePropType from 'react-style-proptype';
import { prepareMessage } from 'simple-siwe';
import PropTypes from 'prop-types';

const {
	ADMIN_URL,
	ATTRIBUTES,
	LOGGED_IN,
	LOGIN_API,
	NONCE_API,
	REDIRECT_URL,
	SITE_TITLE,
} = wpRainbowData;

/**
 * WP Rainbow Connect Button.
 *
 * @param {Object}   props                         Props for WP Rainbow Connect Button.
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
   @param {boolean}  props.redirectBoomerang       Enable redirecting to current page.
 * @param {string}   props.redirectURL             Redirect URL override.
 */
export function WPRainbowConnect( {
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
	const [ state, setState ] = React.useState( {} );
	const { address, chain, connector: activeConnector } = useAccount();
	const { signMessageAsync } = useSignMessage();
	const { data: ensName, isSuccess: isENSSuccess } = useEnsName( {
		address,
		chainId: 1,
	} );
	const connections = useConnections();

	const provider = usePublicClient( {
		chainId: 1,
	} );

	const { disconnect, disconnectAsync } = useDisconnect();

	const signIn = React.useCallback( async () => {
		try {
			if ( ! address || ! chain?.id ) {
				return;
			}
			if ( LOGGED_IN ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				return;
			}
			if ( window.signingIn && window.signingIn.length > 1 ) {
				return;
			}
			setState( ( x ) => ( { ...x, error: undefined, loading: true } ) );
			const nonceRes = await fetch( NONCE_API );
			const nonce = await nonceRes.json();
			const siwePayload = {
				address,
				chainId: chain.id,
				domain: window.location.host,
				issuedAt: new Date().toISOString(),
				nonce,
				statement: `Log In with Ethereum to ${ SITE_TITLE }`,
				uri: window.location.origin,
				version: '1',
			};
			const message = prepareMessage( siwePayload );
			const attributes = {};
			if ( ensName ) {
				try {
					await ATTRIBUTES.forEach( async ( attributeKey ) => {
						const attributeValue = await provider.getEnsText( {
							name: ensName,
							key: attributeKey,
						} );
						attributes[ attributeKey ] = attributeValue;
					} );
				} catch ( error ) {
					// eslint-disable-next-line no-console
					console.error( error );
				}
			}
			const signature = await signMessageAsync( {
				message,
			} );
			if ( mockLogin ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				return;
			}

			const verifyRes = await fetch( LOGIN_API, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					address,
					attributes,
					displayName: ensName ?? address,
					nonce,
					signature,
					siwePayload,
				} ),
			} );
			if ( verifyRes.ok ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				onLogin();
				if ( redirectBoomerang ) {
					window.location.reload();
				} else {
					window.location = redirectURL || REDIRECT_URL || ADMIN_URL;
				}
			} else {
				const error = await verifyRes.json();
				onError( error );
				setState( ( x ) => ( { ...x, error, loading: false } ) );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( error );
			setState( ( x ) => ( { ...x, error, loading: false } ) );
		}
	}, [ address, chain, ensName ] );

	const [ triggeredLogin, setTriggeredLogin ] = React.useState( false );
	React.useEffect( () => {
		const urlParams = new URLSearchParams( window.location.search );
		// This code is NUTS but Metamask doesn't support disconnecting apparently?
		// Keep an eye on these issues:
		// - https://github.com/wevm/wagmi/issues/684
		// - https://github.com/MetaMask/metamask-extension/issues/10353
		if (
			activeConnector &&
			activeConnector.id.includes( 'metamask' ) &&
			! triggeredLogin &&
			( urlParams.has( 'wp-rainbow-logout' ) ||
				( urlParams.has( 'loggedout' ) &&
					! urlParams.has( 'wp-rainbow-loggedout' ) ) )
		) {
			connections.forEach( ( { connector } ) => {
				disconnect( { connector } );
			} );
			// Remove the query parameter from the URL.
			if ( urlParams.has( 'wp-rainbow-logout' ) ) {
				urlParams.delete( 'wp-rainbow-logout' );
			}
			if ( urlParams.has( 'loggedout' ) ) {
				urlParams.append( 'wp-rainbow-loggedout', 'true' );
			}
			const paramsString = urlParams.toString();
			// Set the new URL without the query parameter.
			window.history.replaceState(
				{},
				document.title,
				`${ window.location.pathname }${
					paramsString ? `?${ paramsString }` : ''
				}`
			);
			return;
		}
		if ( activeConnector && address && isENSSuccess && ! triggeredLogin ) {
			window.signingIn = ! window.signingIn
				? [ true ]
				: [ true, ...window.signingIn ];
			signIn();
			setTriggeredLogin( true );
		} else if ( ! address && state.address && ! window.signingOut ) {
			connections.forEach( ( { connector } ) => {
				disconnect( { connector } );
			} );
			window.signingOut = true;
			setState( {} );
			setTriggeredLogin( false );
			onLogout();
		} else if (
			! address &&
			! ( LOGGED_IN || state.address ) &&
			window?.signingIn &&
			window.signingIn.length > 0
		) {
			window.signingIn = []; // Clear signin attempt
		}
	}, [ address, isENSSuccess, state.address ] );

	const buttonClassNameWithState = React.useMemo( () => {
		let buttonClassNameEnriched = buttonClassName;
		buttonClassNameEnriched += ' wpr-button';
		if ( LOGGED_IN ) {
			buttonClassNameEnriched += ' wpr-logged-in';
		}
		if ( state.loading ) {
			buttonClassNameEnriched += ' wpr-signing-in';
		}
		if ( state.error ) {
			buttonClassNameEnriched += ' wpr-error';
		}
		return buttonClassNameEnriched;
	}, [ buttonClassName, state.error, state.loading ] );

	return (
		<ConnectButton.Custom>
			{ ( { account, openAccountModal, openConnectModal } ) => {
				let button = null;
				if ( state.error ) {
					button = (
						<div
							className={ buttonClassNameWithState }
							onClick={ () => {
								window.location = window.location.href;
							} }
							onKeyDown={ ( e ) => {
								if ( e.keyCode === 13 ) {
									window.location = window.location.href;
								}
							} }
							role="button"
							style={ style }
							tabIndex={ 0 }
						>
							{ errorText ||
								__(
									'Log In Error, Click to Refresh',
									'wp-rainbow'
								) }
						</div>
					);
				} else if ( activeConnector && account ) {
					let loginButtonText = __( 'Continue Log In with Ethereum' );
					if ( LOGGED_IN || state.address ) {
						loginButtonText = `${ __( 'Logged In as ' ) } ${
							account.displayName
						}`;
					} else if ( state.loading ) {
						loginButtonText =
							checkWalletText ||
							__( 'Check Wallet to Sign Message' );
					}
					const triggerContinueLogin = () => {
						if ( LOGGED_IN || state.address || state.loading ) {
							openAccountModal();
						} else {
							signIn();
						}
					};
					button = (
						<div
							className={ buttonClassNameWithState }
							onClick={ triggerContinueLogin }
							onKeyDown={ ( e ) => {
								if ( e.keyCode === 13 ) {
									triggerContinueLogin();
								}
							} }
							role="button"
							style={ style }
							tabIndex={ 0 }
						>
							{ loginButtonText }
						</div>
					);
				} else {
					const triggerLogin = () => {
						// Make sure we don't have an active signing attempt.
						setState( {} );
						setTriggeredLogin( false );
						// This is a little weird, but since RainbowKit autoconnects,
						// we need to make sure we're disconnected before logging in.
						disconnectAsync().then( openConnectModal );
					};
					button = (
						<div
							className={ buttonClassNameWithState }
							onClick={ () => {
								triggerLogin();
							} }
							onKeyDown={ ( e ) => {
								if ( e.keyCode === 13 ) {
									triggerLogin();
								}
							} }
							role="button"
							style={ style }
							tabIndex={ 0 }
						>
							{ loginText ||
								__( 'Log In with Ethereum', 'wp-rainbow' ) }
						</div>
					);
				}
				if ( containers ) {
					return (
						<div className={ outerContainerClassName }>
							<div className={ containerClassName }>
								{ button }
							</div>
						</div>
					);
				}
				return button;
			} }
		</ConnectButton.Custom>
	);
}

WPRainbowConnect.defaultProps = {
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

WPRainbowConnect.propTypes = {
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

export default WPRainbowConnect;
