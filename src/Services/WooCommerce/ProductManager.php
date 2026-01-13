<?php
/**
 * WooCommerce Product Manager Service
 */

declare(strict_types=1);

namespace WPSiteManager\Services\WooCommerce;

use WPSiteManager\Services\AbstractService;

class ProductManager extends AbstractService {

    /**
     * List products with filtering and pagination
     */
    public static function list_products( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $args = [
            'post_type'      => 'product',
            'post_status'    => $input['status'] ?? 'any',
            'posts_per_page' => min( (int) ( $input['limit'] ?? 20 ), 100 ),
            'offset'         => (int) ( $input['offset'] ?? 0 ),
            'orderby'        => $input['orderby'] ?? 'date',
            'order'          => $input['order'] ?? 'DESC',
        ];

        // Search
        if ( ! empty( $input['search'] ) ) {
            $args['s'] = sanitize_text_field( $input['search'] );
        }

        // Category filter
        if ( ! empty( $input['category'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => is_numeric( $input['category'] ) ? 'term_id' : 'slug',
                    'terms'    => $input['category'],
                ],
            ];
        }

        // Product type filter
        if ( ! empty( $input['type'] ) ) {
            $args['tax_query'] = $args['tax_query'] ?? [];
            $args['tax_query'][] = [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $input['type'],
            ];
        }

        // Stock status filter
        if ( ! empty( $input['stock_status'] ) ) {
            $args['meta_query'] = [
                [
                    'key'   => '_stock_status',
                    'value' => $input['stock_status'],
                ],
            ];
        }

        // Featured filter
        if ( isset( $input['featured'] ) ) {
            $args['tax_query'] = $args['tax_query'] ?? [];
            $args['tax_query'][] = [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
                'operator' => $input['featured'] ? 'IN' : 'NOT IN',
            ];
        }

        // On sale filter
        if ( isset( $input['on_sale'] ) && $input['on_sale'] ) {
            $args['post__in'] = wc_get_product_ids_on_sale();
            if ( empty( $args['post__in'] ) ) {
                $args['post__in'] = [ 0 ];
            }
        }

        $query = new \WP_Query( $args );
        $products = [];

        foreach ( $query->posts as $post ) {
            $product = wc_get_product( $post->ID );
            if ( $product ) {
                $products[] = self::format_product( $product );
            }
        }

        return [
            'products'    => $products,
            'total'       => $query->found_posts,
            'total_pages' => ceil( $query->found_posts / $args['posts_per_page'] ),
            'limit'       => $args['posts_per_page'],
            'offset'      => $args['offset'],
            'has_more'    => ( $args['offset'] + count( $products ) ) < $query->found_posts,
        ];
    }

    /**
     * Get single product details
     */
    public static function get_product( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $product = null;

        if ( ! empty( $input['id'] ) ) {
            $product = wc_get_product( (int) $input['id'] );
        } elseif ( ! empty( $input['sku'] ) ) {
            $product_id = wc_get_product_id_by_sku( $input['sku'] );
            if ( $product_id ) {
                $product = wc_get_product( $product_id );
            }
        } elseif ( ! empty( $input['slug'] ) ) {
            $post = get_page_by_path( $input['slug'], OBJECT, 'product' );
            if ( $post ) {
                $product = wc_get_product( $post->ID );
            }
        }

        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        return [
            'success' => true,
            'product' => self::format_product( $product, true ),
        ];
    }

    /**
     * Create a new product
     */
    public static function create_product( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'name', 'Product name is required' );
        if ( $error ) {
            return $error;
        }

        $type = $input['type'] ?? 'simple';
        $classname = \WC_Product_Factory::get_product_classname( 0, $type );

        if ( ! $classname || ! class_exists( $classname ) ) {
            return self::errorResponse( 'invalid_product_type', 'Invalid product type: ' . $type, 400 );
        }

