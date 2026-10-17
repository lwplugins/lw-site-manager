<?php
/**
 * WooCommerce permission callbacks of the PermissionManager.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities;

/**
 * Capabilities as WooCommerce defines them (product, order and product-term
 * caps, manage_woocommerce) — never the generic post capabilities.
 */
trait WooCommercePermissionsTrait {

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
     * Check if user can manage WooCommerce products.
     *
     * Products are a WooCommerce post type with their own capability set —
     * the generic edit_posts capability (which Contributor holds) must never
     * be used to gate them.
     */
    public function can_manage_products(): bool {
        return current_user_can( 'edit_products' )
            || current_user_can( 'manage_woocommerce' );
    }

    /**
     * Check if user can manage WooCommerce product terms (categories, tags,
     * brands, shipping classes). The service re-checks the exact term
     * capability (edit_term / delete_term) against the term.
     */
    public function can_manage_product_terms(): bool {
        return current_user_can( 'manage_product_terms' )
            || current_user_can( 'edit_product_terms' )
            || current_user_can( 'manage_woocommerce' );
    }

    /**
     * Read product terms and manage global attributes: manage_product_terms,
     * which WooCommerce's Attributes screen and its REST attribute and term
     * endpoints (wc_rest_check_manager_permissions 'attributes',
     * wc_rest_check_product_term_permissions 'read') require.
     */
    public function can_read_product_terms(): bool {
        return current_user_can( 'manage_product_terms' );
    }

    /**
     * Create or edit product terms (taxonomy cap edit_terms).
     */
    public function can_edit_product_terms(): bool {
        return current_user_can( 'edit_product_terms' );
    }

    /**
     * Delete product terms (taxonomy cap delete_terms).
     */
    public function can_delete_product_terms(): bool {
        return current_user_can( 'delete_product_terms' );
    }

    /**
     * Check if user can manage WooCommerce store settings, payment gateways
     * and shipping zones — the same capability WooCommerce's settings screens
     * and REST settings endpoints require.
     */
    public function can_manage_woocommerce(): bool {
        return current_user_can( 'manage_woocommerce' );
    }
}
