<?php
/**
 * Taxonomy Manager Service - Handles category and tag operations
 */

declare(strict_types=1);

namespace WPSiteManager\Services;

class TaxonomyManager extends AbstractService {

    /**
     * List terms for a taxonomy
     */
    public static function list_terms( array $input ): array {
        $taxonomy = $input['taxonomy'] ?? 'category';

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return [
                'terms'       => [],
                'total'       => 0,
                'total_pages' => 0,
                'limit'       => $input['limit'] ?? 20,
                'offset'      => $input['offset'] ?? 0,
            ];
        }

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => $input['hide_empty'] ?? false,
            'number'     => $input['limit'] ?? 20,
            'offset'     => $input['offset'] ?? 0,
            'orderby'    => $input['orderby'] ?? 'name',
            'order'      => $input['order'] ?? 'ASC',
        ];

        if ( ! empty( $input['search'] ) ) {
            $args['search'] = $input['search'];
        }

        if ( ! empty( $input['parent'] ) ) {
            $args['parent'] = (int) $input['parent'];
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            $terms = [];
        }

        // Get total count
        $count_args = $args;
        unset( $count_args['number'], $count_args['offset'] );
        $count_args['fields'] = 'count';
        $total = (int) get_terms( $count_args );

        $items = [];
        foreach ( $terms as $term ) {
            $items[] = self::format_term( $term );
        }

        return [
            'terms'       => $items,
            'total'       => $total,
            'total_pages' => (int) ceil( $total / ( $input['limit'] ?? 20 ) ),
            'limit'       => $input['limit'] ?? 20,
            'offset'      => $input['offset'] ?? 0,
        ];
    }

    /**
     * Get single term
     */
    public static function get_term( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Term ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $taxonomy = $input['taxonomy'] ?? '';

        $term = get_term( $input['id'], $taxonomy );

        if ( is_wp_error( $term ) ) {
            return self::errorResponse( 'not_found', $term->get_error_message(), 404 );
        }

        if ( ! $term ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        return self::entityResponse( 'term', self::format_term( $term, true ) );
    }

    /**
     * Create term
     */
    public static function create_term( array $input ): array|\WP_Error {
        if ( empty( $input['name'] ) ) {
            return self::errorResponse( 'missing_name', 'Term name is required', 400 );
        }

        $taxonomy = $input['taxonomy'] ?? 'category';

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return self::errorResponse( 'invalid_taxonomy', 'Invalid taxonomy', 400 );
        }

        $args = [];

        if ( ! empty( $input['slug'] ) ) {
            $args['slug'] = $input['slug'];
        }
        if ( ! empty( $input['description'] ) ) {
            $args['description'] = $input['description'];
        }
        if ( isset( $input['parent'] ) && is_taxonomy_hierarchical( $taxonomy ) ) {
            $args['parent'] = (int) $input['parent'];
        }

        $result = wp_insert_term( $input['name'], $taxonomy, $args );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'create_failed', $result->get_error_message(), 500 );
        }

        $term = get_term( $result['term_id'], $taxonomy );

        return self::entityResponse( 'term', self::format_term( $term, true ) );
    }

    /**
     * Update term
     */
    public static function update_term( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Term ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $taxonomy = $input['taxonomy'] ?? '';

        $term = get_term( $input['id'], $taxonomy );

        if ( is_wp_error( $term ) || ! $term ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        $args = [];

        if ( isset( $input['name'] ) ) {
            $args['name'] = $input['name'];
        }
        if ( isset( $input['slug'] ) ) {
            $args['slug'] = $input['slug'];
        }
        if ( isset( $input['description'] ) ) {
            $args['description'] = $input['description'];
        }
        if ( isset( $input['parent'] ) && is_taxonomy_hierarchical( $term->taxonomy ) ) {
            $args['parent'] = (int) $input['parent'];
        }

        if ( empty( $args ) ) {
            return self::entityResponse( 'term', self::format_term( $term, true ) );
        }

        $result = wp_update_term( $input['id'], $term->taxonomy, $args );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'update_failed', $result->get_error_message(), 500 );
        }

        $term = get_term( $result['term_id'], $term->taxonomy );

        return self::entityResponse( 'term', self::format_term( $term, true ) );
    }

    /**
     * Delete term
     */
    public static function delete_term( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Term ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $taxonomy = $input['taxonomy'] ?? '';

        $term = get_term( $input['id'], $taxonomy );

        if ( is_wp_error( $term ) || ! $term ) {
            return self::errorResponse( 'not_found', 'Term not found', 404 );
        }

        // Prevent deleting default category
        if ( $term->taxonomy === 'category' && $term->term_id === (int) get_option( 'default_category' ) ) {
            return self::errorResponse( 'cannot_delete_default', 'Cannot delete default category', 400 );
        }

        $result = wp_delete_term( $input['id'], $term->taxonomy );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'delete_failed', $result->get_error_message(), 500 );
        }

        if ( $result === false ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete term', 500 );
        }

        return [
            'success' => true,
            'message' => 'Term deleted successfully',
            'id'      => $input['id'],
        ];
    }

    /**
     * Format term for output
     */
    private static function format_term( \WP_Term $term, bool $detailed = false ): array {
        $data = [
            'id'       => $term->term_id,
            'name'     => $term->name,
            'slug'     => $term->slug,
            'taxonomy' => $term->taxonomy,
            'count'    => $term->count,
        ];

        if ( $detailed ) {
            $data['description'] = $term->description;
            $data['parent']      = $term->parent;
            $data['link']        = get_term_link( $term );
        }

        return $data;
    }
}
