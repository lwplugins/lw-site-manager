<?php
/**
 * Global product attributes (pa_*) and their terms.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class AttributeManager extends AbstractService {

    public static function list_attributes( array $input = [] ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $taxonomies = wc_get_attribute_taxonomies();
        $items      = [];

        foreach ( $taxonomies as $tax ) {
            $items[] = self::format_attribute( $tax );
        }

        return [
            'attributes' => $items,
            'total'      => count( $items ),
        ];
    }

    public static function get_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        return [
            'attribute' => self::format_attribute( $tax ),
        ];
    }

    public static function create_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $name = trim( (string) ( $input['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'invalid_name', 'Attribute name is required', 400 );
        }

        $args = [
            'name'         => $name,
            'slug'         => self::normalize_slug( $input['slug'] ?? wc_sanitize_taxonomy_name( $name ) ),
            'type'         => $input['type'] ?? 'select',
            'order_by'     => $input['order_by'] ?? 'menu_order',
            'has_archives' => (bool) ( $input['has_archives'] ?? false ),
        ];

        if ( ! in_array( $args['type'], [ 'select', 'text' ], true ) ) {
            return self::errorResponse( 'invalid_type', 'type must be one of: select, text', 400 );
        }

        if ( ! in_array( $args['order_by'], [ 'menu_order', 'name', 'name_num', 'id' ], true ) ) {
            return self::errorResponse( 'invalid_order_by', 'order_by must be one of: menu_order, name, name_num, id', 400 );
        }

        $id = wc_create_attribute( $args );
        if ( $id instanceof \WP_Error ) {
            return $id;
        }

        delete_transient( 'wc_attribute_taxonomies' );
        \WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

        $tax = self::find_taxonomy_by_id( (int) $id );

        return [
            'success'   => true,
            'message'   => 'Attribute created',
            'id'        => (int) $id,
            'attribute' => $tax ? self::format_attribute( $tax ) : null,
        ];
    }

    public static function update_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $args = [
            'name'         => $input['name'] ?? $tax->attribute_label,
            'slug'         => $tax->attribute_name,
            'type'         => $input['type'] ?? $tax->attribute_type,
            'order_by'     => $input['order_by'] ?? $tax->attribute_orderby,
            'has_archives' => array_key_exists( 'has_archives', $input )
                ? (bool) $input['has_archives']
                : (bool) $tax->attribute_public,
        ];

        $result = wc_update_attribute( (int) $tax->attribute_id, $args );
        if ( $result instanceof \WP_Error ) {
            return $result;
        }

        delete_transient( 'wc_attribute_taxonomies' );
        \WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

        $fresh = self::find_taxonomy_by_id( (int) $tax->attribute_id );

        return [
            'success'   => true,
            'message'   => 'Attribute updated',
            'attribute' => $fresh ? self::format_attribute( $fresh ) : null,
        ];
    }

    public static function delete_attribute( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $result = wc_delete_attribute( (int) $tax->attribute_id );
        if ( ! $result ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete attribute', 500 );
        }

        delete_transient( 'wc_attribute_taxonomies' );
        \WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

        return [
            'success' => true,
            'message' => 'Attribute deleted',
            'id'      => (int) $tax->attribute_id,
            'name'    => $tax->attribute_label,
        ];
    }

    public static function list_attribute_terms( array $input ): array|\WP_Error {
        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );

        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => (bool) ( $input['hide_empty'] ?? false ),
                'number'     => (int) ( $input['limit'] ?? 100 ),
                'offset'     => (int) ( $input['offset'] ?? 0 ),
                'search'     => $input['search'] ?? '',
            ]
        );

        if ( $terms instanceof \WP_Error ) {
            return $terms;
        }

        $items = array_map( [ self::class, 'format_term' ], $terms );

        return [
            'attribute_id'   => (int) $tax->attribute_id,
            'attribute_slug' => $tax->attribute_name,
            'terms'          => $items,
            'total'          => count( $items ),
        ];
    }

    public static function create_attribute_term( array $input ): array|\WP_Error {
        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $name = trim( (string) ( $input['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'invalid_name', 'Term name is required', 400 );
        }

        $taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
        $args     = [];

        if ( ! empty( $input['slug'] ) ) {
            $args['slug'] = sanitize_title( (string) $input['slug'] );
        }
        if ( ! empty( $input['description'] ) ) {
            $args['description'] = (string) $input['description'];
        }

        $result = wp_insert_term( $name, $taxonomy, $args );
        if ( $result instanceof \WP_Error ) {
            return $result;
        }

        $term = get_term( (int) $result['term_id'], $taxonomy );

        return [
            'success'      => true,
            'message'      => 'Term created',
            'attribute_id' => (int) $tax->attribute_id,
            'term'         => $term instanceof \WP_Term ? self::format_term( $term ) : null,
        ];
    }

    public static function update_attribute_term( array $input ): array|\WP_Error {
        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $term_id = (int) ( $input['term_id'] ?? 0 );
        if ( $term_id <= 0 ) {
            return self::errorResponse( 'invalid_term_id', 'term_id is required', 400 );
        }

        $taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
        $args     = array_filter(
            [
                'name'        => $input['name']        ?? null,
                'slug'        => isset( $input['slug'] ) ? sanitize_title( (string) $input['slug'] ) : null,
                'description' => $input['description'] ?? null,
            ],
            static fn( $v ) => null !== $v
        );

        if ( empty( $args ) ) {
            return self::errorResponse( 'no_changes', 'No fields to update were provided', 400 );
        }

        $result = wp_update_term( $term_id, $taxonomy, $args );
        if ( $result instanceof \WP_Error ) {
            return $result;
        }

        $term = get_term( $term_id, $taxonomy );

        return [
            'success' => true,
            'message' => 'Term updated',
            'term'    => $term instanceof \WP_Term ? self::format_term( $term ) : null,
        ];
    }

    public static function delete_attribute_term( array $input ): array|\WP_Error {
        $tax = self::resolve_attribute( $input );
        if ( $tax instanceof \WP_Error ) {
            return $tax;
        }

        $term_id = (int) ( $input['term_id'] ?? 0 );
        if ( $term_id <= 0 ) {
            return self::errorResponse( 'invalid_term_id', 'term_id is required', 400 );
        }

        $taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
        $term     = get_term( $term_id, $taxonomy );

        if ( ! $term instanceof \WP_Term ) {
            return self::errorResponse( 'term_not_found', 'Term not found in this attribute', 404 );
        }

        $result = wp_delete_term( $term_id, $taxonomy );
        if ( $result instanceof \WP_Error ) {
            return $result;
        }

        return [
            'success' => true,
            'message' => 'Term deleted',
            'id'      => $term_id,
            'name'    => $term->name,
        ];
    }

    private static function resolve_attribute( array $input ): \stdClass|\WP_Error {
        $id   = (int) ( $input['id'] ?? $input['attribute_id'] ?? 0 );
        $slug = isset( $input['slug'] ) ? self::normalize_slug( (string) $input['slug'] ) : '';

        if ( $id > 0 ) {
            $tax = self::find_taxonomy_by_id( $id );
        } elseif ( '' !== $slug ) {
            $tax = self::find_taxonomy_by_slug( $slug );
        } else {
            return self::errorResponse( 'missing_identifier', 'id or slug is required', 400 );
        }

        if ( ! $tax ) {
            return self::errorResponse( 'attribute_not_found', 'Global attribute not found', 404 );
        }

        return $tax;
    }

    private static function find_taxonomy_by_id( int $id ): ?\stdClass {
        foreach ( wc_get_attribute_taxonomies() as $tax ) {
            if ( (int) $tax->attribute_id === $id ) {
                return $tax;
            }
        }
        return null;
    }

    private static function find_taxonomy_by_slug( string $slug ): ?\stdClass {
        foreach ( wc_get_attribute_taxonomies() as $tax ) {
            if ( $tax->attribute_name === $slug ) {
                return $tax;
            }
        }
        return null;
    }

    private static function normalize_slug( string $slug ): string {
        return ltrim( wc_sanitize_taxonomy_name( $slug ), '_' );
    }

    private static function format_attribute( \stdClass $tax ): array {
        return [
            'id'           => (int) $tax->attribute_id,
            'name'         => $tax->attribute_label,
            'slug'         => $tax->attribute_name,
            'taxonomy'     => wc_attribute_taxonomy_name( $tax->attribute_name ),
            'type'         => $tax->attribute_type,
            'order_by'     => $tax->attribute_orderby,
            'has_archives' => (bool) $tax->attribute_public,
        ];
    }

    private static function format_term( \WP_Term $term ): array {
        return [
            'id'          => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'count'       => $term->count,
        ];
    }
}
