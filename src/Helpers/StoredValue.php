<?php
/**
 * Compare values the way they end up in the database.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tells "already stored" apart from "write failed".
 *
 * Core's update_{type}_meta() and update_option() return false both when the
 * write fails and when the new value equals the stored one (an identity check,
 * or an UPDATE that affects 0 rows). These helpers repeat the core
 * normalization (unslash, registered sanitizer, serialization) and compare the
 * column value that would be written with the one already there.
 */
final class StoredValue {

    /**
     * The value as written to a meta_value / option_value column.
     *
     * @param mixed $value Value.
     */
    public static function column( mixed $value ): string {
        $serialized = maybe_serialize( $value );
        return is_scalar( $serialized ) ? (string) $serialized : '';
    }

    /**
     * Whether every stored row of a meta key already holds this value.
     *
     * @param string $type     Meta type: post, user, comment or term.
     * @param int    $objectId Object ID, as passed to update_{type}_meta().
     * @param string $key      Meta key, as passed to update_{type}_meta().
     * @param mixed  $value    Meta value, as passed to update_{type}_meta().
     */
    public static function metaUnchanged( string $type, int $objectId, string $key, mixed $value ): bool {
        if ( 'post' === $type ) {
            // update_post_meta() writes a revision's meta to its parent.
            $parent   = wp_is_post_revision( $objectId );
            $objectId = $parent ? (int) $parent : $objectId;
        }

        $key    = wp_unslash( $key );
        $stored = get_metadata_raw( $type, $objectId, $key, false );
        if ( ! is_array( $stored ) || [] === $stored ) {
            return false;
        }

        $value = sanitize_meta( $key, wp_unslash( $value ), $type, get_object_subtype( $type, $objectId ) );
        $new   = self::column( $value );
        foreach ( $stored as $old ) {
            if ( self::column( $old ) !== $new ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Whether an option already holds this value (after its sanitizer).
     *
     * @param string $option Option name.
     * @param mixed  $value  Value passed to update_option().
     */
    public static function optionUnchanged( string $option, mixed $value ): bool {
        return self::column( sanitize_option( $option, $value ) ) === self::column( get_option( $option ) );
    }
}
