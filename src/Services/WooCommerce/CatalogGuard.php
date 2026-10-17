<?php
/**
 * Shared guards for the WooCommerce catalog, settings and shipping services.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

/**
 * Permission-first lookups and explicit confirmation for risky writes.
 *
 * A lookup that fails because the object is missing and one that fails
 * because the caller may not touch it return the same error, so the response
 * never tells a caller without access whether an ID exists.
 */
final class CatalogGuard {

    /**
     * Refuse a risky or permanent change unless the caller confirmed it.
     *
     * @param bool   $needed  Whether this request needs a confirmation.
     * @param array  $input   Ability input.
     * @param string $key     Input key that carries the confirmation.
     * @param string $message What the confirmation is for.
     * @param array  $detail  Extra data for the caller (e.g. a preview).
     */
    public static function confirm( bool $needed, array $input, string $key, string $message, array $detail = [] ): ?\WP_Error {
        if ( ! $needed || true === ( $input[ $key ] ?? false ) ) {
            return null;
        }

        return ResponseFormatter::error(
            $key . '_required',
            sprintf( '%s Resend with %s: true once that is intended.', $message, $key ),
            409,
            $detail
        );
    }

    /**
     * The one error for "missing" and "not allowed".
     */
    public static function unavailable( string $what ): \WP_Error {
        return ResponseFormatter::error(
            'not_found_or_forbidden',
            sprintf( '%s not found, or you are not allowed to change it.', $what ),
            404
        );
    }

    /**
     * Load a product (or variation) the current user may act on.
     *
     * @param int    $productId  Product or variation ID.
     * @param string $capability Meta capability checked against it (read_post, edit_post, delete_post).
     */
    public static function product( int $productId, string $capability ): \WC_Product|\WP_Error {
        if ( $productId <= 0 || ! current_user_can( $capability, $productId ) ) {
            return self::unavailable( 'Product' );
        }

        $product = wc_get_product( $productId );

        return $product instanceof \WC_Product ? $product : self::unavailable( 'Product' );
    }

    /**
     * Load a product (or variation) the current user may edit.
     */
    public static function editableProduct( int $productId ): \WC_Product|\WP_Error {
        return self::product( $productId, 'edit_post' );
    }

    /**
     * Load a product (or variation) the current user may read.
     */
    public static function readableProduct( int $productId ): \WC_Product|\WP_Error {
        return self::product( $productId, 'read_post' );
    }

    /**
     * Require manage_woocommerce (store settings, payments, shipping).
     */
    public static function storeManager(): ?\WP_Error {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return null;
        }

        return ResponseFormatter::error( 'forbidden', 'You are not allowed to manage WooCommerce settings.', 403 );
    }
}
