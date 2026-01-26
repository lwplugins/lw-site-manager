<?php
/**
 * Settings Abilities - WordPress settings management
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\SettingsManager;

class SettingsAbilities {

    public static function register( PermissionManager $permissions ): void {
        self::registerGeneralSettingsAbilities( $permissions );
        self::registerReadingSettingsAbilities( $permissions );
        self::registerDiscussionSettingsAbilities( $permissions );
        self::registerPermalinkSettingsAbilities( $permissions );
    }

    private static function registerGeneralSettingsAbilities( PermissionManager $permissions ): void {
        // Get general settings
        wp_register_ability(
            'site-manager/get-general-settings',
            [
                'label'       => __( 'Get General Settings', 'lw-site-manager' ),
                'description' => __( 'Get WordPress general settings (site title, tagline, email, etc.)', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'settings' => [
                            'type'       => 'object',
                            'default'    => [],
                            'properties' => [
                                'blogname'           => [ 'type' => 'string' ],
                                'blogdescription'    => [ 'type' => 'string' ],
                                'siteurl'            => [ 'type' => 'string' ],
                                'home'               => [ 'type' => 'string' ],
                                'admin_email'        => [ 'type' => 'string' ],
                                'users_can_register' => [ 'type' => 'string' ],
                                'default_role'       => [ 'type' => 'string' ],
                                'timezone_string'    => [ 'type' => 'string' ],
                                'date_format'        => [ 'type' => 'string' ],
                                'time_format'        => [ 'type' => 'string' ],
                                'start_of_week'      => [ 'type' => 'string' ],
                                'WPLANG'             => [ 'type' => 'string' ],
                                'site_language'      => [ 'type' => 'string' ],
                                'available_roles'    => [
                                    'type'  => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ SettingsManager::class, 'get_general_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Update general settings
        wp_register_ability(
            'site-manager/update-general-settings',
            [
                'label'       => __( 'Update General Settings', 'lw-site-manager' ),
                'description' => __( 'Update WordPress general settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'blogname' => [
                            'type'        => 'string',
                            'description' => 'Site title',
                        ],
                        'blogdescription' => [
                            'type'        => 'string',
                            'description' => 'Tagline',
                        ],
                        'admin_email' => [
                            'type'        => 'string',
                            'description' => 'Administration email address',
                        ],
                        'users_can_register' => [
                            'type'        => 'boolean',
                            'description' => 'Anyone can register',
                        ],
                        'default_role' => [
                            'type'        => 'string',
                            'description' => 'New user default role',
                        ],
                        'timezone_string' => [
                            'type'        => 'string',
                            'description' => 'Timezone (e.g., Europe/Budapest)',
                        ],
                        'date_format' => [
                            'type'        => 'string',
                            'description' => 'Date format (e.g., Y-m-d)',
                        ],
                        'time_format' => [
                            'type'        => 'string',
                            'description' => 'Time format (e.g., H:i)',
                        ],
                        'start_of_week' => [
                            'type'        => 'integer',
                            'description' => 'Week starts on (0=Sunday, 1=Monday, etc.)',
                            'minimum'     => 0,
                            'maximum'     => 6,
                        ],
                        'WPLANG' => [
                            'type'        => 'string',
                            'description' => 'Site language code',
                        ],
                    ],
                ],
                'output_schema' => self::updateOutputSchema(),
                'execute_callback'    => [ SettingsManager::class, 'update_general_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    private static function registerReadingSettingsAbilities( PermissionManager $permissions ): void {
        // Get reading settings
        wp_register_ability(
            'site-manager/get-reading-settings',
            [
                'label'       => __( 'Get Reading Settings', 'lw-site-manager' ),
                'description' => __( 'Get WordPress reading settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'settings' => [
                            'type'       => 'object',
                            'default'    => [],
                            'properties' => [
                                'posts_per_page'    => [ 'type' => 'string' ],
                                'posts_per_rss'     => [ 'type' => 'string' ],
                                'rss_use_excerpt'   => [ 'type' => 'string' ],
                                'show_on_front'     => [ 'type' => 'string' ],
                                'page_on_front'     => [ 'type' => 'string' ],
                                'page_for_posts'    => [ 'type' => 'string' ],
                                'blog_public'       => [ 'type' => 'string' ],
                                'homepage_title'    => [ 'type' => 'string' ],
                                'posts_page_title'  => [ 'type' => 'string' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ SettingsManager::class, 'get_reading_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Update reading settings
        wp_register_ability(
            'site-manager/update-reading-settings',
            [
                'label'       => __( 'Update Reading Settings', 'lw-site-manager' ),
                'description' => __( 'Update WordPress reading settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'posts_per_page' => [
                            'type'        => 'integer',
                            'description' => 'Blog pages show at most',
                            'minimum'     => 1,
                            'maximum'     => 100,
                        ],
                        'posts_per_rss' => [
                            'type'        => 'integer',
                            'description' => 'Syndication feeds show most recent',
                            'minimum'     => 1,
                            'maximum'     => 100,
                        ],
                        'rss_use_excerpt' => [
                            'type'        => 'boolean',
                            'description' => 'For each post in a feed, include excerpt only',
                        ],
                        'show_on_front' => [
                            'type'        => 'string',
                            'enum'        => [ 'posts', 'page' ],
                            'description' => 'Your homepage displays',
                        ],
                        'page_on_front' => [
                            'type'        => 'integer',
                            'description' => 'Homepage page ID',
                        ],
                        'page_for_posts' => [
                            'type'        => 'integer',
                            'description' => 'Posts page ID',
                        ],
                        'blog_public' => [
                            'type'        => 'boolean',
                            'description' => 'Discourage search engines from indexing',
                        ],
                    ],
                ],
                'output_schema' => self::updateOutputSchema(),
                'execute_callback'    => [ SettingsManager::class, 'update_reading_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    private static function registerDiscussionSettingsAbilities( PermissionManager $permissions ): void {
        // Get discussion settings
        wp_register_ability(
            'site-manager/get-discussion-settings',
            [
                'label'       => __( 'Get Discussion Settings', 'lw-site-manager' ),
                'description' => __( 'Get WordPress discussion/comment settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'settings' => [
                            'type'                 => 'object',
                            'default'              => [],
                            'additionalProperties' => true,
                        ],
                    ],
                ],
                'execute_callback'    => [ SettingsManager::class, 'get_discussion_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Update discussion settings
        wp_register_ability(
            'site-manager/update-discussion-settings',
            [
                'label'       => __( 'Update Discussion Settings', 'lw-site-manager' ),
                'description' => __( 'Update WordPress discussion/comment settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'default_pingback_flag' => [
                            'type'        => 'boolean',
                            'description' => 'Attempt to notify linked blogs',
                        ],
                        'default_ping_status' => [
                            'type'        => 'string',
                            'enum'        => [ 'open', 'closed' ],
                            'description' => 'Allow link notifications from other blogs',
                        ],
                        'default_comment_status' => [
                            'type'        => 'string',
                            'enum'        => [ 'open', 'closed' ],
                            'description' => 'Allow people to submit comments on new posts',
                        ],
                        'require_name_email' => [
                            'type'        => 'boolean',
                            'description' => 'Comment author must fill out name and email',
                        ],
                        'comment_registration' => [
                            'type'        => 'boolean',
                            'description' => 'Users must be registered to comment',
                        ],
                        'close_comments_for_old_posts' => [
                            'type'        => 'boolean',
                            'description' => 'Automatically close comments on old posts',
                        ],
                        'close_comments_days_old' => [
                            'type'        => 'integer',
                            'description' => 'Days after which comments are closed',
                        ],
                        'thread_comments' => [
                            'type'        => 'boolean',
                            'description' => 'Enable threaded comments',
                        ],
                        'thread_comments_depth' => [
                            'type'        => 'integer',
                            'description' => 'Threading depth level',
                            'minimum'     => 1,
                            'maximum'     => 10,
                        ],
                        'page_comments' => [
                            'type'        => 'boolean',
                            'description' => 'Break comments into pages',
                        ],
                        'comments_per_page' => [
                            'type'        => 'integer',
                            'description' => 'Comments per page',
                        ],
                        'default_comments_page' => [
                            'type'        => 'string',
                            'enum'        => [ 'newest', 'oldest' ],
                            'description' => 'Default comments page',
                        ],
                        'comment_order' => [
                            'type'        => 'string',
                            'enum'        => [ 'asc', 'desc' ],
                            'description' => 'Comments order',
                        ],
                        'comments_notify' => [
                            'type'        => 'boolean',
                            'description' => 'Email me when anyone posts a comment',
                        ],
                        'moderation_notify' => [
                            'type'        => 'boolean',
                            'description' => 'Email me when comment is held for moderation',
                        ],
                        'comment_moderation' => [
                            'type'        => 'boolean',
                            'description' => 'Comment must be manually approved',
                        ],
                        'comment_previously_approved' => [
                            'type'        => 'boolean',
                            'description' => 'Comment author must have a previously approved comment',
                        ],
                        'show_avatars' => [
                            'type'        => 'boolean',
                            'description' => 'Show avatars',
                        ],
                        'avatar_rating' => [
                            'type'        => 'string',
                            'enum'        => [ 'G', 'PG', 'R', 'X' ],
                            'description' => 'Maximum avatar rating',
                        ],
                        'avatar_default' => [
                            'type'        => 'string',
                            'description' => 'Default avatar',
                        ],
                    ],
                ],
                'output_schema' => self::updateOutputSchema(),
                'execute_callback'    => [ SettingsManager::class, 'update_discussion_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    private static function registerPermalinkSettingsAbilities( PermissionManager $permissions ): void {
        // Get permalink settings
        wp_register_ability(
            'site-manager/get-permalink-settings',
            [
                'label'       => __( 'Get Permalink Settings', 'lw-site-manager' ),
                'description' => __( 'Get WordPress permalink settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'settings' => [
                            'type'       => 'object',
                            'default'    => [],
                            'properties' => [
                                'permalink_structure' => [ 'type' => 'string' ],
                                'category_base'       => [ 'type' => 'string' ],
                                'tag_base'            => [ 'type' => 'string' ],
                                'common_structures'   => [
                                    'type'                 => 'object',
                                    'default'              => [],
                                    'additionalProperties' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ SettingsManager::class, 'get_permalink_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Update permalink settings
        wp_register_ability(
            'site-manager/update-permalink-settings',
            [
                'label'       => __( 'Update Permalink Settings', 'lw-site-manager' ),
                'description' => __( 'Update WordPress permalink settings', 'lw-site-manager' ),
                'category'    => 'settings',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'permalink_structure' => [
                            'type'        => 'string',
                            'description' => 'Permalink structure (e.g., /%postname%/)',
                        ],
                        'category_base' => [
                            'type'        => 'string',
                            'description' => 'Category base',
                        ],
                        'tag_base' => [
                            'type'        => 'string',
                            'description' => 'Tag base',
                        ],
                    ],
                ],
                'output_schema' => self::updateOutputSchema(),
                'execute_callback'    => [ SettingsManager::class, 'update_permalink_settings' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::writeMeta(),
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

    private static function updateOutputSchema(): array {
        return [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'updated' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'failed' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'default'    => [],
                        'properties' => [
                            'key'     => [ 'type' => 'string' ],
                            'message' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
