<?php
/**
 * Brand and shipping-class assignment on products.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Brands have no WC_Product setter, so they go through the term API like
 * WooCommerce's own product screen; the shipping class goes through
 * WC_Product::set_shipping_class_id(). Both need edit_post on the product and
 * the taxonomy's assign_terms capability.
 */
class ProductAssignmentManager extends AbstractService {

    public static function set_product_brands( array $input ): array|\WP_Error {
        $product = self::productFor( $input, 'product_brand' );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }
        if ( $product->is_type( 'variation' ) ) {
            return self::errorResponse( 'invalid_product', 'Brands belong to the parent product, not a variation.', 400 );
        }

        $ids = array_values( array_unique( array_map( 'intval', (array) ( $input['brand_ids'] ?? [] ) ) ) );
        foreach ( $ids as $id ) {
            if ( $id <= 0 || ! get_term( $id, 'product_brand' ) instanceof \WP_Term ) {
                return self::errorResponse( 'invalid_brand', sprintf( 'Brand %d does not exist.', $id ), 400 );
            }
        }

        $result = wp_set_object_terms( $product->get_id(), $ids, 'product_brand', (bool) ( $input['append'] ?? false ) );
        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'assign_failed', $result->get_error_message(), 500 );
        }
        wc_delete_product_transients( $product->get_id() );

        $terms  = wp_get_object_terms( $product->get_id(), 'product_brand' );
        $brands = [];
        foreach ( is_array( $terms ) ? $terms : [] as $term ) {
            $brands[] = [ 'id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug ];
        }

        return [
            'success'    => true,
            'message'    => 'Brands updated.',
            'product_id' => $product->get_id(),
            'brands'     => $brands,
        ];
    }

    public static function set_product_shipping_class( array $input ): array|\WP_Error {
        $product = self::productFor( $input, 'product_shipping_class' );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }

        $classId = (int) ( $input['shipping_class_id'] ?? -1 );
        if ( $classId < 0 || ( $classId > 0 && ! get_term( $classId, 'product_shipping_class' ) instanceof \WP_Term ) ) {
            return self::errorResponse( 'invalid_shipping_class', 'shipping_class_id must be an existing shipping class, or 0 to clear it.', 400 );
        }

        $product->set_shipping_class_id( $classId );
        $product->save();

        $saved = wc_get_product( $product->get_id() );

        return [
            'success'             => true,
            'message'             => 0 === $classId ? 'Shipping class cleared.' : 'Shipping class set.',
            'product_id'          => $product->get_id(),
            'shipping_class_id'   => $saved ? $saved->get_shipping_class_id() : $classId,
            'shipping_class_slug' => $saved ? $saved->get_shipping_class() : '',
        ];
    }

    /**
     * The product, if the user may edit it and assign terms of the taxonomy.
     */
    private static function productFor( array $input, string $taxonomy ): \WC_Product|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $tax = get_taxonomy( $taxonomy );
        if ( ! $tax ) {
            return self::errorResponse( 'taxonomy_unavailable', sprintf( '%s is not registered on this site.', $taxonomy ), 400 );
        }
        if ( ! current_user_can( $tax->cap->assign_terms ) ) {
            return CatalogGuard::unavailable( 'Product' );
        }

        return CatalogGuard::editableProduct( (int) ( $input['product_id'] ?? 0 ) );
    }
}
