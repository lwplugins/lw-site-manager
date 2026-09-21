<?php
/**
 * Meta keys that need an extra capability to write.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Meta;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Some meta values are served somewhere as raw markup, so the plugin that owns
 * them only lets users with a specific capability set them. The meta abilities
 * must not become a way around that: being an administrator is not enough,
 * because on multisite, or with DISALLOW_UNFILTERED_HTML, an administrator
 * does not hold unfiltered_html.
 *
 * Keys are compared case-insensitively, as the meta_key collation compares
 * them. MetaGuard hands over the canonical key (unslashed, printable ASCII);
 * the WooCommerce data stores write keys literally, so for them a stricter
 * match only ever refuses a harmless spelling.
 */
final class GatedMetaKeys {

    /**
     * Built-in gated keys, as meta key => required capability.
     *
     * LW SEO serves _lw_seo_markdown_content verbatim at /md, and its own
     * editor and abilities only let unfiltered_html users set it.
     *
     * @var array<string, string>
     */
    private const KEYS = [
        '_lw_seo_markdown_content' => 'unfiltered_html',
    ];

    /**
     * Guard a write (or delete) of one key.
     *
     * @param string $key Meta key being written.
     */
    public static function guard( string $key ): ?\WP_Error {
        $capability = self::deniedKeys()[ self::normalize( $key ) ] ?? null;

        if ( null === $capability ) {
            return null;
        }

        return ResponseFormatter::error(
            'forbidden_meta_key',
            sprintf( 'The meta key "%s" can only be modified by a user with the %s capability.', $key, $capability ),
            403
        );
    }

    /**
     * Guard every key of a meta map, for writes that bypass MetaGuard's
     * other rules (the WooCommerce meta maps).
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
     * Gated keys whose capability the current user lacks, by normalized key.
     *
     * @return array<string, string>
     */
    private static function deniedKeys(): array {
        /**
         * Filters the meta keys the meta abilities only let users with a
         * given capability write, as meta key => capability.
         *
         * @param array<string, string> $keys Gated keys.
         */
        $keys   = apply_filters( 'lw_site_manager_gated_meta_keys', self::KEYS );
        $denied = [];

        foreach ( $keys as $key => $capability ) {
            if ( '' !== $capability && ! current_user_can( $capability ) ) {
                $denied[ self::normalize( (string) $key ) ] = $capability;
            }
        }

        return $denied;
    }

    /**
     * Comparison form of a key.
     */
    private static function normalize( string $key ): string {
        return strtolower( trim( $key ) );
    }
}
