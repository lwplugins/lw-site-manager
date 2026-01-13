<?php
/**
 * Page Abilities - Registers all page management abilities
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Definitions;

use WPSiteManager\Abilities\PermissionManager;
use WPSiteManager\Services\PageManager;

class PageAbilities {

    /**
     * Register all page management abilities
     *
     * @param PermissionManager $permissions Permission manager instance
     */
    public static function register( PermissionManager $permissions ): void {
        self::register_list_abilities( $permissions );
        self::register_crud_abilities( $permissions );
        self::register_hierarchy_abilities( $permissions );
        self::register_settings_abilities( $permissions );
    }

    // =========================================================================
    // List & Get Abilities
    // =========================================================================

    private static function register_list_abilities( PermissionManager $permissions ): void {
        // List pages
        wp_register_ability(
            'site-manager/list-pages',
            [
                'label'       => __( 'List Pages', 'wp-site-manager' ),
                'description' => __( 'List pages with filtering options', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
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
                            'description' => 'Page status: publish, draft, pending, trash, any',
                        ],
                        'author' => [
                            'type'        => 'integer',
                            'description' => 'Filter by author ID',
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search in title and content',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Filter by parent page ID',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'menu_order',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'ASC',
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => [ PageManager::class, 'list_pages' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get single page
        wp_register_ability(
            'site-manager/get-page',
            [
                'label'       => __( 'Get Page', 'wp-site-manager' ),
                'description' => __( 'Get detailed information about a page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Page slug',
                        ],
                    ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PageManager::class, 'get_page' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // CRUD Abilities
    // =========================================================================

    private static function register_crud_abilities( PermissionManager $permissions ): void {
        // Create page
        wp_register_ability(
            'site-manager/create-page',
            [
                'label'       => __( 'Create Page', 'wp-site-manager' ),
                'description' => __( 'Create a new page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'title' => [
                            'type'        => 'string',
                            'description' => 'Page title',
                        ],
                        'content' => [
                            'type'        => 'string',
                            'description' => 'Page content',
                        ],
                        'excerpt' => [
                            'type'        => 'string',
                            'description' => 'Page excerpt',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'default'     => 'draft',
                            'enum'        => [ 'draft', 'publish', 'pending', 'private' ],
                            'description' => 'Page status',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Page slug',
                        ],
                        'author' => [
                            'type'        => 'integer',
                            'description' => 'Author user ID',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent page ID',
                        ],
                        'menu_order' => [
                            'type'        => 'integer',
                            'description' => 'Order in menu/listing',
                        ],
                        'template' => [
                            'type'        => 'string',
                            'description' => 'Page template slug',
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
                'execute_callback'    => [ PageManager::class, 'create_page' ],
                'permission_callback' => $permissions->callback( 'can_publish_pages' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update page
        wp_register_ability(
            'site-manager/update-page',
            [
                'label'       => __( 'Update Page', 'wp-site-manager' ),
                'description' => __( 'Update an existing page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID',
                        ],
                        'title' => [
                            'type' => 'string',
                        ],
                        'content' => [
                            'type' => 'string',
                        ],
                        'excerpt' => [
                            'type' => 'string',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [ 'draft', 'publish', 'pending', 'private', 'trash' ],
                        ],
                        'slug' => [
                            'type' => 'string',
                        ],
                        'author' => [
                            'type' => 'integer',
                        ],
                        'parent' => [
                            'type' => 'integer',
                        ],
                        'menu_order' => [
                            'type' => 'integer',
                        ],
                        'template' => [
                            'type' => 'string',
                        ],
                        'featured_image' => [
                            'type' => 'integer',
                        ],
                        'meta' => [
                            'type' => 'object',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PageManager::class, 'update_page' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete page
        wp_register_ability(
            'site-manager/delete-page',
            [
                'label'       => __( 'Delete Page', 'wp-site-manager' ),
                'description' => __( 'Delete a page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID',
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
                'execute_callback'    => [ PageManager::class, 'delete_page' ],
                'permission_callback' => $permissions->callback( 'can_delete_pages' ),
                'meta' => self::destructiveMeta(),
            ]
        );

        // Restore page
        wp_register_ability(
            'site-manager/restore-page',
            [
                'label'       => __( 'Restore Page', 'wp-site-manager' ),
                'description' => __( 'Restore a page from trash', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => [ PageManager::class, 'restore_page' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Duplicate page
        wp_register_ability(
            'site-manager/duplicate-page',
            [
                'label'       => __( 'Duplicate Page', 'wp-site-manager' ),
                'description' => __( 'Create a copy of a page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID to duplicate',
                        ],
                        'new_title' => [
                            'type'        => 'string',
                            'description' => 'Title for the copy',
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
                'execute_callback'    => [ PageManager::class, 'duplicate_page' ],
                'permission_callback' => $permissions->callback( 'can_publish_pages' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    // =========================================================================
    // Hierarchy Abilities
    // =========================================================================

    private static function register_hierarchy_abilities( PermissionManager $permissions ): void {
        // Get page hierarchy
        wp_register_ability(
            'site-manager/page-hierarchy',
            [
                'label'       => __( 'Get Page Hierarchy', 'wp-site-manager' ),
                'description' => __( 'Get hierarchical page tree structure', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status' => [
                            'type'        => 'string',
                            'default'     => 'publish',
                            'description' => 'Page status to include',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'pages' => [
                            'type'        => 'array',
                            'description' => 'Hierarchical tree of pages',
                            'items'       => self::getPageTreeSchema(),
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'get_hierarchy' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Reorder pages
        wp_register_ability(
            'site-manager/reorder-pages',
            [
                'label'       => __( 'Reorder Pages', 'wp-site-manager' ),
                'description' => __( 'Update page order (menu_order)', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'integer' ],
                            'description' => 'Array of page IDs in desired order',
                        ],
                    ],
                    'required' => [ 'order' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'message'  => [ 'type' => 'string' ],
                        'updated'  => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'reorder_pages' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    // =========================================================================
    // Settings Abilities
    // =========================================================================

    private static function register_settings_abilities( PermissionManager $permissions ): void {
        // Set homepage
        wp_register_ability(
            'site-manager/set-homepage',
            [
                'label'       => __( 'Set Homepage', 'wp-site-manager' ),
                'description' => __( 'Set a page as the site homepage', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID to set as homepage',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'      => [ 'type' => 'boolean' ],
                        'message'      => [ 'type' => 'string' ],
                        'page_id'      => [ 'type' => 'integer' ],
                        'previous_id'  => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'set_homepage' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::destructiveMeta(),
            ]
        );

        // Set posts page (blog)
        wp_register_ability(
            'site-manager/set-posts-page',
            [
                'label'       => __( 'Set Posts Page', 'wp-site-manager' ),
                'description' => __( 'Set a page as the blog/posts page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID to set as posts page',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'     => [ 'type' => 'boolean' ],
                        'message'     => [ 'type' => 'string' ],
                        'page_id'     => [ 'type' => 'integer' ],
                        'previous_id' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'set_posts_page' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::destructiveMeta(),
            ]
        );

        // Get front page settings
        wp_register_ability(
            'site-manager/front-page-settings',
            [
                'label'       => __( 'Get Front Page Settings', 'wp-site-manager' ),
                'description' => __( 'Get current homepage and posts page settings', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'display_mode'  => [ 'type' => 'string' ],
                        'homepage'       => [
                            'type' => 'object',
                            'default' => [],
                            'properties' => [
                                'id'    => [ 'type' => 'integer' ],
                                'title' => [ 'type' => 'string' ],
                                'slug'  => [ 'type' => 'string' ],
                            ],
                        ],
                        'posts_page'     => [
                            'type' => 'object',
                            'default' => [],
                            'properties' => [
                                'id'    => [ 'type' => 'integer' ],
                                'title' => [ 'type' => 'string' ],
                                'slug'  => [ 'type' => 'string' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'get_front_page_settings' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get page templates
        wp_register_ability(
            'site-manager/page-templates',
            [
                'label'       => __( 'Get Page Templates', 'wp-site-manager' ),
                'description' => __( 'List available page templates', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'templates' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'  => [ 'type' => 'string' ],
                                    'name'  => [ 'type' => 'string' ],
                                    'file'  => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'get_templates' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Set page template
        wp_register_ability(
            'site-manager/set-page-template',
            [
                'label'       => __( 'Set Page Template', 'wp-site-manager' ),
                'description' => __( 'Assign a template to a page', 'wp-site-manager' ),
                'category'    => 'content',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Page ID',
                        ],
                        'template' => [
                            'type'        => 'string',
                            'description' => 'Template slug (or "default")',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'           => [ 'type' => 'boolean' ],
                        'message'           => [ 'type' => 'string' ],
                        'page_id'           => [ 'type' => 'integer' ],
                        'template'          => [ 'type' => 'string' ],
                        'previous_template' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ PageManager::class, 'set_template' ],
                'permission_callback' => $permissions->callback( 'can_edit_pages' ),
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

    private static function getPageSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'             => [ 'type' => 'integer' ],
                'title'          => [ 'type' => 'string' ],
                'slug'           => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'author'         => [ 'type' => 'integer' ],
                'author_name'    => [ 'type' => 'string' ],
                'date'           => [ 'type' => 'string' ],
                'modified'       => [ 'type' => 'string' ],
                'content'        => [ 'type' => 'string' ],
                'excerpt'        => [ 'type' => 'string' ],
                'parent'         => [ 'type' => 'integer' ],
                'menu_order'     => [ 'type' => 'integer' ],
                'template'       => [ 'type' => 'string' ],
                'featured_image' => [
                    'type'       => [ 'object', 'null' ],
                    'properties' => [
                        'id'  => [ 'type' => 'integer' ],
                        'url' => [ 'type' => 'string' ],
                    ],
                ],
                'link'           => [ 'type' => 'string' ],
            ],
        ];
    }

    private static function getPageTreeSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'         => [ 'type' => 'integer' ],
                'title'      => [ 'type' => 'string' ],
                'slug'       => [ 'type' => 'string' ],
                'status'     => [ 'type' => 'string' ],
                'menu_order' => [ 'type' => 'integer' ],
                'parent'     => [ 'type' => 'integer' ],
                'children'   => [
                    'type'  => 'array',
                    'items' => [ '$ref' => '#' ],
                ],
            ],
        ];
    }

    private static function getListOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'pages'       => [
                    'type'  => 'array',
                    'items' => self::getPageSchema(),
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
            'page'    => self::getPageSchema(),
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
}
