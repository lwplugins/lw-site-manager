<?php
/**
 * Flags the plugin's own abilities as MCP-public.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flags every `site-manager/*` ability as MCP-public via the core
 * `wp_register_ability_args` filter, regardless of how each ability builds
 * its meta. Only registered (hooked) while the MCP server is enabled, so the
 * flag is absent when MCP is off. Foreign abilities (other namespaces) are
 * untouched — they opt in via their own `mcp.public` meta.
 */
final class AbilityExposer {

	public const PREFIX = 'site-manager/';

	/**
	 * Filter callback for `wp_register_ability_args`.
	 *
	 * @param mixed  $args The ability registration args.
	 * @param string $name The ability name.
	 * @return mixed The args, with mcp.public added for own abilities.
	 */
	public static function flag_args( mixed $args, string $name ): mixed {
		if ( ! is_array( $args ) || ! str_starts_with( $name, self::PREFIX ) ) {
			return $args;
		}
		if ( ! is_array( $args['meta'] ?? null ) ) {
			$args['meta'] = [];
		}
		if ( ! is_array( $args['meta']['mcp'] ?? null ) ) {
			$args['meta']['mcp'] = [];
		}
		if ( ! isset( $args['meta']['mcp']['public'] ) ) {
			$args['meta']['mcp']['public'] = true;
		}
		if ( ! isset( $args['meta']['mcp']['type'] ) ) {
			$args['meta']['mcp']['type'] = 'tool';
		}
		return $args;
	}
}
