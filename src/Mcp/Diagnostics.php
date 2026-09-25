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
 * The warnings the AI / MCP screen shows: the adapter problems Bootstrap
 * reports as admin notices (hidden on LW screens), plus why the server is
 * off after a domain change.
 */
final class Diagnostics {

	public const ADAPTER_OK       = 'ok';
	public const ADAPTER_MISSING  = 'missing';
	public const ADAPTER_OUTDATED = 'outdated';

	/**
	 * State of the MCP adapter library that actually loaded.
	 */
	public static function adapter_state(): string {
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return self::ADAPTER_MISSING;
		}

		return AdapterVersion::isCurrent() ? self::ADAPTER_OK : self::ADAPTER_OUTDATED;
	}

	/**
	 * Warnings for the given state. Adapter problems count only while the
	 * server is on, as in Bootstrap (a disabled server never loads it).
	 *
	 * @param bool        $enabled      Whether the server is on.
	 * @param string      $adapter      One of the ADAPTER_* states.
	 * @param string|null $adapter_root Directory the loaded adapter came from.
	 * @param string      $locked       Domain the server is bound to ('' = never bound).
	 * @param string      $current      Host of the current site URL.
	 * @return list<array{code: string, message: string, detail: string}>
	 */
	public static function warnings( bool $enabled, string $adapter, ?string $adapter_root, string $locked, string $current ): array {
		$warnings = [];

		if ( $enabled && self::ADAPTER_MISSING === $adapter ) {
			$warnings[] = self::warning( 'adapter_missing', self::missing_message() );
		}

		if ( $enabled && self::ADAPTER_OUTDATED === $adapter ) {
			$warnings[] = self::warning( 'adapter_outdated', self::outdated_message(), (string) $adapter_root );
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
	 * Text for an older adapter copy that another plugin loaded first.
	 */
	public static function outdated_message(): string {
		return __( 'Another plugin has loaded an older copy of the MCP Adapter library, so some features are unavailable — most importantly, failed tool calls are reported to the AI agent as successful. WooCommerce is the usual source: it bundles its own copy and loads it first.', 'lw-site-manager' );
	}

	/**
	 * One warning entry.
	 *
	 * @return array{code: string, message: string, detail: string}
	 */
	private static function warning( string $code, string $message, string $detail = '' ): array {
		return [
			'code'    => $code,
			'message' => $message,
			'detail'  => $detail,
		];
	}
}
