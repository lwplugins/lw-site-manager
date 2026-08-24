<?php
/**
 * Detects which MCP adapter installation is actually running.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Works out whether the MCP adapter that actually loaded is recent enough.
 *
 * The adapter is a shared library several plugins bundle privately. WooCommerce
 * ships v0.3.0 in its own vendor directory and requires it eagerly, which beats
 * Composer's lazy PSR-4 loading — so on a WooCommerce store this plugin runs
 * against 0.3.0 even when its own lockfile pins 0.5.0.
 *
 * That copy has no `mcp_adapter_tool_call_result` filter, so ResultUnwrapper is
 * never called and a failed tool call reaches the agent wrapped as a success.
 *
 * A class_exists() check cannot see this. Our PSR-4 autoloader still resolves
 * classes the older copy lacks from our own vendor, so the runtime ends up a
 * mixture: a 0.3.0 core with newer classes filled in behind it, and the check
 * happily reports "current". The only honest question is which installation the
 * loaded class came from, so that is what this asks.
 */
final class AdapterVersion {

	/**
	 * File that exists only from v0.5.0 onwards.
	 *
	 * Used as a version marker for the installation on disk. It is a proxy for
	 * "new enough to have the tool-result filter" rather than the filter itself,
	 * because the filter lives inside a file that exists in both versions.
	 */
	private const MARKER = 'Core/McpVersionNegotiator.php';

	/**
	 * Cached answer for the current request.
	 */
	private static ?bool $isCurrent = null;

	/**
	 * Whether the loaded adapter is recent enough for every hook this plugin uses.
	 */
	public static function isCurrent(): bool {
		if ( null !== self::$isCurrent ) {
			return self::$isCurrent;
		}

		$root = self::loadedRoot();

		self::$isCurrent = null !== $root && self::treeIsCurrent( $root );

		return self::$isCurrent;
	}

	/**
	 * Absolute path of the `includes` directory the loaded adapter came from.
	 */
	public static function loadedRoot(): ?string {
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return null;
		}

		try {
			$file = ( new \ReflectionClass( '\WP\MCP\Core\McpAdapter' ) )->getFileName();
		} catch ( \ReflectionException $e ) {
			return null;
		}

		// getFileName() is false for classes defined in PHP itself, which the
		// adapter never is — but guard anyway rather than assume.
		if ( ! is_string( $file ) ) {
			return null;
		}

		// .../mcp-adapter/includes/Core/McpAdapter.php -> .../mcp-adapter/includes
		return dirname( $file, 2 );
	}

	/**
	 * Whether an adapter installation on disk is v0.5.0 or newer.
	 *
	 * @param string $root Absolute path to the adapter's `includes` directory.
	 */
	public static function treeIsCurrent( string $root ): bool {
		if ( '' === $root ) {
			return false;
		}

		return file_exists( rtrim( $root, '/\\' ) . '/' . self::MARKER );
	}

	/**
	 * Reset the cached answer (tests).
	 */
	public static function reset(): void {
		self::$isCurrent = null;
	}
}
