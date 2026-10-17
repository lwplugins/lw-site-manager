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
     * Resolve an order the current user may act on.
     *
     * The capability is checked against the order ID first; WooCommerce maps
     * read_post / edit_post / delete_post on an order to its order
     * capabilities, also under HPOS. Missing and forbidden orders return the
     * same error.
     *
     * @param int    $order_id   Order ID.
     * @param string $capability read_post, edit_post (default) or delete_post.
     */
    public static function fetchOrder( int $order_id, string $capability = 'edit_post' ): \WC_Order|\WP_Error {
        if ( $order_id <= 0 ) {
            return ResponseFormatter::error( 'invalid_order_id', 'Order ID must be a positive integer', 400 );
        }

        $order = current_user_can( $capability, $order_id ) ? wc_get_order( $order_id ) : null;

        return $order instanceof \WC_Order ? $order : CatalogGuard::unavailable( 'Order' );
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
