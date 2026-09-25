/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Nav extras: the server state dot on "MCP server" (green on, amber when a
 * warning needs attention, none when simply off) and the skill count.
 *
 * @param {Object}      props
 * @param {Object|null} props.mcp    MCP state.
 * @param {Array|null}  props.skills Skill list.
 * @return {Object} { tabId: node }.
 */
export default function navMeta( { mcp, skills } ) {
	const meta = {};

	if ( mcp ) {
		let dot = mcp.enabled ? 'ok' : 'idle';
		if ( mcp.warnings.length ) {
			dot = 'warning';
		}
		const labels = {
			ok: __( 'On', 'lw-site-manager' ),
			warning: __( 'Needs attention', 'lw-site-manager' ),
		};
		if ( dot !== 'idle' ) {
			meta.mcp = (
				<span className={ `lw-admin-sidenav__dot is-${ dot }` }>
					<span className="screen-reader-text">
						{ labels[ dot ] }
					</span>
				</span>
			);
		}
	}

	if ( skills?.length ) {
		meta.skills = (
			<>
				<span aria-hidden="true">{ String( skills.length ) }</span>
				<span className="screen-reader-text">
					{ sprintf(
						/* translators: %d: number of skills. */
						_n(
							'%d skill',
							'%d skills',
							skills.length,
							'lw-site-manager'
						),
						skills.length
					) }
				</span>
			</>
		);
	}

	return meta;
}
