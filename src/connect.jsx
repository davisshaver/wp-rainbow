import { ConnectButton } from '@rainbow-me/rainbowkit';
import { __ } from '@wordpress/i18n';
import {
	useAccount,
	useDisconnect,
	useEnsName,
	useNetwork,
	useSignMessage,
} from 'wagmi';
import stylePropType from 'react-style-proptype';
import { SiweMessage } from 'siwe';
import PropTypes from 'prop-types';

const { ADMIN_URL, LOGGED_IN, LOGIN_API, NONCE_API, REDIRECT_URL, SITE_TITLE } =
	wpRainbowData;

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
	const { address, connector: activeConnector } = useAccount();
	const { chain } = useNetwork();
	const { signMessageAsync } = useSignMessage();
	const { data: ensName, isSuccess: isENSSuccess } = useEnsName( {
		address,
		chainId: 1,
	} );
	const { disconnectAsync } = useDisconnect();

	const signIn = React.useCallback( async () => {
		try {
			if ( ! address || ! chain?.id ) {
				return;
			}
			if ( LOGGED_IN ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				return;
			}
			if ( window?.signingIn.length > 1 ) {
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
			const message = new SiweMessage( siwePayload );
			const signature = await signMessageAsync( {
				message: message.prepareMessage(),
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
				// Handle errors as strings or as encoded JSON strings.
				let parsedError = null;
				const rawError = await verifyRes.json();
				try {
					parsedError = JSON.parse( rawError );
				} catch {
					parsedError = rawError;
				}
				if ( typeof parsedError === 'string' ) {
					onError( parsedError );
					setState( ( x ) => ( {
						...x,
						error: parsedError,
						loading: false,
					} ) );
				} else if ( typeof parsedError === 'object' ) {
					if (
						parsedError?.type === 'redirect' &&
						parsedError.redirectURL
					) {
						window.location = parsedError.redirectURL;
					}
				}
			}
		} catch ( error ) {
			setState( ( x ) => ( { ...x, error, loading: false } ) );
		}
	}, [ address, chain, ensName ] );

	const [ triggeredLogin, setTriggeredLogin ] = React.useState( false );
	React.useEffect( () => {
		if ( activeConnector && address && isENSSuccess && ! triggeredLogin ) {
			window.signingIn = ! window.signingIn
				? [ true ]
				: [ true, ...window.signingIn ];
			signIn();
			setTriggeredLogin( true );
		} else if ( ! address && state.address && ! window.signingOut ) {
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

	return (
		<ConnectButton.Custom>
			{ ( { account, openAccountModal, openConnectModal } ) => {
				let button = null;
				if ( state.error ) {
					button = (
						<div
							className={ buttonClassName }
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
							className={ buttonClassName }
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
							className={ buttonClassName }
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
