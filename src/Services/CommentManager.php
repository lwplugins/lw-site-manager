<?php
/**
 * Comment Manager Service - Comment management operations
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

use LightweightPlugins\SiteManager\Services\Comments\CommentCounter;

class CommentManager extends AbstractService {

    /**
     * List comments
     */
    public static function list_comments( array $input ): array {
        $args = [
            'orderby' => $input['orderby'] ?? 'comment_date',
            'order'   => $input['order'] ?? 'DESC',
            // Pin the type. Left unset, whether WooCommerce product reviews
            // appear here depends on which other plugins are active: WooCommerce
            // excludes reviews from comment queries, but bails out of that
            // filter when any of a dozen query vars is set (type__not_in among
            // them, which LearnDash populates), so the same call returned
            // different rows per site. WordPress maps 'comment' to
            // comment_type IN ( '', 'comment' ), so legacy comments stored with
            // an empty type are still included. Pass type=review for reviews,
            // or type=all to opt back into everything.
            'type'    => $input['type'] ?? 'comment',
        ];

        // Apply pagination
        self::applyPaginationToCommentQuery( $args, $input );

        // Filter by status
        if ( ! empty( $input['status'] ) ) {
            $args['status'] = $input['status'];
        }

        // Filter by post
        if ( ! empty( $input['post_id'] ) ) {
            $args['post_id'] = $input['post_id'];
        }

        // Search
        if ( ! empty( $input['search'] ) ) {
            $args['search'] = $input['search'];
        }

        $comments = get_comments( $args );
        $formatted = [];

        foreach ( $comments as $comment ) {
            $formatted[] = self::format_comment( $comment );
        }

        // Get total count
        $count_args = $args;
        $count_args['count'] = true;
        unset( $count_args['number'], $count_args['offset'] );
        $total = get_comments( $count_args );

        return self::listResponse(
            'comments',
            $formatted,
            $total,
            $args['number'],
            $args['offset']
        );
    }

    /**
     * Get single comment
     */
    public static function get_comment( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $comment = self::getCommentOrError( $input['id'] );
        if ( is_wp_error( $comment ) ) {
            return $comment;
        }

        return self::entityResponse( 'comment', self::format_comment( $comment, true ) );
    }

    /**
     * Create comment
     */
    public static function create_comment( array $input ): array|\WP_Error {
        $error = self::validateRequired( $input, [ 'post_id', 'content' ] );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['post_id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $commentdata = [
            'comment_post_ID'      => $input['post_id'],
            'comment_content'      => wp_kses_post( $input['content'] ),
            'comment_author'       => $input['author_name'] ?? '',
            'comment_author_email' => $input['author_email'] ?? '',
            'comment_author_url'   => $input['author_url'] ?? '',
            'comment_parent'       => $input['parent'] ?? 0,
            'comment_approved'     => $input['status'] ?? 1,
        ];

        // If user_id provided, use that user's info
        if ( ! empty( $input['user_id'] ) ) {
            $user = get_user_by( 'id', $input['user_id'] );
            if ( $user ) {
                $commentdata['user_id'] = $user->ID;
                $commentdata['comment_author'] = $user->display_name;
                $commentdata['comment_author_email'] = $user->user_email;
            }
        }

        $comment_id = wp_insert_comment( $commentdata );

        if ( ! $comment_id ) {
            return self::errorResponse( 'create_failed', 'Failed to create comment', 500 );
        }

        // Apply inline meta map
        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            foreach ( $input['meta'] as $key => $value ) {
                update_comment_meta( (int) $comment_id, (string) $key, $value );
            }
        }

        $comment = get_comment( $comment_id );

        return self::createdResponse( 'comment', self::format_comment( $comment ), $comment_id );
    }

    /**
     * Update comment
     */
    public static function update_comment( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $comment = self::getCommentOrError( $input['id'] );
        if ( is_wp_error( $comment ) ) {
            return $comment;
        }

        $commentdata = [ 'comment_ID' => $input['id'] ];

        if ( isset( $input['content'] ) ) {
            $commentdata['comment_content'] = wp_kses_post( $input['content'] );
        }
        if ( isset( $input['author_name'] ) ) {
            $commentdata['comment_author'] = sanitize_text_field( $input['author_name'] );
        }
        if ( isset( $input['author_email'] ) ) {
            $commentdata['comment_author_email'] = sanitize_email( $input['author_email'] );
        }
        if ( isset( $input['author_url'] ) ) {
            $commentdata['comment_author_url'] = esc_url_raw( $input['author_url'] );
        }
        if ( isset( $input['status'] ) ) {
            $commentdata['comment_approved'] = $input['status'];
        }

        $has_data = count( $commentdata ) > 1;
        $has_meta = ! empty( $input['meta'] ) && is_array( $input['meta'] );

        if ( $has_data ) {
            $result = wp_update_comment( $commentdata );
            if ( ! $result ) {
                return self::errorResponse( 'update_failed', 'Failed to update comment', 500 );
            }
        }

        if ( $has_meta ) {
            foreach ( $input['meta'] as $key => $value ) {
                update_comment_meta( (int) $input['id'], (string) $key, $value );
            }
        }

        $updated_comment = get_comment( $input['id'] );

        return self::updatedResponse( 'comment', self::format_comment( $updated_comment ) );
    }

    /**
     * Delete comment
     */
    public static function delete_comment( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $comment = self::getCommentOrError( $input['id'] );
        if ( is_wp_error( $comment ) ) {
            return $comment;
        }

        $force_delete = $input['force'] ?? false;
        $deleted = wp_delete_comment( $input['id'], $force_delete );

        if ( ! $deleted ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete comment', 500 );
        }

        return [
            'success'      => true,
            'message'      => $force_delete ? 'Comment permanently deleted' : 'Comment moved to trash',
            'deleted_id'   => $input['id'],
            'force_delete' => $force_delete,
        ];
    }

    /**
     * Approve comment
     */
    public static function approve_comment( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $comment = self::getCommentOrError( $input['id'] );
        if ( is_wp_error( $comment ) ) {
            return $comment;
        }

        $result = wp_set_comment_status( $input['id'], 'approve' );

        if ( ! $result ) {
            return self::errorResponse( 'approve_failed', 'Failed to approve comment', 500 );
        }

        return self::successResponse(
            [ 'comment' => self::format_comment( get_comment( $input['id'] ) ) ],
            'Comment approved'
        );
    }

    /**
     * Mark comment as spam
     */
    public static function spam_comment( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $comment = self::getCommentOrError( $input['id'] );
        if ( is_wp_error( $comment ) ) {
            return $comment;
        }

        $result = wp_spam_comment( $input['id'] );

        if ( ! $result ) {
            return self::errorResponse( 'spam_failed', 'Failed to mark comment as spam', 500 );
        }

        return self::successResponse(
            [ 'id' => $input['id'] ],
            'Comment marked as spam'
        );
    }

    /**
     * Bulk action on comments
     */
    public static function bulk_action( array $input ): array|\WP_Error {
        $error = self::validateIdArray( $input, 'ids' );
        if ( $error ) {
            return $error;
        }

        $error = self::validateRequiredField( $input, 'action', 'Action is required' );
        if ( $error ) {
            return $error;
        }

        $valid_actions = [ 'approve', 'unapprove', 'spam', 'trash', 'delete' ];
        $error = self::validateEnum( $input['action'], $valid_actions, 'action' );
        if ( $error ) {
            return $error;
        }

        $success = [];
        $failed = [];

        foreach ( $input['ids'] as $id ) {
            $comment = get_comment( $id );
            if ( ! $comment ) {
                $failed[] = (int) $id;
                continue;
            }

            $result = false;
            switch ( $input['action'] ) {
                case 'approve':
                    $result = wp_set_comment_status( $id, 'approve' );
                    break;
                case 'unapprove':
                    $result = wp_set_comment_status( $id, 'hold' );
                    break;
                case 'spam':
                    $result = wp_spam_comment( $id );
                    break;
                case 'trash':
                    $result = wp_trash_comment( $id );
                    break;
                case 'delete':
                    $result = wp_delete_comment( $id, true );
                    break;
            }

            if ( $result ) {
                $success[] = $id;
            } else {
                $failed[] = (int) $id;
            }
        }

        return self::bulkResponse( $success, $failed, $input['action'] );
    }

    /**
     * Get comment counts by status
     */
    public static function get_counts( array $input = [] ): array {
        $post_id = (int) ( $input['post_id'] ?? 0 );

        // Comments and product reviews are counted separately: they are
        // different things, and summing them made the totals depend on which
        // other plugins the site runs. See CommentCounter.
        return CommentCounter::forType( 'comment', $post_id )
            + [ 'reviews' => CommentCounter::forType( 'review', $post_id ) ];
    }

    /**
     * Format comment for output
     */
    private static function format_comment( \WP_Comment $comment, bool $detailed = false ): array {
        $data = [
            'id'           => (int) $comment->comment_ID,
            'post_id'      => (int) $comment->comment_post_ID,
            'author'       => $comment->comment_author,
            'author_email' => $comment->comment_author_email,
            'content'      => $comment->comment_content,
            'date'         => $comment->comment_date,
            'status'       => wp_get_comment_status( $comment ),
            'parent'       => (int) $comment->comment_parent,
            'type'         => $comment->comment_type ?: 'comment',
        ];

        // WooCommerce stores the star rating and the verified-purchase flag as
        // comment meta. Without them a review is just text, and an agent asked
        // to moderate reviews cannot see what it is moderating.
        if ( 'review' === $comment->comment_type ) {
            $rating = get_comment_meta( (int) $comment->comment_ID, 'rating', true );

            $data['rating']   = ( '' === $rating || null === $rating ) ? null : (int) $rating;
            $data['verified'] = (bool) get_comment_meta( (int) $comment->comment_ID, 'verified', true );
        }

        if ( $detailed ) {
            $data['author_url'] = $comment->comment_author_url;
            $data['author_ip'] = $comment->comment_author_IP;
            $data['user_id'] = (int) $comment->user_id;
            $data['agent'] = $comment->comment_agent;
            $data['date_gmt'] = $comment->comment_date_gmt;
            $data['post_title'] = get_the_title( (int) $comment->comment_post_ID );
            $data['avatar'] = get_avatar_url( $comment->comment_author_email, [ 'size' => 48 ] );

            // Get replies count
            $data['replies_count'] = (int) get_comments( [
                'parent' => (int) $comment->comment_ID,
                'count'  => true,
            ] );
        }

        return $data;
    }
}
