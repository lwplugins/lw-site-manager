<?php
/**
 * Single writer for the set-*-meta abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Meta;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;
use LightweightPlugins\SiteManager\Helpers\StoredValue;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Writes one meta value and says whether it changed.
 *
 * Submitting the value that is already stored is a success with
 * `changed: false`; only a write that left a different value behind is an
 * error. Authorization stays with MetaGuard, which callers run first.
 */
final class MetaWriter {

    /**
     * Write a meta value through the core update_{type}_meta() function.
     *
     * @param string $type     Meta type: post, user, comment or term.
     * @param int    $objectId Object ID.
     * @param string $key      Meta key.
     * @param mixed  $value    Meta value.
     * @return bool|\WP_Error True when the stored value changed, false when it was already stored.
     */
    public static function set( string $type, int $objectId, string $key, mixed $value ): bool|\WP_Error {
        $result = match ( $type ) {
            'post'    => update_post_meta( $objectId, $key, $value ),
            'user'    => update_user_meta( $objectId, $key, $value ),
            'comment' => update_comment_meta( $objectId, $key, $value ),
            'term'    => update_term_meta( $objectId, $key, $value ),
            default   => false,
        };

        if ( $result instanceof \WP_Error ) {
            return $result;
        }
        if ( false !== $result ) {
            return true;
        }
        if ( StoredValue::metaUnchanged( $type, $objectId, $key, $value ) ) {
            return false;
        }

        return ResponseFormatter::error( 'update_failed', 'Failed to update meta', 500 );
    }
}
