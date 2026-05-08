<?php
/**
 * Product-attribute binding abilities (set / add / remove on a product).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductAttributeManager;

final class ProductAttributeAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-set-product-attributes',
            [
                'label'               => __( 'Set Product Attributes', 'lw-site-manager' ),
                'description'         => __( 'Replace the entire attribute set on a product (global pa_* or custom)', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [ 'type' => 'integer' ],
                        'attributes' => [
                            'type'  => 'array',
                            'items' => self::attributeEntrySchema(),
                        ],
                    ],
                    'required'   => [ 'product_id', 'attributes' ],
                ],
                'output_schema'       => self::productAttrOutputSchema(),
                'execute_callback'    => [ ProductAttributeManager::class, 'set_product_attributes' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-add-product-attribute',
            [
                'label'               => __( 'Add Product Attribute', 'lw-site-manager' ),
                'description'         => __( 'Append an attribute to a product without disturbing existing ones', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [ 'type' => 'integer' ],
                        'name'       => [
                            'type'        => 'string',
                            'description' => 'Attribute name. Use pa_xxx for global, any string for custom.',
                        ],
                        'options'    => [
                            'type'        => 'array',
                            'items'       => [ 'type' => [ 'string', 'integer' ] ],
                            'description' => 'For global: term IDs / slugs / names. For custom: free-form strings.',
                        ],
                        'position'   => [ 'type' => 'integer', 'default' => 0 ],
                        'visible'    => [ 'type' => 'boolean', 'default' => true ],
                        'variation'  => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Whether this attribute drives variations on a variable product',
                        ],
                    ],
                    'required'   => [ 'product_id', 'name' ],
                ],
                'output_schema'       => self::productAttrOutputSchema(),
                'execute_callback'    => [ ProductAttributeManager::class, 'add_product_attribute' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-remove-product-attribute',
            [
                'label'               => __( 'Remove Product Attribute', 'lw-site-manager' ),
                'description'         => __( 'Detach a single attribute from a product by name', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [ 'type' => 'integer' ],
                        'name'       => [
                            'type'        => 'string',
                            'description' => 'Attribute name (pa_color for global, or the custom label)',
                        ],
                    ],
                    'required'   => [ 'product_id', 'name' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'      => [ 'type' => 'boolean' ],
                        'message'      => [ 'type' => 'string' ],
                        'product_id'   => [ 'type' => 'integer' ],
                        'removed_name' => [ 'type' => 'string' ],
                        'attributes'   => [ 'type' => 'array', 'items' => self::attachedAttributeSchema() ],
                    ],
                ],
                'execute_callback'    => [ ProductAttributeManager::class, 'remove_product_attribute' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function attributeEntrySchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'name'      => [
                    'type'        => 'string',
                    'description' => 'Attribute name. Use pa_xxx for global, any string for custom.',
                ],
                'options'   => [
                    'type'        => 'array',
                    'items'       => [ 'type' => [ 'string', 'integer' ] ],
                    'description' => 'For global: term IDs / slugs / names. For custom: free-form strings.',
                ],
                'position'  => [ 'type' => 'integer', 'default' => 0 ],
                'visible'   => [ 'type' => 'boolean', 'default' => true ],
                'variation' => [
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Whether this attribute drives variations on a variable product',
                ],
            ],
        ];
    }

    private static function attachedAttributeSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'name'      => [ 'type' => 'string' ],
                'options'   => [ 'type' => 'array' ],
                'position'  => [ 'type' => 'integer' ],
                'visible'   => [ 'type' => 'boolean' ],
                'variation' => [ 'type' => 'boolean' ],
                'is_global' => [ 'type' => 'boolean' ],
            ],
        ];
    }

    private static function productAttrOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'    => [ 'type' => 'boolean' ],
                'message'    => [ 'type' => 'string' ],
                'product_id' => [ 'type' => 'integer' ],
                'attribute'  => self::attachedAttributeSchema(),
                'attributes' => [ 'type' => 'array', 'items' => self::attachedAttributeSchema() ],
            ],
        ];
    }
}
