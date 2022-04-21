import { ConnectButton } from '@rainbow-me/rainbowkit';
import { __ } from '@wordpress/i18n';
import { useAccount, useNetwork, useSignMessage } from 'wagmi';
import { SiweMessage } from 'siwe';
import PropTypes from 'prop-types';

const {
	ADMIN_URL,
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
 * @param            props.container
 * @param            props.containerClassName
 * @param            props.style
 * @param            props.containers
 * @param            props.outerContainerClassName
 * @param            props.loginText
 * @param            props.checkWalletText
 * @param            props.errorText
 * @param            props.redirectURL
 * @param            props.loggedIn
 */
export function WPRainbowConnect( {
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
	const [ state, setState ] = React.useState( {} );
	const [ { data: accountData, loading } ] = useAccount( {
		fetchEns: true,
	} );
	const [ { data: networkData } ] = useNetwork();
	const [ , signMessage ] = useSignMessage();

	const [ hasLoadedFirstTime, setHasLoadedFirstTime ] = React.useState(
		false
	);
	const [ isLoadingSecondTime, setIsLoadingSecondTime ] = React.useState(
		false
	);
	const [ hasLoadedSecondTime, setHasLoadedSecondTime ] = React.useState(
		false
	);

	React.useEffect( () => {
		if ( accountData && ! loading && ! hasLoadedFirstTime ) {
			setHasLoadedFirstTime( true );
		}
		if ( accountData && loading && hasLoadedFirstTime ) {
			setIsLoadingSecondTime( true );
		}
		if (
			accountData &&
			! loading &&
			hasLoadedFirstTime &&
			isLoadingSecondTime &&
			! hasLoadedSecondTime
		) {
			setIsLoadingSecondTime( false );
			setHasLoadedSecondTime( true );
		}
	}, [
		accountData,
		loading,
		hasLoadedFirstTime,
		isLoadingSecondTime,
		hasLoadedSecondTime,
	] );

	const signIn = React.useCallback( async () => {
		try {
			const address = accountData?.address;
			const chainId = networkData?.chain?.id;
			if ( ! address || ! chainId ) {
				return;
			}
			setState( ( x ) => ( { ...x, error: undefined, loading: true } ) );
			const nonceRes = await fetch( NONCE_API );
			const nonce = await nonceRes.json();
			const siwePayload = {
				address,
				chainId,
				domain: window.location.host,
				issuedAt: new Date().toISOString(),
				nonce,
				statement: `Log In with Ethereum to ${ SITE_TITLE }`,
				uri: window.location.origin,
				version: '1',
			};
			if ( loggedIn ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				return;
			}
			const message = new SiweMessage( siwePayload );
			const signRes = await signMessage( {
				message: message.prepareMessage(),
			} );
			if ( mockLogin ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				return;
			}
			if ( signRes.error ) {
				onError(
					__( 'Signature request failed or rejected.', 'wp-rainbow' )
				);
				setState( ( x ) => ( {
					...x,
					error: signRes.error,
					loading: false,
				} ) );
				return;
			}
			const verifyRes = await fetch( LOGIN_API, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					address,
					displayName: accountData?.ens?.name ?? address,
					nonce,
					signature: signRes.data,
					siwePayload,
				} ),
			} );
			if ( verifyRes.ok ) {
				setState( ( x ) => ( { ...x, address, loading: false } ) );
				onLogin();
				window.location = redirectURL || REDIRECT_URL || ADMIN_URL;
			} else {
				const error = await verifyRes.json();
				onError( error );
				setState( ( x ) => ( { ...x, error, loading: false } ) );
			}
		} catch ( error ) {
			setState( ( x ) => ( { ...x, error, loading: false } ) );
		}
	}, [ accountData, networkData, loading ] );

	const [ triggeredLogin, setTriggeredLogin ] = React.useState( false );
	React.useEffect( () => {
		if ( accountData && ! triggeredLogin && hasLoadedSecondTime ) {
			signIn();
			setTriggeredLogin( true );
		} else if ( ! accountData && state.address ) {
			setState( {} );
			setTriggeredLogin( false );
			onLogout();
		}
	}, [ accountData, state.address, loading, hasLoadedSecondTime ] );

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
							role="button"
							style={ style }
						>
							{ errorText ||
								__(
									'Log In Error, Click to Refresh',
									'wp-rainbow'
								) }
						</div>
					);
				} else if ( account ) {
					let loginText = __( 'Continue Log In with Ethereum' );
					if ( state.address ) {
						loginText = `${ __( 'Logged In as ' ) } ${
							account.displayName
						}`;
					} else if ( state.loading ) {
						loginText =
							checkWalletText ||
							__( 'Check Wallet to Sign Message' );
					}
					button = (
						<div
							className={ buttonClassName }
							onClick={
								state.address || state.loading
									? openAccountModal
									: signIn
							}
							role="button"
							style={ style }
						>
							{ loginText }
						</div>
					);
				} else {
					button = (
						<div
							className={ buttonClassName }
							onClick={ () => {
								// Make sure we don't have an active signing attempt.
								setState( {} );
								setTriggeredLogin( false );
								openConnectModal();
							} }
							role="button"
							style={ style }
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

WPRainbowConnect.propTypes = {
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
	style: PropTypes.object,
};

export default WPRainbowConnect;
