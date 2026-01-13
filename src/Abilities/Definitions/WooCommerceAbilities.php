<?php
/**
 * WooCommerce Abilities - Registers all WooCommerce management abilities
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Definitions;

use WPSiteManager\Abilities\PermissionManager;
use WPSiteManager\Services\WooCommerce\ProductManager;
use WPSiteManager\Services\WooCommerce\OrderManager;
use WPSiteManager\Services\WooCommerce\ReportManager;

class WooCommerceAbilities {

    /**
     * Register all WooCommerce abilities
     */
    public static function register( PermissionManager $permissions ): void {
        // Only register if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        self::register_product_abilities( $permissions );
        self::register_order_abilities( $permissions );
        self::register_report_abilities( $permissions );
    }

    // =========================================================================
    // Product Abilities
    // =========================================================================

    private static function register_product_abilities( PermissionManager $permissions ): void {
        // List products
        wp_register_ability(
            'site-manager/wc-list-products',
            [
                'label'       => __( 'List Products', 'wp-site-manager' ),
                'description' => __( 'List WooCommerce products with filtering options', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status' => [
                            'type'    => 'string',
                            'default' => 'any',
                            'enum'    => [ 'any', 'publish', 'draft', 'pending', 'private', 'trash' ],
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => [ 'simple', 'variable', 'grouped', 'external' ],
                        ],
                        'category' => [
                            'type'        => 'string',
                            'description' => 'Category slug or ID',
                        ],
                        'stock_status' => [
                            'type' => 'string',
                            'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
                        ],
                        'featured' => [
                            'type' => 'boolean',
                        ],
                        'on_sale' => [
                            'type' => 'boolean',
                        ],
                        'search' => [
                            'type' => 'string',
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'date',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema' => self::listOutputSchema( 'products', self::productSchema() ),
                'execute_callback'    => [ ProductManager::class, 'list_products' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get product
        wp_register_ability(
            'site-manager/wc-get-product',
            [
                'label'       => __( 'Get Product', 'wp-site-manager' ),
                'description' => __( 'Get detailed information about a WooCommerce product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Product ID',
                        ],
                        'sku' => [
                            'type'        => 'string',
                            'description' => 'Product SKU',
                        ],
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Product slug',
                        ],
                    ],
                ],
                'output_schema' => self::entityOutputSchema( 'product', self::productSchema( true ) ),
                'execute_callback'    => [ ProductManager::class, 'get_product' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Create product
        wp_register_ability(
            'site-manager/wc-create-product',
            [
                'label'       => __( 'Create Product', 'wp-site-manager' ),
                'description' => __( 'Create a new WooCommerce product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => self::productInputSchema(),
                    'required'   => [ 'name' ],
                ],
                'output_schema' => self::entityOutputSchema( 'product', self::productSchema( true ), true ),
                'execute_callback'    => [ ProductManager::class, 'create_product' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update product
        wp_register_ability(
            'site-manager/wc-update-product',
            [
                'label'       => __( 'Update Product', 'wp-site-manager' ),
                'description' => __( 'Update an existing WooCommerce product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        [ 'id' => [ 'type' => 'integer', 'description' => 'Product ID' ] ],
                        self::productInputSchema()
                    ),
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::entityOutputSchema( 'product', self::productSchema( true ) ),
                'execute_callback'    => [ ProductManager::class, 'update_product' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Delete product
        wp_register_ability(
            'site-manager/wc-delete-product',
            [
                'label'       => __( 'Delete Product', 'wp-site-manager' ),
                'description' => __( 'Delete a WooCommerce product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Product ID',
                        ],
                        'force' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Permanently delete (skip trash)',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::deleteOutputSchema(),
                'execute_callback'    => [ ProductManager::class, 'delete_product' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta' => self::destructiveMeta(),
            ]
        );

        // Duplicate product
        wp_register_ability(
            'site-manager/wc-duplicate-product',
            [
                'label'       => __( 'Duplicate Product', 'wp-site-manager' ),
                'description' => __( 'Create a copy of a WooCommerce product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Product ID to duplicate',
                        ],
                        'new_name' => [
                            'type'        => 'string',
                            'description' => 'Name for the copy',
                        ],
                        'status' => [
                            'type'    => 'string',
                            'default' => 'draft',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::entityOutputSchema( 'product', self::productSchema( true ), true ),
                'execute_callback'    => [ ProductManager::class, 'duplicate_product' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Update stock
        wp_register_ability(
            'site-manager/wc-update-stock',
            [
                'label'       => __( 'Update Stock', 'wp-site-manager' ),
                'description' => __( 'Update product stock quantity or status', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Product ID',
                        ],
                        'quantity' => [
                            'type'        => 'integer',
                            'description' => 'Set stock quantity',
                        ],
                        'adjust' => [
                            'type'        => 'integer',
                            'description' => 'Adjust stock (positive to add, negative to subtract)',
                        ],
                        'stock_status' => [
                            'type' => 'string',
                            'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'      => [ 'type' => 'boolean' ],
                        'message'      => [ 'type' => 'string' ],
                        'id'           => [ 'type' => 'integer' ],
                        'name'         => [ 'type' => 'string' ],
                        'old_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                        'new_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                        'old_status'   => [ 'type' => 'string' ],
                        'new_status'   => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'update_stock' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // List product categories
        wp_register_ability(
            'site-manager/wc-list-product-categories',
            [
                'label'       => __( 'List Product Categories', 'wp-site-manager' ),
                'description' => __( 'List WooCommerce product categories', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'hide_empty' => [
                            'type'    => 'boolean',
                            'default' => false,
                        ],
                        'parent' => [
                            'type' => 'integer',
                        ],
                        'search' => [
                            'type' => 'string',
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 100,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'name',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'ASC',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'categories' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
                                'properties' => [
                                    'id'          => [ 'type' => 'integer' ],
                                    'name'        => [ 'type' => 'string' ],
                                    'slug'        => [ 'type' => 'string' ],
                                    'description' => [ 'type' => 'string' ],
                                    'parent'      => [ 'type' => 'integer' ],
                                    'count'       => [ 'type' => 'integer' ],
                                    'image'       => [ 'type' => [ 'string', 'null' ] ],
                                ],
                            ],
                        ],
                        'total'  => [ 'type' => 'integer' ],
                        'limit'  => [ 'type' => 'integer' ],
                        'offset' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'list_product_categories' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // List variations
        wp_register_ability(
            'site-manager/wc-list-variations',
            [
                'label'       => __( 'List Product Variations', 'wp-site-manager' ),
                'description' => __( 'List variations of a variable product', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [
                            'type'        => 'integer',
                            'description' => 'Variable product ID',
                        ],
                    ],
                    'required' => [ 'product_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id'   => [ 'type' => 'integer' ],
                        'product_name' => [ 'type' => 'string' ],
                        'variations'   => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
                                'properties' => [
                                    'id'             => [ 'type' => 'integer' ],
                                    'sku'            => [ 'type' => 'string' ],
                                    'price'          => [ 'type' => 'string' ],
                                    'regular_price'  => [ 'type' => 'string' ],
                                    'sale_price'     => [ 'type' => 'string' ],
                                    'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                                    'stock_status'   => [ 'type' => 'string' ],
                                    'attributes'     => [ 'type' => 'object', 'default' => [] ],
                                    'image'          => [ 'type' => [ 'string', 'null' ] ],
                                    'status'         => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ProductManager::class, 'list_variations' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Bulk products
        wp_register_ability(
            'site-manager/wc-bulk-products',
            [
                'label'       => __( 'Bulk Product Action', 'wp-site-manager' ),
                'description' => __( 'Perform bulk actions on multiple products', 'wp-site-manager' ),
                'category'    => 'wc-products',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'integer' ],
                        ],
                        'action' => [
                            'type' => 'string',
                            'enum' => [ 'publish', 'draft', 'trash', 'delete', 'restore' ],
                        ],
                    ],
                    'required' => [ 'ids', 'action' ],
                ],
                'output_schema' => self::bulkOutputSchema(),
                'execute_callback'    => [ ProductManager::class, 'bulk_products' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    // =========================================================================
    // Order Abilities
    // =========================================================================

    private static function register_order_abilities( PermissionManager $permissions ): void {
        // List orders
        wp_register_ability(
            'site-manager/wc-list-orders',
            [
                'label'       => __( 'List Orders', 'wp-site-manager' ),
                'description' => __( 'List WooCommerce orders with filtering options', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                        ],
                        'customer' => [
                            'type'        => 'integer',
                            'description' => 'Customer ID',
                        ],
                        'product' => [
                            'type'        => 'integer',
                            'description' => 'Filter by product ID',
                        ],
                        'date_after' => [
                            'type'        => 'string',
                            'description' => 'Orders after date (Y-m-d)',
                        ],
                        'date_before' => [
                            'type'        => 'string',
                            'description' => 'Orders before date (Y-m-d)',
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'offset' => [
                            'type'    => 'integer',
                            'default' => 0,
                        ],
                        'orderby' => [
                            'type'    => 'string',
                            'default' => 'date',
                        ],
                        'order' => [
                            'type'    => 'string',
                            'enum'    => [ 'ASC', 'DESC' ],
                            'default' => 'DESC',
                        ],
                    ],
                ],
                'output_schema' => self::listOutputSchema( 'orders', self::orderSchema() ),
                'execute_callback'    => [ OrderManager::class, 'list_orders' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Get order
        wp_register_ability(
            'site-manager/wc-get-order',
            [
                'label'       => __( 'Get Order', 'wp-site-manager' ),
                'description' => __( 'Get detailed information about a WooCommerce order', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Order ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => self::entityOutputSchema( 'order', self::orderSchema( true ) ),
                'execute_callback'    => [ OrderManager::class, 'get_order' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Update order status
        wp_register_ability(
            'site-manager/wc-update-order-status',
            [
                'label'       => __( 'Update Order Status', 'wp-site-manager' ),
                'description' => __( 'Change the status of an order', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'Order ID',
                        ],
                        'status' => [
                            'type'        => 'string',
                            'description' => 'New status (e.g., processing, completed, on-hold)',
                        ],
                        'note' => [
                            'type'        => 'string',
                            'description' => 'Optional note for the status change',
                        ],
                    ],
                    'required' => [ 'id', 'status' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'id'         => [ 'type' => 'integer' ],
                        'old_status' => [ 'type' => 'string' ],
                        'new_status' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'update_order_status' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // List order statuses
        wp_register_ability(
            'site-manager/wc-list-order-statuses',
            [
                'label'       => __( 'List Order Statuses', 'wp-site-manager' ),
                'description' => __( 'List available WooCommerce order statuses', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'statuses' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
                                'properties' => [
                                    'slug'  => [ 'type' => 'string' ],
                                    'label' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'list_order_statuses' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Create refund
        wp_register_ability(
            'site-manager/wc-create-refund',
            [
                'label'       => __( 'Create Refund', 'wp-site-manager' ),
                'description' => __( 'Create a refund for an order', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [
                            'type'        => 'integer',
                            'description' => 'Order ID',
                        ],
                        'amount' => [
                            'type'        => 'number',
                            'description' => 'Refund amount (defaults to full order total)',
                        ],
                        'reason' => [
                            'type'        => 'string',
                            'description' => 'Reason for refund',
                        ],
                        'restock_items' => [
                            'type'    => 'boolean',
                            'default' => true,
                        ],
                        'line_items' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
                                'properties' => [
                                    'id'       => [ 'type' => 'integer' ],
                                    'quantity' => [ 'type' => 'integer' ],
                                    'total'    => [ 'type' => 'number' ],
                                ],
                            ],
                        ],
                    ],
                    'required' => [ 'order_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'message'   => [ 'type' => 'string' ],
                        'refund_id' => [ 'type' => 'integer' ],
                        'order_id'  => [ 'type' => 'integer' ],
                        'amount'    => [ 'type' => 'number' ],
                        'reason'    => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'create_refund' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::destructiveMeta( false ),
            ]
        );

        // List order notes
        wp_register_ability(
            'site-manager/wc-list-order-notes',
            [
                'label'       => __( 'List Order Notes', 'wp-site-manager' ),
                'description' => __( 'List notes for an order', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [
                            'type'        => 'integer',
                            'description' => 'Order ID',
                        ],
                        'type' => [
                            'type'    => 'string',
                            'enum'    => [ 'any', 'customer', 'internal' ],
                            'default' => 'any',
                        ],
                    ],
                    'required' => [ 'order_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [ 'type' => 'integer' ],
                        'notes'    => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
                                'properties' => [
                                    'id'            => [ 'type' => 'integer' ],
                                    'content'       => [ 'type' => 'string' ],
                                    'date_created'  => [ 'type' => 'string' ],
                                    'customer_note' => [ 'type' => 'boolean' ],
                                    'added_by'      => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'list_order_notes' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Add order note
        wp_register_ability(
            'site-manager/wc-add-order-note',
            [
                'label'       => __( 'Add Order Note', 'wp-site-manager' ),
                'description' => __( 'Add a note to an order', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [
                            'type'        => 'integer',
                            'description' => 'Order ID',
                        ],
                        'note' => [
                            'type'        => 'string',
                            'description' => 'Note content',
                        ],
                        'customer_note' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Send note to customer',
                        ],
                    ],
                    'required' => [ 'order_id', 'note' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'       => [ 'type' => 'boolean' ],
                        'message'       => [ 'type' => 'string' ],
                        'note_id'       => [ 'type' => 'integer' ],
                        'order_id'      => [ 'type' => 'integer' ],
                        'customer_note' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ OrderManager::class, 'add_order_note' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );

        // Bulk orders
        wp_register_ability(
            'site-manager/wc-bulk-orders',
            [
                'label'       => __( 'Bulk Order Action', 'wp-site-manager' ),
                'description' => __( 'Update status for multiple orders', 'wp-site-manager' ),
                'category'    => 'wc-orders',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'ids' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'integer' ],
                        ],
                        'status' => [
                            'type'        => 'string',
                            'description' => 'New status',
                        ],
                        'note' => [
                            'type' => 'string',
                        ],
                    ],
                    'required' => [ 'ids', 'status' ],
                ],
                'output_schema' => self::bulkOutputSchema(),
                'execute_callback'    => [ OrderManager::class, 'bulk_orders' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta' => self::writeMeta(),
            ]
        );
    }

    // =========================================================================
    // Report Abilities
    // =========================================================================

    private static function register_report_abilities( PermissionManager $permissions ): void {
        // Sales report
        wp_register_ability(
            'site-manager/wc-sales-report',
            [
                'label'       => __( 'Sales Report', 'wp-site-manager' ),
                'description' => __( 'Get WooCommerce sales summary', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'period' => [
                            'type'    => 'string',
                            'enum'    => [ 'day', 'week', 'month', 'year', 'last_7_days', 'last_30_days' ],
                            'default' => 'month',
                        ],
                        'date_min' => [
                            'type'        => 'string',
                            'description' => 'Start date (Y-m-d)',
                        ],
                        'date_max' => [
                            'type'        => 'string',
                            'description' => 'End date (Y-m-d)',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
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
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Top sellers
        wp_register_ability(
            'site-manager/wc-top-sellers',
            [
                'label'       => __( 'Top Selling Products', 'wp-site-manager' ),
                'description' => __( 'Get top selling WooCommerce products', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'limit' => [
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
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'period'    => [ 'type' => 'string' ],
                        'date_from' => [ 'type' => 'string' ],
                        'products'  => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
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
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'top_sellers' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Orders totals
        wp_register_ability(
            'site-manager/wc-orders-totals',
            [
                'label'       => __( 'Orders Totals', 'wp-site-manager' ),
                'description' => __( 'Get order counts by status', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'     => [ 'type' => 'boolean' ],
                        'totals'      => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
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
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Revenue stats
        wp_register_ability(
            'site-manager/wc-revenue-stats',
            [
                'label'       => __( 'Revenue Stats', 'wp-site-manager' ),
                'description' => __( 'Get revenue statistics with period comparison', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'period' => [
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
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'period'   => [ 'type' => 'string' ],
                        'current'  => [
                            'type'       => 'object',
                            'default'    => [],
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
                            'default'    => [],
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
                            'default'    => [],
                            'properties' => [
                                'revenue'    => [ 'type' => 'object', 'default' => [] ],
                                'orders'     => [ 'type' => 'object', 'default' => [] ],
                                'items_sold' => [ 'type' => 'object', 'default' => [] ],
                            ],
                        ],
                        'currency' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'revenue_stats' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Low stock products
        wp_register_ability(
            'site-manager/wc-low-stock-products',
            [
                'label'       => __( 'Low Stock Products', 'wp-site-manager' ),
                'description' => __( 'Get products with low or no stock', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'threshold' => [
                            'type'        => 'integer',
                            'description' => 'Low stock threshold (default: WooCommerce setting)',
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                            'minimum' => 1,
                            'maximum' => 100,
                        ],
                        'include_out_of_stock' => [
                            'type'    => 'boolean',
                            'default' => true,
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'threshold' => [ 'type' => 'integer' ],
                        'products'  => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'default'    => [],
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
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ReportManager::class, 'low_stock_products' ],
                'permission_callback' => $permissions->callback( 'can_manage_options' ),
                'meta' => self::readOnlyMeta(),
            ]
        );

        // Products totals
        wp_register_ability(
            'site-manager/wc-products-totals',
            [
                'label'       => __( 'Products Totals', 'wp-site-manager' ),
                'description' => __( 'Get product counts by status and stock', 'wp-site-manager' ),
                'category'    => 'wc-reports',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
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
                'meta' => self::readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // Meta Helpers
    // =========================================================================

    private static function readOnlyMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ];
    }

    private static function writeMeta(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ],
        ];
    }

    private static function destructiveMeta( bool $idempotent = true ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => $idempotent,
            ],
        ];
    }

    // =========================================================================
    // Schema Helpers
    // =========================================================================

    private static function productSchema( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                'id'             => [ 'type' => 'integer' ],
                'name'           => [ 'type' => 'string' ],
                'slug'           => [ 'type' => 'string' ],
                'type'           => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'sku'            => [ 'type' => 'string' ],
                'price'          => [ 'type' => 'string' ],
                'regular_price'  => [ 'type' => 'string' ],
                'sale_price'     => [ 'type' => 'string' ],
                'on_sale'        => [ 'type' => 'boolean' ],
                'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                'stock_status'   => [ 'type' => 'string' ],
                'manage_stock'   => [ 'type' => 'boolean' ],
                'featured'       => [ 'type' => 'boolean' ],
                'virtual'        => [ 'type' => 'boolean' ],
                'downloadable'   => [ 'type' => 'boolean' ],
                'permalink'      => [ 'type' => 'string' ],
                'date_created'   => [ 'type' => 'string' ],
                'date_modified'  => [ 'type' => 'string' ],
                'image'          => [ 'type' => [ 'object', 'null' ], 'default' => null ],
                'categories'     => [ 'type' => 'array' ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['description'] = [ 'type' => 'string' ];
            $schema['properties']['short_description'] = [ 'type' => 'string' ];
            $schema['properties']['weight'] = [ 'type' => 'string' ];
            $schema['properties']['length'] = [ 'type' => 'string' ];
            $schema['properties']['width'] = [ 'type' => 'string' ];
            $schema['properties']['height'] = [ 'type' => 'string' ];
            $schema['properties']['gallery'] = [ 'type' => 'array' ];
            $schema['properties']['tags'] = [ 'type' => 'array' ];
            $schema['properties']['attributes'] = [ 'type' => 'array' ];
            $schema['properties']['total_sales'] = [ 'type' => 'integer' ];
            $schema['properties']['average_rating'] = [ 'type' => 'string' ];
            $schema['properties']['review_count'] = [ 'type' => 'integer' ];
        }

        return $schema;
    }

    private static function productInputSchema(): array {
        return [
            'name' => [
                'type'        => 'string',
                'description' => 'Product name',
            ],
            'type' => [
                'type'    => 'string',
                'enum'    => [ 'simple', 'variable', 'grouped', 'external' ],
                'default' => 'simple',
            ],
            'slug' => [
                'type' => 'string',
            ],
            'description' => [
                'type' => 'string',
            ],
            'short_description' => [
                'type' => 'string',
            ],
            'status' => [
                'type'    => 'string',
                'enum'    => [ 'draft', 'pending', 'private', 'publish' ],
                'default' => 'publish',
            ],
            'featured' => [
                'type' => 'boolean',
            ],
            'catalog_visibility' => [
                'type' => 'string',
                'enum' => [ 'visible', 'catalog', 'search', 'hidden' ],
            ],
            'regular_price' => [
                'type' => 'string',
            ],
            'sale_price' => [
                'type' => 'string',
            ],
            'sku' => [
                'type' => 'string',
            ],
            'manage_stock' => [
                'type' => 'boolean',
            ],
            'stock_quantity' => [
                'type' => 'integer',
            ],
            'stock_status' => [
                'type' => 'string',
                'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
            ],
            'backorders' => [
                'type' => 'string',
                'enum' => [ 'no', 'notify', 'yes' ],
            ],
            'weight' => [
                'type' => 'string',
            ],
            'length' => [
                'type' => 'string',
            ],
            'width' => [
                'type' => 'string',
            ],
            'height' => [
                'type' => 'string',
            ],
            'tax_status' => [
                'type' => 'string',
                'enum' => [ 'taxable', 'shipping', 'none' ],
            ],
            'tax_class' => [
                'type' => 'string',
            ],
            'virtual' => [
                'type' => 'boolean',
            ],
            'downloadable' => [
                'type' => 'boolean',
            ],
            'categories' => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'tags' => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'image_id' => [
                'type' => 'integer',
            ],
            'gallery_image_ids' => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'menu_order' => [
                'type' => 'integer',
            ],
            'meta' => [
                'type'    => 'object',
                'default' => [],
            ],
        ];
    }

    private static function orderSchema( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                'id'                   => [ 'type' => 'integer' ],
                'order_number'         => [ 'type' => 'string' ],
                'status'               => [ 'type' => 'string' ],
                'currency'             => [ 'type' => 'string' ],
                'total'                => [ 'type' => 'string' ],
                'subtotal'             => [ 'type' => 'string' ],
                'total_tax'            => [ 'type' => 'string' ],
                'shipping_total'       => [ 'type' => 'string' ],
                'discount_total'       => [ 'type' => 'string' ],
                'customer_id'          => [ 'type' => 'integer' ],
                'date_created'         => [ 'type' => 'string' ],
                'date_modified'        => [ 'type' => 'string' ],
                'payment_method'       => [ 'type' => 'string' ],
                'payment_method_title' => [ 'type' => 'string' ],
                'items_count'          => [ 'type' => 'integer' ],
                'billing'              => [ 'type' => 'object', 'default' => [] ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['shipping'] = [ 'type' => 'object', 'default' => [] ];
            $schema['properties']['line_items'] = [ 'type' => 'array' ];
            $schema['properties']['shipping_lines'] = [ 'type' => 'array' ];
            $schema['properties']['coupon_lines'] = [ 'type' => 'array' ];
            $schema['properties']['customer_note'] = [ 'type' => 'string' ];
            $schema['properties']['refunds_total'] = [ 'type' => 'string' ];
            $schema['properties']['date_completed'] = [ 'type' => [ 'string', 'null' ] ];
            $schema['properties']['date_paid'] = [ 'type' => [ 'string', 'null' ] ];
        }

        return $schema;
    }

    private static function listOutputSchema( string $key, array $itemSchema ): array {
        return [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                $key          => [ 'type' => 'array', 'items' => $itemSchema ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
                'has_more'    => [ 'type' => 'boolean' ],
            ],
        ];
    }

    private static function entityOutputSchema( string $key, array $entitySchema, bool $includeId = false ): array {
        $properties = [
            'success' => [ 'type' => 'boolean' ],
            'message' => [ 'type' => 'string' ],
            $key      => $entitySchema,
        ];

        if ( $includeId ) {
            $properties['id'] = [ 'type' => 'integer' ];
        }

        return [
            'type'       => 'object',
            'default'    => [],
            'properties' => $properties,
        ];
    }

    private static function deleteOutputSchema(): array {
        return [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'id'      => [ 'type' => 'integer' ],
                'name'    => [ 'type' => 'string' ],
                'trashed' => [ 'type' => 'boolean' ],
            ],
        ];
    }

    private static function bulkOutputSchema(): array {
        return [
            'type'       => 'object',
            'default'    => [],
            'properties' => [
                'success'     => [ 'type' => 'boolean' ],
                'action'      => [ 'type' => 'string' ],
                'processed'   => [ 'type' => 'integer' ],
                'failed'      => [ 'type' => 'integer' ],
                'total'       => [ 'type' => 'integer' ],
                'success_ids' => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                'failed_ids'  => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                'message'     => [ 'type' => 'string' ],
            ],
        ];
    }
}
