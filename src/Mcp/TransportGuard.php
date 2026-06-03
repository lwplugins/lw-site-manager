<?php
/**
 * MCP transport-level capability gate.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transport-level capability gate for the MCP endpoint.
 */
final class TransportGuard {

	/**
	 * Filter callback for `mcp_adapter_default_transport_permission_user_capability`.
	 *
	 * @param mixed $capability The incoming capability (default 'read').
	 * @param mixed $context    The transport context (unused).
	 * @return string The required capability.
	 */
	public static function capability( mixed $capability, mixed $context = null ): string {
		/**
		 * Filters the minimum capability required to reach the MCP transport.
		 *
		 * @param string $cap Default 'manage_options'.
		 */
		$cap = (string) apply_filters( 'lw_site_manager_mcp_capability', 'manage_options', $context );
		return '' !== $cap ? $cap : 'manage_options';
	}

	/**
	 * Permission callback for read-only meta abilities (e.g. discover).
	 */
	public static function allow_read(): bool {
		return current_user_can( 'manage_options' );
	}
}
