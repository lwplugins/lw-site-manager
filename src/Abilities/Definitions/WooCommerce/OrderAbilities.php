<?php
/**
 * WooCommerce order CRUD abilities (list/get/create/update/delete + status).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\OrderSchema;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ResponseSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;

final class OrderAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-list-orders',
            [
                'label'               => __( 'List Orders', 'lw-site-manager' ),
                'description'         => __( 'List WooCommerce orders with filtering options', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status'      => [ 'type' => 'string' ],
                        'customer'    => [ 'type' => 'integer', 'description' => 'Customer ID' ],
                        'product'     => [ 'type' => 'integer', 'description' => 'Filter by product ID' ],
                        'date_after'  => [ 'type' => 'string', 'description' => 'Orders after date (Y-m-d)' ],
                        'date_before' => [ 'type' => 'string', 'description' => 'Orders before date (Y-m-d)' ],
                        'limit'       => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'offset'      => [ 'type' => 'integer', 'default' => 0 ],
                        'orderby'     => [ 'type' => 'string', 'default' => 'date' ],
                        'order'       => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema'       => ResponseSchema::list( 'orders', OrderSchema::order() ),
                'execute_callback'    => [ OrderManager::class, 'list_orders' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-get-order',
            [
                'label'               => __( 'Get Order', 'lw-site-manager' ),
                'description'         => __( 'Get detailed information about a WooCommerce order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [ 'type' => 'integer', 'description' => 'Order ID' ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => ResponseSchema::entity( 'order', OrderSchema::order( true ) ),
                'execute_callback'    => [ OrderManager::class, 'get_order' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-order',
            [
                'label'               => __( 'Create Order', 'lw-site-manager' ),
                'description'         => __( 'Create a new WooCommerce order, optionally on behalf of a customer with line items', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'customer_id'          => [
                            'type'        => 'integer',
                            'description' => 'User ID to assign the order to (0 = guest)',
                        ],
                        'line_items'           => [
                            'type'        => 'array',
                            'description' => 'Products to add to the order',
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'product_id' => [ 'type' => 'integer' ],
                                    'quantity'   => [ 'type' => 'integer', 'default' => 1 ],
                                    'subtotal'   => [ 'type' => 'string' ],
                                    'total'      => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'billing'              => OrderSchema::address( true ),
                        'shipping'             => OrderSchema::address( false ),
                        'payment_method'       => [ 'type' => 'string' ],
                        'payment_method_title' => [ 'type' => 'string' ],
                        'currency'             => [ 'type' => 'string' ],
                        'status'               => [
                            'type'        => 'string',
                            'default'     => 'pending',
                            'description' => 'Order status (e.g., pending, processing, completed, on-hold)',
                        ],
                        'customer_note'        => [ 'type' => 'string' ],
                        'note'                 => [
                            'type'        => 'string',
                            'description' => 'Internal order note added after creation',
                        ],
                        'meta'                 => [
                            'type'        => 'object',
                            'description' => 'Map of meta_key => value applied to the order before save (HPOS-aware)',
                        ],
                    ],
                ],
                'output_schema'       => ResponseSchema::entity( 'order', OrderSchema::order( true ), true ),
                'execute_callback'    => [ OrderManager::class, 'create_order' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-order',
            [
                'label'               => __( 'Update Order', 'lw-site-manager' ),
                'description'         => __( 'Update billing/shipping details or customer note on an existing order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'            => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'billing'       => OrderSchema::address( true ),
                        'shipping'      => OrderSchema::address( false ),
                        'customer_note' => [ 'type' => 'string' ],
                        'note'          => [
                            'type'        => 'string',
                            'description' => 'Internal order note added after update',
                        ],
                        'meta'          => [
                            'type'        => 'object',
                            'description' => 'Map of meta_key => value applied to the order (HPOS-aware)',
                        ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => ResponseSchema::entity( 'order', OrderSchema::order( true ) ),
                'execute_callback'    => [ OrderManager::class, 'update_order' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-order',
            [
                'label'               => __( 'Delete Order', 'lw-site-manager' ),
                'description'         => __( 'Move an order to trash, or permanently delete with force=true', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'    => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'force' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Permanently delete instead of trashing',
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
                        'order_number' => [ 'type' => 'string' ],
                        'trashed'      => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'delete_order' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-order-status',
            [
                'label'               => __( 'Update Order Status', 'lw-site-manager' ),
                'description'         => __( 'Change the status of an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'     => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'status' => [
                            'type'        => 'string',
                            'description' => 'New status (e.g., processing, completed, on-hold)',
                        ],
                        'note'   => [
                            'type'        => 'string',
                            'description' => 'Optional note for the status change',
                        ],
                    ],
                    'required'   => [ 'id', 'status' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'id'         => [ 'type' => 'integer' ],
                        'old_status' => [ 'type' => 'string' ],
                        'new_status' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'update_order_status' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );
    }
}
