/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { api, errorMessage } from './api';

/**
 * Turns the server on or off and puts the returned state on screen (no
 * refetch). A snackbar reports the result either way.
 *
 * @param {Function} setData useRemote().setData of the MCP state.
 * @return {Object} { save( enabled ), isSaving }.
 */
export default function useMcpToggle( setData ) {
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const save = useCallback(
		( enabled ) => {
			setIsSaving( true );
			return api
				.saveMcp( enabled )
				.then(
					( data ) => {
						setData( data );
						createSuccessNotice(
							data.enabled
								? __(
										'MCP server turned on.',
										'lw-site-manager'
									)
								: __(
										'MCP server turned off.',
										'lw-site-manager'
									),
							{ type: 'snackbar' }
						);
					},
					( error ) =>
						createErrorNotice( errorMessage( error ), {
							type: 'snackbar',
						} )
				)
				.finally( () => setIsSaving( false ) );
		},
		[ setData, createSuccessNotice, createErrorNotice ]
	);

	return { save, isSaving };
}
