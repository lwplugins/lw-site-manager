<?php
/**
 * Product variation CRUD and combinatorial generation.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class ProductVariationManager extends AbstractService {

    public static function generate_variations( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $parent = self::fetch_variable_parent( (int) ( $input['product_id'] ?? 0 ) );
        if ( $parent instanceof \WP_Error ) {
            return $parent;
        }

        $combinations = self::variation_attribute_combinations( $parent );
        if ( empty( $combinations ) ) {
            return self::errorResponse(
                'no_variation_attributes',
                'Parent product has no variation-flagged attributes; mark at least one attribute with variation=true first',
                400
            );
        }

        $existing = self::existing_variation_keys( $parent );
        $created  = [];
        $skipped  = 0;

        foreach ( $combinations as $combo ) {
            $key = self::combo_key( $combo );
            if ( isset( $existing[ $key ] ) ) {
                $skipped++;
                continue;
            }

            $variation = new \WC_Product_Variation();
            $variation->set_parent_id( $parent->get_id() );
            $variation->set_attributes( $combo );
            $variation->set_status( 'publish' );

            if ( ! empty( $input['default_price'] ) ) {
                $variation->set_regular_price( (string) $input['default_price'] );
            }

            $id = $variation->save();
            if ( $id ) {
                $created[] = (int) $id;
            }
        }

        \WC_Product_Variable::sync( $parent->get_id() );

        return [
            'success'    => true,
            'message'    => 'Variations generated',
            'product_id' => $parent->get_id(),
            'created'    => $created,
            'skipped'    => $skipped,
            'total'      => count( $created ),
        ];
    }

    public static function create_variation( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $parent = self::fetch_variable_parent( (int) ( $input['product_id'] ?? 0 ) );
        if ( $parent instanceof \WP_Error ) {
            return $parent;
        }

        $attributes = $input['attributes'] ?? [];
        if ( ! is_array( $attributes ) ) {
            return self::errorResponse( 'invalid_attributes', 'attributes must be an object map (taxonomy => slug)', 400 );
        }

        $variation = new \WC_Product_Variation();
        $variation->set_parent_id( $parent->get_id() );
        $variation->set_attributes( self::normalize_attribute_map( $attributes ) );
        self::apply_variation_fields( $variation, $input );

        $id = $variation->save();
        if ( ! $id ) {
            return self::errorResponse( 'create_failed', 'Failed to create variation', 500 );
        }

        \WC_Product_Variable::sync( $parent->get_id() );

        return [
            'success'    => true,
            'message'    => 'Variation created',
            'product_id' => $parent->get_id(),
            'id'         => (int) $id,
            'variation'  => self::format_variation( wc_get_product( (int) $id ) ),
        ];
    }

    public static function update_variation( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $id        = (int) ( $input['id'] ?? 0 );
        $variation = $id > 0 ? wc_get_product( $id ) : null;

        if ( ! $variation instanceof \WC_Product_Variation ) {
            return self::errorResponse( 'variation_not_found', 'Variation not found', 404 );
        }

        if ( isset( $input['attributes'] ) && is_array( $input['attributes'] ) ) {
            $variation->set_attributes( self::normalize_attribute_map( $input['attributes'] ) );
        }

        self::apply_variation_fields( $variation, $input );

        $variation->save();
        \WC_Product_Variable::sync( $variation->get_parent_id() );

        return [
            'success'   => true,
            'message'   => 'Variation updated',
            'id'        => $variation->get_id(),
            'variation' => self::format_variation( $variation ),
        ];
    }

    public static function delete_variation( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $id        = (int) ( $input['id'] ?? 0 );
        $variation = $id > 0 ? wc_get_product( $id ) : null;

        if ( ! $variation instanceof \WC_Product_Variation ) {
            return self::errorResponse( 'variation_not_found', 'Variation not found', 404 );
        }

        $parent_id = $variation->get_parent_id();
        $force     = (bool) ( $input['force'] ?? false );
        $variation->delete( $force );

        \WC_Product_Variable::sync( $parent_id );

        return [
            'success'   => true,
            'message'   => $force ? 'Variation permanently deleted' : 'Variation moved to trash',
            'id'        => $id,
            'product_id'=> $parent_id,
            'trashed'   => ! $force,
        ];
    }

    private static function apply_variation_fields( \WC_Product_Variation $variation, array $input ): void {
        $setters = [
            'sku'            => 'set_sku',
            'regular_price'  => 'set_regular_price',
            'sale_price'     => 'set_sale_price',
            'status'         => 'set_status',
            'description'    => 'set_description',
            'stock_quantity' => 'set_stock_quantity',
            'stock_status'   => 'set_stock_status',
            'manage_stock'   => 'set_manage_stock',
            'weight'         => 'set_weight',
            'length'         => 'set_length',
            'width'          => 'set_width',
            'height'         => 'set_height',
            'image_id'       => 'set_image_id',
            'menu_order'     => 'set_menu_order',
            'virtual'        => 'set_virtual',
            'downloadable'   => 'set_downloadable',
            'tax_class'      => 'set_tax_class',
            'tax_status'     => 'set_tax_status',
        ];

        foreach ( $setters as $field => $setter ) {
            if ( array_key_exists( $field, $input ) ) {
                $variation->{$setter}( $input[ $field ] );
            }
        }

        if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
            WcMetaManager::apply_meta_map( $variation, $input['meta'] );
        }
    }

    /**
     * Normalize an attribute map to the canonical taxonomy → slug shape.
     *
     * @param array<string, string> $attributes Map of taxonomy → term slug (or value for custom).
     * @return array<string, string>
     */
    private static function normalize_attribute_map( array $attributes ): array {
        $normalized = [];
        foreach ( $attributes as $key => $value ) {
            $taxonomy = str_starts_with( (string) $key, 'pa_' )
                ? (string) $key
                : (string) $key;
            $normalized[ $taxonomy ] = (string) $value;
        }
        return $normalized;
    }

    /**
     * Build the cartesian product of variation-flagged attributes.
     *
     * @return array<int, array<string, string>>
     */
    private static function variation_attribute_combinations( \WC_Product $parent ): array {
        $axes = [];
        foreach ( $parent->get_attributes() as $attr ) {
            if ( ! $attr instanceof \WC_Product_Attribute || ! $attr->get_variation() ) {
                continue;
            }
            $taxonomy = $attr->get_name();
            $values   = [];

            if ( $attr->is_taxonomy() ) {
                foreach ( $attr->get_terms() as $term ) {
                    if ( $term instanceof \WP_Term ) {
                        $values[] = $term->slug;
                    }
                }
            } else {
                foreach ( $attr->get_options() as $option ) {
                    $values[] = (string) $option;
                }
            }

            if ( ! empty( $values ) ) {
                $axes[ $taxonomy ] = $values;
            }
        }

        return self::cartesian( $axes );
    }

    /**
     * Compute the cartesian product of a set of named axes.
     *
     * @param array<string, string[]> $axes
     * @return array<int, array<string, string>>
     */
    private static function cartesian( array $axes ): array {
        if ( empty( $axes ) ) {
            return [];
        }

        $result = [ [] ];
        foreach ( $axes as $taxonomy => $values ) {
            $next = [];
            foreach ( $result as $partial ) {
                foreach ( $values as $value ) {
                    $next[] = $partial + [ $taxonomy => $value ];
                }
            }
            $result = $next;
        }
        return $result;
    }

    /**
     * Map every existing variation to its canonical attribute-combination key.
     *
     * @return array<string, int>
     */
    private static function existing_variation_keys( \WC_Product $parent ): array {
        $keys = [];
        foreach ( $parent->get_children() as $child_id ) {
            $variation = wc_get_product( (int) $child_id );
            if ( $variation instanceof \WC_Product_Variation ) {
                $keys[ self::combo_key( $variation->get_attributes() ) ] = (int) $child_id;
            }
        }
        return $keys;
    }

    private static function combo_key( array $combo ): string {
        ksort( $combo );
        return md5( wp_json_encode( $combo ) ?: '' );
    }

    private static function fetch_variable_parent( int $product_id ): \WC_Product|\WP_Error {
        if ( $product_id <= 0 ) {
            return self::errorResponse( 'invalid_product_id', 'product_id is required', 400 );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product instanceof \WC_Product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        if ( 'variable' !== $product->get_type() ) {
            return self::errorResponse(
                'not_variable_product',
                sprintf( 'Product #%d is not a variable product (type: %s)', $product->get_id(), $product->get_type() ),
                400
            );
        }

        return $product;
    }

    private static function format_variation( ?\WC_Product $variation ): ?array {
        if ( ! $variation instanceof \WC_Product_Variation ) {
            return null;
        }

        return [
            'id'             => $variation->get_id(),
            'product_id'     => $variation->get_parent_id(),
            'sku'            => $variation->get_sku(),
            'price'          => $variation->get_price(),
            'regular_price'  => $variation->get_regular_price(),
            'sale_price'     => $variation->get_sale_price(),
            'stock_quantity' => $variation->get_stock_quantity(),
            'stock_status'   => $variation->get_stock_status(),
            'manage_stock'   => $variation->get_manage_stock(),
            'attributes'     => $variation->get_attributes(),
            'status'         => $variation->get_status(),
            'menu_order'     => $variation->get_menu_order(),
            'image'          => self::attachmentUrlOrNull( (int) $variation->get_image_id() ),
        ];
    }
}
