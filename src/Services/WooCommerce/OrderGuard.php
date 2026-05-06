<?php
/**
 * Shared guards for order modification services.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

final class OrderGuard {

    private const MIN_WC_VERSION = '7.0';

    private const LOCKED_STATUSES = [ 'cancelled', 'refunded', 'failed' ];

    /**
     * Ensure WooCommerce is active and meets the minimum version.
     */
    public static function ensureAvailable(): ?\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return ResponseFormatter::error( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, self::MIN_WC_VERSION, '<' ) ) {
            return ResponseFormatter::error(
                'woocommerce_version_too_low',
                sprintf( 'These abilities require WooCommerce %s or higher (found %s)', self::MIN_WC_VERSION, WC_VERSION ),
                400
            );
        }

        return null;
    }

    /**
     * Resolve and return the order, or a WP_Error if the ID is invalid / not found.
     */
    public static function fetchOrder( int $order_id ): \WC_Order|\WP_Error {
        if ( $order_id <= 0 ) {
            return ResponseFormatter::error( 'invalid_order_id', 'Order ID must be a positive integer', 400 );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order instanceof \WC_Order ) {
            return ResponseFormatter::error( 'order_not_found', 'Order not found', 404 );
        }

        return $order;
    }

    /**
     * Ensure the order is in a modifiable state (i.e. not cancelled / refunded / failed).
     */
    public static function ensureModifiable( \WC_Order $order ): ?\WP_Error {
        $status = $order->get_status();

        if ( in_array( $status, self::LOCKED_STATUSES, true ) ) {
            return ResponseFormatter::error(
                'order_locked',
                sprintf( 'Order #%d is %s and cannot be modified', $order->get_id(), $status ),
                409
            );
        }

        return null;
    }
}
