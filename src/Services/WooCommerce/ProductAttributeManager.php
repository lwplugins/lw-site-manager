<?php
/**
 * Bind attributes to products (set / add / remove on a single product).
 *
 * Supports both global (pa_*) attributes and custom per-product string attributes.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class ProductAttributeManager extends AbstractService {

    public static function set_product_attributes( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $product = self::fetch_product( (int) ( $input['product_id'] ?? 0 ) );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }

        $entries = $input['attributes'] ?? null;
        if ( ! is_array( $entries ) ) {
            return self::errorResponse( 'invalid_attributes', 'attributes must be an array', 400 );
        }

        $built = [];
        foreach ( $entries as $position => $entry ) {
            $attr = self::build_attribute( $entry, (int) $position );
            if ( $attr instanceof \WP_Error ) {
                return $attr;
            }
            $built[] = $attr;
        }

        $product->set_attributes( $built );
        $product->save();

        return [
            'success'    => true,
            'message'    => 'Attributes set',
            'product_id' => $product->get_id(),
            'attributes' => self::format_attributes( $product ),
        ];
    }

    public static function add_product_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $product = self::fetch_product( (int) ( $input['product_id'] ?? 0 ) );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }

        $attr = self::build_attribute( $input, count( $product->get_attributes() ) );
        if ( $attr instanceof \WP_Error ) {
            return $attr;
        }

        $existing = $product->get_attributes();
        $key      = $attr->get_name();

        if ( isset( $existing[ $key ] ) ) {
            return self::errorResponse(
                'attribute_already_exists',
                sprintf( 'Attribute "%s" is already attached; use wc-set-product-attributes or wc-remove-product-attribute first', $key ),
                409
            );
        }

        $existing[ $key ] = $attr;
        $product->set_attributes( $existing );
        $product->save();

        return [
            'success'    => true,
            'message'    => 'Attribute added',
            'product_id' => $product->get_id(),
            'attribute'  => self::format_attribute( $attr ),
            'attributes' => self::format_attributes( $product ),
        ];
    }

    public static function remove_product_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $product = self::fetch_product( (int) ( $input['product_id'] ?? 0 ) );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }

        $name = trim( (string) ( $input['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'invalid_name', 'Attribute name is required', 400 );
        }

        $key      = self::resolve_attribute_key( $name );
        $existing = $product->get_attributes();

        if ( ! isset( $existing[ $key ] ) ) {
            return self::errorResponse( 'attribute_not_attached', 'Attribute not attached to this product', 404 );
        }

        unset( $existing[ $key ] );
        $product->set_attributes( $existing );
        $product->save();

        return [
            'success'        => true,
            'message'        => 'Attribute removed',
            'product_id'     => $product->get_id(),
            'removed_name'   => $key,
            'attributes'     => self::format_attributes( $product ),
        ];
    }

    private static function build_attribute( mixed $entry, int $position ): \WC_Product_Attribute|\WP_Error {
        if ( ! is_array( $entry ) ) {
            return self::errorResponse( 'invalid_entry', 'Each attribute entry must be an object', 400 );
        }

        $name = trim( (string) ( $entry['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'invalid_name', 'Attribute name is required', 400 );
        }

        $is_global = self::looks_global( $name );
        $taxonomy  = $is_global ? wc_attribute_taxonomy_name( ltrim( $name, 'pa_' ) ) : '';
        $attr      = new \WC_Product_Attribute();

        if ( $is_global ) {
            $tax_id = wc_attribute_taxonomy_id_by_name( ltrim( $name, 'pa_' ) );
            if ( ! $tax_id ) {
                return self::errorResponse( 'attribute_not_found', sprintf( 'Global attribute "%s" not found', $name ), 404 );
            }
            $attr->set_id( $tax_id );
            $attr->set_name( $taxonomy );
            $attr->set_options( self::resolve_term_ids( $taxonomy, $entry['options'] ?? [] ) );
        } else {
            $options = $entry['options'] ?? [];
            if ( ! is_array( $options ) ) {
                $options = [ $options ];
            }
            $attr->set_id( 0 );
            $attr->set_name( $name );
            $attr->set_options( array_map( 'strval', $options ) );
        }

        $attr->set_position( (int) ( $entry['position'] ?? $position ) );
        $attr->set_visible( (bool) ( $entry['visible']   ?? true ) );
        $attr->set_variation( (bool) ( $entry['variation'] ?? false ) );

        return $attr;
    }

    /**
     * Resolve term IDs from a flexible input (IDs, slugs, names).
     *
     * @return int[]
     */
    private static function resolve_term_ids( string $taxonomy, mixed $options ): array {
        if ( ! is_array( $options ) ) {
            $options = [ $options ];
        }

        $ids = [];
        foreach ( $options as $option ) {
            if ( is_numeric( $option ) ) {
                $term = get_term( (int) $option, $taxonomy );
            } else {
                $term = get_term_by( 'slug', (string) $option, $taxonomy );
                if ( ! $term instanceof \WP_Term ) {
                    $term = get_term_by( 'name', (string) $option, $taxonomy );
                }
            }

            if ( $term instanceof \WP_Term ) {
                $ids[] = (int) $term->term_id;
            }
        }

        return array_values( array_unique( $ids ) );
    }

    private static function fetch_product( int $product_id ): \WC_Product|\WP_Error {
        if ( $product_id <= 0 ) {
            return self::errorResponse( 'invalid_product_id', 'product_id is required', 400 );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product instanceof \WC_Product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        return $product;
    }

    private static function resolve_attribute_key( string $name ): string {
        if ( self::looks_global( $name ) ) {
            return wc_attribute_taxonomy_name( ltrim( $name, 'pa_' ) );
        }
        return $name;
    }

    private static function looks_global( string $name ): bool {
        return str_starts_with( $name, 'pa_' );
    }

    private static function format_attribute( \WC_Product_Attribute $attr ): array {
        return [
            'name'      => $attr->get_name(),
            'options'   => $attr->get_options(),
            'position'  => $attr->get_position(),
            'visible'   => $attr->get_visible(),
            'variation' => $attr->get_variation(),
            'is_global' => $attr->is_taxonomy(),
        ];
    }

    /**
     * Format every WC_Product_Attribute attached to a product.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function format_attributes( \WC_Product $product ): array {
        $items = [];
        foreach ( $product->get_attributes() as $attr ) {
            if ( $attr instanceof \WC_Product_Attribute ) {
                $items[] = self::format_attribute( $attr );
            }
        }
        return $items;
    }
}
