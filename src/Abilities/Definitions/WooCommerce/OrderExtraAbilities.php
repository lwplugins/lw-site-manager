<?php
/**
 * WooCommerce order extras (statuses list, refunds, notes, bulk).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ResponseSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;

final class OrderExtraAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-list-order-statuses',
            [
                'label'               => __( 'List Order Statuses', 'lw-site-manager' ),
                'description'         => __( 'List available WooCommerce order statuses', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'statuses' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'slug'  => [ 'type' => 'string' ],
                                    'label' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total'    => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'list_order_statuses' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-refund',
            [
                'label'               => __( 'Create Refund', 'lw-site-manager' ),
                'description'         => __( 'Create a refund for an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'      => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'amount'        => [
                            'type'        => 'number',
                            'description' => 'Refund amount (defaults to full order total)',
                        ],
                        'reason'        => [ 'type' => 'string', 'description' => 'Reason for refund' ],
                        'restock_items' => [ 'type' => 'boolean', 'default' => true ],
                        'line_items'    => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'       => [ 'type' => 'integer' ],
                                    'quantity' => [ 'type' => 'integer' ],
                                    'total'    => [ 'type' => 'number' ],
                                ],
                            ],
                        ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'message'   => [ 'type' => 'string' ],
                        'refund_id' => [ 'type' => 'integer' ],
                        'order_id'  => [ 'type' => 'integer' ],
                        'amount'    => [ 'type' => 'number' ],
                        'reason'    => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'create_refund' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );

        wp_register_ability(
            'site-manager/wc-list-order-notes',
            [
                'label'               => __( 'List Order Notes', 'lw-site-manager' ),
                'description'         => __( 'List notes for an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'type'     => [
                            'type'    => 'string',
                            'enum'    => [ 'any', 'customer', 'internal' ],
                            'default' => 'any',
                        ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'order_id' => [ 'type' => 'integer' ],
                        'notes'    => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'            => [ 'type' => 'integer' ],
                                    'content'       => [ 'type' => 'string' ],
                                    'date_created'  => [ 'type' => 'string' ],
                                    'customer_note' => [ 'type' => 'boolean' ],
                                    'added_by'      => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total'    => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'list_order_notes' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-add-order-note',
            [
                'label'               => __( 'Add Order Note', 'lw-site-manager' ),
                'description'         => __( 'Add a note to an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'      => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'note'          => [ 'type' => 'string', 'description' => 'Note content' ],
                        'customer_note' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Send note to customer',
                        ],
                    ],
                    'required'   => [ 'order_id', 'note' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'       => [ 'type' => 'boolean' ],
                        'message'       => [ 'type' => 'string' ],
                        'note_id'       => [ 'type' => 'integer' ],
                        'order_id'      => [ 'type' => 'integer' ],
                        'customer_note' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'add_order_note' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-bulk-orders',
            [
                'label'               => __( 'Bulk Order Action', 'lw-site-manager' ),
                'description'         => __( 'Update status for multiple orders', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids'    => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'integer' ],
                        ],
                        'status' => [ 'type' => 'string', 'description' => 'New status' ],
                        'note'   => [ 'type' => 'string' ],
                    ],
                    'required'   => [ 'ids', 'status' ],
                ],
                'output_schema'       => ResponseSchema::bulk(),
                'execute_callback'    => [ OrderManager::class, 'bulk_orders' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );
    }
}