        try {
            $product = new $classname();

            // Basic fields
            $product->set_name( sanitize_text_field( $input['name'] ) );

            if ( isset( $input['slug'] ) ) {
                $product->set_slug( sanitize_title( $input['slug'] ) );
            }

            if ( isset( $input['description'] ) ) {
                $product->set_description( wp_kses_post( $input['description'] ) );
            }

            if ( isset( $input['short_description'] ) ) {
                $product->set_short_description( wp_kses_post( $input['short_description'] ) );
            }

            if ( isset( $input['status'] ) ) {
                $product->set_status( $input['status'] );
            }

            if ( isset( $input['featured'] ) ) {
                $product->set_featured( (bool) $input['featured'] );
            }

            if ( isset( $input['catalog_visibility'] ) ) {
                $product->set_catalog_visibility( $input['catalog_visibility'] );
            }

            // Pricing
            if ( isset( $input['regular_price'] ) ) {
                $product->set_regular_price( (string) $input['regular_price'] );
            }

            if ( isset( $input['sale_price'] ) ) {
                $product->set_sale_price( (string) $input['sale_price'] );
            }

            // SKU
            if ( isset( $input['sku'] ) ) {
                $product->set_sku( $input['sku'] );
            }

            // Stock
            if ( isset( $input['manage_stock'] ) ) {
                $product->set_manage_stock( (bool) $input['manage_stock'] );
            }

            if ( isset( $input['stock_quantity'] ) ) {
                $product->set_stock_quantity( (int) $input['stock_quantity'] );
            }

            if ( isset( $input['stock_status'] ) ) {
                $product->set_stock_status( $input['stock_status'] );
            }

            if ( isset( $input['backorders'] ) ) {
                $product->set_backorders( $input['backorders'] );
            }

            // Shipping
            if ( isset( $input['weight'] ) ) {
                $product->set_weight( (string) $input['weight'] );
            }

            if ( isset( $input['length'] ) ) {
                $product->set_length( (string) $input['length'] );
            }

            if ( isset( $input['width'] ) ) {
                $product->set_width( (string) $input['width'] );
            }

            if ( isset( $input['height'] ) ) {
                $product->set_height( (string) $input['height'] );
            }

            if ( isset( $input['shipping_class'] ) ) {
                $product->set_shipping_class_id( (int) $input['shipping_class'] );
            }

            // Tax
            if ( isset( $input['tax_status'] ) ) {
                $product->set_tax_status( $input['tax_status'] );
            }

            if ( isset( $input['tax_class'] ) ) {
                $product->set_tax_class( $input['tax_class'] );
            }

            // Virtual/Downloadable
            if ( isset( $input['virtual'] ) ) {
                $product->set_virtual( (bool) $input['virtual'] );
            }

            if ( isset( $input['downloadable'] ) ) {
                $product->set_downloadable( (bool) $input['downloadable'] );
            }

            // Categories
            if ( ! empty( $input['categories'] ) ) {
                $product->set_category_ids( array_map( 'intval', (array) $input['categories'] ) );
            }

            // Tags
            if ( ! empty( $input['tags'] ) ) {
                $product->set_tag_ids( array_map( 'intval', (array) $input['tags'] ) );
            }

            // Images
            if ( isset( $input['image_id'] ) ) {
                $product->set_image_id( (int) $input['image_id'] );
            }

            if ( ! empty( $input['gallery_image_ids'] ) ) {
                $product->set_gallery_image_ids( array_map( 'intval', (array) $input['gallery_image_ids'] ) );
            }

            // Menu order
            if ( isset( $input['menu_order'] ) ) {
                $product->set_menu_order( (int) $input['menu_order'] );
            }

            // Save
            $product_id = $product->save();

            if ( ! $product_id ) {
                return self::errorResponse( 'product_create_failed', 'Failed to create product', 500 );
            }

            // Handle meta data
            if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
                foreach ( $input['meta'] as $key => $value ) {
                    update_post_meta( $product_id, $key, $value );
                }
            }

