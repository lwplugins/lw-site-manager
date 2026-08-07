<?php
/**
 * Object-level (meta) capability checks.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Object-level capability checks for the service layer.
 *
 * Ability registration can only gate on *primitive* capabilities
 * ( edit_posts, edit_users, ... ), which mean "may edit posts in general" —
 * never "may edit THIS post". WordPress expresses the real rule as a *meta*
 * capability resolved against a target ID through map_meta_cap(), and that is
 * also the layer WooCommerce ( wc_modify_map_meta_cap ) and role plugins hook
 * to constrain delegated managers. A service that skips it bypasses every one
 * of those protections, so each service must re-check against the object it is
 * about to read or write.
 *
 * Every method returns null when the action is allowed, or a 403 WP_Error when
 * it is denied, matching the AbstractService::validate* convention.
 */
final class Capability {

    /**
     * Check a meta capability against a target object ID.
     *
     * @param string $capability Meta capability name (e.g. edit_post).
     * @param int    $objectId   Target object ID.
     * @param string $message    Message for the denial response.
     */
    public static function check( string $capability, int $objectId, string $message ): ?\WP_Error {
        if ( current_user_can( $capability, $objectId ) ) {
            return null;
        }

        return ResponseFormatter::error( 'forbidden', $message, 403 );
    }

    /**
     * Whether the current user may edit a specific post.
     */
    public static function editPost( int $postId ): ?\WP_Error {
        return self::check( 'edit_post', $postId, 'You are not allowed to edit this post.' );
    }

    /**
     * Whether the current user may delete a specific post.
     */
    public static function deletePost( int $postId ): ?\WP_Error {
        return self::check( 'delete_post', $postId, 'You are not allowed to delete this post.' );
    }

    /**
     * Whether the current user may read a specific post.
     */
    public static function readPost( int $postId ): ?\WP_Error {
        return self::check( 'read_post', $postId, 'You are not allowed to read this post.' );
    }

    /**
     * Whether the current user may edit a specific user account.
     */
    public static function editUser( int $userId ): ?\WP_Error {
        return self::check( 'edit_user', $userId, 'You are not allowed to edit this user.' );
    }

    /**
     * Whether the current user may change a specific user's role.
     */
    public static function promoteUser( int $userId ): ?\WP_Error {
        return self::check( 'promote_user', $userId, 'You are not allowed to change this user\'s role.' );
    }

    /**
     * Whether the current user may edit a specific comment.
     */
    public static function editComment( int $commentId ): ?\WP_Error {
        return self::check( 'edit_comment', $commentId, 'You are not allowed to edit this comment.' );
    }

    /**
     * Whether the current user may edit a specific term.
     */
    public static function editTerm( int $termId ): ?\WP_Error {
        return self::check( 'edit_term', $termId, 'You are not allowed to edit this term.' );
    }

    /**
     * Guard a super-admin target against a caller who is not one.
     *
     * Mirrors the guard UserManager::delete_user() already applies, so a
     * delegated user manager cannot take over a network administrator.
     */
    public static function notSuperAdmin( int $userId ): ?\WP_Error {
        if ( ! is_super_admin( $userId ) || is_super_admin() ) {
            return null;
        }

        return ResponseFormatter::error(
            'forbidden',
            'You are not allowed to modify a super admin account.',
            403
        );
    }
}
