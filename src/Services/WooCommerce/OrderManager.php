<?php
/**
 * WooCommerce Order Manager Service
 */

declare(strict_types=1);

namespace WPSiteManager\Services\WooCommerce;

use WPSiteManager\Services\AbstractService;

class OrderManager extends AbstractService {

    /**
     * List orders with filtering and pagination
     */
    public static function list_orders( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $args = [
            'limit'    => min( (int) ( $input['limit'] ?? 20 ), 100 ),
            'offset'   => (int) ( $input['offset'] ?? 0 ),
            'orderby'  => $input['orderby'] ?? 'date',
            'order'    => $input['order'] ?? 'DESC',
            'paginate' => true,
        ];

        // Status filter
        if ( ! empty( $input['status'] ) ) {
            $args['status'] = $input['status'];
        }

        // Customer filter
        if ( ! empty( $input['customer'] ) ) {
            $args['customer_id'] = (int) $input['customer'];
        }

        // Date filters
        if ( ! empty( $input['date_after'] ) ) {
            $args['date_created'] = '>=' . strtotime( $input['date_after'] );
        }

        if ( ! empty( $input['date_before'] ) ) {
            $args['date_created'] = '<=' . strtotime( $input['date_before'] );
        }

        // Product filter
        if ( ! empty( $input['product'] ) ) {
            $args['product_id'] = (int) $input['product'];
        }

        $results = wc_get_orders( $args );

        $orders = [];
        foreach ( $results->orders as $order ) {
            $orders[] = self::format_order( $order );
        }

        return [
            'orders'      => $orders,
            'total'       => $results->total,
            'total_pages' => $results->max_num_pages,
            'limit'       => $args['limit'],
            'offset'      => $args['offset'],
            'has_more'    => ( $args['offset'] + count( $orders ) ) < $results->total,
        ];
    }

    /**
     * Get single order details
     */
    public static function get_order( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        return [
            'success' => true,
            'order'   => self::format_order( $order, true ),
        ];
    }

