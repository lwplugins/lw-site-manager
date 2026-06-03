<?php
/**
 * MCP server toggle with domain-lock.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable/disable state for the MCP server, with a domain-lock that
 * auto-disables when the site URL host changes (e.g. a staging clone).
 */
final class Toggle {

	public const OPTION_ENABLED = 'lw_site_manager_mcp_enabled';
	public const OPTION_DOMAIN  = 'lw_site_manager_mcp_domain';

	/**
	 * Whether the MCP server is currently enabled (and the domain still matches).
	 */
	public static function is_enabled(): bool {
		if ( ! get_option( self::OPTION_ENABLED, false ) ) {
			return false;
		}

		$locked  = (string) get_option( self::OPTION_DOMAIN, '' );
		$current = self::current_domain();

		if ( '' !== $locked && $locked !== $current ) {
			self::disable();
			return false;
		}

		return true;
	}

	/**
	 * Enable the MCP server and lock it to the current domain.
	 */
	public static function enable(): void {
		update_option( self::OPTION_ENABLED, true );
		update_option( self::OPTION_DOMAIN, self::current_domain() );
	}

	/**
	 * Disable the MCP server.
	 */
	public static function disable(): void {
		update_option( self::OPTION_ENABLED, false );
	}

	/**
	 * The domain the server was locked to (empty when never enabled).
	 */
	public static function locked_domain(): string {
		return (string) get_option( self::OPTION_DOMAIN, '' );
	}

	/**
	 * Get the host part of the current site URL.
	 */
	private static function current_domain(): string {
		return (string) wp_parse_url( get_site_url(), PHP_URL_HOST );
	}
}
