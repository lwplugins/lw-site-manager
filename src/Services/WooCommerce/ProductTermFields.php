<?php
/**
 * Which WooCommerce product taxonomies we manage, and their term-meta extras.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

/**
 * WooCommerce keeps category and brand extras in term meta: the image in
 * `thumbnail_id` (product_cat and product_brand), the archive display type in
 * `display_type` and the drag-and-drop position in `order` (product_cat).
 * This class maps ability input onto those keys and validates it; it never
 * touches the database.
 */
final class ProductTermFields {

    /** Ability input field => term meta key. */
    private const META_KEYS = [
        'image_id'     => 'thumbnail_id',
        'display_type' => 'display_type',
        'menu_order'   => 'order',
    ];

    /** Taxonomy => extras it supports. */
    private const TAXONOMIES = [
        'product_cat'            => [ 'image_id', 'display_type', 'menu_order' ],
        'product_tag'            => [],
        'product_brand'          => [ 'image_id' ],
        'product_shipping_class' => [],
    ];

    /** Allowed display_type values ('' = the shop default). */
    public const DISPLAY_TYPES = [ '', 'products', 'subcategories', 'both' ];

    /**
     * Managed taxonomy names.
     *
     * @return string[]
     */
    public static function taxonomies(): array {
        return array_keys( self::TAXONOMIES );
    }

    public static function isManaged( string $taxonomy ): bool {
        return isset( self::TAXONOMIES[ $taxonomy ] );
    }

    /**
     * Extras of one taxonomy.
     *
     * @return string[] Input fields the taxonomy supports.
     */
    public static function extras( string $taxonomy ): array {
        return self::TAXONOMIES[ $taxonomy ] ?? [];
    }

    public static function metaKey( string $field ): string {
        return self::META_KEYS[ $field ];
    }

    /**
     * Turn ability input into the term meta to write.
     *
     * Only fields present in the input are returned, so an update leaves the
     * others alone. A field the taxonomy does not support is an error rather
     * than a silent no-op.
     *
     * @return array<string, int|string>|\WP_Error Meta key => value.
     */
    public static function metaFromInput( string $taxonomy, array $input ): array|\WP_Error {
        $meta = [];

        foreach ( self::META_KEYS as $field => $key ) {
            if ( ! array_key_exists( $field, $input ) || null === $input[ $field ] ) {
                continue;
            }

            if ( ! in_array( $field, self::extras( $taxonomy ), true ) ) {
                return ResponseFormatter::error(
                    'unsupported_field',
                    sprintf( '%s is not supported for %s terms.', $field, $taxonomy ),
                    400
                );
            }

            $value = self::validate( $field, $input[ $field ] );
            if ( $value instanceof \WP_Error ) {
                return $value;
            }

            $meta[ $key ] = $value;
        }

        return $meta;
    }

    private static function validate( string $field, mixed $value ): int|string|\WP_Error {
        if ( 'display_type' === $field ) {
            $value = is_string( $value ) ? $value : '';
            if ( ! in_array( $value, self::DISPLAY_TYPES, true ) ) {
                return ResponseFormatter::error( 'invalid_display_type', 'display_type must be one of: "", products, subcategories, both.', 400 );
            }
            return $value;
        }

        if ( ! is_numeric( $value ) || (int) $value != $value ) {
            return ResponseFormatter::error( 'invalid_' . $field, $field . ' must be an integer.', 400 );
        }

        $value = (int) $value;
        if ( 'image_id' === $field && $value < 0 ) {
            return ResponseFormatter::error( 'invalid_image_id', 'image_id must be an attachment ID, or 0 to clear the image.', 400 );
        }

        return $value;
    }
}