    /**
     * Create a new order
     */
    public static function create_order( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        try {
            $order = wc_create_order();

            // Customer
            if ( ! empty( $input['customer_id'] ) ) {
                $order->set_customer_id( (int) $input['customer_id'] );
            }

            // Billing address
            if ( ! empty( $input['billing'] ) ) {
                $order->set_billing_first_name( $input['billing']['first_name'] ?? '' );
                $order->set_billing_last_name( $input['billing']['last_name'] ?? '' );
                $order->set_billing_company( $input['billing']['company'] ?? '' );
                $order->set_billing_address_1( $input['billing']['address_1'] ?? '' );
                $order->set_billing_address_2( $input['billing']['address_2'] ?? '' );
                $order->set_billing_city( $input['billing']['city'] ?? '' );
                $order->set_billing_state( $input['billing']['state'] ?? '' );
                $order->set_billing_postcode( $input['billing']['postcode'] ?? '' );
                $order->set_billing_country( $input['billing']['country'] ?? '' );
                $order->set_billing_email( $input['billing']['email'] ?? '' );
                $order->set_billing_phone( $input['billing']['phone'] ?? '' );
            }

            // Shipping address
            if ( ! empty( $input['shipping'] ) ) {
                $order->set_shipping_first_name( $input['shipping']['first_name'] ?? '' );
                $order->set_shipping_last_name( $input['shipping']['last_name'] ?? '' );
                $order->set_shipping_company( $input['shipping']['company'] ?? '' );
                $order->set_shipping_address_1( $input['shipping']['address_1'] ?? '' );
                $order->set_shipping_address_2( $input['shipping']['address_2'] ?? '' );
                $order->set_shipping_city( $input['shipping']['city'] ?? '' );
                $order->set_shipping_state( $input['shipping']['state'] ?? '' );
                $order->set_shipping_postcode( $input['shipping']['postcode'] ?? '' );
                $order->set_shipping_country( $input['shipping']['country'] ?? '' );
            }

            // Add line items
            if ( ! empty( $input['line_items'] ) ) {
                foreach ( $input['line_items'] as $item ) {
                    $product = wc_get_product( (int) $item['product_id'] );
                    if ( $product ) {
                        $order->add_product(
                            $product,
                            (int) ( $item['quantity'] ?? 1 ),
                            [
                                'subtotal' => $item['subtotal'] ?? '',
                                'total'    => $item['total'] ?? '',
                            ]
                        );
                    }
                }
            }

            // Payment method
            if ( ! empty( $input['payment_method'] ) ) {
                $order->set_payment_method( $input['payment_method'] );
            }

            if ( ! empty( $input['payment_method_title'] ) ) {
                $order->set_payment_method_title( $input['payment_method_title'] );
            }

            // Currency
            if ( ! empty( $input['currency'] ) ) {
                $order->set_currency( $input['currency'] );
            }

            // Status
            $status = $input['status'] ?? 'pending';
            $order->set_status( $status );

            // Customer note
            if ( ! empty( $input['customer_note'] ) ) {
                $order->set_customer_note( $input['customer_note'] );
            }

            // Calculate totals
            $order->calculate_totals();

            // Save
            $order_id = $order->save();

            // Add order note if provided
            if ( ! empty( $input['note'] ) ) {
                $order->add_order_note( $input['note'], false, true );
            }

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'id'      => $order_id,
                'order'   => self::format_order( wc_get_order( $order_id ), true ),
            ];

        } catch ( \Exception $e ) {
            return self::errorResponse( 'order_create_error', $e->getMessage(), 500 );
        }
    }

    /**
     * Update an existing order
     */
    public static function update_order( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        try {
            // Update billing
            if ( ! empty( $input['billing'] ) ) {
                if ( isset( $input['billing']['first_name'] ) ) {
                    $order->set_billing_first_name( $input['billing']['first_name'] );
                }
                if ( isset( $input['billing']['last_name'] ) ) {
                    $order->set_billing_last_name( $input['billing']['last_name'] );
                }
                if ( isset( $input['billing']['email'] ) ) {
                    $order->set_billing_email( $input['billing']['email'] );
                }
                if ( isset( $input['billing']['phone'] ) ) {
                    $order->set_billing_phone( $input['billing']['phone'] );
                }
                if ( isset( $input['billing']['address_1'] ) ) {
                    $order->set_billing_address_1( $input['billing']['address_1'] );
                }
                if ( isset( $input['billing']['city'] ) ) {
                    $order->set_billing_city( $input['billing']['city'] );
                }
                if ( isset( $input['billing']['postcode'] ) ) {
                    $order->set_billing_postcode( $input['billing']['postcode'] );
                }
                if ( isset( $input['billing']['country'] ) ) {
                    $order->set_billing_country( $input['billing']['country'] );
                }
            }

            // Update shipping
            if ( ! empty( $input['shipping'] ) ) {
                if ( isset( $input['shipping']['first_name'] ) ) {
                    $order->set_shipping_first_name( $input['shipping']['first_name'] );
                }
                if ( isset( $input['shipping']['last_name'] ) ) {
                    $order->set_shipping_last_name( $input['shipping']['last_name'] );
                }
                if ( isset( $input['shipping']['address_1'] ) ) {
                    $order->set_shipping_address_1( $input['shipping']['address_1'] );
                }
                if ( isset( $input['shipping']['city'] ) ) {
                    $order->set_shipping_city( $input['shipping']['city'] );
                }
                if ( isset( $input['shipping']['postcode'] ) ) {
                    $order->set_shipping_postcode( $input['shipping']['postcode'] );
                }
                if ( isset( $input['shipping']['country'] ) ) {
                    $order->set_shipping_country( $input['shipping']['country'] );
                }
            }

            // Customer note
            if ( isset( $input['customer_note'] ) ) {
                $order->set_customer_note( $input['customer_note'] );
            }

            $order->save();

            // Add note if provided
            if ( ! empty( $input['note'] ) ) {
                $order->add_order_note( $input['note'], false, true );
            }

            return [
                'success' => true,
                'message' => 'Order updated successfully',
                'order'   => self::format_order( wc_get_order( $order->get_id() ), true ),
            ];

        } catch ( \Exception $e ) {
            return self::errorResponse( 'order_update_error', $e->getMessage(), 500 );
        }
    }

    /**
     * Update order status
     */
    public static function update_order_status( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $error = self::validateRequiredField( $input, 'status', 'Status is required' );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $old_status = $order->get_status();
        $new_status = str_replace( 'wc-', '', $input['status'] );

        // Validate status
        $valid_statuses = array_keys( wc_get_order_statuses() );
        $valid_statuses = array_map( fn( $s ) => str_replace( 'wc-', '', $s ), $valid_statuses );

        if ( ! in_array( $new_status, $valid_statuses, true ) ) {
            return self::errorResponse(
                'invalid_status',
                'Invalid status. Valid statuses: ' . implode( ', ', $valid_statuses ),
                400
            );
        }

        $note = $input['note'] ?? '';
        $order->update_status( $new_status, $note );

        return [
            'success'    => true,
            'message'    => sprintf( 'Order status changed from %s to %s', $old_status, $new_status ),
            'id'         => $order->get_id(),
            'old_status' => $old_status,
            'new_status' => $new_status,
        ];
    }

    /**
     * Delete an order
     */
    public static function delete_order( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $force = (bool) ( $input['force'] ?? false );
        $order_number = $order->get_order_number();

        if ( $force ) {
            $result = $order->delete( true );
        } else {
            $result = wp_trash_post( $order->get_id() );
        }

        if ( ! $result ) {
            return self::errorResponse( 'order_delete_failed', 'Failed to delete order', 500 );
        }

        return [
            'success'      => true,
            'message'      => $force ? 'Order permanently deleted' : 'Order moved to trash',
            'id'           => (int) $input['id'],
            'order_number' => $order_number,
            'trashed'      => ! $force,
        ];
    }

    /**
     * List order notes
     */
    public static function list_order_notes( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'order_id', 'Order ID is required' );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['order_id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $type = $input['type'] ?? 'any';
        $notes = wc_get_order_notes( [
            'order_id' => $order->get_id(),
            'type'     => $type,
        ] );

        $formatted_notes = [];
        foreach ( $notes as $note ) {
            $formatted_notes[] = [
                'id'              => $note->id,
                'content'         => $note->content,
                'date_created'    => $note->date_created->format( 'Y-m-d H:i:s' ),
                'customer_note'   => $note->customer_note,
                'added_by'        => $note->added_by,
            ];
        }

        return [
            'order_id' => $order->get_id(),
            'notes'    => $formatted_notes,
            'total'    => count( $formatted_notes ),
        ];
    }

    /**
     * Add order note
     */
    public static function add_order_note( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'order_id', 'Order ID is required' );
        if ( $error ) {
            return $error;
        }

        $error = self::validateRequiredField( $input, 'note', 'Note content is required' );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['order_id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $is_customer_note = (bool) ( $input['customer_note'] ?? false );
        $note_id = $order->add_order_note( $input['note'], $is_customer_note, true );

        return [
            'success'       => true,
            'message'       => 'Note added successfully',
            'note_id'       => $note_id,
            'order_id'      => $order->get_id(),
            'customer_note' => $is_customer_note,
        ];
    }

    /**
     * List order statuses
     */
    public static function list_order_statuses( array $input ): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [ 'statuses' => [], 'total' => 0 ];
        }

        $statuses = wc_get_order_statuses();
        $formatted = [];

        foreach ( $statuses as $slug => $label ) {
            $formatted[] = [
                'slug'  => str_replace( 'wc-', '', $slug ),
                'label' => $label,
            ];
        }

        return [
            'statuses' => $formatted,
            'total'    => count( $formatted ),
        ];
    }

    /**
     * Create a refund
     */
    public static function create_refund( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'order_id', 'Order ID is required' );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['order_id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $amount = isset( $input['amount'] ) ? (float) $input['amount'] : $order->get_total();
        $reason = $input['reason'] ?? '';
        $restock = (bool) ( $input['restock_items'] ?? true );

        // Line items to refund
        $line_items = [];
        if ( ! empty( $input['line_items'] ) ) {
            foreach ( $input['line_items'] as $item ) {
                $line_items[ $item['id'] ] = [
                    'qty'          => $item['quantity'] ?? 0,
                    'refund_total' => $item['total'] ?? 0,
                ];
            }
        }

        $refund = wc_create_refund( [
            'order_id'       => $order->get_id(),
            'amount'         => $amount,
            'reason'         => $reason,
            'line_items'     => $line_items,
            'restock_items'  => $restock,
        ] );

        if ( is_wp_error( $refund ) ) {
            return $refund;
        }

        return [
            'success'   => true,
            'message'   => 'Refund created successfully',
            'refund_id' => $refund->get_id(),
            'order_id'  => $order->get_id(),
            'amount'    => $amount,
            'reason'    => $reason,
        ];
    }

    /**
     * List refunds for an order
     */
    public static function list_refunds( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'order_id', 'Order ID is required' );
        if ( $error ) {
            return $error;
        }

        $order = wc_get_order( (int) $input['order_id'] );
        if ( ! $order ) {
            return self::errorResponse( 'order_not_found', 'Order not found', 404 );
        }

        $refunds = $order->get_refunds();
        $formatted = [];

        foreach ( $refunds as $refund ) {
            $formatted[] = [
                'id'           => $refund->get_id(),
                'amount'       => $refund->get_amount(),
                'reason'       => $refund->get_reason(),
                'date_created' => $refund->get_date_created()?->format( 'Y-m-d H:i:s' ),
                'refunded_by'  => $refund->get_refunded_by(),
            ];
        }

        return [
            'order_id'     => $order->get_id(),
            'refunds'      => $formatted,
            'total'        => count( $formatted ),
            'total_amount' => $order->get_total_refunded(),
        ];
    }

    /**
     * Bulk order status update
     */
    public static function bulk_orders( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateIdArray( $input, 'ids' );
        if ( $error ) {
            return $error;
        }

        $error = self::validateRequiredField( $input, 'status', 'Status is required' );
        if ( $error ) {
            return $error;
        }

        $new_status = str_replace( 'wc-', '', $input['status'] );
        $note = $input['note'] ?? '';

        $success_ids = [];
        $failed_ids = [];

        foreach ( $input['ids'] as $id ) {
            $order = wc_get_order( (int) $id );
            if ( ! $order ) {
                $failed_ids[] = (int) $id;
                continue;
            }

            try {
                $order->update_status( $new_status, $note );
                $success_ids[] = (int) $id;
            } catch ( \Exception $e ) {
                $failed_ids[] = (int) $id;
            }
        }

        return self::bulkResponse( $success_ids, $failed_ids, 'status_update' );
    }

    /**
     * Format order data for response
     */
    private static function format_order( \WC_Order $order, bool $detailed = false ): array {
        $data = [
            'id'               => $order->get_id(),
            'order_number'     => $order->get_order_number(),
            'status'           => $order->get_status(),
            'currency'         => $order->get_currency(),
            'total'            => (string) $order->get_total(),
            'subtotal'         => (string) $order->get_subtotal(),
            'total_tax'        => (string) $order->get_total_tax(),
            'shipping_total'   => (string) $order->get_shipping_total(),
            'discount_total'   => (string) $order->get_discount_total(),
            'customer_id'      => $order->get_customer_id(),
            'date_created'     => $order->get_date_created()?->format( 'Y-m-d H:i:s' ),
            'date_modified'    => $order->get_date_modified()?->format( 'Y-m-d H:i:s' ),
            'date_completed'   => $order->get_date_completed()?->format( 'Y-m-d H:i:s' ),
            'date_paid'        => $order->get_date_paid()?->format( 'Y-m-d H:i:s' ),
            'payment_method'   => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'items_count'      => $order->get_item_count(),
        ];

        // Billing summary
        $data['billing'] = [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
        ];

        if ( $detailed ) {
            // Full billing address
            $data['billing'] = [
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'company'    => $order->get_billing_company(),
                'address_1'  => $order->get_billing_address_1(),
                'address_2'  => $order->get_billing_address_2(),
                'city'       => $order->get_billing_city(),
                'state'      => $order->get_billing_state(),
                'postcode'   => $order->get_billing_postcode(),
                'country'    => $order->get_billing_country(),
                'email'      => $order->get_billing_email(),
                'phone'      => $order->get_billing_phone(),
            ];

            // Full shipping address
            $data['shipping'] = [
                'first_name' => $order->get_shipping_first_name(),
                'last_name'  => $order->get_shipping_last_name(),
                'company'    => $order->get_shipping_company(),
                'address_1'  => $order->get_shipping_address_1(),
                'address_2'  => $order->get_shipping_address_2(),
                'city'       => $order->get_shipping_city(),
                'state'      => $order->get_shipping_state(),
                'postcode'   => $order->get_shipping_postcode(),
                'country'    => $order->get_shipping_country(),
            ];

            // Line items
            $data['line_items'] = [];
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();
                $data['line_items'][] = [
                    'id'           => $item_id,
                    'product_id'   => $item->get_product_id(),
                    'variation_id' => $item->get_variation_id(),
                    'name'         => $item->get_name(),
                    'quantity'     => $item->get_quantity(),
                    'subtotal'     => $item->get_subtotal(),
                    'total'        => $item->get_total(),
                    'tax'          => $item->get_total_tax(),
                    'sku'          => $product ? $product->get_sku() : '',
                    'image'        => $product ? wp_get_attachment_url( $product->get_image_id() ) : null,
                ];
            }

            // Shipping lines
            $data['shipping_lines'] = [];
            foreach ( $order->get_shipping_methods() as $shipping_id => $shipping ) {
                $data['shipping_lines'][] = [
                    'id'          => $shipping_id,
                    'method_id'   => $shipping->get_method_id(),
                    'method_title' => $shipping->get_method_title(),
                    'total'       => $shipping->get_total(),
                ];
            }

            // Coupons
            $data['coupon_lines'] = [];
            foreach ( $order->get_coupons() as $coupon_id => $coupon ) {
                $data['coupon_lines'][] = [
                    'id'       => $coupon_id,
                    'code'     => $coupon->get_code(),
                    'discount' => $coupon->get_discount(),
                ];
            }

            // Customer note
            $data['customer_note'] = $order->get_customer_note();

            // Refunds summary
            $data['refunds_total'] = (string) $order->get_total_refunded();
        }

        return $data;
    }
}
