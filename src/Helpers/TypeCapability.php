<?php
/**
 * Capability checks resolved through post type and taxonomy cap objects.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Post type and taxonomy capability checks for the service layer.
 *
 * Each post type and taxonomy names its own capabilities ( a page is created
 * with edit_pages, a product category is assigned with assign_product_terms ),
 * so the generic primitive an ability is registered with says nothing about
 * the type actually being written. These checks follow the rules the core REST
 * controllers apply: create_posts to create, publish_posts to publish, schedule
 * or make private, edit_others_posts to write on behalf of another author, and
 * assign_terms / edit_terms / delete_terms for term operations.
 *
 * Every method returns null when the action is allowed, or a WP_Error when it
 * is denied, matching the Capability convention.
 */
final class TypeCapability {

    /**
     * Statuses that make a post visible or scheduled and so need publish_posts.
     */
    private const PUBLISHING_STATUSES = [ 'publish', 'future', 'private' ];

    /**
     * Registered types that store internal state, not content. WordPress and
     * WooCommerce create these through their own APIs (revisions, privacy
     * requests, Customizer, menus, global styles, media uploads, refunds), so
     * create-post and duplicate-post refuse them.
     */
    private const INTERNAL_POST_TYPES = [
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_global_styles',
        'shop_order_refund',
        'shop_order_placehold',
    ];

    /**
     * Whether the current user may create a post of a type, status and author.
     *
     * @param string $postType Post type name.
     * @param string $status   Requested post status.
     * @param int    $authorId Requested author ID.
     */
    public static function createPost( string $postType, string $status, int $authorId ): ?\WP_Error {
        $type = in_array( $postType, self::INTERNAL_POST_TYPES, true ) ? null : get_post_type_object( $postType );
        if ( ! $type ) {
            return ResponseFormatter::error( 'invalid_post_type', sprintf( 'Invalid post type: %s', $postType ), 400 );
        }

        if ( ! current_user_can( $type->cap->create_posts ) ) {
            return self::denied( 'You are not allowed to create posts of this type.' );
        }

        return self::statusAndAuthor( $type, $status, $authorId );
    }

    /**
     * Whether the current user may write an existing post, optionally changing
     * its status or author.
     *
     * @param \WP_Post    $post     Target post.
     * @param string|null $status   New status, or null when unchanged.
     * @param int|null    $authorId New author ID, or null when unchanged.
     */
    public static function writePost( \WP_Post $post, ?string $status = null, ?int $authorId = null ): ?\WP_Error {
        $error = Capability::editPost( (int) $post->ID );
        if ( $error ) {
            return $error;
        }

        // Trashing through a status change is a delete, as in wp-admin.
        if ( 'trash' === $status ) {
            $error = Capability::deletePost( (int) $post->ID );
            if ( $error ) {
                return $error;
            }
        }

        $type = get_post_type_object( $post->post_type );
        if ( ! $type ) {
            return null;
        }

        return self::statusAndAuthor( $type, $status, $authorId );
    }

    /**
     * Whether the current user may assign terms of a taxonomy.
     *
     * An unregistered taxonomy is not a permission question; callers either
     * reject it or skip it, so it passes here.
     *
     * @param string $taxonomy Taxonomy name.
     */
    public static function assignTerms( string $taxonomy ): ?\WP_Error {
        return self::taxonomyCap( $taxonomy, 'assign_terms', 'You are not allowed to assign terms in this taxonomy.' );
    }

    /**
     * Whether the current user may assign every taxonomy a post write names:
     * the categories, tags and taxonomies inputs of create-post / update-post.
     *
     * @param array<string, mixed> $input Ability input.
     */
    public static function assignPostTerms( array $input ): ?\WP_Error {
        $taxonomies = [];

        if ( isset( $input['categories'] ) ) {
            $taxonomies[] = 'category';
        }
        if ( isset( $input['tags'] ) ) {
            $taxonomies[] = 'post_tag';
        }
        if ( isset( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ) {
            $taxonomies = array_merge( $taxonomies, array_map( 'strval', array_keys( $input['taxonomies'] ) ) );
        }

        foreach ( array_unique( $taxonomies ) as $taxonomy ) {
            $error = self::assignTerms( $taxonomy );
            if ( $error ) {
                return $error;
            }
        }

        return null;
    }

    /**
     * Whether the current user may create terms in a taxonomy.
     *
     * @param string $taxonomy Taxonomy name.
     */
    public static function createTerm( string $taxonomy ): ?\WP_Error {
        return self::taxonomyCap( $taxonomy, 'edit_terms', 'You are not allowed to create terms in this taxonomy.' );
    }

    /**
     * Check the publish and author rules shared by create and update.
     *
     * @param \WP_Post_Type $type     Post type object.
     * @param string|null   $status   Requested status, or null when unchanged.
     * @param int|null      $authorId Requested author ID, or null when unchanged.
     */
    private static function statusAndAuthor( \WP_Post_Type $type, ?string $status, ?int $authorId ): ?\WP_Error {
        if ( null !== $status && in_array( $status, self::PUBLISHING_STATUSES, true )
            && ! current_user_can( $type->cap->publish_posts ) ) {
            return self::denied( 'You are not allowed to publish posts of this type.' );
        }

        if ( null !== $authorId && get_current_user_id() !== $authorId
            && ! current_user_can( $type->cap->edit_others_posts ) ) {
            return self::denied( 'You are not allowed to write posts as another user.' );
        }

        return null;
    }

    /**
     * Check one capability from a taxonomy's cap object.
     *
     * @param string $taxonomy Taxonomy name.
     * @param string $cap      Key in the taxonomy's cap object.
     * @param string $message  Message for the denial response.
     */
    private static function taxonomyCap( string $taxonomy, string $cap, string $message ): ?\WP_Error {
        $object = get_taxonomy( $taxonomy );
        if ( ! $object || current_user_can( $object->cap->$cap ) ) {
            return null;
        }

        return self::denied( $message );
    }

    /**
     * Build a 403 denial.
     *
     * @param string $message Message for the denial response.
     */
    private static function denied( string $message ): \WP_Error {
        return ResponseFormatter::error( 'forbidden', $message, 403 );
    }
}
