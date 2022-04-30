export const addErrorMessage = ( errorMessage ) => {
	const loginElement = document.getElementById( 'login' );
	const loginForm = document.getElementById( 'loginform' );
	if ( ! loginElement || ! loginForm ) {
		return;
	}
	if ( document.getElementById( 'login_error' ) ) {
		document.getElementById( 'login_error' ).remove();
	}
	const loginError = document.createElement( 'div' );
	loginError.id = 'login_error';
	loginError.innerHTML = `<strong>Error</strong>: ${ errorMessage }`;
	loginElement.insertBefore( loginError, loginForm );
};

export default addErrorMessage;
