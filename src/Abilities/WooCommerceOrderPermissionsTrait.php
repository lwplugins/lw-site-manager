<?php
/**
 * WooCommerce order and report permission callbacks of the PermissionManager.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities;

/**
 * Order and report capabilities as WooCommerce defines them.
 */
trait WooCommerceOrderPermissionsTrait {

    /**
     * Check if user can manage WooCommerce orders.
     *
     * Falls back to manage_woocommerce when edit_shop_orders is unavailable
     * (e.g., on stores where the WC capability mapping is overridden).
     */
    public function can_manage_orders(): bool {
        return current_user_can( 'edit_shop_orders' )
            || current_user_can( 'manage_woocommerce' );
    }

    /**
     * View WooCommerce reports: view_woocommerce_reports, as WooCommerce's
     * Reports screen and REST reports endpoints require.
     */
    public function can_view_reports(): bool {
        return current_user_can( 'view_woocommerce_reports' );
    }

    /**
     * List orders: read_private_shop_orders (REST orders 'read' context).
     */
    public function can_read_orders(): bool {
        return current_user_can( 'read_private_shop_orders' );
    }

    /**
     * Create orders: publish_shop_orders (REST orders 'create' context).
     */
    public function can_create_orders(): bool {
        return current_user_can( 'publish_shop_orders' );
    }

    /**
     * Delete orders: delete_shop_orders; the service checks delete_post on the order.
     */
    public function can_delete_orders(): bool {
        return current_user_can( 'delete_shop_orders' );
    }

    /**
     * Bulk order changes: edit_others_shop_orders (REST orders 'batch' context).
     */
    public function can_batch_orders(): bool {
        return current_user_can( 'edit_others_shop_orders' );
    }
}
