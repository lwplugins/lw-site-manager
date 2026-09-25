/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { connection, pages } from '@wordpress/icons';

/**
 * Tab registry: hash slugs and order. The screen has no form to save — the
 * MCP switch saves on its own — so there is no Save button in the top bar.
 */
export const TABS = [
	{
		id: 'mcp',
		label: __( 'MCP server', 'lw-site-manager' ),
		title: __( 'AI / MCP', 'lw-site-manager' ),
		icon: connection,
	},
	{
		id: 'skills',
		label: __( 'Skills', 'lw-site-manager' ),
		title: __( 'Bundled skills', 'lw-site-manager' ),
		icon: pages,
	},
];
