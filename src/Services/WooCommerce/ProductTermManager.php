<?php
/**
 * Product categories, tags, brands and shipping classes.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * CRUD for the WooCommerce product taxonomies through the WordPress term API,
 * with WooCommerce's term meta for the extras (see ProductTermFields).
 *
 * Capabilities follow the taxonomy's own cap object, which WooCommerce maps
 * to manage/edit/delete/assign_product_terms: creating needs edit_terms,
 * changing a term the edit_term meta capability and deleting it delete_term
 * (WordPress refuses delete_term on the default product category).
 */
class ProductTermManager extends AbstractService {

    private const ORDERBY = [ 'name', 'slug', 'count', 'id', 'menu_order' ];

    public static function list_terms( array $input ): array|\WP_Error {
        $taxonomy = self::taxonomy( $input );
        if ( $taxonomy instanceof \WP_Error ) {
            return $taxonomy;
        }

        $limit   = max( 1, min( 200, (int) ( $input['limit'] ?? 100 ) ) );
        $offset  = max( 0, (int) ( $input['offset'] ?? 0 ) );
        $orderby = in_array( $input['orderby'] ?? 'name', self::ORDERBY, true ) ? ( $input['orderby'] ?? 'name' ) : 'name';
        $args    = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => (bool) ( $input['hide_empty'] ?? false ),
            'orderby'    => 'id' === $orderby ? 'term_id' : $orderby,
            'order'      => 'DESC' === strtoupper( (string) ( $input['order'] ?? 'ASC' ) ) ? 'DESC' : 'ASC',
        ];

        if ( isset( $input['parent'] ) && is_taxonomy_hierarchical( $taxonomy ) ) {
            $args['parent'] = max( 0, (int) $input['parent'] );
        }
        if ( ! empty( $input['search'] ) ) {
            $args['search'] = sanitize_text_field( (string) $input['search'] );
        }

        $total = get_terms( array_merge( $args, [ 'fields' => 'count' ] ) );
        $terms = get_terms( array_merge( $args, [ 'number' => $limit, 'offset' => $offset ] ) );
        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        $items = array_map( [ self::class, 'format' ], array_values( $terms ) );

