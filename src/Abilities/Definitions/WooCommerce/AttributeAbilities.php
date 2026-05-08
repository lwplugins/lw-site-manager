<?php
/**
 * Global WooCommerce attribute abilities (pa_*).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\AttributeManager;

final class AttributeAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-list-attributes',
            [
                'label'               => __( 'List Global Attributes', 'lw-site-manager' ),
                'description'         => __( 'List WooCommerce global product attributes (pa_* taxonomies)', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'attributes' => [ 'type' => 'array', 'items' => self::attributeSchema() ],
                        'total'      => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ AttributeManager::class, 'list_attributes' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-get-attribute',
            [
                'label'               => __( 'Get Global Attribute', 'lw-site-manager' ),
                'description'         => __( 'Get a global product attribute by id or slug', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'   => [ 'type' => 'integer' ],
                        'slug' => [ 'type' => 'string', 'description' => 'Attribute slug without pa_ prefix (e.g. "color")' ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'attribute' => self::attributeSchema(),
                    ],
                ],
                'execute_callback'    => [ AttributeManager::class, 'get_attribute' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-attribute',
            [
                'label'               => __( 'Create Global Attribute', 'lw-site-manager' ),
                'description'         => __( 'Create a new global product attribute taxonomy', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'name'         => [ 'type' => 'string', 'description' => 'Display label (e.g. "Color")' ],
                        'slug'         => [ 'type' => 'string', 'description' => 'Optional slug (without pa_ prefix); derived from name if omitted' ],
                        'type'         => [ 'type' => 'string', 'enum' => [ 'select', 'text' ], 'default' => 'select' ],
                        'order_by'     => [
                            'type'    => 'string',
                            'enum'    => [ 'menu_order', 'name', 'name_num', 'id' ],
                            'default' => 'menu_order',
                        ],
                        'has_archives' => [ 'type' => 'boolean', 'default' => false ],
                    ],
                    'required'   => [ 'name' ],
                ],
                'output_schema'       => self::mutationOutputSchema(),
                'execute_callback'    => [ AttributeManager::class, 'create_attribute' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-attribute',
            [
                'label'               => __( 'Update Global Attribute', 'lw-site-manager' ),
                'description'         => __( 'Update label, type, or ordering of a global attribute', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'           => [ 'type' => 'integer' ],
                        'slug'         => [ 'type' => 'string' ],
                        'name'         => [ 'type' => 'string' ],
                        'type'         => [ 'type' => 'string', 'enum' => [ 'select', 'text' ] ],
                        'order_by'     => [ 'type' => 'string', 'enum' => [ 'menu_order', 'name', 'name_num', 'id' ] ],
                        'has_archives' => [ 'type' => 'boolean' ],
                    ],
                ],
                'output_schema'       => self::mutationOutputSchema(),
                'execute_callback'    => [ AttributeManager::class, 'update_attribute' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-attribute',
            [
                'label'               => __( 'Delete Global Attribute', 'lw-site-manager' ),
                'description'         => __( 'Permanently delete a global attribute and all of its terms', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'   => [ 'type' => 'integer' ],
                        'slug' => [ 'type' => 'string' ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'id'      => [ 'type' => 'integer' ],
                        'name'    => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ AttributeManager::class, 'delete_attribute' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function attributeSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => [ 'type' => 'integer' ],
                'name'         => [ 'type' => 'string' ],
                'slug'         => [ 'type' => 'string' ],
                'taxonomy'     => [ 'type' => 'string' ],
                'type'         => [ 'type' => 'string' ],
                'order_by'     => [ 'type' => 'string' ],
                'has_archives' => [ 'type' => 'boolean' ],
            ],
        ];
    }

    private static function mutationOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'   => [ 'type' => 'boolean' ],
                'message'   => [ 'type' => 'string' ],
                'id'        => [ 'type' => 'integer' ],
                'attribute' => self::attributeSchema(),
            ],
        ];
    }
}
