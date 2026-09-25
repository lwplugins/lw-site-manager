<?php
/**
 * The AI / MCP screen.
 *
 * @package LightweightPlugins\SiteManager
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Admin;

use LightweightPlugins\SiteManager\Rest\Admin\Routes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "AI / MCP" submenu: a mount point for the React admin (build/index),
 * which reads and writes through the lw-site-manager/v1 admin REST routes.
 */
final class McpSettingsPage {

	public const SLUG = 'lw-site-manager-mcp';

	/**
	 * Script and style handle.
	 */
	private const HANDLE = 'lw-site-manager-admin-app';

	/**
	 * Documentation of the MCP server.
	 */
	private const DOCS_URL = 'https://github.com/lwplugins/lw-site-manager/blob/main/docs/mcp-server.md';

	/**
	 * Hook suffix returned by add_submenu_page().
	 *
	 * Assets are keyed on it rather than on a hard-coded
	 * "lw-plugins_page_lw-site-manager-mcp": WordPress derives that prefix
	 * from the translated parent menu title, so a locale that translates
	 * "LW Plugins" would silently stop the screen from loading.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Hook the screen.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * Register the submenu under the LW Plugins parent.
	 */
	public function register_menu(): void {
		ParentPage::maybe_register();

		$hook = add_submenu_page(
			ParentPage::SLUG,
			__( 'AI / MCP', 'lw-site-manager' ),
			__( 'AI / MCP', 'lw-site-manager' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);

		$this->hook_suffix = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Enqueue the React app on this screen only.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		if ( ! BuildAssets::enqueue( 'index', self::HANDLE ) ) {
			return;
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.lwSiteManager = ' . wp_json_encode(
				[
					'version'   => LW_SITE_MANAGER_VERSION,
					'namespace' => Routes::NAMESPACE,
					'docsUrl'   => self::DOCS_URL,
				]
			) . ';',
			'before'
		);
	}

	/**
	 * Mark this screen's body for the app's styles.
	 *
	 * @param string $classes Space-separated body classes.
	 */
	public function body_class( $classes ): string {
		$classes = (string) $classes;
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( '' === $this->hook_suffix || ! $screen || $screen->id !== $this->hook_suffix ) {
			return $classes;
		}

		return $classes . ' lw-site-manager-screen';
	}

	/**
	 * Render the mount point (or a notice when the build is missing).
	 *
	 * The mount point sits outside .wrap so NoticeManager's direct-child
	 * notice rules never reach the app; the missing-build notice carries
	 * `lw-notice` so it is not hidden.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! BuildAssets::exists( 'index' ) ) {
			printf(
				'<div class="wrap"><h1>%s</h1><div class="notice notice-error lw-notice"><p>%s</p></div></div>',
				esc_html__( 'AI / MCP', 'lw-site-manager' ),
				esc_html__( 'The screen files are missing. Re-install the plugin from a release ZIP, or run "npm install && npm run build" in the plugin directory.', 'lw-site-manager' )
			);
			return;
		}

		echo '<div id="lw-site-manager-root" class="lw-site-manager-root"></div>';
	}
}
