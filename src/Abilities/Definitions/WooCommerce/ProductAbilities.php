<?php
/**
 * WooCommerce product CRUD abilities (list/get/create/update/delete).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ProductSchema;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ResponseSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;

final class ProductAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-list-products',
            [
                'label'               => __( 'List Products', 'lw-site-manager' ),
                'description'         => __( 'List WooCommerce products with filtering options', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status'       => [
                            'type'    => 'string',
                            'default' => 'any',
                            'enum'    => [ 'any', 'publish', 'draft', 'pending', 'private', 'trash' ],
                        ],
                        'type'         => [
                            'type' => 'string',
                            'enum' => [ 'simple', 'variable', 'grouped', 'external' ],
                        ],
                        'category'     => [
                            'type'        => 'string',
                            'description' => 'Category slug or ID',
                        ],
                        'stock_status' => [
                            'type' => 'string',
                            'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
                        ],
                        'featured'     => [ 'type' => 'boolean' ],
                        'on_sale'      => [ 'type' => 'boolean' ],
                        'search'       => [ 'type' => 'string' ],
                        'limit'        => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'offset'       => [ 'type' => 'integer', 'default' => 0 ],
                        'orderby'      => [ 'type' => 'string', 'default' => 'date' ],
                        'order'        => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema'       => ResponseSchema::list( 'products', ProductSchema::product() ),
                'execute_callback'    => [ ProductManager::class, 'list_products' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-get-product',
            [
                'label'               => __( 'Get Product', 'lw-site-manager' ),
                'description'         => __( 'Get detailed information about a WooCommerce product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'   => [ 'type' => 'integer', 'description' => 'Product ID' ],
                        'sku'  => [ 'type' => 'string', 'description' => 'Product SKU' ],
                        'slug' => [ 'type' => 'string', 'description' => 'Product slug' ],
                    ],
                ],
                'output_schema'       => ResponseSchema::entity( 'product', ProductSchema::product( true ) ),
                'execute_callback'    => [ ProductManager::class, 'get_product' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-product',
            [
                'label'               => __( 'Create Product', 'lw-site-manager' ),
                'description'         => __( 'Create a new WooCommerce product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => ProductSchema::input(),
                    'required'   => [ 'name' ],
                ],
                'output_schema'       => ResponseSchema::entity( 'product', ProductSchema::product( true ), true ),
                'execute_callback'    => [ ProductManager::class, 'create_product' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-product',
            [
                'label'               => __( 'Update Product', 'lw-site-manager' ),
                'description'         => __( 'Update an existing WooCommerce product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        [ 'id' => [ 'type' => 'integer', 'description' => 'Product ID' ] ],
                        ProductSchema::input()
                    ),
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => ResponseSchema::entity( 'product', ProductSchema::product( true ) ),
                'execute_callback'    => [ ProductManager::class, 'update_product' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-product',
            [
                'label'               => __( 'Delete Product', 'lw-site-manager' ),
                'description'         => __( 'Delete a WooCommerce product', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'    => [ 'type' => 'integer', 'description' => 'Product ID' ],
                        'force' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Permanently delete (skip trash)',
                        ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => ResponseSchema::delete(),
                'execute_callback'    => [ ProductManager::class, 'delete_product' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive(),
            ]
        );
    }
}
