/* eslint-disable react/prop-types, @wordpress/no-unsafe-wp-apis, react/jsx-props-no-spreading */
import classnames from 'classnames';

import {
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
	__experimentalUseBorderProps as useBorderProps,
	__experimentalUseColorProps as useColorProps,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { useCallback } from '@wordpress/element';
import {
	Button,
	ButtonGroup,
	PanelBody,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import WPRainbow from '../../src/provider';
import './editor.scss';

function WidthPanel( { selectedWidth, setAttributes } ) {
	function handleChange( newWidth ) {
		const width = selectedWidth === newWidth ? undefined : newWidth;
		setAttributes( { width } );
	}
	return (
		<PanelBody title={ __( 'Width settings' ) }>
			<ButtonGroup aria-label={ __( 'Button width' ) }>
				{ [ 25, 50, 75, 100 ].map( ( widthValue ) => (
					<Button
						key={ widthValue }
						isSmall
						onClick={ () => handleChange( widthValue ) }
						variant={
							widthValue === selectedWidth ? 'primary' : undefined
						}
					>
						{ widthValue }%
					</Button>
				) ) }
			</ButtonGroup>
		</PanelBody>
	);
}

export default function Edit( { attributes, className, setAttributes } ) {
	const {
		checkWalletText,
		errorText,
		loginText,
		redirectURL,
		width,
	} = attributes;
	const onSetLoginText = useCallback(
		( value ) => {
			setAttributes( { loginText: value } );
		},
		[ setAttributes ]
	);
	const onSetErrorText = useCallback(
		( value ) => {
			setAttributes( { errorText: value } );
		},
		[ setAttributes ]
	);
	const onSetCheckWalletText = useCallback(
		( value ) => {
			setAttributes( { checkWalletText: value } );
		},
		[ setAttributes ]
	);
	const onSetRedirectURL = useCallback(
		( value ) => {
			setAttributes( { redirectURL: value } );
		},
		[ setAttributes ]
	);
	const borderProps = useBorderProps( attributes );
	const colorProps = useColorProps( attributes );
	const spacingProps = useSpacingProps( attributes );
	const { className: blockClassName, ...blockProps } = useBlockProps();

	return (
		<>
			<div
				className={ `${ blockClassName } wp-block-button` }
				{ ...blockProps }
			>
				<WPRainbow
					buttonClassName={ classnames(
						'wp-block-button__link',
						className,
						colorProps.className,
						borderProps.className
					) }
					checkWalletText={ checkWalletText }
					containers
					containerClassName={ classnames(
						'wp-block-button',
						blockClassName,
						{
							[ `has-custom-width wp-block-button__width-${ width }` ]: width,
							[ `has-custom-font-size` ]: blockProps.style
								.fontSize,
						}
					) }
					errorText={ errorText }
					loginText={ loginText }
					mockLogin
					outerContainerClassName={ classnames( 'wp-block-buttons' ) }
					style={ {
						...borderProps.style,
						...colorProps.style,
						...spacingProps.style,
					} }
				/>
			</div>
			<InspectorControls>
				<PanelBody title={ __( 'Functionality' ) }>
					<TextControl
						label={ __( 'Redirect URL' ) }
						onChange={ onSetRedirectURL }
						type="url"
						value={ redirectURL || '' }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Text Settings' ) }>
					<TextControl
						label={ __( 'Log In' ) }
						onChange={ onSetLoginText }
						value={ loginText || '' }
					/>
					<TextControl
						label={ __( 'Check Wallet' ) }
						onChange={ onSetCheckWalletText }
						value={ checkWalletText || '' }
					/>
					<TextControl
						label={ __( 'Error' ) }
						onChange={ onSetErrorText }
						value={ errorText || '' }
					/>
				</PanelBody>
				<WidthPanel
					selectedWidth={ width }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
		</>
	);
}
