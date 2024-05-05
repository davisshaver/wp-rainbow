import { createRoot } from '@wordpress/element';
import './index.scss';
import WPRainbow from '../../src/provider';

const { LOGOUT_URL } = wpRainbowData;

const loginBlocks = document.querySelectorAll( '.wp-block-wp-rainbow-login' );
if ( loginBlocks ) {
	loginBlocks.forEach( ( wpRainbowBlock ) => {
		const blockDetails = wpRainbowBlock.firstChild;
		let style = {};
		try {
			style = JSON.parse( blockDetails.dataset.style );
		} catch {
			// Continue regardless of error.
		}
		const root = createRoot( wpRainbowBlock );
		root.render(
			<WPRainbow
				buttonClassName={ blockDetails.dataset.buttonClassName }
				checkWalletText={ blockDetails.dataset.checkWalletText }
				containers
				containerClassName={ blockDetails.dataset.containerClassName }
				errorText={ blockDetails.dataset.errorText }
				loginText={ blockDetails.dataset.loginText }
				outerContainerClassName={
					blockDetails.dataset.outerContainerClassName
				}
				onLogout={ () => {
					const logoutURL = new URL( LOGOUT_URL );
					const currentURL = new URL( window.location.href );
					// Add a 'wp-rainbow-logout' query parameter to the current URL.
					currentURL.searchParams.append( 'wp-rainbow-logout', 'true' );
					logoutURL.searchParams.append(
						'redirect_to',
						encodeURI( currentURL )
					);
					window.location = logoutURL.toString();
				} }
				redirectBoomerang={
					blockDetails.dataset.redirectBoomerang === 'true'
				}
				redirectURL={ blockDetails.dataset.redirectUrl }
				style={ style }
			/>
		);
	} );
}
