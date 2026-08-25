<?php
/**
 * Deterministic comment counts, split by comment type.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Comments;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Counts comments per status for one comment type at a time.
 *
 * WordPress's own wp_count_comments() cannot separate types, and on a
 * WooCommerce store its result silently varies: WooCommerce filters it through
 * get_comments(), whose review-excluding clause bails out whenever another
 * plugin sets one of a dozen query vars. The same call therefore returned
 * different totals per site, with product reviews sometimes folded into the
 * comment counts and sometimes not.
 *
 * Querying each type explicitly removes that ambiguity — the numbers now mean
 * the same thing everywhere, and match what list-comments returns for the same
 * type.
 */
final class CommentCounter {

    /**
     * Response key => WP_Comment_Query status.
     */
    private const STATUSES = [
        'approved'     => 'approve',
        'awaiting'     => 'hold',
        'spam'         => 'spam',
        'trash'        => 'trash',
        'post_trashed' => 'post-trashed',
    ];

    /**
     * Count one comment type per status.
     *
     * `total` mirrors wp_count_comments()'s total_comments: approved plus
     * awaiting moderation, excluding spam and trash.
     *
     * @param string $type   Comment type ('comment', 'review', ...).
     * @param int    $postId Restrict to one post, or 0 for the whole site.
     * @return array<string, int>
     */
    public static function forType( string $type, int $postId = 0 ): array {
        $counts = [];

        foreach ( self::STATUSES as $key => $status ) {
            $args = [
                'type'                      => $type,
                'status'                    => $status,
                'count'                     => true,
                'orderby'                   => 'none',
                'update_comment_meta_cache' => false,
            ];

            if ( $postId > 0 ) {
                $args['post_id'] = $postId;
            }

            $counts[ $key ] = (int) get_comments( $args );
        }

        return [ 'total' => $counts['approved'] + $counts['awaiting'] ] + $counts;
    }
}
