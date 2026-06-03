<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter-based skill source registry. Other plugins may add sources via
 * the `lw_site_manager_skill_sources` filter.
 */
final class Sources {

	/**
	 * Returns all registered sources sorted by priority.
	 *
	 * @return list<array{id:string,priority:int,label:string,loader:callable}>
	 */
	public static function registry(): array {
		// @var array<string,array{id:string,priority:int,label:string,loader:callable}> $sources
		$sources = apply_filters( 'lw_site_manager_skill_sources', [] );
		$list    = array_values( $sources );
		usort( $list, static fn( array $a, array $b ): int => $a['priority'] <=> $b['priority'] );
		return $list;
	}

	/**
	 * Find a skill by slug across all sources. Returns null if not found.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function find( string $slug ): ?array {
		foreach ( self::registry() as $entry ) {
			foreach ( ( $entry['loader'] )() as $skill ) {
				if ( ( $skill['slug'] ?? '' ) === $slug ) {
					$skill['source']       = $entry['id'];
					$skill['source_label'] = $entry['label'];
					return $skill;
				}
			}
		}
		return null;
	}

	/**
	 * Returns all skills from all registered sources with source metadata.
	 *
	 * @return list<array<string,mixed>>
	 */
	public static function all(): array {
		$out = [];
		foreach ( self::registry() as $entry ) {
			foreach ( ( $entry['loader'] )() as $skill ) {
				$skill['source']       = $entry['id'];
				$skill['source_label'] = $entry['label'];
				$out[]                 = $skill;
			}
		}
		return $out;
	}

	/**
	 * Returns skills that are discoverable for the given mode (agentic or prompt).
	 *
	 * @param 'agentic'|'prompt' $mode
	 * @return list<array<string,mixed>>
	 */
	public static function discoverable( string $mode ): array {
		$key     = 'agentic' === $mode ? 'enable_agentic' : 'enable_prompt';
		$default = 'agentic' === $mode;
		$out     = [];
		foreach ( self::all() as $skill ) {
			if ( '' === trim( (string) ( $skill['description'] ?? '' ) ) ) {
				continue;
			}
			if ( '' === trim( (string) ( $skill['content'] ?? '' ) ) ) {
				continue;
			}
			if ( ! ( $skill[ $key ] ?? $default ) ) {
				continue;
			}
			$out[] = $skill;
		}
		return $out;
	}
}
