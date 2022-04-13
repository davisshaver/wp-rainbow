import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	// eslint-disable-next-line react/jsx-props-no-spreading
	return <div { ...useBlockProps.save() }>{ attributes.content }</div>;
}
