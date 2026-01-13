<?php
/**
 * Post Abilities - Registers all post management abilities
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Definitions;

use WPSiteManager\Abilities\PermissionManager;
use WPSiteManager\Services\PostManager;

class PostAbilities {

    /**
     * Register all post management abilities
     *
     * @param PermissionManager $permissions Permission manager instance
     */
    public static function register( PermissionManager $permissions ): void {
        self::register_list_abilities( $permissions );
        self::register_crud_abilities( $permissions );
        self::register_utility_abilities( $permissions );
    }

    // =========================================================================
    // List & Get Abilities
    // =========================================================================

    private static function register_list_abilities( PermissionManager $permissions ): void {
        // List posts
        wp_register_ability(
            'site-manager/list-posts',
            [
                'label'       => __( 'List Posts', 'wp-site-manager' ),
                'description' => __( 'List posts with filtering options', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_type' => [
                            'type'        => 'string',
                            'default'     => 'post',
                            'description' => 'Post type to list',
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                            'minimum' => 0,
                        ],
                        'status' => [
                            'type'        => 'string',
                            'default'     => 'any',
                            'description' => 'Post status: publish, draft, pending, trash, any',
                        ],
                        'author' => [
                            'type'        => 'integer',
                            'description' => 'Filter by author ID',
                        ],
                        'category' => [
                            'type'        => 'string',
                            'description' => 'Filter by category slug',
                        ],
                        'tag' => [
                            'type'        => 'string',
                            'description' => 'Filter by tag slug',
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search in title and content',
                        ],
                        'date_after' => [
                            'type'        => 'string',
                            'description' => 'Posts after this date (Y-m-d)',
                        ],
                        'date_before' => [
                            'type'        => 'string',
                            'description' => 'Posts before this date (Y-m-d)',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'date',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => [ PostManager::class, 'list_posts' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get single post
        wp_register_ability(
            'site-manager/get-post',
            [
                'label'       => __( 'Get Post', 'wp-site-manager' ),
                'description' => __( 'Get detailed information about a post', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Post slug',
                        ],
                        'post_type' => [
                            'type'        => 'string',
                            'default'     => 'post',
                            'description' => 'Post type (required when using slug)',
                        ],
                    ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PostManager::class, 'get_post' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get post types
        wp_register_ability(
            'site-manager/get-post-types',
            [
                'label'       => __( 'Get Post Types', 'wp-site-manager' ),
                'description' => __( 'List available post types', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'public' => [
                            'type'        => 'boolean',
                            'description' => 'Filter by public status',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_types' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'        => [ 'type' => 'string' ],
                                    'name'        => [ 'type' => 'string' ],
                                    'label'       => [ 'type' => 'string' ],
                                    'public'      => [ 'type' => 'boolean' ],
                                    'hierarchical' => [ 'type' => 'boolean' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PostManager::class, 'get_post_types' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // CRUD Abilities
    // =========================================================================

    private static function register_crud_abilities( PermissionManager $permissions ): void {
        // Create post
        wp_register_ability(
            'site-manager/create-post',
            [
                'label'       => __( 'Create Post', 'wp-site-manager' ),
                'description' => __( 'Create a new post', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Post title',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Post content',
                        ],
                        'excerpt' => [
                            'type'        => 'string',
                            'description' => 'Post excerpt',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'default'     => 'draft',
                            'enum'        => [ 'draft', 'publish', 'pending', 'private', 'future' ],
                            'description' => 'Post status',
                        ],
                        'post_type' => [
                            'type'        => 'string',
                            'default'     => 'post',
                            'description' => 'Post type',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Post slug (auto-generated if empty)',
                        ],
                        'author' => [
                            'type'        => 'integer',
                            'description' => 'Author user ID',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent post ID',
                        ],
                        'menu_order' => [
                            'type'        => 'integer',
                            'description' => 'Menu order',
                        ],
                        'date' => [
                            'type'        => 'string',
                            'description' => 'Post date (Y-m-d H:i:s)',
                        ],
                        'categories' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'description' => 'Category IDs',
                        ],
                        'tags' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'string' ],
                            'description' => 'Tag names or IDs',
                        ],
                        'featured_image' => [
                            'type'        => 'integer',
                            'description' => 'Featured image attachment ID',
                        ],
                        'meta' => [
                            'type'        => 'object',
                    'default'    => [],
                            'description' => 'Custom meta fields',
                        ],
                    ],
                    'required' => [ 'title' ],
                ],
                'output_schema' => self::getEntityOutputSchema( true ),
                'execute_callback'    => [ PostManager::class, 'create_post' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update post
        wp_register_ability(
            'site-manager/update-post',
            [
                'label'       => __( 'Update Post', 'wp-site-manager' ),
                'description' => __( 'Update an existing post', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ],
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Post title',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Post content',
                        ],
                        'excerpt' => [
                            'type'        => 'string',
                            'description' => 'Post excerpt',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'enum'        => [ 'draft', 'publish', 'pending', 'private', 'future', 'trash' ],
                            'description' => 'Post status',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Post slug',
                        ],
                        'author' => [
                            'type'        => 'integer',
                            'description' => 'Author user ID',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent post ID',
                        ],
                        'menu_order' => [
                            'type'        => 'integer',
                            'description' => 'Menu order',
                        ],
                        'date' => [
                            'type'        => 'string',
                            'description' => 'Post date (Y-m-d H:i:s)',
                        ],
                        'categories' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'description' => 'Category IDs',
                        ],
                        'tags' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'string' ],
                            'description' => 'Tag names',
                        ],
                        'featured_image' => [
                            'type'        => 'integer',
                            'description' => 'Featured image attachment ID',
                        ],
                        'meta' => [
                            'type'        => 'object',
                    'default'    => [],
                            'description' => 'Custom meta fields',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PostManager::class, 'update_post' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete post
        wp_register_ability(
            'site-manager/delete-post',
            [
                'label'       => __( 'Delete Post', 'wp-site-manager' ),
                'description' => __( 'Delete a post', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ],
                        'force' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Permanently delete (skip trash)',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'id'      => [ 'type' => 'integer' ],
                        'trashed' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ PostManager::class, 'delete_post' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta' => self::destructiveMeta(),
            ]
        );

        // Restore post
        wp_register_ability(
            'site-manager/restore-post',
            [
                'label'       => __( 'Restore Post', 'wp-site-manager' ),
                'description' => __( 'Restore a post from trash', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PostManager::class, 'restore_post' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    // =========================================================================
    // Utility Abilities
    // =========================================================================

    private static function register_utility_abilities( PermissionManager $permissions ): void {
        // Duplicate post
        wp_register_ability(
            'site-manager/duplicate-post',
            [
                'label'       => __( 'Duplicate Post', 'wp-site-manager' ),
                'description' => __( 'Create a copy of a post', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID to duplicate',
                        ],
                        'new_title' => [
                            'type'        => 'string',
                            'description' => 'Title for the copy (default: original + " (Copy)")',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'default'     => 'draft',
                            'description' => 'Status for the copy',
                        ],
                        'copy_meta' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Copy custom meta fields',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema( true ),
                'execute_callback'    => [ PostManager::class, 'duplicate_post' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Bulk action
        wp_register_ability(
            'site-manager/bulk-posts',
            [
                'label'       => __( 'Bulk Post Action', 'wp-site-manager' ),
                'description' => __( 'Perform bulk actions on multiple posts', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'description' => 'Array of post IDs',
                        ],
                        'action' => [
                            'type'        => 'string',
                            'enum'        => [ 'publish', 'draft', 'trash', 'delete', 'restore' ],
                            'description' => 'Action to perform',
                        ],
                    ],
                    'required' => [ 'ids', 'action' ],
                ],
                'output_schema' => self::getBulkOutputSchema(),
                'execute_callback'    => [ PostManager::class, 'bulk_action' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    // =========================================================================
    // Meta Helpers
    // =========================================================================

    private static function readOnlyMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ];
    }

    private static function writeMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ],
        ];
    }

    private static function destructiveMeta( bool $idempotent = true ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => $idempotent,
            ],
        ];
    }

    // =========================================================================
    // Schema Helpers
    // =========================================================================

    private static function getPostSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'             => [ 'type' => 'integer' ],
                'title'          => [ 'type' => 'string' ],
                'slug'           => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'post_type'      => [ 'type' => 'string' ],
                'author'         => [ 'type' => 'integer' ],
                'author_name'    => [ 'type' => 'string' ],
                'date'           => [ 'type' => 'string' ],
                'date_gmt'       => [ 'type' => 'string' ],
                'modified'       => [ 'type' => 'string' ],
                'modified_gmt'   => [ 'type' => 'string' ],
                'content'        => [ 'type' => 'string' ],
                'excerpt'        => [ 'type' => 'string' ],
                'parent'         => [ 'type' => 'integer' ],
                'menu_order'     => [ 'type' => 'integer' ],
                'featured_image' => [
                    'type'       => [ 'object', 'null' ],
                    'properties' => [
                        'id'  => [ 'type' => 'integer' ],
                        'url' => [ 'type' => 'string' ],
                    ],
                ],
                'link'           => [ 'type' => 'string' ],
                'categories'     => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                    'default'    => [],
                        'properties' => [
                            'id'   => [ 'type' => 'integer' ],
                            'name' => [ 'type' => 'string' ],
                            'slug' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'tags'           => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                    'default'    => [],
                        'properties' => [
                            'id'   => [ 'type' => 'integer' ],
                            'name' => [ 'type' => 'string' ],
                            'slug' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function getListOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'posts'       => [
                    'type'  => 'array',
                    'items' => self::getPostSchema(),
                ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
                'has_more'    => [ 'type' => 'boolean' ],
            ],
        ];
    }

    private static function getEntityOutputSchema( bool $includeId = false ): array {
        $properties = [
            'success' => [ 'type' => 'boolean' ],
            'message' => [ 'type' => 'string' ],
            'post'    => self::getPostSchema(),
        ];

        if ( $includeId ) {
            $properties['id'] = [ 'type' => 'integer' ];
        }

        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => $properties,
        ];
    }

    private static function getBulkOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success'     => [ 'type' => 'boolean' ],
                'action'      => [ 'type' => 'string' ],
                'processed'   => [ 'type' => 'integer' ],
                'failed'      => [ 'type' => 'integer' ],
                'total'       => [ 'type' => 'integer' ],
                'success_ids' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
                'failed_ids' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
                'message' => [ 'type' => 'string' ],
            ],
        ];
    }
}
