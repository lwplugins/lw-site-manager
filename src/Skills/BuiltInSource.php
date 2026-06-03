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
	 * Delegates to the shared directory loader so external plugins and the
	 * built-in source use identical parsing/validation logic.
	 *
	 * @return list<array{slug:string,name:string,description:string,content:string,enable_prompt:bool,enable_agentic:bool}>
	 */
	public static function load(): array {
		return DirectorySkillSource::load_dir( self::dir() );
	}

	private static function dir(): string {
		return rtrim( LW_SITE_MANAGER_DIR, '/' ) . '/skills';
	}
}
