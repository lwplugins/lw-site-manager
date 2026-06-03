<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the statically bundled skill library: skills/<slug>/SKILL.md.
 */
final class BuiltInSource {

	public const SOURCE_ID    = 'built-in';
	public const SOURCE_LABEL = 'Built-in';
	public const PRIORITY     = 10;

	/**
	 * Register with the source registry. Hooked on `lw_site_manager_skill_sources`.
	 *
	 * @param array<string,array<string,mixed>> $sources
	 * @return array<string,array<string,mixed>>
	 */
	public static function register( array $sources ): array {
		$sources[ self::SOURCE_ID ] = [
			'id'       => self::SOURCE_ID,
			'priority' => self::PRIORITY,
			'label'    => self::SOURCE_LABEL,
			'loader'   => [ self::class, 'load' ],
		];
		return $sources;
	}

	/**
	 * Load and return all valid skills from the bundled skills directory.
	 *
	 * @return list<array{slug:string,name:string,description:string,content:string,enable_prompt:bool,enable_agentic:bool}>
	 */
	public static function load(): array {
		static $cache = null;
		if ( is_array( $cache ) ) {
			return $cache;
		}

		$dir   = self::dir();
		$files = is_dir( $dir ) ? glob( $dir . '/*/SKILL.md' ) : [];
		$out   = [];

		foreach ( (array) $files as $path ) {
			$slug = Parser::normalize_slug( basename( dirname( $path ) ) );
			if ( '' === $slug ) {
				continue;
			}
			$raw = file_get_contents( $path );
			if ( false === $raw ) {
				continue;
			}
			if ( strlen( $raw ) > Parser::MAX_BODY_BYTES ) {
				continue;
			}
			$parsed = Parser::parse( $raw );
			if ( null !== $parsed['parse_error'] || '' === trim( $parsed['body'] ) ) {
				continue;
			}
			$out[] = [
				'slug'           => $slug,
				'name'           => '' !== $parsed['name'] ? $parsed['name'] : $slug,
				'description'    => $parsed['description'],
				'content'        => $parsed['body'],
				'enable_prompt'  => $parsed['enable_prompt'],
				'enable_agentic' => $parsed['enable_agentic'],
			];
		}

		$cache = $out;
		return $out;
	}

	private static function dir(): string {
		return rtrim( LW_SITE_MANAGER_DIR, '/' ) . '/skills';
	}
}
