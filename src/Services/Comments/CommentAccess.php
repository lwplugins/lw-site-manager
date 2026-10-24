<?php
/**
 * Comment lookup combined with the object-level capability check.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Comments;

use LightweightPlugins\SiteManager\Helpers\Capability;
use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolves the comment or post a comment ability acts on, and authorizes it.
 *
 * The comment abilities are registered with moderate_comments, which says only
 * that the caller moderates comments in general. WordPress expresses "may
 * moderate THIS comment" as edit_comment, which maps to edit_post of the
 * comment's post; role plugins hook exactly that to confine a moderator.
 */
final class CommentAccess {

    /**
     * The comment, when it exists and the current user may edit it.
     *
     * @param int $commentId Comment ID.
     * @return \WP_Comment|\WP_Error 404 when missing, 403 when not allowed.
     */
    public static function editable( int $commentId ): \WP_Comment|\WP_Error {
        $comment = get_comment( $commentId );
        if ( ! $comment instanceof \WP_Comment ) {
            return ResponseFormatter::notFound( 'comment', $commentId );
        }

        return Capability::editComment( $commentId ) ?? $comment;
    }

    /**
     * Whether the current user may add a comment to a post as a moderator.
     *
     * @param int $postId Target post ID.
     * @return \WP_Error|null 404 when the post is missing, 403 when not allowed.
     */
    public static function commentablePost( int $postId ): ?\WP_Error {
        if ( ! get_post( $postId ) ) {
            return ResponseFormatter::notFound( 'post', $postId );
        }

        return Capability::editPost( $postId );
    }
}
