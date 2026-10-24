<?php
/**
 * Post type and term-assignment capability checks for the post abilities.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\PostManager;
use PHPUnit\Framework\TestCase;

final class PostTypeAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
        reset_wp_terms();
        reset_wp_post_meta();
        $GLOBALS['wp_current_user_id'] = 5;
    }

    protected function tearDown(): void {
        reset_wp_caps();
        unset(
            $GLOBALS['wp_current_user_id'],
            $GLOBALS['wp_stub_post_props'],
            $GLOBALS['wp_set_object_terms_calls'],
            $GLOBALS['wp_set_post_tags_calls']
        );
        parent::tearDown();
    }

    private function assertForbidden( mixed $result ): void {
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    // =========================================================================
    // create-post
    // =========================================================================

    public function test_create_post_rejects_unregistered_post_type(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] );

        $result = PostManager::create_post( [ 'title' => 'T', 'post_type' => 'no_such_type' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'invalid_post_type', $result->get_error_code() );
    }

    public function test_create_post_requires_create_capability_of_the_type(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] ); // No edit_pages.

        $this->assertForbidden( PostManager::create_post( [ 'title' => 'T', 'post_type' => 'page' ] ) );
    }

    public function test_create_post_requires_publish_capability_to_publish(): void {
        grant_wp_caps( [ 'edit_posts' ] ); // Contributor-equivalent.

        foreach ( [ 'publish', 'future', 'private' ] as $status ) {
            $this->assertForbidden( PostManager::create_post( [ 'title' => 'T', 'status' => $status ] ) );
        }
    }

    public function test_create_post_allows_contributor_draft(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $result = PostManager::create_post( [ 'title' => 'T', 'status' => 'pending' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_create_post_for_another_author_requires_edit_others(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] );

        $this->assertForbidden( PostManager::create_post( [ 'title' => 'T', 'author' => 1 ] ) );

        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'edit_others_posts' ] );
        $this->assertNotInstanceOf( \WP_Error::class, PostManager::create_post( [ 'title' => 'T', 'author' => 1 ] ) );
    }

    public function test_create_post_with_own_author_id_is_allowed(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $this->assertNotInstanceOf( \WP_Error::class, PostManager::create_post( [ 'title' => 'T', 'author' => 5 ] ) );
    }

    public function test_create_post_term_inputs_require_assign_terms(): void {
        grant_wp_caps( [ 'create_only' ] );
        $GLOBALS['wp_stub_post_type_objects']['note'] = new \WP_Post_Type(
            'note',
            [ 'create_posts' => 'create_only', 'publish_posts' => 'x', 'edit_others_posts' => 'x' ]
        );

        try {
            foreach ( [ [ 'categories' => [ 3 ] ], [ 'tags' => [ 'news' ] ], [ 'taxonomies' => [ 'category' => [ 3 ] ] ] ] as $terms ) {
                $this->assertForbidden( PostManager::create_post( [ 'title' => 'T', 'post_type' => 'note' ] + $terms ) );
            }
            $this->assertArrayNotHasKey( 'wp_set_post_tags_calls', $GLOBALS );
        } finally {
            unset( $GLOBALS['wp_stub_post_type_objects'] );
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provide_internal_post_types(): array {
        $types = [ 'revision', 'user_request', 'oembed_cache', 'customize_changeset', 'custom_css', 'nav_menu_item', 'wp_global_styles', 'attachment' ];
        return array_combine( $types, array_map( fn( string $type ) => [ $type ], $types ) );
    }

    /**
     * @dataProvider provide_internal_post_types
     */
    public function test_create_post_rejects_internal_post_type( string $type ): void {
        grant_wp_caps( [ 'create_any' ] );
        $GLOBALS['wp_stub_post_type_objects'][ $type ] = new \WP_Post_Type(
            $type,
            [ 'create_posts' => 'create_any', 'publish_posts' => 'create_any', 'edit_others_posts' => 'create_any' ]
        );

        try {
            $result = PostManager::create_post( [ 'title' => 'T', 'post_type' => $type ] );
        } finally {
            unset( $GLOBALS['wp_stub_post_type_objects'] );
        }

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'invalid_post_type', $result->get_error_code() );
    }

    public function test_create_post_accepts_registered_content_types(): void {
        grant_wp_caps( [ 'edit_products', 'edit_posts' ] );
        $GLOBALS['wp_stub_post_type_objects']['product'] = new \WP_Post_Type(
            'product',
            [ 'create_posts' => 'edit_products', 'publish_posts' => 'publish_products', 'edit_others_posts' => 'edit_others_products' ]
        );

        try {
            $result = PostManager::create_post( [ 'title' => 'T', 'post_type' => 'product' ] );
        } finally {
            unset( $GLOBALS['wp_stub_post_type_objects'] );
        }

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // duplicate-post
    // =========================================================================

    public function test_duplicate_post_as_published_requires_publish_capability(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $this->assertForbidden( PostManager::duplicate_post( [ 'id' => 42, 'status' => 'publish' ] ) );
    }

    public function test_duplicate_post_as_draft_is_allowed_for_contributor(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $this->assertNotInstanceOf( \WP_Error::class, PostManager::duplicate_post( [ 'id' => 42 ] ) );
    }

    public function test_duplicate_post_skips_taxonomies_the_caller_may_not_assign(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );
        $GLOBALS['wp_stub_object_taxonomies'] = [ 'category', 'restricted' ];
        $GLOBALS['wp_stub_taxonomy_objects']['restricted'] = new \WP_Taxonomy( 'restricted', [ 'assign_terms' => 'assign_restricted' ] );

        try {
            PostManager::duplicate_post( [ 'id' => 42 ] );
        } finally {
            unset( $GLOBALS['wp_stub_object_taxonomies'], $GLOBALS['wp_stub_taxonomy_objects'] );
        }

        $this->assertSame( [ 'category' ], array_column( $GLOBALS['wp_set_object_terms_calls'] ?? [], 2 ) );
    }

    public function test_duplicate_post_does_not_copy_meta_keys_the_caller_may_not_write(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );
        $GLOBALS['wp_post_meta'][42] = [ 'plain' => [ 'a' ], '_plugin_state' => [ 'b' ] ];

        PostManager::duplicate_post( [ 'id' => 42 ] );

        $this->assertSame( [ 'plain' ], array_keys( $GLOBALS['wp_post_meta'][1] ?? [] ) );
    }

    public function test_duplicate_post_copies_protected_meta_for_administrator(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42', 'manage_options' ] );
        $GLOBALS['wp_post_meta'][42] = [ 'plain' => [ 'a' ], '_plugin_state' => [ 'b' ] ];

        PostManager::duplicate_post( [ 'id' => 42 ] );

        $this->assertSame( [ 'plain', '_plugin_state' ], array_keys( $GLOBALS['wp_post_meta'][1] ?? [] ) );
    }

    public function test_duplicate_post_requires_create_capability_of_the_type(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'edit_post:42' ] );
        $GLOBALS['wp_stub_post_type'] = 'page';

        try {
            $this->assertForbidden( PostManager::duplicate_post( [ 'id' => 42 ] ) );
        } finally {
            unset( $GLOBALS['wp_stub_post_type'] );
        }
    }

    // =========================================================================
    // update-post and bulk publish
    // =========================================================================

    public function test_update_post_cannot_publish_without_publish_capability(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $this->assertForbidden( PostManager::update_post( [ 'id' => 42, 'status' => 'publish' ] ) );
        $this->assertNotInstanceOf( \WP_Error::class, PostManager::update_post( [ 'id' => 42, 'status' => 'draft' ] ) );
    }

    public function test_contributor_can_move_own_draft_to_pending(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );
        $GLOBALS['wp_stub_post_props'] = [ 'post_status' => 'draft', 'post_author' => 5 ];

        $result = PostManager::update_post( [ 'id' => 42, 'status' => 'pending' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_update_post_cannot_reassign_author_without_edit_others(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $this->assertForbidden( PostManager::update_post( [ 'id' => 42, 'author' => 1 ] ) );
    }

    public function test_update_post_to_trash_requires_delete_capability(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $this->assertForbidden( PostManager::update_post( [ 'id' => 42, 'status' => 'trash' ] ) );
    }

    public function test_update_post_term_inputs_require_assign_terms(): void {
        grant_wp_caps( [ 'edit_post:42' ] ); // edit_post for this post, but no assign_terms (edit_posts).

        $this->assertForbidden( PostManager::update_post( [ 'id' => 42, 'categories' => [ 3 ] ] ) );
        $this->assertForbidden( PostManager::update_post( [ 'id' => 42, 'taxonomies' => [ 'post_tag' => [ 3 ] ] ] ) );
        $this->assertArrayNotHasKey( 'wp_set_object_terms_calls', $GLOBALS );
    }

    public function test_bulk_publish_skips_posts_without_publish_capability(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:7', 'edit_post:8' ] );

        $result = PostManager::bulk_action( [ 'ids' => [ 7, 8 ], 'action' => 'publish' ] );

        $this->assertSame( [], $result['success_ids'] );
        $this->assertSame( [ 7, 8 ], $result['failed_ids'] );
    }

    public function test_bulk_trash_and_restore_require_delete_capability(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:7', 'delete_post:8' ] );

        foreach ( [ 'trash', 'restore' ] as $action ) {
            $result = PostManager::bulk_action( [ 'ids' => [ 7, 8 ], 'action' => $action ] );
            $this->assertSame( [ 8 ], $result['success_ids'], $action );
            $this->assertSame( [ 7 ], $result['failed_ids'], $action );
        }
    }

    // =========================================================================
    // set-post-terms and restore-post
    // =========================================================================

    public function test_set_post_terms_requires_edit_post_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] );
        $GLOBALS['wp_stub_object_taxonomies'] = [ 'category' ];

        try {
            $this->assertForbidden( PostManager::set_post_terms( [ 'id' => 42, 'taxonomy' => 'category', 'terms' => [ 3 ] ] ) );
            $this->assertArrayNotHasKey( 'wp_set_object_terms_calls', $GLOBALS );
        } finally {
            unset( $GLOBALS['wp_stub_object_taxonomies'] );
        }
    }

    public function test_set_post_terms_requires_assign_terms(): void {
        grant_wp_caps( [ 'edit_post:42' ] );
        $GLOBALS['wp_stub_object_taxonomies'] = [ 'category' ];

        try {
            $this->assertForbidden( PostManager::set_post_terms( [ 'id' => 42, 'taxonomy' => 'category', 'terms' => [ 3 ] ] ) );

            grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );
            $result = PostManager::set_post_terms( [ 'id' => 42, 'taxonomy' => 'category', 'terms' => [ 3 ] ] );
            $this->assertNotInstanceOf( \WP_Error::class, $result );
        } finally {
            unset( $GLOBALS['wp_stub_object_taxonomies'] );
        }
    }

    public function test_restore_post_requires_delete_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );
        $GLOBALS['wp_stub_post_props'] = [ 'post_status' => 'trash' ];

        $this->assertForbidden( PostManager::restore_post( [ 'id' => 42 ] ) );

        grant_wp_caps( [ 'edit_posts', 'delete_post:42' ] );
        $this->assertNotInstanceOf( \WP_Error::class, PostManager::restore_post( [ 'id' => 42 ] ) );
    }
}
