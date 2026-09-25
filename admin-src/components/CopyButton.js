/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { copy } from '@wordpress/icons';

/**
 * Copies `text`; the label reads "Copied" for a moment afterwards.
 *
 * @param {Object} props
 * @param {string} props.text  Text to copy.
 * @param {string} props.label Screen-reader text naming what is copied.
 */
export default function CopyButton( { text, label } ) {
	const [ copied, setCopied ] = useState( false );
	const timer = useRef();
	const ref = useCopyToClipboard( text, () => {
		setCopied( true );
		clearTimeout( timer.current );
		timer.current = setTimeout( () => setCopied( false ), 1500 );
	} );

	useEffect( () => () => clearTimeout( timer.current ), [] );

	return (
		<Button ref={ ref } size="compact" variant="secondary" icon={ copy }>
			{ copied
				? __( 'Copied', 'lw-site-manager' )
				: __( 'Copy', 'lw-site-manager' ) }
			{ label && <span className="screen-reader-text">{ label }</span> }
		</Button>
	);
}
