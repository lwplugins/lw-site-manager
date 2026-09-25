/**
 * Server-provided boot data (McpSettingsPage inline script
 * `window.lwSiteManager`).
 */
const boot = window.lwSiteManager || {};

export const VERSION = boot.version || '';
export const NAMESPACE = boot.namespace || 'lw-site-manager/v1';
export const DOCS_URL =
	boot.docsUrl ||
	'https://github.com/lwplugins/lw-site-manager/blob/main/docs/mcp-server.md';
