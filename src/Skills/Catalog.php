<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and injects the "## Available Skills" catalog into the MCP
 * discover-abilities instructions string.
 */
final class Catalog {

	/**
	 * Filter callback for `lw_site_manager_discover_instructions`.
	 *
	 * @param mixed $instructions Incoming instructions value.
	 * @return mixed The instructions, with the catalog prepended when applicable.
	 */
	public static function inject_filter( mixed $instructions ): mixed {
		if ( ! is_string( $instructions ) ) {
			return $instructions;
		}
		return self::inject( $instructions, Sources::discoverable( 'agentic' ) );
	}

	/**
	 * Prepend the catalog to an instructions string when skills exist.
	 *
	 * @param string                    $instructions Base instructions.
	 * @param list<array<string,mixed>> $skills       Discoverable skills.
	 * @return string Combined instructions.
	 */
	public static function inject( string $instructions, array $skills ): string {
		if ( [] === $skills ) {
			return $instructions;
		}
		return self::render( $skills ) . "\n" . $instructions;
	}

	/**
	 * Render the catalog markdown block.
	 *
	 * @param list<array<string,mixed>> $skills Discoverable skills.
	 * @return string Markdown catalog.
	 */
	public static function render( array $skills ): string {
		$lines = [
			'',
			'## Available Skills',
			'',
			'When a skill description matches the user request, call `site-manager/skill-get` with the slug to load its full instructions before starting work.',
			'',
		];
		foreach ( $skills as $skill ) {
			$lines[] = sprintf(
				'- **`%s`** *(%s)* — %s',
				(string) ( $skill['slug'] ?? '' ),
				(string) ( $skill['source_label'] ?? '' ),
				trim( (string) ( $skill['description'] ?? '' ) )
			);
		}
		$lines[] = '';
		return implode( "\n", $lines );
	}
}