            return [
                'success' => true,
                'message' => 'Product created successfully',
                'id'      => $product_id,
                'product' => self::format_product( wc_get_product( $product_id ), true ),
            ];

        } catch ( \Exception $e ) {
            return self::errorResponse( 'product_create_error', $e->getMessage(), 500 );
        }
    }

    /**
     * Update an existing product
     */
    public static function update_product( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $product = wc_get_product( (int) $input['id'] );
        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        try {
            // Basic fields
            if ( isset( $input['name'] ) ) {
                $product->set_name( sanitize_text_field( $input['name'] ) );
            }

            if ( isset( $input['slug'] ) ) {
                $product->set_slug( sanitize_title( $input['slug'] ) );
            }

            if ( isset( $input['description'] ) ) {
                $product->set_description( wp_kses_post( $input['description'] ) );
            }

            if ( isset( $input['short_description'] ) ) {
                $product->set_short_description( wp_kses_post( $input['short_description'] ) );
            }

            if ( isset( $input['status'] ) ) {
                $product->set_status( $input['status'] );
            }

            if ( isset( $input['featured'] ) ) {
                $product->set_featured( (bool) $input['featured'] );
            }

            if ( isset( $input['catalog_visibility'] ) ) {
                $product->set_catalog_visibility( $input['catalog_visibility'] );
            }

            // Pricing
            if ( isset( $input['regular_price'] ) ) {
                $product->set_regular_price( (string) $input['regular_price'] );
            }

            if ( isset( $input['sale_price'] ) ) {
                $product->set_sale_price( (string) $input['sale_price'] );
            }

            // SKU
            if ( isset( $input['sku'] ) ) {
                $product->set_sku( $input['sku'] );
            }

            // Stock
            if ( isset( $input['manage_stock'] ) ) {
                $product->set_manage_stock( (bool) $input['manage_stock'] );
            }

            if ( isset( $input['stock_quantity'] ) ) {
                $product->set_stock_quantity( (int) $input['stock_quantity'] );
            }

            if ( isset( $input['stock_status'] ) ) {
                $product->set_stock_status( $input['stock_status'] );
            }

            if ( isset( $input['backorders'] ) ) {
                $product->set_backorders( $input['backorders'] );
            }

            // Shipping
            if ( isset( $input['weight'] ) ) {
                $product->set_weight( (string) $input['weight'] );
            }

            if ( isset( $input['length'] ) ) {
                $product->set_length( (string) $input['length'] );
            }

            if ( isset( $input['width'] ) ) {
                $product->set_width( (string) $input['width'] );
            }

            if ( isset( $input['height'] ) ) {
                $product->set_height( (string) $input['height'] );
            }

            // Tax
            if ( isset( $input['tax_status'] ) ) {
                $product->set_tax_status( $input['tax_status'] );
            }

            if ( isset( $input['tax_class'] ) ) {
                $product->set_tax_class( $input['tax_class'] );
            }

            // Virtual/Downloadable
            if ( isset( $input['virtual'] ) ) {
                $product->set_virtual( (bool) $input['virtual'] );
            }

            if ( isset( $input['downloadable'] ) ) {
                $product->set_downloadable( (bool) $input['downloadable'] );
            }

            // Categories
            if ( isset( $input['categories'] ) ) {
                $product->set_category_ids( array_map( 'intval', (array) $input['categories'] ) );
            }

            // Tags
            if ( isset( $input['tags'] ) ) {
                $product->set_tag_ids( array_map( 'intval', (array) $input['tags'] ) );
            }

            // Images
            if ( isset( $input['image_id'] ) ) {
                $product->set_image_id( (int) $input['image_id'] );
            }

            if ( isset( $input['gallery_image_ids'] ) ) {
                $product->set_gallery_image_ids( array_map( 'intval', (array) $input['gallery_image_ids'] ) );
            }

            // Menu order
            if ( isset( $input['menu_order'] ) ) {
                $product->set_menu_order( (int) $input['menu_order'] );
            }

            // Save
            $product->save();

            // Handle meta data
            if ( ! empty( $input['meta'] ) && is_array( $input['meta'] ) ) {
                foreach ( $input['meta'] as $key => $value ) {
                    update_post_meta( $product->get_id(), $key, $value );
                }
            }

            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => self::format_product( wc_get_product( $product->get_id() ), true ),
            ];

        } catch ( \Exception $e ) {
            return self::errorResponse( 'product_update_error', $e->getMessage(), 500 );
        }
    }

    /**
     * Delete a product
     */
    public static function delete_product( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $product = wc_get_product( (int) $input['id'] );
        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        $force = (bool) ( $input['force'] ?? false );
        $product_name = $product->get_name();

        if ( $force ) {
            $result = $product->delete( true );
        } else {
            $result = wp_trash_post( $product->get_id() );
        }

        if ( ! $result ) {
            return self::errorResponse( 'product_delete_failed', 'Failed to delete product', 500 );
        }

        return [
            'success' => true,
            'message' => $force ? 'Product permanently deleted' : 'Product moved to trash',
            'id'      => (int) $input['id'],
            'name'    => $product_name,
            'trashed' => ! $force,
        ];
    }

    /**
     * Duplicate a product
     */
    public static function duplicate_product( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $product = wc_get_product( (int) $input['id'] );
        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        $duplicate = new \WC_Admin_Duplicate_Product();
        $new_product = $duplicate->product_duplicate( $product );

        if ( ! $new_product ) {
            return self::errorResponse( 'product_duplicate_failed', 'Failed to duplicate product', 500 );
        }

        // Update title if provided
        if ( ! empty( $input['new_name'] ) ) {
            $new_product->set_name( sanitize_text_field( $input['new_name'] ) );
        }

        // Set status
        $new_product->set_status( $input['status'] ?? 'draft' );

        $new_product->save();

        return [
            'success'     => true,
            'message'     => 'Product duplicated successfully',
            'id'          => $new_product->get_id(),
            'original_id' => $product->get_id(),
            'product'     => self::format_product( $new_product, true ),
        ];
    }

    /**
     * Update product stock
     */
    public static function update_stock( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $product = wc_get_product( (int) $input['id'] );
        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        $old_quantity = $product->get_stock_quantity();
        $old_status = $product->get_stock_status();

        // Update stock quantity
        if ( isset( $input['quantity'] ) ) {
            $product->set_manage_stock( true );
            $product->set_stock_quantity( (int) $input['quantity'] );
        }

        // Adjust stock (add/subtract)
        if ( isset( $input['adjust'] ) ) {
            $product->set_manage_stock( true );
            $current = $product->get_stock_quantity() ?? 0;
            $product->set_stock_quantity( $current + (int) $input['adjust'] );
        }

        // Set stock status directly
        if ( isset( $input['stock_status'] ) ) {
            $product->set_stock_status( $input['stock_status'] );
        }

        $product->save();

        return [
            'success'      => true,
            'message'      => 'Stock updated successfully',
            'id'           => $product->get_id(),
            'name'         => $product->get_name(),
            'old_quantity' => $old_quantity,
            'new_quantity' => $product->get_stock_quantity(),
            'old_status'   => $old_status,
            'new_status'   => $product->get_stock_status(),
        ];
    }

    /**
     * List product categories
     */
    public static function list_product_categories( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => (bool) ( $input['hide_empty'] ?? false ),
            'number'     => (int) ( $input['limit'] ?? 100 ),
            'offset'     => (int) ( $input['offset'] ?? 0 ),
            'orderby'    => $input['orderby'] ?? 'name',
            'order'      => $input['order'] ?? 'ASC',
        ];

        if ( ! empty( $input['parent'] ) ) {
            $args['parent'] = (int) $input['parent'];
        }

        if ( ! empty( $input['search'] ) ) {
            $args['search'] = sanitize_text_field( $input['search'] );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        $categories = [];
        foreach ( $terms as $term ) {
            $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );

            $categories[] = [
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'parent'      => $term->parent,
                'count'       => $term->count,
                'image'       => $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : null,
            ];
        }

        $total = wp_count_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => $args['hide_empty'],
        ] );

        return [
            'categories' => $categories,
            'total'      => (int) $total,
            'limit'      => $args['number'],
            'offset'     => $args['offset'],
        ];
    }

    /**
     * List product variations
     */
    public static function list_variations( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateRequiredField( $input, 'product_id', 'Product ID is required' );
        if ( $error ) {
            return $error;
        }

        $product = wc_get_product( (int) $input['product_id'] );
        if ( ! $product ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        if ( ! $product->is_type( 'variable' ) ) {
            return self::errorResponse( 'not_variable_product', 'Product is not a variable product', 400 );
        }

        $variations = [];
        $variation_ids = $product->get_children();

        foreach ( $variation_ids as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( $variation ) {
                $variations[] = [
                    'id'             => $variation->get_id(),
                    'sku'            => $variation->get_sku(),
                    'price'          => $variation->get_price(),
                    'regular_price'  => $variation->get_regular_price(),
                    'sale_price'     => $variation->get_sale_price(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'stock_status'   => $variation->get_stock_status(),
                    'attributes'     => $variation->get_attributes(),
                    'image'          => wp_get_attachment_url( $variation->get_image_id() ),
                    'status'         => $variation->get_status(),
                ];
            }
        }

        return [
            'product_id'   => $product->get_id(),
            'product_name' => $product->get_name(),
            'variations'   => $variations,
            'total'        => count( $variations ),
        ];
    }

    /**
     * Bulk product actions
     */
    public static function bulk_products( array $input ): array|\WP_Error {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return self::errorResponse( 'woocommerce_not_active', 'WooCommerce is not active', 400 );
        }

        $error = self::validateIdArray( $input, 'ids' );
        if ( $error ) {
            return $error;
        }

        $error = self::validateRequiredField( $input, 'action', 'Action is required' );
        if ( $error ) {
            return $error;
        }

        $allowed_actions = [ 'publish', 'draft', 'trash', 'delete', 'restore' ];
        if ( ! in_array( $input['action'], $allowed_actions, true ) ) {
            return self::errorResponse(
                'invalid_action',
                'Invalid action. Allowed: ' . implode( ', ', $allowed_actions ),
                400
            );
        }

        $success_ids = [];
        $failed_ids = [];

        foreach ( $input['ids'] as $id ) {
            $product = wc_get_product( (int) $id );
            if ( ! $product ) {
                $failed_ids[] = (int) $id;
                continue;
            }

            $result = false;

            switch ( $input['action'] ) {
                case 'publish':
                    $product->set_status( 'publish' );
                    $result = $product->save();
                    break;

                case 'draft':
                    $product->set_status( 'draft' );
                    $result = $product->save();
                    break;

                case 'trash':
                    $result = wp_trash_post( $product->get_id() );
                    break;

                case 'delete':
                    $result = $product->delete( true );
                    break;

                case 'restore':
                    $result = wp_untrash_post( $product->get_id() );
                    break;
            }

            if ( $result ) {
                $success_ids[] = (int) $id;
            } else {
                $failed_ids[] = (int) $id;
            }
        }

        return self::bulkResponse( $success_ids, $failed_ids, $input['action'] );
    }

    /**
     * Format product data for response
     */
    private static function format_product( \WC_Product $product, bool $detailed = false ): array {
        $data = [
            'id'               => $product->get_id(),
            'name'             => $product->get_name(),
            'slug'             => $product->get_slug(),
            'type'             => $product->get_type(),
            'status'           => $product->get_status(),
            'sku'              => $product->get_sku(),
            'price'            => $product->get_price(),
            'regular_price'    => $product->get_regular_price(),
            'sale_price'       => $product->get_sale_price(),
            'on_sale'          => $product->is_on_sale(),
            'stock_quantity'   => $product->get_stock_quantity(),
            'stock_status'     => $product->get_stock_status(),
            'manage_stock'     => $product->get_manage_stock(),
            'featured'         => $product->is_featured(),
            'virtual'          => $product->is_virtual(),
            'downloadable'     => $product->is_downloadable(),
            'permalink'        => $product->get_permalink(),
            'date_created'     => $product->get_date_created()?->format( 'Y-m-d H:i:s' ),
            'date_modified'    => $product->get_date_modified()?->format( 'Y-m-d H:i:s' ),
        ];

        // Image
        $image_id = $product->get_image_id();
        $data['image'] = $image_id ? [
            'id'  => $image_id,
            'url' => wp_get_attachment_url( $image_id ),
        ] : null;

        // Categories
        $category_ids = $product->get_category_ids();
        $data['categories'] = [];
        foreach ( $category_ids as $cat_id ) {
            $term = get_term( $cat_id, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $data['categories'][] = [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        if ( $detailed ) {
            $data['description'] = $product->get_description();
            $data['short_description'] = $product->get_short_description();
            $data['weight'] = $product->get_weight();
            $data['length'] = $product->get_length();
            $data['width'] = $product->get_width();
            $data['height'] = $product->get_height();
            $data['tax_status'] = $product->get_tax_status();
            $data['tax_class'] = $product->get_tax_class();
            $data['backorders'] = $product->get_backorders();
            $data['catalog_visibility'] = $product->get_catalog_visibility();
            $data['menu_order'] = $product->get_menu_order();
            $data['total_sales'] = $product->get_total_sales();

            // Gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            $data['gallery'] = [];
            foreach ( $gallery_ids as $gallery_id ) {
                $data['gallery'][] = [
                    'id'  => $gallery_id,
                    'url' => wp_get_attachment_url( $gallery_id ),
                ];
            }

            // Tags
            $tag_ids = $product->get_tag_ids();
            $data['tags'] = [];
            foreach ( $tag_ids as $tag_id ) {
                $term = get_term( $tag_id, 'product_tag' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $data['tags'][] = [
                        'id'   => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }
            }

            // Attributes
            $data['attributes'] = [];
            foreach ( $product->get_attributes() as $attribute ) {
                if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
                    $data['attributes'][] = [
                        'name'    => $attribute->get_name(),
                        'options' => $attribute->get_options(),
                        'visible' => $attribute->get_visible(),
                    ];
                }
            }

            // Variable product specific
            if ( $product->is_type( 'variable' ) ) {
                $data['variations_count'] = count( $product->get_children() );
                $data['price_range'] = [
                    'min' => $product->get_variation_price( 'min' ),
                    'max' => $product->get_variation_price( 'max' ),
                ];
            }

            // Reviews
            $data['average_rating'] = $product->get_average_rating();
            $data['review_count'] = $product->get_review_count();
        }

        return $data;
    }
}
