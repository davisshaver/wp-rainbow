import { render, Component, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { useForm, useFieldArray } from 'react-hook-form';

function WPRainbowSettings() {
	const {
		control,
		register,
		handleSubmit,
		watch,
		setValue,
		formState: { errors },
	} = useForm();
	const { fields, append, remove } = useFieldArray( {
		control,
		name: 'userAttributesMapping',
	} );
	const setUserRoles = watch( 'wp_rainbow_field_set_user_roles' );
	const {
		fields: erc1155Fields,
		append: erc1155FieldsAppend,
		remove: erc1155FieldsRemove,
	} = useFieldArray( {
		control,
		name: 'roleToIDMapping',
	} );
	const [ state, setState ] = useState( { loaded: false } );
	const checkboxes = [
		'wp_rainbow_field_override_users_can_register',
		'wp_rainbow_field_disable_passwords_for_wp_users',
		'wp_rainbow_field_force_logout',
		'wp_rainbow_field_cool_mode',
		'wp_rainbow_field_disable_overwriting_user_meta',
		'wp_rainbow_field_disable_user_role_updates_on_login',
		'wp_rainbow_field_set_user_roles',
		'wp_rainbow_field_compact_modal',
	];

	const onSubmit = ( settings ) => {
		const data = Object.keys( settings ).reduce(
			( allSettings, setting ) => {
				if ( checkboxes.includes( setting ) ) {
					allSettings[ setting ] = settings[ setting ] ? 'on' : 'off';
				} else if (
					setting === 'userAttributesMapping' ||
					setting === 'roleToIDMapping'
				) {
					allSettings[ setting ] = settings[ setting ].reduce(
						( mapping, { key, value } ) => {
							if ( key && value ) {
								return `${ mapping }${ key },${ value }\n`;
							}
							return mapping;
						},
						''
					);
				} else {
					allSettings[ setting ] = settings[ setting ];
				}
				return allSettings;
			},
			{}
		);

		data.wp_rainbow_field_user_attributes_mapping =
			data.userAttributesMapping.trim();
		delete data.userAttributesMapping;

		data.wp_rainbow_role_to_id_mapping_field = data.roleToIDMapping.trim();
		delete data.roleToIDMapping;

		apiFetch( {
			path: '/wp-rainbow/v1/settings',
			headers: {
				'X-WP-Nonce': window?.wpRainbowSettings?.nonce,
			},
			method: 'POST',
			data,
		} ).then( ( serverSettings ) => {
			let message = 'Settings Saved';
			// Detect if users were logged out and update the field accordingly.
			if (
				data.wp_rainbow_field_force_logout === 'on' &&
				! serverSettings.wp_rainbow_field_force_logout
			) {
				setValue( 'wp_rainbow_field_force_logout', false );
				message = 'Settings Saved and Users Logged Out';
			}
			setState( {
				...state,
				message,
				submitting: false,
			} );
			setTimeout(
				() =>
					window.scrollTo( {
						behavior: 'smooth',
						left: 0,
						top: 0,
					} ),
				10
			);
		} );
	};

	useEffect( () => {
		apiFetch( {
			path: '/wp-rainbow/v1/settings',
			headers: {
				'X-WP-Nonce': window?.wpRainbowSettings?.nonce,
			},
		} ).then( ( settings ) => {
			const userAttributesMapping =
				settings?.wp_rainbow_field_user_attributes_mapping
					.split( '\n' )
					.map( ( line ) => {
						const [ key, value ] = line
							.split( ',' )
							.map( ( item ) => item.trim() );
						return { key, value };
					} );
			const roleToIDMapping =
				settings?.wp_rainbow_role_to_id_mapping_field
					.split( '\n' )
					.map( ( line ) => {
						const [ key, value ] = line
							.split( ',' )
							.map( ( item ) => item.trim() );
						return { key, value };
					} );
			setValue( 'roleToIDMapping', roleToIDMapping );
			setValue( 'userAttributesMapping', userAttributesMapping );
			setState( {
				...state,
				loaded: true,
				settings,
			} );
		} );
	}, [] );

	const { loaded, message, settings: initialSettings, submitting } = state;
	if ( ! loaded ) {
		return (
			<div className="wrap">
				<h1>{ __( 'RainbowKit Login Settings', 'wp-rainbow' ) }</h1>
				<Spinner />
			</div>
		);
	}
	return (
		<div className="wrap">
			<h1>{ __( 'RainbowKit Login Settings', 'wp-rainbow' ) }</h1>
			{ message && (
				<div
					id="setting-error-wp_rainbow_message"
					className="notice notice-success settings-error is-dismissible"
				>
					<p>
						<strong>{ message }</strong>
					</p>
					<button
						type="button"
						className="notice-dismiss"
						onClick={ () => {
							setState( {
								...state,
								message: '',
							} );
						} }
					>
						<span className="screen-reader-text">
							{ __( 'Dismiss this notice.', 'wp-rainbow' ) }
						</span>
					</button>
				</div>
			) }
			<h2>{ __( 'Network Options', 'wp-rainbow' ) }</h2>
			<form onSubmit={ handleSubmit( onSubmit ) }>
				<table className="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_infura_id">
									{ __( 'Infura ID', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_infura_id"
									size="40"
									type="text"
									{ ...register(
										'wp_rainbow_field_infura_id'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_infura_id
									}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_infura_network">
									{ __( 'Infura Network', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<select
									{ ...register(
										'wp_rainbow_field_infura_network'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_infura_network ||
										'mainnet'
									}
									id="wp_rainbow_field_infura_network"
								>
									<option value="mainnet">Mainnet</option>
									<option value="ropsten">Ropsten</option>
									<option value="kovan">Kovan</option>
									<option value="rinkeby">Rinkeby</option>
									<option value="goerli">Goerli</option>
								</select>
								<p>
									<em>
										<small>
											{ __(
												'All contract validation will be performed on this network.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h2>{ __( 'Appearance', 'wp-rainbow' ) }</h2>
							</th>
							<td />
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_rainbowkit_theme">
									{ __(
										'RainbowKit Base Theme',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<select
									{ ...register(
										'wp_rainbow_field_rainbowkit_theme'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_rainbowkit_theme ||
										'lightTheme'
									}
									id="wp_rainbow_field_rainbowkit_theme"
								>
									<option value="lightTheme">
										Light Theme
									</option>
									<option value="darkTheme">
										Dark Theme
									</option>
									<option value="midnightTheme">
										Midnight Theme
									</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_compact_modal">
									{ __( 'Use Compact Modal', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_compact_modal"
									type="checkbox"
									{ ...register(
										'wp_rainbow_field_compact_modal'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_compact_modal ===
										'on'
									}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h2>
									{ __(
										'Registration & Login Options',
										'wp-rainbow'
									) }
								</h2>
							</th>
							<td />
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_override_users_can_register">
									{ __(
										'Always Allow Registration',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_override_users_can_register"
									{ ...register(
										'wp_rainbow_field_override_users_can_register'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_override_users_can_register ===
										'on'
									}
									type="checkbox"
								/>
								<p>
									<em>
										<small>
											{ __(
												'If enabled, this setting will override the General Settings membership option.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_set_user_roles">
									{ __(
										'Set User Roles On Account Creation',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_set_user_roles"
									{ ...register(
										'wp_rainbow_field_set_user_roles'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_set_user_roles ===
										'on'
									}
									type="checkbox"
								/>
								<p>
									<em>
										<small>
											{ __(
												'If enabled, RainbowKit Login will set user roles on account creation and login. The default role is ',
												'wp-rainbow'
											) }
											{ `${ window?.wpRainbowSettings?.default_role }` }
											{ __(
												'. You can override this for RainbowKit Login users below. ',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_default_user_role">
									{ __(
										'Default RainbowKit Login User Role',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_default_user_role"
									size="40"
									type="text"
									{ ...register(
										'wp_rainbow_field_default_user_role'
									) }
									disabled={
										setUserRoles === false ||
										( setUserRoles === undefined &&
											initialSettings?.wp_rainbow_field_set_user_roles !==
												'on' )
									}
									defaultValue={
										initialSettings?.wp_rainbow_field_default_user_role
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If set, this user role will be used for RainbowKit Login users instead of the default role. Only applies if roles are set on account creation.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_disable_user_role_updates_on_login">
									{ __(
										'Prevent User Role Updates on Login',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_disable_user_role_updates_on_login"
									{ ...register(
										'wp_rainbow_field_disable_user_role_updates_on_login'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_disable_user_role_updates_on_login ===
										'on'
									}
									disabled={
										setUserRoles === false ||
										( setUserRoles === undefined &&
											initialSettings?.wp_rainbow_field_set_user_roles !==
												'on' )
									}
									type="checkbox"
								/>
								<p>
									<em>
										<small>
											{ __(
												'Prevent user roles from being updated on login. Only applies if roles are set on account creation.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_disable_passwords_for_wp_users">
									{ __( 'Disable Passwords', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_disable_passwords_for_wp_users"
									type="checkbox"
									{ ...register(
										'wp_rainbow_field_disable_passwords_for_wp_users'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_disable_passwords_for_wp_users ===
										'on'
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If enabled, non-admin RainbowKit Login users will be passwordless.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_redirect_url">
									{ __( 'Redirect URL', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_redirect_url"
									size="40"
									type="url"
									{ ...register(
										'wp_rainbow_field_redirect_url'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_redirect_url
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If set, users will be redirected here on login instead of the admin. (Block redirect configuration will take precedent if set.)',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h2>
									{ __(
										'ENS Text Records to User Meta Mapping',
										'wp-rainbow'
									) }
								</h2>
							</th>
							<td />
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_disable_overwriting_user_meta">
									{ __(
										'Disable Overwriting Fields',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_disable_overwriting_user_meta"
									type="checkbox"
									{ ...register(
										'wp_rainbow_field_disable_overwriting_user_meta'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_disable_overwriting_user_meta ===
										'on'
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If enabled, ENS text records will only be synced to user meta if there is not already a value set. Existing values will not be overwritten.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h3>
									{ __(
										'Text Record Key to User Meta Key',
										'wp-rainbow'
									) }
								</h3>
							</th>
							<td />
						</tr>
						<tr>
							<td>
								<p style={ { fontWeight: 400 } }>
									{ __(
										'ENS text records will be synced to user meta on each login.',
										'wp-rainbow'
									) }
								</p>
							</td>
							<td>
								<small>
									<em>
										{ __(
											'`user_email` is a special case and will be used to set the user email address. `user_url` will be used to set the user URL.',
											'wp-rainbow'
										) }
									</em>
								</small>
							</td>
						</tr>
						{ fields.map( ( { key, value, id }, index ) => (
							<tr key={ id }>
								<th
									scope="row"
									style={ {
										fontWeight: 'normal',
									} }
								>
									<input
										{ ...register(
											`userAttributesMapping.${ index }.key`
										) }
										defaultValue={ key }
										placeholder="ENS Text Record Key"
										type="text"
										size={ 40 }
									/>
								</th>
								<td>
									<input
										{ ...register(
											`userAttributesMapping.${ index }.value`,
											{
												pattern: /[A-Za-z]{3}/,
											}
										) }
										type="text"
										size={ 40 }
										placeholder="WordPress User Meta Key"
										defaultValue={ value }
										style={ {
											marginRight: '20px',
										} }
									/>
									<button
										type="button"
										className="button button-secondary"
										onClick={ () => remove( index ) }
									>
										Delete
									</button>
								</td>
							</tr>
						) ) }
						<tr>
							<th scope="row" />
							<td>
								<button
									type="button"
									name="do_new_application_password"
									id="do_new_application_password"
									className="button button-secondary"
									onClick={ () =>
										append( [
											{
												key: '',
												value: '',
											},
										] )
									}
								>
									Add New User Attribute
								</button>
							</td>
						</tr>
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h2>{ __( 'Token-Gating', 'wp-rainbow' ) }</h2>
								<h3>{ __( 'ERC-721', 'wp-rainbow' ) }</h3>
							</th>
							<td />
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_required_token">
									{ __(
										'Required Token Contract',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_required_token"
									size="40"
									type="text"
									{ ...register(
										'wp_rainbow_field_required_token'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_required_token
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If set, users will be required to own an NFT from this contract',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_required_token_quantity">
									{ __(
										'Required Token Quantity',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_required_token_quantity"
									size="40"
									type="number"
									{ ...register(
										'wp_rainbow_field_required_token_quantity'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_required_token_quantity
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'Optionally set the number of tokens required to be held by user',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						{ window?.wpRainbowSettings?.plugins?.includes(
							'erc-1155-roles'
						) && (
							<>
								<tr>
									<th scope="row" style={ { padding: '0' } }>
										<h3>
											{ __( 'ERC-1155', 'wp-rainbow' ) }
										</h3>
									</th>
									<td />
								</tr>
								{ /* <table className="form-table" role="presentation"> */ }
								{ /*	<tbody> */ }
								<tr>
									<th scope="row">
										<label htmlFor="wp_rainbow_customizations_erc_1155_contract_field">
											ERC-1155 Contract
										</label>
									</th>
									<td>
										<input
											id="wp_rainbow_customizations_erc_1155_contract_field"
											type="text"
											{ ...register(
												'wp_rainbow_customizations_erc_1155_contract_field'
											) }
											defaultValue={
												initialSettings?.wp_rainbow_customizations_erc_1155_contract_field
											}
											size={ 40 }
										/>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label htmlFor="wp_rainbow_redirect_url_field">
											Redirect URL
										</label>
									</th>
									<td>
										<input
											id="wp_rainbow_redirect_url_field"
											type="url"
											{ ...register(
												'wp_rainbow_redirect_url_field'
											) }
											defaultValue={
												initialSettings?.wp_rainbow_redirect_url_field
											}
											size={ 40 }
										/>
										<p>
											<em>
												<small>
													If set, users will be
													redirected to this URL if
													they don't hold one of the
													tokens specified below.
												</small>
											</em>
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										WordPress User Roles to ERC-1155 IDs
										Mapping
									</th>
								</tr>
								{ erc1155Fields.map(
									( { key, value, id }, index ) => (
										<tr key={ id }>
											<th
												scope="row"
												style={ {
													fontWeight: 'normal',
												} }
											>
												<input
													{ ...register(
														`roleToIDMapping.${ index }.key`
													) }
													defaultValue={ key }
													placeholder="WordPress User Role"
													type="text"
													size={ 40 }
												/>
											</th>
											<td>
												<input
													{ ...register(
														`roleToIDMapping.${ index }.value`
													) }
													type="text"
													size={ 40 }
													placeholder="ERC-1155 Token ID"
													defaultValue={ value }
													style={ {
														marginRight: '20px',
													} }
												/>
												<button
													type="button"
													className="button button-secondary"
													onClick={ () =>
														erc1155FieldsRemove(
															index
														)
													}
												>
													Delete
												</button>
											</td>
										</tr>
									)
								) }
								<tr>
									<th scope="row" />
									<td>
										<button
											type="button"
											name="do_new_role_to_id_mapping"
											id="do_new_role_to_id_mapping"
											className="button button-secondary"
											onClick={ () =>
												erc1155FieldsAppend( [
													{
														key: '',
														value: '',
													},
												] )
											}
										>
											Add New Role to ID Mapping
										</button>
									</td>
								</tr>
							</>
						) }
						<tr>
							<th scope="row" style={ { padding: '0' } }>
								<h2>
									{ __( 'Advanced Settings', 'wp-rainbow' ) }
								</h2>
							</th>
							<td />
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_force_logout">
									{ __(
										'Clear Existing Sessions',
										'wp-rainbow'
									) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_force_logout"
									type="checkbox"
									{ ...register(
										'wp_rainbow_field_force_logout'
									) }
									defaultValue={
										initialSettings?.wp_rainbow_field_force_logout ===
										'on'
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If checked, existing sessions will be logged out on save.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label htmlFor="wp_rainbow_field_cool_mode">
									{ __( 'Enable Cool Mode', 'wp-rainbow' ) }
								</label>
							</th>
							<td>
								<input
									id="wp_rainbow_field_cool_mode"
									type="checkbox"
									{ ...register(
										'wp_rainbow_field_cool_mode'
									) }
									defaultChecked={
										initialSettings?.wp_rainbow_field_cool_mode ===
										'on'
									}
								/>
								<p>
									<em>
										<small>
											{ __(
												'If enabled, RainbowKit will use "Cool Mode" effects.',
												'wp-rainbow'
											) }
										</small>
									</em>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<p className="submit">
					<input
						type="submit"
						name="submit"
						id="submit"
						disabled={ submitting }
						className="button button-primary"
						value="Save Settings"
					/>
					{ submitting && <Spinner /> }
				</p>
			</form>
		</div>
	);
}

document.addEventListener( 'DOMContentLoaded', () => {
	render(
		<WPRainbowSettings />,
		document.getElementById( 'wp-rainbow-settings-page' )
	);
} );
