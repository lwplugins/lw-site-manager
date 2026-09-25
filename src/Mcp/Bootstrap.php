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
		// Registered before the enabled check on purpose. This guard only ever
		// denies requests to our own route, so it costs nothing when the server
		// is off — and it must not depend on our own bootstrapping having got
		// that far, since the whole point is that it holds when something else
		// has gone wrong.
		RouteGuard::register();

		if ( ! Toggle::is_enabled() ) {
			return;
		}
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			add_action( 'admin_notices', [ self::class, 'render_missing_notice' ] );
			return;
		}

		// A different plugin may have loaded an older copy of the shared adapter
		// before us — WooCommerce bundles v0.3.0 and requires it eagerly, which
		// beats Composer's lazy autoloading. That copy has no
		// mcp_adapter_tool_call_result filter, so ResultUnwrapper silently never
		// runs and failed tool calls reach the agent looking like successes.
		// Say so instead of degrading quietly.
		if ( ! AdapterVersion::isCurrent() ) {
			add_action( 'admin_notices', [ self::class, 'render_outdated_notice' ] );
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
	 * Admin notice shown when an outdated copy of the adapter is the one running.
	 */
	public static function render_outdated_notice(): void {
		$root = AdapterVersion::loadedRoot();

		echo '<div class="notice notice-warning"><p><strong>LW Site Manager:</strong> ';
		echo esc_html( Diagnostics::outdated_message() );
		if ( is_string( $root ) && '' !== $root ) {
			echo '</p><p><code>' . esc_html( $root ) . '</code>';
		}
		echo '</p></div>';
	}

	/**
	 * Admin notice shown when MCP is enabled but the adapter library is missing.
	 */
	public static function render_missing_notice(): void {
		echo '<div class="notice notice-warning"><p><strong>LW Site Manager:</strong> ';
		echo esc_html( Diagnostics::missing_message() );
		echo '</p></div>';
	}
}
