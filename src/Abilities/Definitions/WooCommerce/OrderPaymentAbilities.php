<?php
/**
 * Order payment workflow abilities (mark paid / send email / pay url).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderPaymentManager;

final class OrderPaymentAbilities {

    private const EMAIL_TYPES = [
        'new_order',
        'customer_invoice',
        'customer_processing_order',
        'customer_completed_order',
        'customer_on_hold_order',
        'customer_refunded_order',
    ];

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-mark-order-paid',
            [
                'label'               => __( 'Mark Order Paid', 'lw-site-manager' ),
                'description'         => __( 'Trigger payment_complete() on an order; auto-transitions status per WooCommerce rules', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'             => [ 'type' => 'integer' ],
                        'payment_method'       => [ 'type' => 'string' ],
                        'payment_method_title' => [ 'type' => 'string' ],
                        'transaction_id'       => [ 'type' => 'string' ],
                        'silent'               => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Suppress customer notification emails for the status change',
                        ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'        => [ 'type' => 'boolean' ],
                        'message'        => [ 'type' => 'string' ],
                        'order_id'       => [ 'type' => 'integer' ],
                        'transaction_id' => [ 'type' => 'string' ],
                        'silent'         => [ 'type' => 'boolean' ],
                        'new_status'     => [ 'type' => 'string' ],
                        'date_paid'      => [ 'type' => [ 'string', 'null' ] ],
                        'order'          => [ 'type' => 'object' ],
                    ],
                ],
                'execute_callback'    => [ OrderPaymentManager::class, 'mark_paid' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-send-order-email',
            [
                'label'               => __( 'Send Order Email', 'lw-site-manager' ),
                'description'         => __( 'Re-send a WooCommerce order email (admin or customer) for the given order', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'   => [ 'type' => 'integer' ],
                        'email_type' => [
                            'type'        => 'string',
                            'enum'        => self::EMAIL_TYPES,
                            'description' => 'Which order email template to send',
                        ],
                    ],
                    'required'   => [ 'order_id', 'email_type' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'order_id'   => [ 'type' => 'integer' ],
                        'email_type' => [ 'type' => 'string' ],
                        'recipient'  => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderPaymentManager::class, 'send_email' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-get-payment-url',
            [
                'label'               => __( 'Get Order Payment URL', 'lw-site-manager' ),
                'description'         => __( 'Return the customer-facing pay-for-order URL for an unpaid order', 'lw-site-manager' ),
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
                        'order_id'  => [ 'type' => 'integer' ],
                        'pay_url'   => [ 'type' => 'string' ],
                        'order_key' => [ 'type' => 'string' ],
                        'status'    => [ 'type' => 'string' ],
                        'total'     => [ 'type' => 'string' ],
                        'currency'  => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderPaymentManager::class, 'get_payment_url' ],
                'permission_callback' => $permissions->callback( 'can_manage_orders' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );
    }
}