        return self::listResponse( 'terms', $items, is_wp_error( $total ) ? count( $items ) : (int) $total, $limit, $offset );
    }

    public static function get_term( array $input ): array|\WP_Error {
        $taxonomy = self::taxonomy( $input );
        if ( $taxonomy instanceof \WP_Error ) {
            return $taxonomy;
        }

        $term = get_term( (int) ( $input['id'] ?? 0 ), $taxonomy );
        if ( ! $term instanceof \WP_Term ) {
            return self::errorResponse( 'term_not_found', 'Term not found.', 404 );
        }

        return [ 'term' => self::format( $term ) ];
    }

    public static function create_term( array $input ): array|\WP_Error {
        $taxonomy = self::taxonomy( $input );
        if ( $taxonomy instanceof \WP_Error ) {
            return $taxonomy;
        }

        $tax = get_taxonomy( $taxonomy );
        if ( ! $tax || ! current_user_can( $tax->cap->edit_terms ) ) {
            return self::errorResponse( 'forbidden', 'You are not allowed to create terms in this taxonomy.', 403 );
        }

        $name = trim( (string) ( $input['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'missing_name', 'name is required.', 400 );
        }

        $meta = self::metaFor( $taxonomy, $input );
        if ( $meta instanceof \WP_Error ) {
            return $meta;
        }

        $args = self::termArgs( $taxonomy, $input );
        if ( $args instanceof \WP_Error ) {
            return $args;
        }

        $result = wp_insert_term( $name, $taxonomy, $args );
        if ( is_wp_error( $result ) ) {
            return self::errorResponse( $result->get_error_code(), $result->get_error_message(), 'term_exists' === $result->get_error_code() ? 409 : 400 );
        }

        $termId = (int) $result['term_id'];
        self::writeMeta( $termId, $meta );

        return [
            'success' => true,
            'message' => 'Term created.',
            'term'    => self::format( get_term( $termId, $taxonomy ) ),
        ];
    }

    public static function update_term( array $input ): array|\WP_Error {
        $term = self::termFor( $input, 'edit_term' );
        if ( $term instanceof \WP_Error ) {
            return $term;
        }

        $meta = self::metaFor( $term->taxonomy, $input );
        if ( $meta instanceof \WP_Error ) {
            return $meta;
        }

        $args = self::termArgs( $term->taxonomy, $input, $term->term_id );
        if ( $args instanceof \WP_Error ) {
            return $args;
        }
        if ( isset( $input['name'] ) ) {
            $args['name'] = trim( (string) $input['name'] );
        }

        if ( [] !== $args ) {
            $result = wp_update_term( $term->term_id, $term->taxonomy, $args );
            if ( is_wp_error( $result ) ) {
                return self::errorResponse( $result->get_error_code(), $result->get_error_message(), 400 );
            }
        }
        self::writeMeta( $term->term_id, $meta );

        return [
            'success' => true,
            'message' => 'Term updated.',
            'term'    => self::format( get_term( $term->term_id, $term->taxonomy ) ),
        ];
    }

    public static function delete_term( array $input ): array|\WP_Error {
        $taxonomy = self::taxonomy( $input );
        if ( $taxonomy instanceof \WP_Error ) {
            return $taxonomy;
        }

        $id = (int) ( $input['id'] ?? 0 );
        if ( 'product_cat' === $taxonomy && $id > 0 && (int) get_option( 'default_product_cat', 0 ) === $id ) {
            return self::errorResponse( 'default_category', 'The default product category cannot be deleted.', 409 );
        }

        $term = self::termFor( $input, 'delete_term' );
        if ( $term instanceof \WP_Error ) {
            return $term;
        }

        $unconfirmed = CatalogGuard::confirm(
            true,
            $input,
            'confirm_delete',
            sprintf( 'Deleting "%s" is permanent (terms have no trash) and removes it from %d product(s).', $term->name, (int) $term->count )
        );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        $result = wp_delete_term( $term->term_id, $term->taxonomy );
        if ( true !== $result ) {
            return self::errorResponse( 'delete_failed', 'The term could not be deleted.', 500 );
        }

        return [
            'success' => true,
            'message' => 'Term deleted permanently.',
            'id'      => $term->term_id,
            'name'    => $term->name,
        ];
    }

    /**
     * Shape a term for output, including the extras its taxonomy supports.
     */
    public static function format( \WP_Term $term ): array {
        $data = [
            'id'          => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'taxonomy'    => $term->taxonomy,
            'description' => $term->description,
            'parent'      => $term->parent,
            'count'       => $term->count,
        ];

        $extras = ProductTermFields::extras( $term->taxonomy );
        if ( in_array( 'image_id', $extras, true ) ) {
            $imageId            = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
            $data['image_id']   = $imageId;
            $data['image_url']  = $imageId ? self::attachmentUrlOrNull( $imageId ) : null;
        }
        if ( in_array( 'display_type', $extras, true ) ) {
            $data['display_type'] = (string) get_term_meta( $term->term_id, 'display_type', true );
        }
        if ( in_array( 'menu_order', $extras, true ) ) {
            $data['menu_order'] = (int) get_term_meta( $term->term_id, 'order', true );
        }
        if ( 'product_cat' === $term->taxonomy ) {
            $data['is_default'] = (int) get_option( 'default_product_cat', 0 ) === $term->term_id;
        }

        return $data;
    }

    /**
     * Validate the taxonomy input and that it is registered on this site.
     */
    private static function taxonomy( array $input ): string|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $taxonomy = (string) ( $input['taxonomy'] ?? '' );
        if ( ! ProductTermFields::isManaged( $taxonomy ) ) {
            return self::errorResponse( 'invalid_taxonomy', 'taxonomy must be one of: ' . implode( ', ', ProductTermFields::taxonomies() ), 400 );
        }
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return self::errorResponse( 'taxonomy_unavailable', sprintf( '%s is not registered on this site.', $taxonomy ), 400 );
        }

        return $taxonomy;
    }

    /**
     * Resolve a term of the requested taxonomy that the user may act on.
     */
    private static function termFor( array $input, string $capability ): \WP_Term|\WP_Error {
        $taxonomy = self::taxonomy( $input );
        if ( $taxonomy instanceof \WP_Error ) {
            return $taxonomy;
        }

        $id   = (int) ( $input['id'] ?? 0 );
        $term = $id > 0 && current_user_can( $capability, $id ) ? get_term( $id, $taxonomy ) : null;

        return $term instanceof \WP_Term ? $term : CatalogGuard::unavailable( 'Term' );
    }

    /**
     * Slug, description and parent for wp_insert_term / wp_update_term.
     */
    private static function termArgs( string $taxonomy, array $input, int $selfId = 0 ): array|\WP_Error {
        $args = [];
        if ( isset( $input['slug'] ) ) {
            $args['slug'] = sanitize_title( (string) $input['slug'] );
        }
        if ( isset( $input['description'] ) ) {
            $args['description'] = (string) $input['description'];
        }
        if ( ! isset( $input['parent'] ) ) {
            return $args;
        }

        $parent = (int) $input['parent'];
        if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
            return self::errorResponse( 'unsupported_field', sprintf( 'parent is not supported for %s terms.', $taxonomy ), 400 );
        }
        if ( $parent > 0 && ( $parent === $selfId || ! get_term( $parent, $taxonomy ) instanceof \WP_Term ) ) {
            return self::errorResponse( 'invalid_parent', 'parent must be another existing term of the same taxonomy, or 0.', 400 );
        }
        $children = $selfId > 0 ? get_term_children( $selfId, $taxonomy ) : [];
        if ( $parent > 0 && is_array( $children ) && in_array( $parent, array_map( 'intval', $children ), true ) ) {
            return self::errorResponse( 'invalid_parent', 'A term cannot be moved under its own descendant.', 400 );
        }
        $args['parent'] = max( 0, $parent );

        return $args;
    }

    /**
     * Validated term meta, with image IDs checked against the media library.
     */
    private static function metaFor( string $taxonomy, array $input ): array|\WP_Error {
        $meta = ProductTermFields::metaFromInput( $taxonomy, $input );
        if ( $meta instanceof \WP_Error ) {
            return $meta;
        }

        $imageId = (int) ( $meta['thumbnail_id'] ?? 0 );
        if ( $imageId > 0 && ( ! wp_attachment_is_image( $imageId ) || ! current_user_can( 'read_post', $imageId ) ) ) {
            return self::errorResponse( 'invalid_image_id', 'image_id must be an image in the media library.', 400 );
        }

        return $meta;
    }

    private static function writeMeta( int $termId, array $meta ): void {
        foreach ( $meta as $key => $value ) {
            update_term_meta( $termId, $key, $value );
        }
    }
}
