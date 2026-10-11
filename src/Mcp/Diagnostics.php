<?php
/**
 * Problems that keep the MCP server from working as expected.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The warnings the AI / MCP screen shows: a missing adapter library, which
 * Bootstrap also reports as an admin notice (hidden on LW screens), plus why
 * the server is off after a domain change.
 */
final class Diagnostics {

	public const ADAPTER_OK      = 'ok';
	public const ADAPTER_MISSING = 'missing';

	/**
	 * State of the MCP adapter library.
	 *
	 * Any loaded copy counts as present, including the older one WooCommerce
	 * bundles and loads first: ExecuteAbility hooks into WordPress core rather
	 * than into the adapter, so the server works the same on every copy.
	 */
	public static function adapter_state(): string {
		return class_exists( '\WP\MCP\Core\McpAdapter' ) ? self::ADAPTER_OK : self::ADAPTER_MISSING;
	}

	/**
	 * Warnings for the given state. Adapter problems count only while the
	 * server is on, as in Bootstrap (a disabled server never loads it).
	 *
	 * @param bool   $enabled Whether the server is on.
	 * @param string $adapter One of the ADAPTER_* states.
	 * @param string $locked  Domain the server is bound to ('' = never bound).
	 * @param string $current Host of the current site URL.
	 * @return list<array{code: string, message: string, detail: string}>
	 */
	public static function warnings( bool $enabled, string $adapter, string $locked, string $current ): array {
		$warnings = [];

		if ( $enabled && self::ADAPTER_MISSING === $adapter ) {
			$warnings[] = self::warning( 'adapter_missing', self::missing_message() );
		}

		if ( ! $enabled && '' !== $locked && $locked !== $current ) {
			$warnings[] = self::warning(
				'domain_changed',
				sprintf(
					/* translators: 1: domain the MCP server was turned on for, 2: current domain of the site. */
					__( 'The site address changed since the MCP server was turned on (it was set up for %1$s, this site now runs on %2$s), so it switched itself off. If the move was intentional, turn it back on.', 'lw-site-manager' ),
					$locked,
					$current
				)
			);
		}

		return $warnings;
	}

	/**
	 * Text for an MCP server that is on while the adapter library is absent.
	 */
	public static function missing_message(): string {
		return __( 'The MCP server is enabled but the MCP Adapter library is missing. Run "composer install" or re-install from a release ZIP.', 'lw-site-manager' );
	}

	/**
	 * One warning entry. `detail` stays in the shape the screen renders, but no
	 * current warning carries one.
	 *
	 * @return array{code: string, message: string, detail: string}
	 */
	private static function warning( string $code, string $message ): array {
		return [
			'code'    => $code,
			'message' => $message,
			'detail'  => '',
		];
	}
}
