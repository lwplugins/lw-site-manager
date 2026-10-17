<?php
/**
 * The preselected (default) variation of a variable product.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Writes `_default_attributes` through WC_Product_Variable::set_default_attributes(),
 * keyed and cleaned the way WooCommerce's product screen does it: the key is
 * sanitize_title( attribute name ), a taxonomy value is a term slug and a
 * custom value is the option text. An axis left empty means "any".
 */
class DefaultVariationManager extends AbstractService {

    public static function set_default_variation( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $product = CatalogGuard::editableProduct( (int) ( $input['product_id'] ?? 0 ) );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }
        if ( ! $product instanceof \WC_Product_Variable ) {
            return self::errorResponse( 'not_variable', 'The product is not a variable product.', 400 );
        }

        $defaults = self::resolve( $product, $input );
        if ( $defaults instanceof \WP_Error ) {
            return $defaults;
        }

        $product->set_default_attributes( $defaults );
        $product->save();

        $saved = wc_get_product( $product->get_id() );

        return [
            'success'            => true,
            'message'            => [] === array_filter( $defaults, static fn( string $value ): bool => '' !== $value ) ? 'Default variation cleared.' : 'Default variation set.',
            'product_id'         => $product->get_id(),
            'default_attributes' => $saved ? (object) $saved->get_default_attributes() : (object) [],
        ];
    }

    /**
     * Default attributes from clear, variation_id or attributes.
     *
     * @return array<string, string>|\WP_Error
     */
    private static function resolve( \WC_Product_Variable $product, array $input ): array|\WP_Error {
        if ( ! empty( $input['clear'] ) ) {
            return [];
        }

        if ( ! empty( $input['variation_id'] ) ) {
            $variation = wc_get_product( (int) $input['variation_id'] );
            if ( ! $variation instanceof \WC_Product_Variation || $variation->get_parent_id() !== $product->get_id() ) {
                return self::errorResponse( 'invalid_variation', 'variation_id must be a variation of this product.', 400 );
            }
            return array_map( 'strval', $variation->get_attributes() );
        }

        if ( empty( $input['attributes'] ) || ! is_array( $input['attributes'] ) ) {
            return self::errorResponse( 'missing_input', 'Pass attributes, variation_id or clear: true.', 400 );
        }

        return self::fromAttributes( $product, $input['attributes'] );
    }

    /**
     * Default attributes from an attribute => value map.
     *
     * @return array<string, string>|\WP_Error
     */
    private static function fromAttributes( \WC_Product_Variable $product, array $given ): array|\WP_Error {
        $normalized = [];
        foreach ( $given as $key => $value ) {
            $normalized[ sanitize_title( preg_replace( '/^attribute_/', '', (string) $key ) ) ] = (string) $value;
        }

        $defaults = [];
        foreach ( $product->get_attributes() as $attribute ) {
            if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_variation() ) {
                continue;
            }

            $key   = sanitize_title( $attribute->get_name() );
            $value = $normalized[ $key ] ?? '';
            unset( $normalized[ $key ] );

            if ( '' !== $value ) {
                $value = self::option( $attribute, $value );
                if ( null === $value ) {
                    return self::errorResponse( 'invalid_option', sprintf( 'That value is not an option of the %s attribute.', $attribute->get_name() ), 400 );
                }
            }
            $defaults[ $key ] = $value;
        }

        if ( [] !== $normalized ) {
            return self::errorResponse( 'unknown_attribute', 'Not a variation attribute of this product: ' . implode( ', ', array_keys( $normalized ) ), 400 );
        }

        return $defaults;
    }

    /**
     * The stored form of a value if it is one of the attribute's options.
     */
    private static function option( \WC_Product_Attribute $attribute, string $value ): ?string {
        if ( $attribute->is_taxonomy() ) {
            $slug = sanitize_title( $value );
            foreach ( $attribute->get_terms() ?? [] as $term ) {
                if ( $term->slug === $slug ) {
                    return $slug;
                }
            }
            return null;
        }

        $value = html_entity_decode( wc_clean( $value ), ENT_QUOTES, get_bloginfo( 'charset' ) );

        return in_array( $value, array_map( 'strval', $attribute->get_options() ), true ) ? $value : null;
    }
}
