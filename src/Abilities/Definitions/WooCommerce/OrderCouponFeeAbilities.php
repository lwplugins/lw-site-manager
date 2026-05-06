<?php
/**
 * Order coupon and fee abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderModificationManager;

final class OrderCouponFeeAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-apply-order-coupon',
            [
                'label'               => __( 'Apply Coupon to Order', 'lw-site-manager' ),
                'description'         => __( 'Apply a coupon code to an existing order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'code'        => [ 'type' => 'string' ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'code' ],
                ],
                'output_schema'       => self::couponOutputSchema(),
                'execute_callback'    => [ OrderModificationManager::class, 'apply_coupon' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-remove-order-coupon',
            [
                'label'               => __( 'Remove Coupon from Order', 'lw-site-manager' ),
                'description'         => __( 'Remove a coupon from an existing order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'code'        => [ 'type' => 'string' ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'code' ],
                ],
                'output_schema'       => self::couponOutputSchema(),
                'execute_callback'    => [ OrderModificationManager::class, 'remove_coupon' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );

        wp_register_ability(
            'site-manager/wc-add-order-fee',
            [
                'label'               => __( 'Add Order Fee', 'lw-site-manager' ),
                'description'         => __( 'Add a custom fee (e.g., gift wrap, surcharge) to an order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'    => [ 'type' => 'integer' ],
                        'name'        => [ 'type' => 'string', 'description' => 'Fee label (e.g., "Gift wrap")' ],
                        'amount'      => [ 'type' => 'number' ],
                        'tax_status'  => [
                            'type'    => 'string',
                            'enum'    => [ 'taxable', 'none' ],
                            'default' => 'taxable',
                        ],
                        'tax_class'   => [ 'type' => 'string' ],
                        'recalculate' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                    'required'   => [ 'order_id', 'name', 'amount' ],
                ],
                'output_schema'       => self::feeOutputSchema(),
                'execute_callback'    => [ OrderModificationManager::class, 'add_fee' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-remove-order-fee',
            [
                'label'               => __( 'Remove Order Fee', 'lw-site-manager' ),
                'description'         => __( 'Remove a fee item from an order', 'lw-site-manager' ),
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
                'output_schema'       => self::feeOutputSchema(),
                'execute_callback'    => [ OrderModificationManager::class, 'remove_fee' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function couponOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'        => [ 'type' => 'boolean' ],
                'message'        => [ 'type' => 'string' ],
                'order_id'       => [ 'type' => 'integer' ],
                'code'           => [ 'type' => 'string' ],
                'discount_total' => [ 'type' => 'string' ],
                'order'          => [ 'type' => 'object' ],
            ],
        ];
    }

    private static function feeOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'         => [ 'type' => 'boolean' ],
                'message'         => [ 'type' => 'string' ],
                'order_id'        => [ 'type' => 'integer' ],
                'item_id'         => [ 'type' => 'integer' ],
                'removed_item_id' => [ 'type' => 'integer' ],
                'order'           => [ 'type' => 'object' ],
            ],
        ];
    }
}
