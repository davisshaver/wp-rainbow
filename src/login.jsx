import { __ } from '@wordpress/i18n';
import { createRoot } from 'react-dom/client';
import WPRainbow from './provider';
import { addErrorMessage } from './utilities/addErrorMessage';

const siteLoginText = (
	<div className="wp-rainbow-sso-or">
		<span>{ __( 'Or', 'wp-rainbow' ) }</span>
	</div>
);

const loginForm = document.getElementById( 'loginform' );

const LoginPageElement = (
	<>
		{ WPRainbow( {
			buttonClassName: 'button button-secondary button-hero',
			onError: addErrorMessage,
			onLogin: () => loginForm && loginForm.classList.add( 'logged-in' ),
			onLogout: () =>
				loginForm && loginForm.classList.remove( 'logged-in' ),
		} ) }
		{ siteLoginText }
	</>
);

const wpRainbowButton = document.getElementById( 'wp-rainbow-button' );

if ( wpRainbowButton ) {
	const root = createRoot( wpRainbowButton );
	root.render( LoginPageElement );
}
