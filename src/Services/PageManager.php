<?php
/**
 * Page Manager Service - Page management operations
 *
 * This is a specialized wrapper around PostManager for pages,
 * providing page-specific functionality.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

class PageManager extends AbstractService {

    /**
     * List pages
     */
    public static function list_pages( array $input ): array {
        $input['post_type'] = 'page';
        $result = PostManager::list_posts( $input );

        // Rename for clarity
        $result['pages'] = $result['posts'];
        unset( $result['posts'] );

        return $result;
    }

    /**
     * Get single page
     */
    public static function get_page( array $input ): array|\WP_Error {
        $input['post_type'] = 'page';
        $result = PostManager::get_post( $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Rename key for clarity
        if ( isset( $result['post'] ) ) {
            $result['page'] = $result['post'];
            unset( $result['post'] );
        }

        return $result;
    }

    /**
     * Create page
     */
    public static function create_page( array $input ): array|\WP_Error {
        $input['post_type'] = 'page';
        $result = PostManager::create_post( $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Rename key for clarity
        if ( isset( $result['post'] ) ) {
            $result['page'] = $result['post'];
            unset( $result['post'] );
        }

        return $result;
    }

    /**
     * Update page
     */
    public static function update_page( array $input ): array|\WP_Error {
        // Verify it's actually a page
        if ( ! empty( $input['id'] ) ) {
            $post = self::getPostOrError( $input['id'], 'page' );
            if ( is_wp_error( $post ) ) {
                return self::errorResponse( 'not_a_page', 'The specified ID is not a page', 400 );
            }
        }

        $result = PostManager::update_post( $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Rename key for clarity
        if ( isset( $result['post'] ) ) {
            $result['page'] = $result['post'];
            unset( $result['post'] );
        }

        return $result;
    }

    /**
     * Delete page
     */
    public static function delete_page( array $input ): array|\WP_Error {
        // Verify it's actually a page
        if ( ! empty( $input['id'] ) ) {
            $input['id'] = (int) $input['id'];
            $post = self::getPostOrError( $input['id'], 'page' );
            if ( is_wp_error( $post ) ) {
                return self::errorResponse( 'not_a_page', 'The specified ID is not a page', 400 );
            }
        }

        return PostManager::delete_post( $input );
    }

    /**
     * Restore page from trash
     */
    public static function restore_page( array $input ): array|\WP_Error {
        // Verify it's actually a page
        if ( ! empty( $input['id'] ) ) {
            $post = self::getPostOrError( $input['id'], 'page' );
            if ( is_wp_error( $post ) ) {
                return self::errorResponse( 'not_a_page', 'The specified ID is not a page', 400 );
            }
        }

        $result = PostManager::restore_post( $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Rename key for clarity
        if ( isset( $result['post'] ) ) {
            $result['page'] = $result['post'];
            unset( $result['post'] );
        }

        return $result;
    }

    /**
     * Duplicate page
     */
    public static function duplicate_page( array $input ): array|\WP_Error {
        // Verify it's actually a page
        if ( ! empty( $input['id'] ) ) {
            $post = self::getPostOrError( $input['id'], 'page' );
            if ( is_wp_error( $post ) ) {
                return self::errorResponse( 'not_a_page', 'The specified ID is not a page', 400 );
            }
        }

        $result = PostManager::duplicate_post( $input );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Rename key for clarity
        if ( isset( $result['post'] ) ) {
            $result['page'] = $result['post'];
            unset( $result['post'] );
        }

        return $result;
    }

    /**
     * Get page hierarchy/tree
     */
    public static function get_hierarchy( array $input = [] ): array {
        $pages = get_pages( [
            'sort_column' => 'menu_order,post_title',
            'post_status' => $input['status'] ?? 'publish',
        ] );

        $tree = self::build_page_tree( $pages, 0 );

        return [
            'hierarchy' => $tree,
            'total'     => count( $pages ),
        ];
    }

    /**
     * Reorder pages
     */
    public static function reorder_pages( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'order', 'Order array is required' );
        if ( $error ) {
            return $error;
        }

        if ( ! is_array( $input['order'] ) ) {
            return self::errorResponse( 'invalid_order', 'Order must be an array', 400 );
        }

        $updated = [];
        $failed = [];

        foreach ( $input['order'] as $index => $page_id ) {
            $result = wp_update_post( [
                'ID'         => $page_id,
                'menu_order' => $index,
            ] );

            if ( is_wp_error( $result ) || ! $result ) {
                $failed[] = $page_id;
            } else {
                $updated[] = $page_id;
            }
        }

        return [
            'success' => count( $failed ) === 0,
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }

    /**
     * Set page as homepage
     */
    public static function set_homepage( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $page = self::getPostOrError( $input['id'], 'page' );
        if ( is_wp_error( $page ) ) {
            return $page;
        }

        // Set front page display to static page
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $input['id'] );

        return self::successResponse(
            [
                'page_id'    => $input['id'],
                'page_title' => $page->post_title,
            ],
            'Homepage set successfully'
        );
    }

    /**
     * Set page as posts page (blog)
     */
    public static function set_posts_page( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $page = self::getPostOrError( $input['id'], 'page' );
        if ( is_wp_error( $page ) ) {
            return $page;
        }

        // Ensure we have static front page
        if ( get_option( 'show_on_front' ) !== 'page' ) {
            update_option( 'show_on_front', 'page' );
        }

        update_option( 'page_for_posts', $input['id'] );

        return self::successResponse(
            [
                'page_id'    => $input['id'],
                'page_title' => $page->post_title,
            ],
            'Posts page set successfully'
        );
    }

    /**
     * Get front page settings
     */
    public static function get_front_page_settings( array $input = [] ): array {
        $show_on_front = get_option( 'show_on_front' );
        $page_on_front = (int) get_option( 'page_on_front' );
        $page_for_posts = (int) get_option( 'page_for_posts' );

        $result = [
            'display_mode' => $show_on_front, // 'posts' or 'page'
            'homepage'     => (object) [],
            'posts_page'   => (object) [],
        ];

        if ( $page_on_front ) {
            $homepage = get_post( $page_on_front );
            if ( $homepage ) {
                $result['homepage'] = [
                    'id'    => $homepage->ID,
                    'title' => $homepage->post_title,
                    'slug'  => $homepage->post_name,
                ];
            }
        }

        if ( $page_for_posts ) {
            $posts_page = get_post( $page_for_posts );
            if ( $posts_page ) {
                $result['posts_page'] = [
                    'id'    => $posts_page->ID,
                    'title' => $posts_page->post_title,
                    'slug'  => $posts_page->post_name,
                ];
            }
        }

        return $result;
    }

    /**
     * Get page templates
     */
    public static function get_templates( array $input = [] ): array {
        $templates = wp_get_theme()->get_page_templates();

        $formatted = [
            [
                'slug'  => 'default',
                'name'  => 'Default Template',
            ],
        ];

        foreach ( $templates as $slug => $name ) {
            $formatted[] = [
                'slug' => $slug,
                'name' => $name,
            ];
        }

        return [
            'templates' => $formatted,
            'total'     => count( $formatted ),
        ];
    }

    /**
     * Set page template
     */
    public static function set_template( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $page = self::getPostOrError( $input['id'], 'page' );
        if ( is_wp_error( $page ) ) {
            return $page;
        }

        $template = $input['template'] ?? 'default';

        if ( $template === 'default' ) {
            delete_post_meta( $input['id'], '_wp_page_template' );
        } else {
            update_post_meta( $input['id'], '_wp_page_template', $template );
        }

        return self::successResponse(
            [
                'page_id'  => $input['id'],
                'template' => $template,
            ],
            'Page template updated'
        );
    }

    /**
     * Build hierarchical page tree
     */
    private static function build_page_tree( array $pages, int $parent_id ): array {
        $tree = [];

        foreach ( $pages as $page ) {
            if ( (int) $page->post_parent === $parent_id ) {
                $children = self::build_page_tree( $pages, $page->ID );

                $item = [
                    'id'         => $page->ID,
                    'title'      => $page->post_title,
                    'slug'       => $page->post_name,
                    'status'     => $page->post_status,
                    'menu_order' => $page->menu_order,
                ];

                if ( ! empty( $children ) ) {
                    $item['children'] = $children;
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }
}
