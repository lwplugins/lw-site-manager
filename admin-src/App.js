/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Notices from './components/Notices';
import { api } from './data/api';
import useMcpToggle from './data/useMcpToggle';
import useRemote from './data/useRemote';
import Footer from './shell/Footer';
import navMeta from './shell/navMeta';
import SideNav from './shell/SideNav';
import TopBar from './shell/TopBar';
import { TABS } from './shell/tabs';
import useTab from './shell/useTab';
import McpTab from './tabs/mcp/McpTab';
import SkillsTab from './tabs/skills/SkillsTab';

const VIEWS = {
	mcp: McpTab,
	skills: SkillsTab,
};

// Classic links (?page=lw-site-manager-mcp&tab=skills) open that tab.
const INITIAL_TAB =
	new URLSearchParams( window.location.search ).get( 'tab' ) || 'mcp';

/**
 * Shell + the two read models: the MCP server state (written back by the
 * switch) and the skill list. Both load on mount, so the nav can show the
 * server dot and the skill count from the first paint.
 */
export default function App() {
	const loadMcp = useCallback( () => api.mcp(), [] );
	const loadSkills = useCallback( () => api.skills(), [] );
	const mcp = useRemote( loadMcp );
	const skills = useRemote( loadSkills );
	const toggle = useMcpToggle( mcp.setData );
	const tab = useTab(
		TABS.map( ( t ) => t.id ),
		INITIAL_TAB
	);
	const current = TABS.find( ( t ) => t.id === tab ) || TABS[ 0 ];
	const View = VIEWS[ current.id ];

	return (
		<>
			<div className="lw-admin-shell">
				<SideNav
					tabs={ TABS }
					current={ current.id }
					meta={ navMeta( { mcp: mcp.data, skills: skills.data } ) }
				/>
				<div className="lw-admin-main">
					<TopBar title={ current.title } />
					<main className="lw-admin-scroll">
						<div className="lw-admin-content">
							<View
								mcp={ mcp }
								skills={ skills }
								toggle={ toggle }
							/>
						</div>
					</main>
					<Footer />
				</div>
			</div>
			<Notices />
		</>
	);
}
