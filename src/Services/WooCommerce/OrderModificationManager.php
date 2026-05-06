<?php
/**
 * Order modifications: coupons, fees, shipping, recalculate totals.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class OrderModificationManager extends AbstractService {

    public static function apply_coupon( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $code  = trim( (string) ( $input['code'] ?? '' ) );
        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        if ( '' === $code ) {
            return self::errorResponse( 'invalid_coupon_code', 'Coupon code is required', 400 );
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $applied = $order->apply_coupon( $code );
        if ( $applied instanceof \WP_Error ) {
            return $applied;
        }

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Coupon applied via Site Manager: %s', $code ) );

        return [
            'success'        => true,
            'message'        => 'Coupon applied',
            'order_id'       => $order->get_id(),
            'code'           => $code,
            'discount_total' => (string) $order->get_discount_total(),
            'order'          => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    public static function remove_coupon( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $code  = trim( (string) ( $input['code'] ?? '' ) );
        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        if ( '' === $code ) {
            return self::errorResponse( 'invalid_coupon_code', 'Coupon code is required', 400 );
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $order->remove_coupon( $code );

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Coupon removed via Site Manager: %s', $code ) );

        return [
            'success'        => true,
            'message'        => 'Coupon removed',
            'order_id'       => $order->get_id(),
            'code'           => $code,
            'discount_total' => (string) $order->get_discount_total(),
            'order'          => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    public static function add_fee( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $name   = trim( (string) ( $input['name'] ?? '' ) );
        $amount = $input['amount'] ?? null;

        if ( '' === $name ) {
            return self::errorResponse( 'invalid_fee_name', 'Fee name is required', 400 );
        }

        if ( null === $amount || ! is_numeric( $amount ) ) {
            return self::errorResponse( 'invalid_fee_amount', 'Fee amount must be numeric', 400 );
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $fee = new \WC_Order_Item_Fee();
        $fee->set_name( $name );
        $fee->set_amount( (string) $amount );
        $fee->set_total( (string) $amount );
        $fee->set_tax_status( $input['tax_status'] ?? 'taxable' );

        if ( ! empty( $input['tax_class'] ) ) {
            $fee->set_tax_class( (string) $input['tax_class'] );
        }

        $order->add_item( $fee );
        $fee->save();

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Fee added via Site Manager: %s (%s)', $name, $amount ) );

        return [
            'success'  => true,
            'message'  => 'Fee added',
            'order_id' => $order->get_id(),
            'item_id'  => $fee->get_id(),
            'order'    => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    public static function remove_fee( array $input ): array|\WP_Error {
        return self::remove_item_by_type( $input, 'fee', 'Fee removed' );
    }

    public static function set_shipping( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $method_id = trim( (string) ( $input['method_id'] ?? '' ) );
        $total     = $input['total'] ?? null;

        if ( '' === $method_id ) {
            return self::errorResponse( 'invalid_method_id', 'method_id is required', 400 );
        }

        if ( null === $total || ! is_numeric( $total ) ) {
            return self::errorResponse( 'invalid_shipping_total', 'Shipping total must be numeric', 400 );
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        if ( $input['replace_existing'] ?? true ) {
            foreach ( $order->get_shipping_methods() as $existing_id => $existing ) {
                $order->remove_item( (int) $existing_id );
            }
        }

        $shipping = new \WC_Order_Item_Shipping();
        $shipping->set_method_id( $method_id );
        $shipping->set_method_title( (string) ( $input['method_title'] ?? $method_id ) );
        $shipping->set_total( (string) $total );

        $order->add_item( $shipping );
        $shipping->save();

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Shipping set via Site Manager: %s (%s)', $method_id, $total ) );

        return [
            'success'        => true,
            'message'        => 'Shipping set',
            'order_id'       => $order->get_id(),
            'item_id'        => $shipping->get_id(),
            'shipping_total' => (string) $order->get_shipping_total(),
            'order'          => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    public static function remove_shipping( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $item_id = (int) ( $input['item_id'] ?? 0 );

        if ( $item_id > 0 ) {
            $item = $order->get_item( $item_id );
            if ( ! $item || ! $item instanceof \WC_Order_Item_Shipping ) {
                return self::errorResponse( 'shipping_not_found', 'Shipping line not found on this order', 404 );
            }
            $order->remove_item( $item_id );
            $removed = 1;
        } else {
            $removed = 0;
            foreach ( $order->get_shipping_methods() as $existing_id => $existing ) {
                $order->remove_item( (int) $existing_id );
                $removed++;
            }
        }

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Shipping removed via Site Manager (%d line(s))', $removed ) );

        return [
            'success'  => true,
            'message'  => 'Shipping removed',
            'order_id' => $order->get_id(),
            'removed'  => $removed,
            'order'    => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    public static function recalculate( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $old_total = (string) $order->get_total();
        $order->calculate_totals();
        $order->save();

        return [
            'success'   => true,
            'message'   => 'Order totals recalculated',
            'order_id'  => $order->get_id(),
            'old_total' => $old_total,
            'new_total' => (string) $order->get_total(),
            'order'     => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    /**
     * Shared helper: remove an item that must be of a specific type (e.g. 'fee').
     */
    private static function remove_item_by_type( array $input, string $expected_type, string $success_message ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $item_id = (int) ( $input['item_id'] ?? 0 );
        if ( $item_id <= 0 ) {
            return self::errorResponse( 'invalid_item_id', 'item_id is required', 400 );
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $item = $order->get_item( $item_id );
        if ( ! $item || $item->get_type() !== $expected_type ) {
            return self::errorResponse(
                'item_not_found',
                sprintf( '%s item not found on this order', ucfirst( $expected_type ) ),
                404
            );
        }

        $name = $item->get_name();
        $order->remove_item( $item_id );

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( '%s removed via Site Manager: %s', ucfirst( $expected_type ), $name ) );

        return [
            'success'         => true,
            'message'         => $success_message,
            'order_id'        => $order->get_id(),
            'removed_item_id' => $item_id,
            'order'           => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }
}
