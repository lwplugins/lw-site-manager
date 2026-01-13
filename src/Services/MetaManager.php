<?php
/**
 * Meta Manager Service - Handles post, page, and user meta operations
 */

declare(strict_types=1);

namespace WPSiteManager\Services;

class MetaManager extends AbstractService {

    /**
     * Get post meta
     */
    public static function get_post_meta( array $input ): array|\WP_Error {
        if ( empty( $input['post_id'] ) ) {
            return self::errorResponse( 'missing_post_id', 'Post ID is required', 400 );
        }

        $post_id = (int) $input['post_id'];
        $post = get_post( $post_id );

        if ( ! $post ) {
            return self::errorResponse( 'not_found', 'Post not found', 404 );
        }

        // Get specific key or all meta
        if ( ! empty( $input['key'] ) ) {
            $value = get_post_meta( $post_id, $input['key'], true );
            return [
                'success' => true,
                'post_id' => $post_id,
                'key'     => $input['key'],
                'value'   => $value,
            ];
        }

        // Get all meta
        $all_meta = get_post_meta( $post_id );
        $meta = [];

        foreach ( $all_meta as $key => $values ) {
            // Skip private meta unless explicitly requested
            if ( ! ( $input['include_private'] ?? false ) && strpos( $key, '_' ) === 0 ) {
                continue;
            }
            $meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
        }

        return [
            'success' => true,
            'post_id' => $post_id,
            'meta'    => $meta,
        ];
    }

