<?php
/**
 * MCP subsystem bootstrap — wires and boots the MCP server.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires and boots the MCP subsystem. Hard no-op when the MCP server is
 * disabled or the adapter library is absent.
 */
final class Bootstrap {

	/**
	 * Boot the MCP subsystem if enabled and the adapter is available.
	 */
	public static function init(): void {
		if ( ! Toggle::is_enabled() ) {
			return;
		}
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			add_action( 'admin_notices', [ self::class, 'render_missing_notice' ] );
			return;
		}

		add_filter( 'mcp_adapter_default_server_config', [ Server::class, 'brand' ] );
		add_filter( 'mcp_adapter_default_transport_permission_user_capability', [ TransportGuard::class, 'capability' ], 10, 2 );
		add_filter( 'mcp_adapter_tool_call_result', [ ResultUnwrapper::class, 'filter' ], 10, 3 );
		add_filter( 'wp_register_ability_args', [ AbilityExposer::class, 'flag_args' ], 10, 2 );
		add_action( 'wp_abilities_api_init', [ DiscoverAbility::class, 'register' ], 999 );

		// Boot the adapter; it registers the (branded) default server on mcp_adapter_init.
		\WP\MCP\Core\McpAdapter::instance();
	}

	/**
	 * Admin notice shown when MCP is enabled but the adapter library is missing.
	 */
	public static function render_missing_notice(): void {
		echo '<div class="notice notice-warning"><p><strong>LW Site Manager:</strong> ';
		echo esc_html__( 'The MCP server is enabled but the MCP Adapter library is missing. Run "composer install" or re-install from a release ZIP.', 'lw-site-manager' );
		echo '</p></div>';
	}
}
