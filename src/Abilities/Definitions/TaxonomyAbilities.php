<?php
/**
 * Taxonomy Abilities - Category and Tag management
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\TaxonomyManager;

class TaxonomyAbilities {

    public static function register( PermissionManager $permissions ): void {
        self::registerCategoryAbilities( $permissions );
        self::registerTagAbilities( $permissions );
    }

    private static function registerCategoryAbilities( PermissionManager $permissions ): void {
        // List categories
        wp_register_ability(
            'site-manager/list-categories',
            [
                'label'       => __( 'List Categories', 'lw-site-manager' ),
                'description' => __( 'List all categories', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'default'    => [],
                    'properties' => [
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'hide_empty' => [
                            'type'    => 'boolean',
                            'default' => false,
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search term',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Filter by parent category ID',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'name',
                            'enum'    => [ 'name', 'slug', 'term_id', 'count' ],
                        ],
                        'order' => [
                            'type'    => 'string',
                            'default' => 'ASC',
                            'enum'    => [ 'ASC', 'DESC' ],
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'category';
                    return TaxonomyManager::list_terms( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get category
        wp_register_ability(
            'site-manager/get-category',
            [
                'label'       => __( 'Get Category', 'lw-site-manager' ),
                'description' => __( 'Get detailed information about a category', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Category ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'category';
                    return TaxonomyManager::get_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Create category
        wp_register_ability(
            'site-manager/create-category',
            [
                'label'       => __( 'Create Category', 'lw-site-manager' ),
                'description' => __( 'Create a new category', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'name' => [
                            'type'        => 'string',
                            'description' => 'Category name',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Category slug',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Category description',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent category ID',
                        ],
                    ],
                    'required' => [ 'name' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'category';
                    return TaxonomyManager::create_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update category
        wp_register_ability(
            'site-manager/update-category',
            [
                'label'       => __( 'Update Category', 'lw-site-manager' ),
                'description' => __( 'Update an existing category', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Category ID',
                        ],
                        'name' => [
                            'type'        => 'string',
                            'description' => 'Category name',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Category slug',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Category description',
                        ],
                        'parent' => [
                            'type'        => 'integer',
                            'description' => 'Parent category ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'category';
                    return TaxonomyManager::update_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete category
        wp_register_ability(
            'site-manager/delete-category',
            [
                'label'       => __( 'Delete Category', 'lw-site-manager' ),
                'description' => __( 'Delete a category', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Category ID',
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
                    ],
                ],
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'category';
                    return TaxonomyManager::delete_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_categories' ),
                'meta' => self::destructiveMeta(),
            ]
        );
    }

    private static function registerTagAbilities( PermissionManager $permissions ): void {
        // List tags
        wp_register_ability(
            'site-manager/list-tags',
            [
                'label'       => __( 'List Tags', 'lw-site-manager' ),
                'description' => __( 'List all tags', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'hide_empty' => [
                            'type'    => 'boolean',
                            'default' => false,
                        ],
                        'search' => [
                            'type'        => 'string',
                            'description' => 'Search term',
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'name',
                            'enum'    => [ 'name', 'slug', 'term_id', 'count' ],
                        ],
                        'order' => [
                            'type'    => 'string',
                            'default' => 'ASC',
                            'enum'    => [ 'ASC', 'DESC' ],
                        ],
                    ],
                ],
                'output_schema' => self::getListOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'post_tag';
                    return TaxonomyManager::list_terms( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_tags' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get tag
        wp_register_ability(
            'site-manager/get-tag',
            [
                'label'       => __( 'Get Tag', 'lw-site-manager' ),
                'description' => __( 'Get detailed information about a tag', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Tag ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'post_tag';
                    return TaxonomyManager::get_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_tags' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Create tag
        wp_register_ability(
            'site-manager/create-tag',
            [
                'label'       => __( 'Create Tag', 'lw-site-manager' ),
                'description' => __( 'Create a new tag', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'name' => [
                            'type'        => 'string',
                            'description' => 'Tag name',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Tag slug',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Tag description',
                        ],
                    ],
                    'required' => [ 'name' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'post_tag';
                    return TaxonomyManager::create_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_tags' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update tag
        wp_register_ability(
            'site-manager/update-tag',
            [
                'label'       => __( 'Update Tag', 'lw-site-manager' ),
                'description' => __( 'Update an existing tag', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Tag ID',
                        ],
                        'name' => [
                            'type'        => 'string',
                            'description' => 'Tag name',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Tag slug',
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => 'Tag description',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::getEntityOutputSchema(),
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'post_tag';
                    return TaxonomyManager::update_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_tags' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete tag
        wp_register_ability(
            'site-manager/delete-tag',
            [
                'label'       => __( 'Delete Tag', 'lw-site-manager' ),
                'description' => __( 'Delete a tag', 'lw-site-manager' ),
                'category'    => 'taxonomy',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Tag ID',
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
                    ],
                ],
                'execute_callback'    => function( array $input ) {
                    $input['taxonomy'] = 'post_tag';
                    return TaxonomyManager::delete_term( $input );
                },
                'permission_callback' => $permissions->callback( 'can_manage_tags' ),
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

    private static function getListOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'terms' => [
                    'type'  => 'array',
                    'items' => self::getTermSchema(),
                ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
            ],
        ];
    }

    private static function getEntityOutputSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'term'    => self::getTermSchema( true ),
            ],
        ];
    }

    private static function getTermSchema( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'       => [ 'type' => 'integer' ],
                'name'     => [ 'type' => 'string' ],
                'slug'     => [ 'type' => 'string' ],
                'taxonomy' => [ 'type' => 'string' ],
                'count'    => [ 'type' => 'integer' ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['description'] = [ 'type' => 'string' ];
            $schema['properties']['parent']      = [ 'type' => 'integer' ];
            $schema['properties']['link']        = [ 'type' => 'string' ];
        }

        return $schema;
    }
}
