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
 * Keys are matched the way the database matches them. WordPress unslashes the
 * key, and meta_key lookups follow the column's case- and accent-insensitive
 * collation, so "_LW_SEO_Markdown_Content" would update the real row. The key
 * is therefore also resolved against the keys actually stored on the object.
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
     * Guard a write (or delete) of one key on one object.
     *
     * @param string $type     Object type: post, user, comment or term.
     * @param int    $objectId Target object ID; 0 for an object not created yet.
     * @param string $key      Meta key being written.
     */
    public static function guard( string $type, int $objectId, string $key ): ?\WP_Error {
        $denied = self::deniedKeys();
        if ( [] === $denied ) {
            return null;
        }

        $key        = (string) wp_unslash( $key );
        $capability = $denied[ self::normalize( $key ) ] ?? null;

        if ( null === $capability ) {
            foreach ( self::storedMatches( $type, $objectId, $key ) as $stored ) {
                $capability = $denied[ self::normalize( $stored ) ] ?? null;
                if ( null !== $capability ) {
                    break;
                }
            }
        }

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
     * Stored keys on this object that the database treats as equal to $key.
     *
     * @param string $type     Object type.
     * @param int    $objectId Target object ID.
     * @param string $key      Unslashed meta key.
     * @return array<int, string>
     */
    private static function storedMatches( string $type, int $objectId, string $key ): array {
        $table = $objectId > 0 ? _get_meta_table( $type ) : false;
        if ( ! $table ) {
            return [];
        }

        global $wpdb;
        $column = sanitize_key( $type . '_id' );

        // Table and column come from _get_meta_table() and the object type, as in core's own meta queries.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $stored = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$table} WHERE meta_key = %s AND {$column} = %d", $key, $objectId ) );

        return array_map( 'strval', (array) $stored );
    }

    /**
     * Comparison form of a key: the collation ignores case and trailing spaces.
     */
    private static function normalize( string $key ): string {
        return strtolower( trim( $key ) );
    }
}
