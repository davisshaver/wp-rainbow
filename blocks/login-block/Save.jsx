import classnames from 'classnames';
import {
	__experimentalGetBorderClassesAndStyles as getBorderClassesAndStyles,
	__experimentalGetColorClassesAndStyles as getColorClassesAndStyles,
	__experimentalGetSpacingClassesAndStyles as getSpacingClassesAndStyles,
	useBlockProps,
} from '@wordpress/block-editor';

export default function save( { attributes, className } ) {
	const { className: blockClassName, ...blockProps } = useBlockProps.save();
	const {
		checkWalletText,
		errorText,
		loginText,
		redirectURL,
		style,
		width,
	} = attributes;
	const borderProps = getBorderClassesAndStyles( attributes );
	const colorProps = getColorClassesAndStyles( attributes );
	const spacingProps = getSpacingClassesAndStyles( attributes );
	return (
		<div
			className={ `${ blockClassName } wp-block-button` }
			{ ...blockProps }
		>
			<div
				data-wp-rainbow-login
				data-button-class-name={ classnames(
					'wp-block-button__link',
					className,
					colorProps.className,
					borderProps.className
				) }
				data-check-wallet-text={ checkWalletText }
				data-container-class-name={ classnames(
					'wp-block-button',
					blockClassName,
					{
						[ `has-custom-width wp-block-button__width-${ width }` ]: width,
						[ `has-custom-font-size` ]: blockProps.style.fontSize,
					}
				) }
				data-error-text={ errorText }
				data-login-text={ loginText }
				data-outer-container-class-name={ classnames(
					'wp-block-buttons'
				) }
				data-redirect-url={ redirectURL }
				data-style={ JSON.stringify( {
					...borderProps.style,
					...colorProps.style,
					...spacingProps.style,
				} ) }
			/>
		</div>
	);
}
