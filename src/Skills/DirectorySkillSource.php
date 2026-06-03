<?php
/**
 * Reusable skills source backed by a directory of SKILL.md files.
 *
 * @package LightweightPlugins\SiteManager\Skills
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets any plugin expose its own bundled skills directory to LW Site Manager,
 * with the same ergonomics as the built-in source.
 *
 * A plugin ships skills as `<dir>/<slug>/SKILL.md` and registers the directory
 * once; the skills then appear in the discovery catalog, are loadable via the
 * `site-manager/skill-get` ability, and (when `enable_prompt: true`) as MCP
 * prompts — exactly like the built-in skills.
 *
 * Example, from another plugin:
 *
 *     add_action( 'init', function () {
 *         if ( class_exists( \LightweightPlugins\SiteManager\Skills\DirectorySkillSource::class ) ) {
 *             \LightweightPlugins\SiteManager\Skills\DirectorySkillSource::register(
 *                 'my-plugin', 'My Plugin', __DIR__ . '/skills'
 *             );
 *         }
 *     } );
 */
final class DirectorySkillSource {

	/**
	 * Register a directory of SKILL.md files as a skill source.
	 *
	 * @param string $id       Unique source id (used as the registry key).
	 * @param string $label    Human-readable badge shown in the catalog.
	 * @param string $dir      Absolute path to the directory holding `<slug>/SKILL.md`.
	 * @param int    $priority Catalog order; lower renders earlier. Built-in is 10, user sources default to 20.
	 */
	public static function register( string $id, string $label, string $dir, int $priority = 20 ): void {
		add_filter(
			'lw_site_manager_skill_sources',
			static function ( array $sources ) use ( $id, $label, $dir, $priority ): array {
				$sources[ $id ] = self::entry( $id, $label, $dir, $priority );
				return $sources;
			}
		);
	}

	/**
	 * Build a source-registry entry for a directory, for callers that manage
	 * the `lw_site_manager_skill_sources` filter themselves.
	 *
	 * @param string $id       Unique source id.
	 * @param string $label    Human-readable badge.
	 * @param string $dir      Absolute path to the skills directory.
	 * @param int    $priority Catalog order.
	 * @return array{id:string,priority:int,label:string,loader:callable}
	 */
	public static function entry( string $id, string $label, string $dir, int $priority = 20 ): array {
		return [
			'id'       => $id,
			'priority' => $priority,
			'label'    => $label,
			'loader'   => static fn(): array => self::load_dir( $dir ),
		];
	}

	/**
	 * Load every valid `<dir>/<slug>/SKILL.md` as a skill record. Memoized per directory.
	 *
	 * @param string $dir Absolute path to the skills directory.
	 * @return list<array{slug:string,name:string,description:string,content:string,enable_prompt:bool,enable_agentic:bool}>
	 */
	public static function load_dir( string $dir ): array {
		static $cache = [];

		$key = rtrim( $dir, '/' );
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$files = is_dir( $key ) ? glob( $key . '/*/SKILL.md' ) : [];
		$out   = [];

		foreach ( (array) $files as $path ) {
			$record = self::read_skill( $path );
			if ( null !== $record ) {
				$out[] = $record;
			}
		}

		$cache[ $key ] = $out;
		return $out;
	}

	/**
	 * Parse one SKILL.md file into a skill record, or null when invalid.
	 *
	 * @param string $path Absolute path to a SKILL.md file.
	 * @return array{slug:string,name:string,description:string,content:string,enable_prompt:bool,enable_agentic:bool}|null
	 */
	private static function read_skill( string $path ): ?array {
		$slug = Parser::normalize_slug( basename( dirname( $path ) ) );
		if ( '' === $slug ) {
			return null;
		}
		$raw = file_get_contents( $path );
		if ( false === $raw || strlen( $raw ) > Parser::MAX_BODY_BYTES ) {
			return null;
		}
		$parsed = Parser::parse( $raw );
		if ( null !== $parsed['parse_error'] || '' === trim( $parsed['body'] ) ) {
			return null;
		}
		return [
			'slug'           => $slug,
			'name'           => '' !== $parsed['name'] ? $parsed['name'] : $slug,
			'description'    => $parsed['description'],
			'content'        => $parsed['body'],
			'enable_prompt'  => $parsed['enable_prompt'],
			'enable_agentic' => $parsed['enable_agentic'],
		];
	}
}