    /**
     * Set post meta
     */
    public static function set_post_meta( array $input ): array|\WP_Error {
        if ( empty( $input['post_id'] ) ) {
            return self::errorResponse( 'missing_post_id', 'Post ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }
        if ( ! isset( $input['value'] ) ) {
            return self::errorResponse( 'missing_value', 'Meta value is required', 400 );
        }

        $post_id = (int) $input['post_id'];
        $post = get_post( $post_id );

        if ( ! $post ) {
            return self::errorResponse( 'not_found', 'Post not found', 404 );
        }

        $result = update_post_meta( $post_id, $input['key'], $input['value'] );

        if ( $result === false ) {
            return self::errorResponse( 'update_failed', 'Failed to update meta', 500 );
        }

        return [
            'success' => true,
            'message' => 'Meta updated successfully',
            'post_id' => $post_id,
            'key'     => $input['key'],
            'value'   => $input['value'],
        ];
    }

    /**
     * Delete post meta
     */
    public static function delete_post_meta( array $input ): array|\WP_Error {
        if ( empty( $input['post_id'] ) ) {
            return self::errorResponse( 'missing_post_id', 'Post ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }

        $post_id = (int) $input['post_id'];
        $post = get_post( $post_id );

        if ( ! $post ) {
            return self::errorResponse( 'not_found', 'Post not found', 404 );
        }

        $result = delete_post_meta( $post_id, $input['key'] );

        return [
            'success' => $result,
            'message' => $result ? 'Meta deleted successfully' : 'Meta not found or already deleted',
            'post_id' => $post_id,
            'key'     => $input['key'],
        ];
    }

    /**
     * Get user meta
     */
    public static function get_user_meta( array $input ): array|\WP_Error {
        if ( empty( $input['user_id'] ) ) {
            return self::errorResponse( 'missing_user_id', 'User ID is required', 400 );
        }

        $user_id = (int) $input['user_id'];
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return self::errorResponse( 'not_found', 'User not found', 404 );
        }

        // Get specific key or all meta
        if ( ! empty( $input['key'] ) ) {
            $value = get_user_meta( $user_id, $input['key'], true );
            return [
                'success' => true,
                'user_id' => $user_id,
                'key'     => $input['key'],
                'value'   => $value,
            ];
        }

        // Get all meta
        $all_meta = get_user_meta( $user_id );
        $meta = [];

        foreach ( $all_meta as $key => $values ) {
            // Skip private meta unless explicitly requested
            if ( ! ( $input['include_private'] ?? false ) && strpos( $key, '_' ) === 0 ) {
                continue;
            }
            $meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
        }

        return [
            'success' => true,
            'user_id' => $user_id,
            'meta'    => $meta,
        ];
    }

    /**
     * Set user meta
     */
    public static function set_user_meta( array $input ): array|\WP_Error {
        if ( empty( $input['user_id'] ) ) {
            return self::errorResponse( 'missing_user_id', 'User ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }
        if ( ! isset( $input['value'] ) ) {
            return self::errorResponse( 'missing_value', 'Meta value is required', 400 );
        }

        $user_id = (int) $input['user_id'];
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return self::errorResponse( 'not_found', 'User not found', 404 );
        }

        $result = update_user_meta( $user_id, $input['key'], $input['value'] );

        if ( $result === false ) {
            return self::errorResponse( 'update_failed', 'Failed to update meta', 500 );
        }

        return [
            'success' => true,
            'message' => 'Meta updated successfully',
            'user_id' => $user_id,
            'key'     => $input['key'],
            'value'   => $input['value'],
        ];
    }

    /**
     * Delete user meta
     */
    public static function delete_user_meta( array $input ): array|\WP_Error {
        if ( empty( $input['user_id'] ) ) {
            return self::errorResponse( 'missing_user_id', 'User ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }

        $user_id = (int) $input['user_id'];
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return self::errorResponse( 'not_found', 'User not found', 404 );
        }

        $result = delete_user_meta( $user_id, $input['key'] );

        return [
            'success' => $result,
            'message' => $result ? 'Meta deleted successfully' : 'Meta not found or already deleted',
            'user_id' => $user_id,
            'key'     => $input['key'],
        ];
    }

    /**
     * Get term meta
     */
    public static function get_term_meta( array $input ): array|\WP_Error {
        if ( empty( $input['term_id'] ) ) {
            return self::errorResponse( 'missing_term_id', 'Term ID is required', 400 );
        }

        $term_id = (int) $input['term_id'];
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        // Get specific key or all meta
        if ( ! empty( $input['key'] ) ) {
            $value = get_term_meta( $term_id, $input['key'], true );
            return [
                'success' => true,
                'term_id' => $term_id,
                'key'     => $input['key'],
                'value'   => $value,
            ];
        }

        // Get all meta
        $all_meta = get_term_meta( $term_id );
        $meta = [];

        foreach ( $all_meta as $key => $values ) {
            $meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
        }

        return [
            'success' => true,
            'term_id' => $term_id,
            'meta'    => $meta,
        ];
    }

    /**
     * Set term meta
     */
    public static function set_term_meta( array $input ): array|\WP_Error {
        if ( empty( $input['term_id'] ) ) {
            return self::errorResponse( 'missing_term_id', 'Term ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }
        if ( ! isset( $input['value'] ) ) {
            return self::errorResponse( 'missing_value', 'Meta value is required', 400 );
        }

        $term_id = (int) $input['term_id'];
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        $result = update_term_meta( $term_id, $input['key'], $input['value'] );

        if ( $result === false ) {
            return self::errorResponse( 'update_failed', 'Failed to update meta', 500 );
        }

        return [
            'success' => true,
            'message' => 'Meta updated successfully',
            'term_id' => $term_id,
            'key'     => $input['key'],
            'value'   => $input['value'],
        ];
    }

    /**
     * Delete term meta
     */
    public static function delete_term_meta( array $input ): array|\WP_Error {
        if ( empty( $input['term_id'] ) ) {
            return self::errorResponse( 'missing_term_id', 'Term ID is required', 400 );
        }
        if ( empty( $input['key'] ) ) {
            return self::errorResponse( 'missing_key', 'Meta key is required', 400 );
        }

        $term_id = (int) $input['term_id'];
        $term = get_term( $term_id );

        if ( ! $term || is_wp_error( $term ) ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        $result = delete_term_meta( $term_id, $input['key'] );

        return [
            'success' => $result,
            'message' => $result ? 'Meta deleted successfully' : 'Meta not found or already deleted',
            'term_id' => $term_id,
            'key'     => $input['key'],
        ];
    }
}
