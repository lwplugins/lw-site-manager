/**
 * Response adapters: the ONLY place that knows the backend's field names
 * (lw-site-manager/v1 admin routes — src/Rest/Admin/*). The UI reads the
 * camelCase objects built here.
 */

const obj = ( value ) =>
	value && typeof value === 'object' && ! Array.isArray( value ) ? value : {};
const list = ( value ) => ( Array.isArray( value ) ? value : [] );
const str = ( value ) =>
	value === null || value === undefined ? '' : String( value );

/**
 * GET/POST /admin/mcp → MCP server state.
 *
 * `snippet` is the .mcp.json object; it is kept as-is (the UI prints it as
 * JSON) apart from falling back to an empty object.
 *
 * @param {Object} data Response.
 * @return {Object} State.
 */
export function toMcp( data ) {
	const d = obj( data );
	const domain = obj( d.domain );
	return {
		enabled: !! d.enabled,
		endpoint: str( d.endpoint ),
		serverId: str( d.server_id ),
		domain: {
			locked: str( domain.locked ),
			current: str( domain.current ),
			matches: domain.matches !== false,
		},
		snippet: obj( d.snippet ),
		warnings: list( d.warnings ).map( ( w, index ) => ( {
			key: `${ str( w?.code ) }-${ index }`,
			code: str( w?.code ),
			message: str( w?.message ),
			detail: str( w?.detail ),
		} ) ),
		appPasswords: !! d.application_passwords_available,
		profileUrl: str( d.profile_url ) || 'profile.php',
	};
}

/**
 * GET /admin/skills → skill list (no bodies).
 *
 * @param {Array} data Response.
 * @return {Array} Skills.
 */
export function toSkills( data ) {
	return list( data ).map( ( s, index ) => ( {
		key: `${ str( s?.source ) }-${ str( s?.slug ) }-${ index }`,
		slug: str( s?.slug ),
		name: str( s?.name ),
		description: str( s?.description ),
		source: str( s?.source ),
		sourceLabel: str( s?.source_label ),
		agentic: s?.agentic !== false,
	} ) );
}
