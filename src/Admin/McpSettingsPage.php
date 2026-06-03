<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Admin;

use LightweightPlugins\SiteManager\Mcp\Server;
use LightweightPlugins\SiteManager\Mcp\Toggle;
use LightweightPlugins\SiteManager\Skills\Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "AI / MCP" submenu: enable toggle, .mcp.json snippet, bundled-skill list.
 */
final class McpSettingsPage {

	public const SLUG  = 'lw-site-manager-mcp';
	public const NONCE = 'lw_site_manager_mcp_toggle';

	/**
	 * Register the submenu under the LW Plugins parent.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			ParentPage::SLUG,
			__( 'AI / MCP', 'lw-site-manager' ),
			__( 'AI / MCP', 'lw-site-manager' ),
			'manage_options',
			self::SLUG,
			[ self::class, 'render' ]
		);
	}

	/**
	 * Handle the toggle form submission.
	 */
	public static function handle_post(): void {
		if ( ! isset( $_POST[ self::NONCE ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::NONCE, self::NONCE ) ) {
			return;
		}
		$enable = ! empty( $_POST['lw_mcp_enable'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( $enable ) {
			Toggle::enable();
		} else {
			Toggle::disable();
		}
		wp_safe_redirect( add_query_arg( 'page', self::SLUG, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$enabled = Toggle::is_enabled();
		echo '<div class="wrap"><h1>' . esc_html__( 'LW Site Manager — AI / MCP', 'lw-site-manager' ) . '</h1>';
		self::render_toggle_form( $enabled );
		if ( $enabled ) {
			self::render_connection_info();
		}
		self::render_skill_list();
		echo '</div>';
	}

	private static function render_toggle_form( bool $enabled ): void {
		echo '<form method="post">';
		wp_nonce_field( self::NONCE, self::NONCE );
		printf(
			'<p><label><input type="checkbox" name="lw_mcp_enable" value="1" %s> %s</label></p>',
			checked( $enabled, true, false ),
			esc_html__( 'Enable the built-in MCP server for AI agents (admin only).', 'lw-site-manager' )
		);
		echo '<p class="description">' . esc_html__( 'Warning: agents can run write/destructive abilities. Enable only on sites you control. The server auto-disables if the site domain changes.', 'lw-site-manager' ) . '</p>';
		submit_button( __( 'Save', 'lw-site-manager' ) );
		echo '</form>';
	}

	private static function render_connection_info(): void {
		$snippet = wp_json_encode(
			[
				'mcpServers' => [
					Server::SERVER_ID => [
						'type'    => 'http',
						'url'     => Server::endpoint(),
						'headers' => [ 'Authorization' => 'Basic BASE64(user:application_password)' ],
					],
				],
			],
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		echo '<h2>' . esc_html__( '.mcp.json', 'lw-site-manager' ) . '</h2>';
		echo '<p>' . esc_html__( 'Create an application password (Users → Profile) and paste this into your MCP client:', 'lw-site-manager' ) . '</p>';
		echo '<textarea readonly rows="10" style="width:100%;font-family:monospace;">' . esc_textarea( (string) $snippet ) . '</textarea>';
	}

	private static function render_skill_list(): void {
		echo '<h2>' . esc_html__( 'Bundled skills', 'lw-site-manager' ) . '</h2><ul>';
		foreach ( Sources::all() as $skill ) {
			printf(
				'<li><code>%s</code> <em>(%s)</em> — %s</li>',
				esc_html( (string) ( $skill['slug'] ?? '' ) ),
				esc_html( (string) ( $skill['source_label'] ?? '' ) ),
				esc_html( (string) ( $skill['description'] ?? '' ) )
			);
		}
		echo '</ul>';
	}
}
