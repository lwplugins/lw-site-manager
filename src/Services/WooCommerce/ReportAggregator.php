<?php
/**
 * SQL-based report aggregation for WooCommerce orders.
 *
 * Replaces per-order iteration (limit=-1) with direct SQL aggregation
 * to prevent PHP timeout on stores with large order counts.
 * Supports both HPOS (wc_orders) and legacy (wp_posts) storage.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use Automattic\WooCommerce\Utilities\OrderUtil;

class ReportAggregator {

	private const VALID_STATUSES = "('wc-completed', 'wc-processing', 'wc-on-hold')";

	/**
	 * Aggregate sales data for a date range using SQL.
	 *
	 * @return array{total_orders: int, total_sales: float, total_tax: float, total_shipping: float, total_discounts: float, total_refunds: float, total_items: int}
	 */
	public static function aggregate( string $date_min, string $date_max ): array {
		if ( self::is_hpos_enabled() ) {
			return self::aggregate_hpos( $date_min, $date_max );
		}

		return self::aggregate_legacy( $date_min, $date_max );
	}

	/**
	 * Check if HPOS (High-Performance Order Storage) is enabled.
	 */
	public static function is_hpos_enabled(): bool {
		return class_exists( OrderUtil::class )
			&& OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Aggregate using HPOS tables (wc_orders + wc_order_operational_data).
	 */
	private static function aggregate_hpos( string $date_min, string $date_max ): array {
		global $wpdb;

		$orders_table = $wpdb->prefix . 'wc_orders';
		$op_table     = $wpdb->prefix . 'wc_order_operational_data';
		$items_table  = $wpdb->prefix . 'woocommerce_order_items';
		$meta_table   = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$statuses     = self::VALID_STATUSES;

		$date_start = $date_min . ' 00:00:00';
		$date_end   = $date_max . ' 23:59:59';

		// Query 1: Main order stats.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$main = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total_orders,
					COALESCE(SUM(o.total_amount), 0) as total_sales,
					COALESCE(SUM(o.tax_amount), 0) as total_tax,
					COALESCE(SUM(od.shipping_total_amount), 0) as total_shipping,
					COALESCE(SUM(od.discount_total_amount), 0) as total_discounts
				FROM {$orders_table} o
				LEFT JOIN {$op_table} od ON o.id = od.order_id
				WHERE o.type = 'shop_order'
					AND o.status IN {$statuses}
					AND o.date_created_gmt >= %s
					AND o.date_created_gmt <= %s",
				$date_start,
				$date_end
			)
		);

		// Query 2: Total items sold.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT COALESCE(SUM(CAST(oim.meta_value AS UNSIGNED)), 0)
				FROM {$items_table} oi
				INNER JOIN {$meta_table} oim
					ON oi.order_item_id = oim.order_item_id
				INNER JOIN {$orders_table} o
					ON oi.order_id = o.id
				WHERE oim.meta_key = '_qty'
					AND oi.order_item_type = 'line_item'
					AND o.type = 'shop_order'
					AND o.status IN {$statuses}
					AND o.date_created_gmt >= %s
					AND o.date_created_gmt <= %s",
				$date_start,
				$date_end
			)
		);

		// Query 3: Total refunds for orders in the period.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$refunds = (float) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT COALESCE(SUM(ABS(r.total_amount)), 0)
				FROM {$orders_table} r
				INNER JOIN {$orders_table} o ON r.parent_order_id = o.id
				WHERE r.type = 'shop_order_refund'
					AND o.type = 'shop_order'
					AND o.status IN {$statuses}
					AND o.date_created_gmt >= %s
					AND o.date_created_gmt <= %s",
				$date_start,
				$date_end
			)
		);

		return self::build_result( $main, $items, $refunds );
	}

	/**
	 * Aggregate using legacy tables (wp_posts + wp_postmeta).
	 */
	private static function aggregate_legacy( string $date_min, string $date_max ): array {
		global $wpdb;

		$items_table = $wpdb->prefix . 'woocommerce_order_items';
		$meta_table  = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$statuses    = self::VALID_STATUSES;

		$date_start = $date_min . ' 00:00:00';
		$date_end   = $date_max . ' 23:59:59';

		// Query 1: Main order stats from postmeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$main = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT
					COUNT(*) as total_orders,
					COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(26,2))), 0) as total_sales,
					COALESCE(SUM(CAST(pm_tax.meta_value AS DECIMAL(26,2))), 0) as total_tax,
					COALESCE(SUM(CAST(pm_ship.meta_value AS DECIMAL(26,2))), 0) as total_shipping,
					COALESCE(SUM(CAST(pm_disc.meta_value AS DECIMAL(26,2))), 0) as total_discounts
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_total
					ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
				LEFT JOIN {$wpdb->postmeta} pm_tax
					ON p.ID = pm_tax.post_id AND pm_tax.meta_key = '_order_tax'
				LEFT JOIN {$wpdb->postmeta} pm_ship
					ON p.ID = pm_ship.post_id AND pm_ship.meta_key = '_order_shipping'
				LEFT JOIN {$wpdb->postmeta} pm_disc
					ON p.ID = pm_disc.post_id AND pm_disc.meta_key = '_cart_discount'
				WHERE p.post_type = 'shop_order'
					AND p.post_status IN {$statuses}
					AND p.post_date >= %s
					AND p.post_date <= %s",
				$date_start,
				$date_end
			)
		);

		// Query 2: Total items sold.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT COALESCE(SUM(CAST(oim.meta_value AS UNSIGNED)), 0)
				FROM {$items_table} oi
				INNER JOIN {$meta_table} oim
					ON oi.order_item_id = oim.order_item_id
				INNER JOIN {$wpdb->posts} p
					ON oi.order_id = p.ID
				WHERE oim.meta_key = '_qty'
					AND oi.order_item_type = 'line_item'
					AND p.post_type = 'shop_order'
					AND p.post_status IN {$statuses}
					AND p.post_date >= %s
					AND p.post_date <= %s",
				$date_start,
				$date_end
			)
		);

		// Query 3: Total refunds.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$refunds = (float) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT COALESCE(SUM(ABS(CAST(pm.meta_value AS DECIMAL(26,2)))), 0)
				FROM {$wpdb->posts} r
				INNER JOIN {$wpdb->postmeta} pm
					ON r.ID = pm.post_id AND pm.meta_key = '_order_total'
				INNER JOIN {$wpdb->posts} p
					ON r.post_parent = p.ID
				WHERE r.post_type = 'shop_order_refund'
					AND p.post_type = 'shop_order'
					AND p.post_status IN {$statuses}
					AND p.post_date >= %s
					AND p.post_date <= %s",
				$date_start,
				$date_end
			)
		);

		return self::build_result( $main, $items, $refunds );
	}

	/**
	 * Build a normalized result array from query results.
	 */
	private static function build_result( object $main, int $items, float $refunds ): array {
		return [
			'total_orders'    => (int) $main->total_orders,
			'total_sales'     => round( (float) $main->total_sales, 2 ),
			'total_tax'       => round( (float) $main->total_tax, 2 ),
			'total_shipping'  => round( (float) $main->total_shipping, 2 ),
			'total_discounts' => round( (float) $main->total_discounts, 2 ),
			'total_refunds'   => round( $refunds, 2 ),
			'total_items'     => $items,
		];
	}
}
