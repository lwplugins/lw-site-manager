<?php
/**
 * MCP server branding — configures the adapter's default server identity.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configures the adapter's default server as the LW Site Manager MCP server.
 */
final class Server {

	public const SERVER_ID    = 'lw-site-manager';
	public const ROUTE_NS     = 'mcp';
	public const SERVER_ROUTE = 'lw-site-manager';

	/**
	 * Filter callback for `mcp_adapter_default_server_config`.
	 *
	 * @param mixed $config The default server config array.
	 * @return mixed The branded config.
	 */
	public static function brand( mixed $config ): mixed {
		if ( ! is_array( $config ) ) {
			return $config;
		}
		$config['server_id']          = self::SERVER_ID;
		$config['server_route']       = self::SERVER_ROUTE;
		$config['server_name']        = 'LW Site Manager';
		$config['server_description'] = 'Manage this WordPress site via LW Site Manager abilities and skills.';
		return $config;
	}

	/**
	 * The REST URL of the MCP endpoint.
	 */
	public static function endpoint(): string {
		return rest_url( self::ROUTE_NS . '/' . self::SERVER_ROUTE );
	}
}
