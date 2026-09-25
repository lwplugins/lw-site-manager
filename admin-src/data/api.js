/**
 * Every REST call the admin makes, in one place (lw-site-manager/v1, prefix
 * /admin). Responses go through ./shapes before the UI reads them, so a
 * backend shape change is a one-file fix there.
 */
/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './boot';
import { toMcp, toSkills } from './shapes';

const path = ( route ) => `/${ NAMESPACE }/admin${ route }`;

export const api = {
	// MCP server: GET → state; POST { enabled } → same shape.
	mcp: () => apiFetch( { path: path( '/mcp' ) } ).then( toMcp ),
	saveMcp: ( enabled ) =>
		apiFetch( {
			path: path( '/mcp' ),
			method: 'POST',
			data: { enabled },
		} ).then( toMcp ),

	// Registered skills (bundled + other plugins' sources).
	skills: () => apiFetch( { path: path( '/skills' ) } ).then( toSkills ),
};

/**
 * Human message of a failed request.
 *
 * @param {Object} error apiFetch rejection.
 * @return {string} Message.
 */
export const errorMessage = ( error ) =>
	error?.message ||
	__(
		'That did not work. Please reload the page and try again.',
		'lw-site-manager'
	);
