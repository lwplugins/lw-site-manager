<?php
/**
 * Post Manager Service - Post management operations
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

class PostManager extends AbstractService {

    /**
     * Process tags input - handles both IDs and names/slugs
     *
     * @param array $tags Array of tag IDs (int) or names/slugs (string)
     * @return array Array of tag IDs for wp_set_post_tags
     */
    private static function process_tags( array $tags ): array {
        $tag_ids = [];

        foreach ( $tags as $tag ) {
            if ( is_int( $tag ) ) {
                // Integer: treat as tag ID
                $term = get_term( $tag, 'post_tag' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $tag_ids[] = $term->term_id;
                }
            } else {
                // String: try slug first, then name, then create new
                $term = get_term_by( 'slug', $tag, 'post_tag' );
                if ( ! $term ) {
                    $term = get_term_by( 'name', $tag, 'post_tag' );
                }
                if ( $term ) {
                    $tag_ids[] = $term->term_id;
                } else {
                    // Create new tag
                    $new_term = wp_insert_term( $tag, 'post_tag' );
                    if ( ! is_wp_error( $new_term ) ) {
                        $tag_ids[] = $new_term['term_id'];
                    }
                }
            }
        }

        return $tag_ids;
    }

    /**
     * Set taxonomies for a post
     *
     * @param int   $post_id    Post ID
     * @param array $taxonomies Array of taxonomy => term_ids pairs
     */
    private static function set_post_taxonomies( int $post_id, array $taxonomies ): void {
        foreach ( $taxonomies as $taxonomy => $term_ids ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            wp_set_object_terms( $post_id, array_map( 'intval', (array) $term_ids ), $taxonomy );
        }
    }

    /**
     * Set terms for a post (public method for ability)
     */
    public static function set_post_terms( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        if ( empty( $input['taxonomy'] ) ) {
            return self::errorResponse( 'missing_taxonomy', 'Taxonomy is required', 400 );
        }

        $taxonomy = $input['taxonomy'];
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return self::errorResponse( 'invalid_taxonomy', 'Invalid taxonomy: ' . $taxonomy, 400 );
        }

        // Check if taxonomy is registered for this post type
        $post_type_taxonomies = get_object_taxonomies( $post->post_type );
        if ( ! in_array( $taxonomy, $post_type_taxonomies, true ) ) {
            return self::errorResponse(
                'taxonomy_not_registered',
                "Taxonomy '{$taxonomy}' is not registered for post type '{$post->post_type}'",
                400
            );
        }

        $term_ids = isset( $input['terms'] ) ? array_map( 'intval', (array) $input['terms'] ) : [];
        $append = $input['append'] ?? false;

        $result = wp_set_object_terms( $input['id'], $term_ids, $taxonomy, $append );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'set_terms_failed', $result->get_error_message(), 500 );
        }

        // Get updated terms
        $terms = wp_get_object_terms( $input['id'], $taxonomy );
        $formatted_terms = array_map( fn( $term ) => [
            'id'   => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        ], $terms );

        return [
            'success'  => true,
            'message'  => 'Terms updated successfully',
            'post_id'  => $input['id'],
            'taxonomy' => $taxonomy,
            'terms'    => $formatted_terms,
        ];
    }

    /**
     * Get terms for a post
     */
    public static function get_post_terms( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $taxonomy = $input['taxonomy'] ?? null;

        if ( $taxonomy ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                return self::errorResponse( 'invalid_taxonomy', 'Invalid taxonomy: ' . $taxonomy, 400 );
            }

            $terms = wp_get_object_terms( $input['id'], $taxonomy );
            if ( is_wp_error( $terms ) ) {
                return self::errorResponse( 'terms_error', $terms->get_error_message(), 500 );
            }
            $formatted_terms = array_map( fn( $term ) => [
                'id'       => $term->term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'taxonomy' => $term->taxonomy,
            ], $terms );

            return [
                'success'  => true,
                'post_id'  => $input['id'],
                'taxonomy' => $taxonomy,
                'terms'    => $formatted_terms,
            ];
        }

        // Get all taxonomies for post type
        $post_type_taxonomies = get_object_taxonomies( $post->post_type );
        $all_terms = [];

        foreach ( $post_type_taxonomies as $tax ) {
            $terms = wp_get_object_terms( $input['id'], $tax );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $all_terms[ $tax ] = array_map( fn( $term ) => [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ], $terms );
            }
        }

        return [
            'success'    => true,
            'post_id'    => $input['id'],
            'taxonomies' => $all_terms,
        ];
    }

    /**
     * List posts
     */
    public static function list_posts( array $input ): array {
        $args = [
            'post_type'   => $input['post_type'] ?? 'post',
            'orderby'     => $input['orderby'] ?? 'date',
            'order'       => $input['order'] ?? 'DESC',
            'post_status' => $input['status'] ?? 'any',
        ];

        // Apply pagination
        self::applyPaginationToQuery( $args, $input );

        // Filter by author
        if ( ! empty( $input['author'] ) ) {
            $args['author'] = $input['author'];
        }

        // Filter by category
        if ( ! empty( $input['category'] ) ) {
            $args['category_name'] = $input['category'];
        }

        // Filter by tag
        if ( ! empty( $input['tag'] ) ) {
            $args['tag'] = $input['tag'];
        }

        // Search
        if ( ! empty( $input['search'] ) ) {
            $args['s'] = $input['search'];
        }

        // Date query
        if ( ! empty( $input['date_after'] ) ) {
            $args['date_query'][] = [
                'after' => $input['date_after'],
            ];
        }
        if ( ! empty( $input['date_before'] ) ) {
            $args['date_query'][] = [
                'before' => $input['date_before'],
            ];
        }

        // Meta query
        if ( ! empty( $input['meta_key'] ) ) {
            $args['meta_key'] = $input['meta_key'];
            if ( ! empty( $input['meta_value'] ) ) {
                $args['meta_value'] = $input['meta_value'];
            }
        }

        $query = new \WP_Query( $args );
        $posts = [];

        foreach ( $query->posts as $post ) {
            $posts[] = self::format_post( $post );
        }

        return self::listResponse(
            'posts',
            $posts,
            $query->found_posts,
            $args['posts_per_page'],
            $args['offset']
        );
    }

    /**
     * Get single post
     */
    public static function get_post( array $input ): array|\WP_Error {
        $post = null;

        if ( ! empty( $input['id'] ) ) {
            $post = get_post( $input['id'] );
        } elseif ( ! empty( $input['slug'] ) ) {
            $posts = get_posts( [
                'name'        => $input['slug'],
                'post_type'   => $input['post_type'] ?? 'post',
                'post_status' => 'any',
                'numberposts' => 1,
            ] );
            $post = $posts[0] ?? null;
        }

        if ( ! $post ) {
            return self::errorResponse( 'post_not_found', 'Post not found', 404 );
        }

        return self::entityResponse( 'post', self::format_post( $post, true ) );
    }

    /**
     * Create post
     */
    public static function create_post( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'title', 'Post title is required' );
        if ( $error ) {
            return $error;
        }

        $postarr = [
            'post_title'   => sanitize_text_field( $input['title'] ),
            'post_content' => $input['content'] ?? '',
            'post_excerpt' => $input['excerpt'] ?? '',
            'post_status'  => $input['status'] ?? 'draft',
            'post_type'    => $input['post_type'] ?? 'post',
            'post_author'  => $input['author'] ?? get_current_user_id(),
        ];

        // Slug
        if ( ! empty( $input['slug'] ) ) {
            $postarr['post_name'] = sanitize_title( $input['slug'] );
        }

        // Parent (for hierarchical types)
        if ( ! empty( $input['parent'] ) ) {
            $postarr['post_parent'] = $input['parent'];
        }

        // Menu order
        if ( isset( $input['menu_order'] ) ) {
            $postarr['menu_order'] = (int) $input['menu_order'];
        }

        // Date
        if ( ! empty( $input['date'] ) ) {
            $postarr['post_date'] = $input['date'];
        }

        // Categories (for posts)
        if ( ! empty( $input['categories'] ) ) {
            $postarr['post_category'] = (array) $input['categories'];
        }

        $post_id = wp_insert_post( $postarr, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Tags (after post creation to handle IDs properly)
        if ( ! empty( $input['tags'] ) ) {
            $tag_ids = self::process_tags( (array) $input['tags'] );
            if ( ! empty( $tag_ids ) ) {
                wp_set_post_tags( $post_id, $tag_ids );
            }
        }

        // Custom taxonomies
        if ( ! empty( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ) {
            self::set_post_taxonomies( $post_id, $input['taxonomies'] );
        }

        // Featured image
        if ( ! empty( $input['featured_image'] ) ) {
            set_post_thumbnail( $post_id, $input['featured_image'] );
        }

        // Custom meta fields
        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            foreach ( $input['meta'] as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
        }

        $post = get_post( $post_id );

        return self::createdResponse( 'post', self::format_post( $post, true ), $post_id );
    }

    /**
     * Update post
     */
    public static function update_post( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $postarr = [ 'ID' => $input['id'] ];

        if ( isset( $input['title'] ) ) {
            $postarr['post_title'] = sanitize_text_field( $input['title'] );
        }
        if ( isset( $input['content'] ) ) {
            $postarr['post_content'] = $input['content'];
        }
        if ( isset( $input['excerpt'] ) ) {
            $postarr['post_excerpt'] = $input['excerpt'];
        }
        if ( isset( $input['status'] ) ) {
            $postarr['post_status'] = $input['status'];
        }
        if ( isset( $input['slug'] ) ) {
            $postarr['post_name'] = sanitize_title( $input['slug'] );
        }
        if ( isset( $input['author'] ) ) {
            $postarr['post_author'] = $input['author'];
        }
        if ( isset( $input['parent'] ) ) {
            $postarr['post_parent'] = $input['parent'];
        }
        if ( isset( $input['menu_order'] ) ) {
            $postarr['menu_order'] = (int) $input['menu_order'];
        }
        if ( isset( $input['date'] ) ) {
            $postarr['post_date'] = $input['date'];
        }

        // Categories
        if ( isset( $input['categories'] ) ) {
            $postarr['post_category'] = (array) $input['categories'];
        }

        $result = wp_update_post( $postarr, true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Tags (after post update to handle IDs properly)
        if ( isset( $input['tags'] ) ) {
            $tag_ids = self::process_tags( (array) $input['tags'] );
            wp_set_post_tags( $input['id'], $tag_ids );
        }

        // Custom taxonomies
        if ( ! empty( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ) {
            self::set_post_taxonomies( $input['id'], $input['taxonomies'] );
        }

        // Featured image
        if ( isset( $input['featured_image'] ) ) {
            if ( $input['featured_image'] ) {
                set_post_thumbnail( $input['id'], $input['featured_image'] );
            } else {
                delete_post_thumbnail( $input['id'] );
            }
        }

        // Custom meta fields
        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            foreach ( $input['meta'] as $key => $value ) {
                update_post_meta( $input['id'], $key, $value );
            }
        }

        $updated_post = get_post( $input['id'] );

        return self::updatedResponse( 'post', self::format_post( $updated_post, true ) );
    }

    /**
     * Delete post
     */
    public static function delete_post( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $force_delete = $input['force'] ?? false;
        $deleted = wp_delete_post( $input['id'], $force_delete );

        if ( ! $deleted ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete post', 500 );
        }

        return [
            'success'      => true,
            'message'      => $force_delete ? 'Post permanently deleted' : 'Post moved to trash',
            'deleted_id'   => $input['id'],
            'force_delete' => $force_delete,
        ];
    }

    /**
     * Restore post from trash
     */
    public static function restore_post( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        if ( $post->post_status !== 'trash' ) {
            return self::errorResponse( 'not_in_trash', 'Post is not in trash', 400 );
        }

        $restored = wp_untrash_post( $input['id'] );

        if ( ! $restored ) {
            return self::errorResponse( 'restore_failed', 'Failed to restore post', 500 );
        }

        return self::successResponse(
            [ 'post' => self::format_post( get_post( $input['id'] ) ) ],
            'Post restored from trash'
        );
    }

    /**
     * Duplicate post
     */
    public static function duplicate_post( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $post = self::getPostOrError( $input['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $new_title = $input['new_title'] ?? $post->post_title . ' (Copy)';
        $new_status = $input['status'] ?? 'draft';

        $new_post = [
            'post_title'   => $new_title,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_status'  => $new_status,
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
            'post_parent'  => $post->post_parent,
            'menu_order'   => $post->menu_order,
        ];

        $new_post_id = wp_insert_post( $new_post, true );

        if ( is_wp_error( $new_post_id ) ) {
            return $new_post_id;
        }

        // Copy taxonomies
        $taxonomies = get_object_taxonomies( $post->post_type );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
            wp_set_object_terms( $new_post_id, $terms, $taxonomy );
        }

        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        if ( $thumbnail_id ) {
            set_post_thumbnail( $new_post_id, $thumbnail_id );
        }

        // Copy meta (optionally)
        if ( $input['copy_meta'] ?? true ) {
            $meta = get_post_meta( $post->ID );
            foreach ( $meta as $key => $values ) {
                if ( $key === '_edit_lock' || $key === '_edit_last' ) {
                    continue;
                }
                foreach ( $values as $value ) {
                    add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
                }
            }
        }

        return [
            'success'     => true,
            'message'     => 'Post duplicated successfully',
            'original_id' => $input['id'],
            'post'        => self::format_post( get_post( $new_post_id ), true ),
        ];
    }

    /**
     * Bulk action on posts
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

        $valid_actions = [ 'publish', 'draft', 'trash', 'delete', 'restore' ];
        $error = self::validateEnum( $input['action'], $valid_actions, 'action' );
        if ( $error ) {
            return $error;
        }

        $success = [];
        $failed = [];

        foreach ( $input['ids'] as $id ) {
            $post = get_post( $id );
            if ( ! $post ) {
                $failed[] = [ 'id' => $id, 'reason' => 'Not found' ];
                continue;
            }

            $result = false;
            switch ( $input['action'] ) {
                case 'publish':
                    $result = wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
                    break;
                case 'draft':
                    $result = wp_update_post( [ 'ID' => $id, 'post_status' => 'draft' ] );
                    break;
                case 'trash':
                    $result = wp_trash_post( $id );
                    break;
                case 'delete':
                    $result = wp_delete_post( $id, true );
                    break;
                case 'restore':
                    $result = wp_untrash_post( $id );
                    break;
            }

            if ( $result ) {
                $success[] = $id;
            } else {
                $failed[] = [ 'id' => $id, 'reason' => 'Action failed' ];
            }
        }

        return self::bulkResponse( $success, $failed, $input['action'] );
    }

    /**
     * Get post types
     */
    public static function get_post_types( array $input = [] ): array {
        $args = [
            'public' => $input['public'] ?? null,
        ];

        $post_types = get_post_types( array_filter( $args ), 'objects' );
        $formatted = [];

        foreach ( $post_types as $post_type ) {
            $formatted[] = [
                'name'         => $post_type->name,
                'label'        => $post_type->label,
                'singular'     => $post_type->labels->singular_name,
                'public'       => $post_type->public,
                'hierarchical' => $post_type->hierarchical,
                'has_archive'  => $post_type->has_archive,
                'supports'     => get_all_post_type_supports( $post_type->name ),
            ];
        }

        return [
            'post_types' => $formatted,
            'total'      => count( $formatted ),
        ];
    }

    /**
     * Format post for output
     */
    private static function format_post( \WP_Post $post, bool $detailed = false ): array {
        $thumbnail_id = get_post_thumbnail_id( $post->ID );

        $data = [
            'id'                => $post->ID,
            'title'             => $post->post_title,
            'slug'              => $post->post_name,
            'status'            => $post->post_status,
            'type'              => $post->post_type,
            'date'              => $post->post_date,
            'modified'          => $post->post_modified,
            'author'            => (int) $post->post_author,
            'featured_image_id' => $thumbnail_id ? $thumbnail_id : null,
        ];

        if ( $detailed ) {
            $data['content'] = $post->post_content;
            $data['excerpt'] = $post->post_excerpt;
            $data['parent'] = (int) $post->post_parent;
            $data['menu_order'] = (int) $post->menu_order;
            $data['guid'] = $post->guid;
            $data['permalink'] = get_permalink( $post );
            $data['author_name'] = get_the_author_meta( 'display_name', $post->post_author );

            // Featured image (full details - $thumbnail_id already set above)
            $data['featured_image'] = $thumbnail_id ? [
                'id'  => $thumbnail_id,
                'url' => wp_get_attachment_url( $thumbnail_id ),
            ] : null;

            // Taxonomies - get all registered taxonomies for this post type
            $post_taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
            $data['taxonomies'] = [];

            foreach ( $post_taxonomies as $taxonomy ) {
                $terms = wp_get_object_terms( $post->ID, $taxonomy->name );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $data['taxonomies'][ $taxonomy->name ] = array_map( fn( $term ) => [
                        'id'   => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ], $terms );
                }
            }

            // Legacy: categories and tags for 'post' type (backwards compatibility)
            if ( $post->post_type === 'post' ) {
                $data['categories'] = $data['taxonomies']['category'] ?? [];
                $data['tags'] = $data['taxonomies']['post_tag'] ?? [];
            }

            // Comments count
            $data['comment_count'] = (int) $post->comment_count;
            $data['comment_status'] = $post->comment_status;
        }

        return $data;
    }
}
