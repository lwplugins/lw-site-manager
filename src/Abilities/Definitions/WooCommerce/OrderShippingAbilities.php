<?php
/**
 * Order shipping abilities (set / remove / recalculate).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderModificationManager;

final class OrderShippingAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-set-order-shipping',
            [
                'label'               => __( 'Set Order Shipping', 'lw-site-manager' ),
                'description'         => __( 'Set or replace the shipping line on an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'         => [ 'type' => 'integer' ],
                        'method_id'        => [ 'type' => 'string', 'description' => 'Shipping method id (e.g. flat_rate)' ],
                        'method_title'     => [ 'type' => 'string', 'description' => 'Display title (defaults to method_id)' ],
                        'total'            => [ 'type' => 'string', 'description' => 'Shipping cost as string-numeric' ],
                        'replace_existing' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Remove existing shipping lines before adding the new one',
                        ],
                        'recalculate'      => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'method_id', 'total' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'        => [ 'type' => 'boolean' ],
                        'message'        => [ 'type' => 'string' ],
                        'order_id'       => [ 'type' => 'integer' ],
                        'item_id'        => [ 'type' => 'integer' ],
                        'shipping_total' => [ 'type' => 'string' ],
                        'order'          => [ 'type' => 'object' ],
                    ],
                ],
                'execute_callback'    => [ OrderModificationManager::class, 'set_shipping' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-remove-order-shipping',
            [
                'label'               => __( 'Remove Order Shipping', 'lw-site-manager' ),
                'description'         => __( 'Remove one or all shipping lines from an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'item_id'     => [
                            'type'        => 'integer',
                            'description' => 'Specific shipping item to remove. Omit to remove all shipping lines.',
                        ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'message'  => [ 'type' => 'string' ],
                        'order_id' => [ 'type' => 'integer' ],
                        'removed'  => [ 'type' => 'integer' ],
                        'order'    => [ 'type' => 'object' ],
                    ],
                ],
                'execute_callback'    => [ OrderModificationManager::class, 'remove_shipping' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );

        wp_register_ability(
            'site-manager/wc-recalculate-order',
            [
                'label'               => __( 'Recalculate Order Totals', 'lw-site-manager' ),
                'description'         => __( 'Force WooCommerce to recompute subtotals, taxes, and total on an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [ 'type' => 'integer' ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'message'   => [ 'type' => 'string' ],
                        'order_id'  => [ 'type' => 'integer' ],
                        'old_total' => [ 'type' => 'string' ],
                        'new_total' => [ 'type' => 'string' ],
                        'order'     => [ 'type' => 'object' ],
                    ],
                ],
                'execute_callback'    => [ OrderModificationManager::class, 'recalculate' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write( ),
            ]
        );
    }
}
