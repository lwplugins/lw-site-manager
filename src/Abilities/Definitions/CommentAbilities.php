<?php
/**
 * Comment Abilities - Registers all comment management abilities
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Definitions;

use WPSiteManager\Abilities\PermissionManager;
use WPSiteManager\Services\CommentManager;

class CommentAbilities {

    /**
     * Register all comment management abilities
     *
     * @param PermissionManager $permissions Permission manager instance
     */
    public static function register( PermissionManager $permissions ): void {
        self::register_list_abilities( $permissions );
        self::register_crud_abilities( $permissions );
        self::register_moderation_abilities( $permissions );
    }

    // =========================================================================
    // List & Get Abilities
    // =========================================================================

    private static function register_list_abilities( PermissionManager $permissions ): void {
        // List comments
        wp_register_ability(
            'site-manager/list-comments',
            [
                'label'       => __( 'List Comments', 'wp-site-manager' ),
                'description' => __( 'List comments with filtering options', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 50,
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
                            'enum'        => [ 'all', 'approve', 'hold', 'spam', 'trash' ],
                            'default'     => 'all',
                            'description' => 'Filter by status',
                        ],
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Filter by post ID',
                        ],
                        'type' => [
                            'type'        => 'string',
                            'description' => 'Filter by type (comment, pingback, trackback)',
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search in comment content',
                        ],
                        'author_email' => [
                            'type'        => 'string',
                            'description' => 'Filter by author email',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'comment_date',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'list_comments' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get single comment
        wp_register_ability(
            'site-manager/get-comment',
            [
                'label'       => __( 'Get Comment', 'wp-site-manager' ),
                'description' => __( 'Get detailed information about a comment', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Comment ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'get_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get comment counts
        wp_register_ability(
            'site-manager/comment-counts',
            [
                'label'       => __( 'Get Comment Counts', 'wp-site-manager' ),
                'description' => __( 'Get comment counts by status', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Get counts for specific post (optional)',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'total'           => [ 'type' => 'integer' ],
                        'approved'        => [ 'type' => 'integer' ],
                        'pending'         => [ 'type' => 'integer' ],
                        'spam'            => [ 'type' => 'integer' ],
                        'trash'           => [ 'type' => 'integer' ],
                        'total_moderated' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ CommentManager::class, 'get_counts' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // CRUD Abilities
    // =========================================================================

    private static function register_crud_abilities( PermissionManager $permissions ): void {
        // Create comment
        wp_register_ability(
            'site-manager/create-comment',
            [
                'label'       => __( 'Create Comment', 'wp-site-manager' ),
                'description' => __( 'Create a new comment', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Post ID to comment on',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Comment content',
                        ],
                        'author_name' => [
                            'type'        => 'string',
                            'description' => 'Author name',
                        ],
                        'author_email' => [
                            'type'        => 'string',
                            'description' => 'Author email',
                        ],
                        'author_url' => [
                            'type'        => 'string',
                            'description' => 'Author URL',
                        ],
                        'user_id' => [
                            'type'        => 'integer',
                            'description' => 'User ID (overrides author fields)',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent comment ID for replies',
                        ],
                        'approved' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Whether comment is approved',
                        ],
                    ],
                    'required' => [ 'post_id', 'content' ],
                ],
                'output_schema' => self::getEntityOutputSchema( true ),
                'execute_callback'    => [ CommentManager::class, 'create_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update comment
        wp_register_ability(
            'site-manager/update-comment',
            [
                'label'       => __( 'Update Comment', 'wp-site-manager' ),
                'description' => __( 'Update an existing comment', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Comment ID',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Comment content',
                        ],
                        'author_name' => [
                            'type'        => 'string',
                            'description' => 'Author name',
                        ],
                        'author_email' => [
                            'type'        => 'string',
                            'description' => 'Author email',
                        ],
                        'author_url' => [
                            'type'        => 'string',
                            'description' => 'Author URL',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'enum'        => [ 'approve', 'hold', 'spam', 'trash' ],
                            'description' => 'Comment status',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'update_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete comment
        wp_register_ability(
            'site-manager/delete-comment',
            [
                'label'       => __( 'Delete Comment', 'wp-site-manager' ),
                'description' => __( 'Delete a comment', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Comment ID',
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
                'execute_callback'    => [ CommentManager::class, 'delete_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    // =========================================================================
    // Moderation Abilities
    // =========================================================================

    private static function register_moderation_abilities( PermissionManager $permissions ): void {
        // Approve comment
        wp_register_ability(
            'site-manager/approve-comment',
            [
                'label'       => __( 'Approve Comment', 'wp-site-manager' ),
                'description' => __( 'Approve a pending comment', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Comment ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getModerationOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'approve_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::writeMeta( idempotent: true ),
            ]
        );

        // Mark as spam
        wp_register_ability(
            'site-manager/spam-comment',
            [
                'label'       => __( 'Mark Comment as Spam', 'wp-site-manager' ),
                'description' => __( 'Mark a comment as spam', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Comment ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getModerationOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'spam_comment' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::writeMeta( idempotent: true ),
            ]
        );

        // Bulk action
        wp_register_ability(
            'site-manager/bulk-comments',
            [
                'label'       => __( 'Bulk Comment Action', 'wp-site-manager' ),
                'description' => __( 'Perform bulk actions on multiple comments', 'wp-site-manager' ),
                'category'    => 'comments',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'description' => 'Array of comment IDs',
                        ],
                        'action' => [
                            'type'        => 'string',
                            'enum'        => [ 'approve', 'unapprove', 'spam', 'trash', 'delete' ],
                            'description' => 'Action to perform',
                        ],
                    ],
                    'required' => [ 'ids', 'action' ],
                ],
                'output_schema' => self::getBulkOutputSchema(),
                'execute_callback'    => [ CommentManager::class, 'bulk_action' ],
                'permission_callback' => $permissions->callback( 'can_moderate_comments' ),
                'meta' => self::destructiveMeta( idempotent: false ),
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

    private static function writeMeta( bool $idempotent = false ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => $idempotent,
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

    private static function getCommentSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'           => [ 'type' => 'integer' ],
                'post_id'      => [ 'type' => 'integer' ],
                'post_title'   => [ 'type' => 'string' ],
                'author_name'  => [ 'type' => 'string' ],
                'author_email' => [ 'type' => 'string' ],
                'author_url'   => [ 'type' => 'string' ],
                'author_ip'    => [ 'type' => 'string' ],
                'content'      => [ 'type' => 'string' ],
                'date'         => [ 'type' => 'string' ],
                'date_gmt'     => [ 'type' => 'string' ],
                'status'       => [ 'type' => 'string' ],
                'type'         => [ 'type' => 'string' ],
                'parent'       => [ 'type' => 'integer' ],
                'user_id'      => [ 'type' => 'integer' ],
                'avatar_url'   => [ 'type' => 'string' ],
            ],
        ];
    }

    private static function getListOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'comments'    => [
                    'type'  => 'array',
                    'items' => self::getCommentSchema(),
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
            'comment' => self::getCommentSchema(),
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

    private static function getModerationOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success'         => [ 'type' => 'boolean' ],
                'message'         => [ 'type' => 'string' ],
                'id'              => [ 'type' => 'integer' ],
                'previous_status' => [ 'type' => 'string' ],
                'new_status'      => [ 'type' => 'string' ],
            ],
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
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'id'     => [ 'type' => 'integer' ],
                            'reason' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'message' => [ 'type' => 'string' ],
            ],
        ];
    }
}
