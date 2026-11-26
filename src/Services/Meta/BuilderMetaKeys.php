<?php
/**
 * Page-builder storage keys the generic meta writes refuse.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Meta;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A builder keeps its layout, caches and generated CSS in post meta that only
 * makes sense together: a hand-written _elementor_data leaves stale CSS behind,
 * a Bricks tree without its element IDs breaks the editor. The builder
 * abilities write these keys with their invariants; the generic meta writes
 * (set/delete-post-meta, the post create/update `meta` map, the WooCommerce
 * product meta) refuse them for every caller, and point to those abilities.
 * Reading them stays allowed.
 *
 * Keys are compared case-insensitively, as the meta_key collation compares
 * them. MetaGuard hands over the canonical key (unslashed, printable ASCII,
 * no surrounding spaces), so no other spelling reaches the same row.
 */
final class BuilderMetaKeys {

    /**
     * Guard a write (or delete) of one post meta key.
     *
     * @param string $key Canonical meta key.
     */
    public static function guard( string $key ): ?\WP_Error {
        $group = self::match( $key );

        if ( null === $group ) {
            return null;
        }

        return ResponseFormatter::error(
            'builder_meta_key',
            sprintf(
                'The meta key "%1$s" is %2$s page-builder storage and cannot be written or deleted through the generic meta abilities. Write it through %3$s instead, so the builder\'s caches and generated CSS stay in sync. Reading it is allowed.',
                $key,
                $group['label'],
                $group['abilities']
            ),
            403
        );
    }

    /**
     * Guard every key of a meta map.
     *
     * @param mixed $meta The caller's meta map.
     */
    public static function guardMap( mixed $meta ): ?\WP_Error {
        if ( ! is_array( $meta ) ) {
            return null;
        }

        foreach ( array_keys( $meta ) as $key ) {
            $error = self::guard( (string) wp_unslash( (string) $key ) );
            if ( $error ) {
                return $error;
            }
        }

        return null;
    }

    /**
     * The builder group a key belongs to, if any.
     *
     * @param string $key Meta key.
     * @return array{label: string, abilities: string}|null
     */
    public static function match( string $key ): ?array {
        $normalized = strtolower( trim( $key ) );

        foreach ( self::groups() as $id => $group ) {
            if ( self::inGroup( $normalized, $group ) ) {
                return [
                    'label'     => (string) ( $group['label'] ?? $id ),
                    'abilities' => (string) ( $group['abilities'] ?? 'the builder\'s own abilities or editor' ),
                ];
            }
        }

        return null;
    }

    /**
     * Whether a normalized key is one of a group's keys, prefixes or patterns.
     *
     * @param string               $key   Normalized meta key.
     * @param array<string, mixed> $group Group definition.
     */
    private static function inGroup( string $key, array $group ): bool {
        foreach ( (array) ( $group['keys'] ?? [] ) as $exact ) {
            if ( strtolower( trim( (string) $exact ) ) === $key ) {
                return true;
            }
        }

        foreach ( (array) ( $group['prefixes'] ?? [] ) as $prefix ) {
            $prefix = strtolower( trim( (string) $prefix ) );
            if ( '' !== $prefix && str_starts_with( $key, $prefix ) ) {
                return true;
            }
        }

        foreach ( (array) ( $group['patterns'] ?? [] ) as $pattern ) {
            if ( 1 === @preg_match( (string) $pattern, $key ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a malformed filtered pattern must not warn on every write; it just never matches.
                return true;
            }
        }

        return false;
    }

    /**
     * Builder groups after the site owner's filter.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function groups(): array {
        /**
         * Filters the page-builder storage keys the generic meta abilities refuse to write.
         *
         * Each group is id => [ 'label', 'abilities', 'keys', 'prefixes', 'patterns' ]:
         * exact keys and prefixes are compared case-insensitively, patterns are
         * regular expressions matched against the lowercased key. Remove a group
         * (or return an empty array) to allow generic writes again; add one to
         * protect another builder.
         *
         * @param array<string, array<string, mixed>> $groups Builder groups.
         */
        return self::wellFormed( apply_filters( 'lw_site_manager_builder_meta_keys', BuilderMetaKeyList::groups() ) );
    }

    /**
     * Keep only array groups: a filter callback may return anything.
     *
     * @param mixed $groups Filtered groups.
     * @return array<string, array<string, mixed>>
     */
    private static function wellFormed( mixed $groups ): array {
        return is_array( $groups ) ? array_filter( $groups, 'is_array' ) : [];
    }
}
