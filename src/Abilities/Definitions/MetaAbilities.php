<?php
/**
 * Meta Abilities - Post, User, and Term meta management
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\MetaManager;

class MetaAbilities {

    public static function register( PermissionManager $permissions ): void {
        self::registerPostMetaAbilities( $permissions );
        self::registerUserMetaAbilities( $permissions );
        self::registerTermMetaAbilities( $permissions );
    }

    private static function registerPostMetaAbilities( PermissionManager $permissions ): void {
        // Get post meta
        wp_register_ability(
            'site-manager/get-post-meta',
            [
                'label'       => __( 'Get Post Meta', 'lw-site-manager' ),
                'description' => __( 'Get meta data for a post or page', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Post or Page ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Specific meta key to retrieve (optional, returns all if not specified)',
                        ],
                        'include_private' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Include private meta keys (starting with _)',
                        ],
                    ],
                    'required' => [ 'post_id' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'post_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                        'meta'    => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'get_post_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Set post meta
        wp_register_ability(
            'site-manager/set-post-meta',
            [
                'label'       => __( 'Set Post Meta', 'lw-site-manager' ),
                'description' => __( 'Set meta data for a post or page', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Post or Page ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key',
                        ],
                        'value' => [
                            'type'        => [ 'string', 'number', 'boolean', 'array', 'object' ],
                            'description' => 'Meta value',
                        ],
                    ],
                    'required' => [ 'post_id', 'key', 'value' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'post_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'set_post_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete post meta
        wp_register_ability(
            'site-manager/delete-post-meta',
            [
                'label'       => __( 'Delete Post Meta', 'lw-site-manager' ),
                'description' => __( 'Delete meta data from a post or page', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'post_id' => [
                            'type'        => 'integer',
                            'description' => 'Post or Page ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key to delete',
                        ],
                    ],
                    'required' => [ 'post_id', 'key' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'post_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'delete_post_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    private static function registerUserMetaAbilities( PermissionManager $permissions ): void {
        // Get user meta
        wp_register_ability(
            'site-manager/get-user-meta',
            [
                'label'       => __( 'Get User Meta', 'lw-site-manager' ),
                'description' => __( 'Get meta data for a user', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'user_id' => [
                            'type'        => 'integer',
                            'description' => 'User ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Specific meta key to retrieve (optional)',
                        ],
                        'include_private' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Include private meta keys',
                        ],
                    ],
                    'required' => [ 'user_id' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'user_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                        'meta'    => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'get_user_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_users' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Set user meta
        wp_register_ability(
            'site-manager/set-user-meta',
            [
                'label'       => __( 'Set User Meta', 'lw-site-manager' ),
                'description' => __( 'Set meta data for a user', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'user_id' => [
                            'type'        => 'integer',
                            'description' => 'User ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key',
                        ],
                        'value' => [
                            'type'        => [ 'string', 'number', 'boolean', 'array', 'object' ],
                            'description' => 'Meta value',
                        ],
                    ],
                    'required' => [ 'user_id', 'key', 'value' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'user_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'set_user_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_users' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete user meta
        wp_register_ability(
            'site-manager/delete-user-meta',
            [
                'label'       => __( 'Delete User Meta', 'lw-site-manager' ),
                'description' => __( 'Delete meta data from a user', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'user_id' => [
                            'type'        => 'integer',
                            'description' => 'User ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key to delete',
                        ],
                    ],
                    'required' => [ 'user_id', 'key' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'user_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'delete_user_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_users' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    private static function registerTermMetaAbilities( PermissionManager $permissions ): void {
        // Get term meta
        wp_register_ability(
            'site-manager/get-term-meta',
            [
                'label'       => __( 'Get Term Meta', 'lw-site-manager' ),
                'description' => __( 'Get meta data for a term (category/tag)', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'term_id' => [
                            'type'        => 'integer',
                            'description' => 'Term ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Specific meta key to retrieve (optional)',
                        ],
                    ],
                    'required' => [ 'term_id' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'term_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                        'meta'    => [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'get_term_meta' ],
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Set term meta
        wp_register_ability(
            'site-manager/set-term-meta',
            [
                'label'       => __( 'Set Term Meta', 'lw-site-manager' ),
                'description' => __( 'Set meta data for a term (category/tag)', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'term_id' => [
                            'type'        => 'integer',
                            'description' => 'Term ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key',
                        ],
                        'value' => [
                            'type'        => [ 'string', 'number', 'boolean', 'array', 'object' ],
                            'description' => 'Meta value',
                        ],
                    ],
                    'required' => [ 'term_id', 'key', 'value' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'term_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                        'value'   => [ 'type' => [ 'string', 'number', 'boolean', 'array', 'object', 'null' ] ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'set_term_meta' ],
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete term meta
        wp_register_ability(
            'site-manager/delete-term-meta',
            [
                'label'       => __( 'Delete Term Meta', 'lw-site-manager' ),
                'description' => __( 'Delete meta data from a term (category/tag)', 'lw-site-manager' ),
                'category'    => 'meta',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'term_id' => [
                            'type'        => 'integer',
                            'description' => 'Term ID',
                        ],
                        'key' => [
                            'type'        => 'string',
                            'description' => 'Meta key to delete',
                        ],
                    ],
                    'required' => [ 'term_id', 'key' ],
                ],
                'output_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'term_id' => [ 'type' => 'integer' ],
                        'key'     => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ MetaManager::class, 'delete_term_meta' ],
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

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
                'idempotent'  => true,
            ],
        ];
    }

    private static function destructiveMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ],
        ];
    }
}
