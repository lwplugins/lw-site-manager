<?php
/**
 * WooCommerce Report Manager Service
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

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

        // SQL aggregation instead of per-order iteration (fixes timeout on large stores).
        $stats         = ReportAggregator::aggregate( $date_min, $date_max );
        $net_sales     = $stats['total_sales'] - $stats['total_refunds'];
        $average_order = $stats['total_orders'] > 0 ? $net_sales / $stats['total_orders'] : 0;

        return [
            'success'          => true,
            'period'           => $period,
            'date_min'         => $date_min,
            'date_max'         => $date_max,
            'total_sales'      => $stats['total_sales'],
            'net_sales'        => round( $net_sales, 2 ),
            'total_orders'     => $stats['total_orders'],
            'total_items'      => $stats['total_items'],
            'total_shipping'   => $stats['total_shipping'],
            'total_tax'        => $stats['total_tax'],
            'total_refunds'    => $stats['total_refunds'],
            'total_discounts'  => $stats['total_discounts'],
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

        // Query for top selling products (HPOS-compatible).
        $results = ReportAggregator::is_hpos_enabled()
            ? self::top_sellers_hpos( $wpdb, $date_from, $limit )
            : self::top_sellers_legacy( $wpdb, $date_from, $limit );

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
                    'price'         => (string) ( $product->get_price() ?? '' ),
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

        global $wpdb;

        $counts = wp_count_posts( 'product' );

        // Count by stock status using SQL instead of loading every product object.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stock_rows = $wpdb->get_results(
            "SELECT pm.meta_value AS stock_status, COUNT(*) AS cnt
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
              AND p.post_status = 'publish'
              AND pm.meta_key = '_stock_status'
            GROUP BY pm.meta_value"
        );

        $in_stock     = 0;
        $out_of_stock = 0;
        $on_backorder = 0;

        foreach ( $stock_rows as $row ) {
            match ( $row->stock_status ) {
                'instock'     => $in_stock = (int) $row->cnt,
                'outofstock'  => $out_of_stock = (int) $row->cnt,
                'onbackorder' => $on_backorder = (int) $row->cnt,
                default       => null,
            };
        }

        // Count low stock products via SQL.
        $low_stock_threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $low_stock = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$wpdb->postmeta} pm_manage
                INNER JOIN {$wpdb->posts} p ON p.ID = pm_manage.post_id
                INNER JOIN {$wpdb->postmeta} pm_stock
                    ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
                WHERE p.post_type = 'product'
                  AND p.post_status = 'publish'
                  AND pm_manage.meta_key = '_manage_stock'
                  AND pm_manage.meta_value = 'yes'
                  AND CAST(pm_stock.meta_value AS SIGNED) > 0
                  AND CAST(pm_stock.meta_value AS SIGNED) <= %d",
                $low_stock_threshold
            )
        );

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

        // Push filtering into the database query instead of loading all products.
        $meta_query = [
            'relation' => 'AND',
            [
                'key'     => '_manage_stock',
                'value'   => 'yes',
                'compare' => '=',
            ],
        ];

        if ( $include_out_of_stock ) {
            // Low stock (0 < qty <= threshold) OR out of stock.
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key'     => '_stock',
                    'value'   => [ 0, $threshold ],
                    'compare' => 'BETWEEN',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_stock_status',
                    'value'   => 'outofstock',
                    'compare' => '=',
                ],
            ];
        } else {
            // Only low stock (0 < qty <= threshold), exclude out of stock.
            $meta_query[] = [
                'key'     => '_stock',
                'value'   => [ 1, $threshold ],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ];
        }

        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            'meta_key'       => '_stock', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ];

        $query = new \WP_Query( $args );
        $low_stock_products = [];

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( ! $product ) {
                continue;
            }

            $low_stock_products[] = [
                'id'             => $product->get_id(),
                'name'           => $product->get_name(),
                'sku'            => $product->get_sku(),
                'stock_quantity' => $product->get_stock_quantity(),
                'stock_status'   => $product->get_stock_status(),
                'price'          => (string) ( $product->get_price() ?? '' ),
                'type'           => $product->get_type(),
                'permalink'      => $product->get_permalink(),
                'image'          => wp_get_attachment_url( $product->get_image_id() ),
            ];
        }

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
        $stats = ReportAggregator::aggregate( $date_start, $date_end );

        return [
            'revenue'    => round( $stats['total_sales'] - $stats['total_refunds'], 2 ),
            'orders'     => $stats['total_orders'],
            'items_sold' => $stats['total_items'],
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

    /**
     * Top sellers query for HPOS-enabled stores (wc_orders table).
     */
    private static function top_sellers_hpos( \wpdb $wpdb, string $date_from, int $limit ): array {
        $orders_table = $wpdb->prefix . 'wc_orders';
        $items_table  = $wpdb->prefix . 'woocommerce_order_items';
        $meta_table   = $wpdb->prefix . 'woocommerce_order_itemmeta';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(
                "SELECT
                    pid.meta_value as product_id,
                    SUM(qty.meta_value) as quantity,
                    SUM(total.meta_value) as total
                FROM {$items_table} oi
                INNER JOIN {$meta_table} pid
                    ON oi.order_item_id = pid.order_item_id AND pid.meta_key = '_product_id'
                INNER JOIN {$meta_table} qty
                    ON oi.order_item_id = qty.order_item_id AND qty.meta_key = '_qty'
                INNER JOIN {$meta_table} total
                    ON oi.order_item_id = total.order_item_id AND total.meta_key = '_line_total'
                INNER JOIN {$orders_table} o
                    ON oi.order_id = o.id
                WHERE o.type = 'shop_order'
                    AND o.status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
                    AND o.date_created_gmt >= %s
                    AND oi.order_item_type = 'line_item'
                GROUP BY pid.meta_value
                ORDER BY quantity DESC
                LIMIT %d",
                $date_from,
                $limit
            )
        );
    }

    /**
     * Top sellers query for legacy stores (wp_posts table).
     */
    private static function top_sellers_legacy( \wpdb $wpdb, string $date_from, int $limit ): array {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results(
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
    }
}
