<?php
/**
 * WooCommerce product extra abilities (duplicate, stock, categories, variations, bulk).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ProductSchema;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ResponseSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;

final class ProductExtraAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-duplicate-product',
            [
                'label'               => __( 'Duplicate Product', 'lw-site-manager' ),
                'description'         => __( 'Create a copy of a WooCommerce product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'       => [ 'type' => 'integer', 'description' => 'Product ID to duplicate' ],
                        'new_name' => [ 'type' => 'string', 'description' => 'Name for the copy' ],
                        'status'   => [ 'type' => 'string', 'default' => 'draft' ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => ResponseSchema::entity( 'product', ProductSchema::product( true ), true ),
                'execute_callback'    => [ ProductManager::class, 'duplicate_product' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-stock',
            [
                'label'               => __( 'Update Stock', 'lw-site-manager' ),
                'description'         => __( 'Update product stock quantity or status', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'           => [ 'type' => 'integer', 'description' => 'Product ID' ],
                        'quantity'     => [ 'type' => 'integer', 'description' => 'Set stock quantity' ],
                        'adjust'       => [
                            'type'        => 'integer',
                            'description' => 'Adjust stock (positive to add, negative to subtract)',
                        ],
                        'stock_status' => [
                            'type' => 'string',
                            'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
                        ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'      => [ 'type' => 'boolean' ],
                        'message'      => [ 'type' => 'string' ],
                        'id'           => [ 'type' => 'integer' ],
                        'name'         => [ 'type' => 'string' ],
                        'old_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                        'new_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                        'old_status'   => [ 'type' => 'string' ],
                        'new_status'   => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'update_stock' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-list-product-categories',
            [
                'label'               => __( 'List Product Categories', 'lw-site-manager' ),
                'description'         => __( 'List WooCommerce product categories', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'hide_empty' => [ 'type' => 'boolean', 'default' => false ],
                        'parent'     => [ 'type' => 'integer' ],
                        'search'     => [ 'type' => 'string' ],
                        'limit'      => [ 'type' => 'integer', 'default' => 100 ],
                        'offset'     => [ 'type' => 'integer', 'default' => 0 ],
                        'orderby'    => [ 'type' => 'string', 'default' => 'name' ],
                        'order'      => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'ASC',
                        ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'categories' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'          => [ 'type' => 'integer' ],
                                    'name'        => [ 'type' => 'string' ],
                                    'slug'        => [ 'type' => 'string' ],
                                    'description' => [ 'type' => 'string' ],
                                    'parent'      => [ 'type' => 'integer' ],
                                    'count'       => [ 'type' => 'integer' ],
                                    'image'       => [ 'type' => [ 'string', 'null' ] ],
                                ],
                            ],
                        ],
                        'total'      => [ 'type' => 'integer' ],
                        'limit'      => [ 'type' => 'integer' ],
                        'offset'     => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'list_product_categories' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-list-variations',
            [
                'label'               => __( 'List Product Variations', 'lw-site-manager' ),
                'description'         => __( 'List variations of a variable product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [
                            'type'        => 'integer',
                            'description' => 'Variable product ID',
                        ],
                    ],
                    'required'   => [ 'product_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'product_id'   => [ 'type' => 'integer' ],
                        'product_name' => [ 'type' => 'string' ],
                        'variations'   => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'             => [ 'type' => 'integer' ],
                                    'sku'            => [ 'type' => 'string' ],
                                    'price'          => [ 'type' => 'string' ],
                                    'regular_price'  => [ 'type' => 'string' ],
                                    'sale_price'     => [ 'type' => 'string' ],
                                    'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                                    'stock_status'   => [ 'type' => 'string' ],
                                    'attributes'     => [ 'type' => 'object' ],
                                    'image'          => [ 'type' => [ 'string', 'null' ] ],
                                    'status'         => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total'        => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'list_variations' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-bulk-products',
            [
                'label'               => __( 'Bulk Product Action', 'lw-site-manager' ),
                'description'         => __( 'Perform bulk actions on multiple products', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids'    => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'integer' ],
                        ],
                        'action' => [
                            'type' => 'string',
                            'enum' => [ 'publish', 'draft', 'trash', 'delete', 'restore' ],
                        ],
                    ],
                    'required'   => [ 'ids', 'action' ],
                ],
                'output_schema'       => ResponseSchema::bulk(),
                'execute_callback'    => [ ProductManager::class, 'bulk_products' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );
    }
}
