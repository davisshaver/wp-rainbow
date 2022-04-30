import { __ } from '@wordpress/i18n';
import WPRainbow from './provider';
import { addErrorMessage } from './utilities/addErrorMessage';

const siteLoginText = (
	<p
		className="wp-rainbow help-text"
		style={ {
			fontSize: '12px',
			fontStyle: 'italic',
			marginBottom: '4px',
			marginTop: '4px',
			textAlign: 'center',
		} }
	>
		{ __( '- OR USE SITE LOGIN -', 'wp-rainbow' ) }
	</p>
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
	ReactDOM.render( LoginPageElement, wpRainbowButton );
}
