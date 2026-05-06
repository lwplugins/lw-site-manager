<?php
/**
 * WooCommerce report abilities (sales, top sellers, totals, revenue, low stock).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ReportManager;

final class ReportAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-sales-report',
            [
                'label'               => __( 'Sales Report', 'lw-site-manager' ),
                'description'         => __( 'Get WooCommerce sales summary', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'period'   => [
                            'type'    => 'string',
                            'enum'    => [ 'day', 'week', 'month', 'year', 'last_7_days', 'last_30_days' ],
                            'default' => 'month',
                        ],
                        'date_min' => [ 'type' => 'string', 'description' => 'Start date (Y-m-d)' ],
                        'date_max' => [ 'type' => 'string', 'description' => 'End date (Y-m-d)' ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'         => [ 'type' => 'boolean' ],
                        'period'          => [ 'type' => 'string' ],
                        'date_min'        => [ 'type' => 'string' ],
                        'date_max'        => [ 'type' => 'string' ],
                        'total_sales'     => [ 'type' => 'number' ],
                        'net_sales'       => [ 'type' => 'number' ],
                        'total_orders'    => [ 'type' => 'integer' ],
                        'total_items'     => [ 'type' => 'integer' ],
                        'total_shipping'  => [ 'type' => 'number' ],
                        'total_tax'       => [ 'type' => 'number' ],
                        'total_refunds'   => [ 'type' => 'number' ],
                        'total_discounts' => [ 'type' => 'number' ],
                        'average_order'   => [ 'type' => 'number' ],
                        'currency'        => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'sales_report' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-top-sellers',
            [
                'label'               => __( 'Top Selling Products', 'lw-site-manager' ),
                'description'         => __( 'Get top selling WooCommerce products', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'limit'  => [
                            'type'    => 'integer',
                            'default' => 10,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'period' => [
                            'type'    => 'string',
                            'enum'    => [ 'week', 'month', 'year' ],
                            'default' => 'month',
                        ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'period'    => [ 'type' => 'string' ],
                        'date_from' => [ 'type' => 'string' ],
                        'products'  => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'             => [ 'type' => 'integer' ],
                                    'name'           => [ 'type' => 'string' ],
                                    'sku'            => [ 'type' => 'string' ],
                                    'quantity_sold'  => [ 'type' => 'integer' ],
                                    'total_sales'    => [ 'type' => 'number' ],
                                    'price'          => [ 'type' => 'string' ],
                                    'stock_status'   => [ 'type' => 'string' ],
                                    'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                                    'image'          => [ 'type' => [ 'string', 'null' ] ],
                                ],
                            ],
                        ],
                        'total'     => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'top_sellers' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-orders-totals',
            [
                'label'               => __( 'Orders Totals', 'lw-site-manager' ),
                'description'         => __( 'Get order counts by status', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'     => [ 'type' => 'boolean' ],
                        'totals'      => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'status' => [ 'type' => 'string' ],
                                    'label'  => [ 'type' => 'string' ],
                                    'count'  => [ 'type' => 'integer' ],
                                ],
                            ],
                        ],
                        'grand_total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'orders_totals' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-revenue-stats',
            [
                'label'               => __( 'Revenue Stats', 'lw-site-manager' ),
                'description'         => __( 'Get revenue statistics with period comparison', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'period'  => [
                            'type'    => 'string',
                            'enum'    => [ 'today', 'last_7_days', 'last_30_days', 'this_month', 'this_year' ],
                            'default' => 'last_7_days',
                        ],
                        'compare' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Compare with previous period',
                        ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'period'   => [ 'type' => 'string' ],
                        'current'  => [
                            'type'       => 'object',
                            'properties' => [
                                'date_start' => [ 'type' => 'string' ],
                                'date_end'   => [ 'type' => 'string' ],
                                'revenue'    => [ 'type' => 'number' ],
                                'orders'     => [ 'type' => 'integer' ],
                                'items_sold' => [ 'type' => 'integer' ],
                            ],
                        ],
                        'previous' => [
                            'type'       => 'object',
                            'properties' => [
                                'date_start' => [ 'type' => 'string' ],
                                'date_end'   => [ 'type' => 'string' ],
                                'revenue'    => [ 'type' => 'number' ],
                                'orders'     => [ 'type' => 'integer' ],
                                'items_sold' => [ 'type' => 'integer' ],
                            ],
                        ],
                        'changes'  => [
                            'type'       => 'object',
                            'properties' => [
                                'revenue'    => [ 'type' => 'object' ],
                                'orders'     => [ 'type' => 'object' ],
                                'items_sold' => [ 'type' => 'object' ],
                            ],
                        ],
                        'currency' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'revenue_stats' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-low-stock-products',
            [
                'label'               => __( 'Low Stock Products', 'lw-site-manager' ),
                'description'         => __( 'Get products with low or no stock', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'threshold'            => [
                            'type'        => 'integer',
                            'description' => 'Low stock threshold (default: WooCommerce setting)',
                        ],
                        'limit'                => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'include_out_of_stock' => [ 'type' => 'boolean', 'default' => true ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'threshold' => [ 'type' => 'integer' ],
                        'products'  => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'             => [ 'type' => 'integer' ],
                                    'name'           => [ 'type' => 'string' ],
                                    'sku'            => [ 'type' => 'string' ],
                                    'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                                    'stock_status'   => [ 'type' => 'string' ],
                                    'price'          => [ 'type' => 'string' ],
                                    'type'           => [ 'type' => 'string' ],
                                    'permalink'      => [ 'type' => 'string' ],
                                    'image'          => [ 'type' => [ 'string', 'null' ] ],
                                ],
                            ],
                        ],
                        'total'     => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'low_stock_products' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-products-totals',
            [
                'label'               => __( 'Products Totals', 'lw-site-manager' ),
                'description'         => __( 'Get product counts by status and stock', 'lw-site-manager' ),
                'category'            => 'wc-reports',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'      => [ 'type' => 'boolean' ],
                        'published'    => [ 'type' => 'integer' ],
                        'draft'        => [ 'type' => 'integer' ],
                        'pending'      => [ 'type' => 'integer' ],
                        'trash'        => [ 'type' => 'integer' ],
                        'in_stock'     => [ 'type' => 'integer' ],
                        'out_of_stock' => [ 'type' => 'integer' ],
                        'on_backorder' => [ 'type' => 'integer' ],
                        'low_stock'    => [ 'type' => 'integer' ],
                        'total'        => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'products_totals' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );
    }
}
