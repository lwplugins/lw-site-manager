<?php
/**
 * Order line item operations: add, update, remove products on existing orders.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class OrderItemManager extends AbstractService {

    /**
     * Add a product line item to an existing order.
     */
    public static function add_order_item( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order_id   = (int) ( $input['order_id'] ?? 0 );
        $product_id = (int) ( $input['product_id'] ?? 0 );

        if ( $product_id <= 0 ) {
            return self::errorResponse( 'invalid_product_id', 'product_id is required', 400 );
        }

        $order = OrderGuard::fetchOrder( $order_id );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $variation_id = (int) ( $input['variation_id'] ?? 0 );
        $product      = wc_get_product( $variation_id > 0 ? $variation_id : $product_id );

        if ( ! $product || ! $product->exists() ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        if ( ! $product->is_purchasable() ) {
            return self::errorResponse( 'product_not_purchasable', 'Product is not purchasable', 400 );
        }

        $quantity = max( 1, (int) ( $input['quantity'] ?? 1 ) );
        $args     = [];

        if ( isset( $input['subtotal'] ) ) {
            $args['subtotal'] = (string) $input['subtotal'];
        }
        if ( isset( $input['total'] ) ) {
            $args['total'] = (string) $input['total'];
        }

        try {
            $item_id = $order->add_product( $product, $quantity, $args );
        } catch ( \Exception $e ) {
            return self::errorResponse( 'add_item_failed', $e->getMessage(), 500 );
        }

        if ( ! $item_id ) {
            return self::errorResponse( 'add_item_failed', 'Failed to add item to order', 500 );
        }

        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            $line_item = $order->get_item( $item_id );
            if ( $line_item ) {
                foreach ( $input['meta'] as $key => $value ) {
                    $line_item->add_meta_data( (string) $key, $value, true );
                }
                $line_item->save();
            }
        }

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note(
            sprintf( 'Line item added via Site Manager: %s (qty: %d)', $product->get_name(), $quantity )
        );

        return [
            'success'  => true,
            'message'  => 'Line item added',
            'order_id' => $order->get_id(),
            'item_id'  => (int) $item_id,
            'order'    => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    /**
     * Update quantity / subtotal / total on an existing line item.
     */
    public static function update_order_item( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order_id = (int) ( $input['order_id'] ?? 0 );
        $item_id  = (int) ( $input['item_id'] ?? 0 );

        if ( $item_id <= 0 ) {
            return self::errorResponse( 'invalid_item_id', 'item_id is required', 400 );
        }

        $order = OrderGuard::fetchOrder( $order_id );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $line_item = $order->get_item( $item_id );
        if ( ! $line_item || ! $line_item instanceof \WC_Order_Item_Product ) {
            return self::errorResponse( 'item_not_found', 'Line item not found on this order', 404 );
        }

        if ( isset( $input['quantity'] ) ) {
            $quantity = (int) $input['quantity'];

            if ( $quantity <= 0 ) {
                return self::errorResponse(
                    'invalid_quantity',
                    'Quantity must be at least 1; use wc-remove-order-item to delete the line',
                    400
                );
            }

            $line_item->set_quantity( $quantity );
        }

        if ( isset( $input['subtotal'] ) ) {
            $line_item->set_subtotal( (string) $input['subtotal'] );
        }

        if ( isset( $input['total'] ) ) {
            $line_item->set_total( (string) $input['total'] );
        }

        // Apply inline meta map on the line item
        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            foreach ( $input['meta'] as $meta_key => $meta_value ) {
                $line_item->update_meta_data( (string) $meta_key, $meta_value );
            }
        }

        $line_item->save();

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();

        return [
            'success'  => true,
            'message'  => 'Line item updated',
            'order_id' => $order->get_id(),
            'item_id'  => $item_id,
            'order'    => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }

    /**
     * Remove a line item from an order.
     */
    public static function remove_order_item( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order_id = (int) ( $input['order_id'] ?? 0 );
        $item_id  = (int) ( $input['item_id'] ?? 0 );

        if ( $item_id <= 0 ) {
            return self::errorResponse( 'invalid_item_id', 'item_id is required', 400 );
        }

        $order = OrderGuard::fetchOrder( $order_id );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        $line_item = $order->get_item( $item_id );
        if ( ! $line_item ) {
            return self::errorResponse( 'item_not_found', 'Line item not found on this order', 404 );
        }

        $name = $line_item->get_name();
        $order->remove_item( $item_id );

        if ( $input['recalculate'] ?? true ) {
            $order->calculate_totals();
        }

        $order->save();
        $order->add_order_note( sprintf( 'Line item removed via Site Manager: %s', $name ) );

        return [
            'success'         => true,
            'message'         => 'Line item removed',
            'order_id'        => $order->get_id(),
            'removed_item_id' => $item_id,
            'order'           => OrderManager::format_order( wc_get_order( $order->get_id() ), true ),
        ];
    }
}
