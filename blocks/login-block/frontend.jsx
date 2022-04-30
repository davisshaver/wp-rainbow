import './index.scss';
import WPRainbow from '../../src/provider';

const { LOGOUT_URL } = wpRainbowData;

const loggedIn = document.body.classList.contains( 'logged-in' );
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
		ReactDOM.render(
			<WPRainbow
				buttonClassName={ blockDetails.dataset.buttonClassName }
				checkWalletText={ blockDetails.dataset.checkWalletText }
				containers
				containerClassName={ blockDetails.dataset.containerClassName }
				errorText={ blockDetails.dataset.errorText }
				loggedIn={ loggedIn }
				loginText={ blockDetails.dataset.loginText }
				outerContainerClassName={
					blockDetails.dataset.outerContainerClassName
				}
				onLogout={ () => {
					const logoutURL = new URL( LOGOUT_URL );
					logoutURL.searchParams.append(
						'redirect_to',
						encodeURI( window.location.href )
					);
					window.location = logoutURL.toString();
				} }
				redirectBoomerang={ !! blockDetails.dataset.redirectBoomerang }
				redirectURL={
					blockDetails.dataset.redirectURL || window.location.href
				}
				style={ style }
			/>,
			wpRainbowBlock
		);
	} );
}