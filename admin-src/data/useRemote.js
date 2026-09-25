/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Minimal fetch-state hook for read endpoints. `reload( arg )` passes `arg`
 * to the fetcher (e.g. refresh = true); the previous data stays on screen
 * while reloading and after a failed reload (then `error` is set too).
 * `ensure()` loads once, for lazily loaded tabs.
 *
 * @param {Function} fetcher Returns a promise.
 * @param {boolean}  auto    Fetch on mount.
 * @return {Object} { data, error, isLoading, reload, ensure, setData }.
 */
export default function useRemote( fetcher, auto = true ) {
	const [ state, setState ] = useState( {
		data: null,
		error: null,
		isLoading: auto,
	} );
	const started = useRef( false );
	const seq = useRef( 0 );

	const reload = useCallback(
		( arg ) => {
			started.current = true;
			const id = ++seq.current;
			setState( ( prev ) => ( {
				...prev,
				error: null,
				isLoading: true,
			} ) );
			return fetcher( arg ).then(
				( data ) => {
					if ( id === seq.current ) {
						setState( { data, error: null, isLoading: false } );
					}
					return data;
				},
				( error ) => {
					if ( id === seq.current ) {
						setState( ( prev ) => ( {
							data: prev.data,
							error,
							isLoading: false,
						} ) );
					}
					return null;
				}
			);
		},
		[ fetcher ]
	);

	const ensure = useCallback( () => {
		if ( ! started.current ) {
			reload();
		}
	}, [ reload ] );

	useEffect( () => {
		if ( auto ) {
			reload();
		}
	}, [ auto, reload ] );

	// Replace the data with a fresh payload a write returned (no refetch).
	const setData = useCallback( ( data ) => {
		started.current = true;
		seq.current++;
		setState( { data, error: null, isLoading: false } );
	}, [] );

	return { ...state, reload, ensure, setData };
}
