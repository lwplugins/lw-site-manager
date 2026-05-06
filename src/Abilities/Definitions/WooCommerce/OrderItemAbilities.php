<?php
/**
 * Order line item abilities (add / update / remove).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderItemManager;

final class OrderItemAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-add-order-item',
            [
                'label'               => __( 'Add Order Line Item', 'lw-site-manager' ),
                'description'         => __( 'Add a product line item to an existing order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'     => [ 'type' => 'integer', 'description' => 'Order ID' ],
                        'product_id'   => [ 'type' => 'integer', 'description' => 'Product ID (simple, variable parent, or variation)' ],
                        'variation_id' => [ 'type' => 'integer', 'description' => 'Optional explicit variation ID' ],
                        'quantity'     => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1 ],
                        'subtotal'     => [ 'type' => 'string', 'description' => 'Override line subtotal' ],
                        'total'        => [ 'type' => 'string', 'description' => 'Override line total' ],
                        'meta'         => [ 'type' => 'object', 'description' => 'Extra line item meta key/value pairs' ],
                        'recalculate'  => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Recalculate order totals after adding the item',
                        ],
                    ],
                    'required'   => [ 'order_id', 'product_id' ],
                ],
                'output_schema'       => self::mutationOutputSchema( 'item_id' ),
                'execute_callback'    => [ OrderItemManager::class, 'add_order_item' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-order-item',
            [
                'label'               => __( 'Update Order Line Item', 'lw-site-manager' ),
                'description'         => __( 'Change quantity, subtotal, or total on an existing line item', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'item_id'     => [ 'type' => 'integer' ],
                        'quantity'    => [ 'type' => 'integer', 'minimum' => 1 ],
                        'subtotal'    => [ 'type' => 'string' ],
                        'total'       => [ 'type' => 'string' ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'item_id' ],
                ],
                'output_schema'       => self::mutationOutputSchema( 'item_id' ),
                'execute_callback'    => [ OrderItemManager::class, 'update_order_item' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-remove-order-item',
            [
                'label'               => __( 'Remove Order Line Item', 'lw-site-manager' ),
                'description'         => __( 'Delete a line item from an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'item_id'     => [ 'type' => 'integer' ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'item_id' ],
                ],
                'output_schema'       => self::mutationOutputSchema( 'removed_item_id' ),
                'execute_callback'    => [ OrderItemManager::class, 'remove_order_item' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function mutationOutputSchema( string $idKey ): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'  => [ 'type' => 'boolean' ],
                'message'  => [ 'type' => 'string' ],
                'order_id' => [ 'type' => 'integer' ],
                $idKey     => [ 'type' => 'integer' ],
                'order'    => [ 'type' => 'object' ],
            ],
        ];
    }
}
