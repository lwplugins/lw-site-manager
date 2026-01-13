<?php
/**
 * WooCommerce Report Manager Service
 */

declare(strict_types=1);

namespace WPSiteManager\Services\WooCommerce;

use WPSiteManager\Services\AbstractService;

class ReportManager extends AbstractService {

    /**
     * Get sales report
     */
    public static function sales_report( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $period = $input['period'] ?? 'month';
        $date_min = $input['date_min'] ?? null;
        $date_max = $input['date_max'] ?? null;

        // Set date range based on period
        if ( ! $date_min || ! $date_max ) {
            switch ( $period ) {
                case 'day':
                    $date_min = gmdate( 'Y-m-d', strtotime( 'today' ) );
                    $date_max = gmdate( 'Y-m-d', strtotime( 'today' ) );
                    break;
                case 'week':
                    $date_min = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
                    $date_max = gmdate( 'Y-m-d', strtotime( 'sunday this week' ) );
                    break;
                case 'month':
                    $date_min = gmdate( 'Y-m-01' );
                    $date_max = gmdate( 'Y-m-t' );
                    break;
                case 'year':
                    $date_min = gmdate( 'Y-01-01' );
                    $date_max = gmdate( 'Y-12-31' );
                    break;
                case 'last_7_days':
                    $date_min = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
                    $date_max = gmdate( 'Y-m-d' );
                    break;
                case 'last_30_days':
                    $date_min = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
                    $date_max = gmdate( 'Y-m-d' );
                    break;
            }
        }

        // Build query args
        $args = [
            'status'       => [ 'completed', 'processing', 'on-hold' ],
            'date_created' => $date_min . '...' . $date_max,
            'limit'        => -1,
            'return'       => 'ids',
        ];

        $order_ids = wc_get_orders( $args );

        // Calculate totals
        $total_sales = 0;
        $total_orders = count( $order_ids );
        $total_items = 0;
        $total_shipping = 0;
        $total_tax = 0;
        $total_refunds = 0;
        $total_discounts = 0;

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $total_sales += (float) $order->get_total();
                $total_items += $order->get_item_count();
                $total_shipping += (float) $order->get_shipping_total();
                $total_tax += (float) $order->get_total_tax();
                $total_refunds += (float) $order->get_total_refunded();
                $total_discounts += (float) $order->get_discount_total();
            }
        }

        $net_sales = $total_sales - $total_refunds;
        $average_order = $total_orders > 0 ? $net_sales / $total_orders : 0;

        return [
            'success'          => true,
            'period'           => $period,
            'date_min'         => $date_min,
            'date_max'         => $date_max,
            'total_sales'      => round( $total_sales, 2 ),
            'net_sales'        => round( $net_sales, 2 ),
            'total_orders'     => $total_orders,
            'total_items'      => $total_items,
            'total_shipping'   => round( $total_shipping, 2 ),
            'total_tax'        => round( $total_tax, 2 ),
            'total_refunds'    => round( $total_refunds, 2 ),
            'total_discounts'  => round( $total_discounts, 2 ),
            'average_order'    => round( $average_order, 2 ),
            'currency'         => get_woocommerce_currency(),
        ];
    }

    /**
     * Get top selling products
     */
    public static function top_sellers( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        global $wpdb;

        $limit = min( (int) ( $input['limit'] ?? 10 ), 100 );
        $period = $input['period'] ?? 'month';

        // Set date range
        switch ( $period ) {
            case 'week':
                $date_from = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
                break;
            case 'month':
                $date_from = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
                break;
            case 'year':
                $date_from = gmdate( 'Y-m-d', strtotime( '-1 year' ) );
                break;
            default:
                $date_from = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        }

        // Query for top selling products
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    order_item_meta.meta_value as product_id,
                    SUM(order_item_meta_qty.meta_value) as quantity,
                    SUM(order_item_meta_total.meta_value) as total
                FROM {$wpdb->prefix}woocommerce_order_items as order_items
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
                    ON order_items.order_item_id = order_item_meta.order_item_id
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_qty
                    ON order_items.order_item_id = order_item_meta_qty.order_item_id
                LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_total
                    ON order_items.order_item_id = order_item_meta_total.order_item_id
                LEFT JOIN {$wpdb->posts} as posts
                    ON order_items.order_id = posts.ID
                WHERE posts.post_type = 'shop_order'
                    AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                    AND posts.post_date >= %s
                    AND order_item_meta.meta_key = '_product_id'
                    AND order_item_meta_qty.meta_key = '_qty'
                    AND order_item_meta_total.meta_key = '_line_total'
                    AND order_items.order_item_type = 'line_item'
                GROUP BY order_item_meta.meta_value
                ORDER BY quantity DESC
                LIMIT %d",
                $date_from,
                $limit
            )
        );

        $products = [];
        foreach ( $results as $row ) {
            $product = wc_get_product( (int) $row->product_id );
            if ( $product ) {
                $products[] = [
                    'id'            => (int) $row->product_id,
                    'name'          => $product->get_name(),
                    'sku'           => $product->get_sku(),
                    'quantity_sold' => (int) $row->quantity,
                    'total_sales'   => round( (float) $row->total, 2 ),
                    'price'         => $product->get_price(),
                    'stock_status'  => $product->get_stock_status(),
                    'stock_quantity' => $product->get_stock_quantity(),
                    'image'         => wp_get_attachment_url( $product->get_image_id() ),
                ];
            }
        }

        return [
            'success'   => true,
            'period'    => $period,
            'date_from' => $date_from,
            'products'  => $products,
            'total'     => count( $products ),
        ];
    }

    /**
     * Get orders totals by status
     */
    public static function orders_totals( array $input ): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [ 'success' => false, 'totals' => [] ];
        }

        $statuses = wc_get_order_statuses();
        $totals = [];

        foreach ( $statuses as $status_slug => $status_label ) {
            $status = str_replace( 'wc-', '', $status_slug );
            $count = wc_orders_count( $status );

            $totals[] = [
                'status' => $status,
                'label'  => $status_label,
                'count'  => (int) $count,
            ];
        }

        // Calculate grand total
        $grand_total = array_sum( array_column( $totals, 'count' ) );

        return [
            'success'     => true,
            'totals'      => $totals,
            'grand_total' => $grand_total,
        ];
    }

    /**
     * Get customers totals
     */
    public static function customers_totals( array $input ): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [ 'success' => false, 'totals' => [] ];
        }

        global $wpdb;

        // Total customers (users who have placed orders)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total_customers = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT meta_value)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_customer_user'
            AND meta_value > 0"
        );

        // Guest orders
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $guest_orders = (int) $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_customer_user'
            AND meta_value = 0"
        );

        // New customers this month
        $this_month = gmdate( 'Y-m-01' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $new_this_month = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.meta_value)
                FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_customer_user'
                AND pm.meta_value > 0
                AND p.post_type = 'shop_order'
                AND p.post_date >= %s",
                $this_month
            )
        );

        return [
            'success'         => true,
            'total_customers' => $total_customers,
            'guest_orders'    => $guest_orders,
            'new_this_month'  => $new_this_month,
        ];
    }

    /**
     * Get products totals
     */
    public static function products_totals( array $input ): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [ 'success' => false, 'totals' => [] ];
        }

        $counts = wp_count_posts( 'product' );

        // Count by stock status
        $in_stock = 0;
        $out_of_stock = 0;
        $on_backorder = 0;
        $low_stock = 0;

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ];

        $product_ids = get_posts( $args );
        $low_stock_threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $stock_status = $product->get_stock_status();

            switch ( $stock_status ) {
                case 'instock':
                    $in_stock++;
                    break;
                case 'outofstock':
                    $out_of_stock++;
                    break;
                case 'onbackorder':
                    $on_backorder++;
                    break;
            }

            // Check low stock
            if ( $product->get_manage_stock() ) {
                $stock_qty = $product->get_stock_quantity();
                if ( $stock_qty !== null && $stock_qty <= $low_stock_threshold && $stock_qty > 0 ) {
                    $low_stock++;
                }
            }
        }

        return [
            'success'       => true,
            'published'     => (int) $counts->publish,
            'draft'         => (int) $counts->draft,
            'pending'       => (int) $counts->pending,
            'trash'         => (int) $counts->trash,
            'in_stock'      => $in_stock,
            'out_of_stock'  => $out_of_stock,
            'on_backorder'  => $on_backorder,
            'low_stock'     => $low_stock,
            'total'         => (int) $counts->publish + (int) $counts->draft + (int) $counts->pending,
        ];
    }

    /**
     * Get coupons totals
     */
    public static function coupons_totals( array $input ): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [ 'success' => false, 'totals' => [] ];
        }

        $counts = wp_count_posts( 'shop_coupon' );

        return [
            'success'   => true,
            'published' => (int) $counts->publish,
            'draft'     => (int) $counts->draft,
            'pending'   => (int) $counts->pending,
            'trash'     => (int) $counts->trash,
            'total'     => (int) $counts->publish + (int) $counts->draft + (int) $counts->pending,
        ];
    }

    /**
     * Get revenue stats
     */
    public static function revenue_stats( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $period = $input['period'] ?? 'last_7_days';
        $compare = (bool) ( $input['compare'] ?? false );

        // Current period dates
        switch ( $period ) {
            case 'today':
                $current_start = gmdate( 'Y-m-d' );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
                $previous_end = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
                break;
            case 'last_7_days':
                $current_start = gmdate( 'Y-m-d', strtotime( '-6 days' ) );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-m-d', strtotime( '-13 days' ) );
                $previous_end = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
                break;
            case 'last_30_days':
                $current_start = gmdate( 'Y-m-d', strtotime( '-29 days' ) );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-m-d', strtotime( '-59 days' ) );
                $previous_end = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
                break;
            case 'this_month':
                $current_start = gmdate( 'Y-m-01' );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-m-01', strtotime( '-1 month' ) );
                $previous_end = gmdate( 'Y-m-t', strtotime( '-1 month' ) );
                break;
            case 'this_year':
                $current_start = gmdate( 'Y-01-01' );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-01-01', strtotime( '-1 year' ) );
                $previous_end = gmdate( 'Y-12-31', strtotime( '-1 year' ) );
                break;
            default:
                $current_start = gmdate( 'Y-m-d', strtotime( '-6 days' ) );
                $current_end = gmdate( 'Y-m-d' );
                $previous_start = gmdate( 'Y-m-d', strtotime( '-13 days' ) );
                $previous_end = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
        }

        // Get current period stats
        $current_stats = self::get_period_stats( $current_start, $current_end );

        $result = [
            'success'       => true,
            'period'        => $period,
            'current'       => [
                'date_start' => $current_start,
                'date_end'   => $current_end,
                'revenue'    => $current_stats['revenue'],
                'orders'     => $current_stats['orders'],
                'items_sold' => $current_stats['items_sold'],
            ],
            'currency'      => get_woocommerce_currency(),
        ];

        // Compare with previous period
        if ( $compare ) {
            $previous_stats = self::get_period_stats( $previous_start, $previous_end );

            $result['previous'] = [
                'date_start' => $previous_start,
                'date_end'   => $previous_end,
                'revenue'    => $previous_stats['revenue'],
                'orders'     => $previous_stats['orders'],
                'items_sold' => $previous_stats['items_sold'],
            ];

            // Calculate changes
            $result['changes'] = [
                'revenue'    => self::calculate_change( $previous_stats['revenue'], $current_stats['revenue'] ),
                'orders'     => self::calculate_change( $previous_stats['orders'], $current_stats['orders'] ),
                'items_sold' => self::calculate_change( $previous_stats['items_sold'], $current_stats['items_sold'] ),
            ];
        }

        return $result;
    }

    /**
     * Get low stock products
     */
    public static function low_stock_products( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $threshold = (int) ( $input['threshold'] ?? get_option( 'woocommerce_notify_low_stock_amount', 2 ) );
        $limit = min( (int) ( $input['limit'] ?? 20 ), 100 );
        $include_out_of_stock = (bool) ( $input['include_out_of_stock'] ?? true );

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_manage_stock',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
            ],
        ];

        $query = new \WP_Query( $args );
        $low_stock_products = [];

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                continue;
            }

            $stock_qty = $product->get_stock_quantity();
            $stock_status = $product->get_stock_status();

            // Check if low stock or out of stock
            $is_low = $stock_qty !== null && $stock_qty <= $threshold && $stock_qty > 0;
            $is_out = $stock_status === 'outofstock';

            if ( $is_low || ( $include_out_of_stock && $is_out ) ) {
                $low_stock_products[] = [
                    'id'             => $product->get_id(),
                    'name'           => $product->get_name(),
                    'sku'            => $product->get_sku(),
                    'stock_quantity' => $stock_qty,
                    'stock_status'   => $stock_status,
                    'price'          => $product->get_price(),
                    'type'           => $product->get_type(),
                    'permalink'      => $product->get_permalink(),
                    'image'          => wp_get_attachment_url( $product->get_image_id() ),
                ];
            }
        }

        // Sort by stock quantity (lowest first)
        usort( $low_stock_products, function ( $a, $b ) {
            $a_qty = $a['stock_quantity'] ?? PHP_INT_MAX;
            $b_qty = $b['stock_quantity'] ?? PHP_INT_MAX;
            return $a_qty <=> $b_qty;
        } );

        // Apply limit
        $low_stock_products = array_slice( $low_stock_products, 0, $limit );

        return [
            'success'   => true,
            'threshold' => $threshold,
            'products'  => $low_stock_products,
            'total'     => count( $low_stock_products ),
        ];
    }

    /**
     * Get stats for a specific period
     */
    private static function get_period_stats( string $date_start, string $date_end ): array {
        $args = [
            'status'       => [ 'completed', 'processing', 'on-hold' ],
            'date_created' => $date_start . '...' . $date_end,
            'limit'        => -1,
            'return'       => 'ids',
        ];

        $order_ids = wc_get_orders( $args );

        $revenue = 0;
        $items_sold = 0;

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $revenue += (float) $order->get_total() - (float) $order->get_total_refunded();
                $items_sold += $order->get_item_count();
            }
        }

        return [
            'revenue'    => round( $revenue, 2 ),
            'orders'     => count( $order_ids ),
            'items_sold' => $items_sold,
        ];
    }

    /**
     * Calculate percentage change
     */
    private static function calculate_change( float $previous, float $current ): array {
        if ( $previous == 0 ) {
            $percentage = $current > 0 ? 100 : 0;
        } else {
            $percentage = ( ( $current - $previous ) / $previous ) * 100;
        }

        return [
            'absolute'   => round( $current - $previous, 2 ),
            'percentage' => round( $percentage, 2 ),
            'trend'      => $percentage > 0 ? 'up' : ( $percentage < 0 ? 'down' : 'stable' ),
        ];
    }
}
