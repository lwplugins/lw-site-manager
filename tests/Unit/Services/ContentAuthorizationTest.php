<?php
/**
 * Object-level authorization for post, page and media abilities.
 *
 * Security regression guard. These services previously performed no per-object
 * capability check, so the registration-time primitive capability was the sole
 * gate: a Contributor (edit_posts + delete_posts) could rewrite or permanently
 * delete any post of any type, and an Author (upload_files) could delete any
 * attachment together with its files on disk.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\MediaManager;
use LightweightPlugins\SiteManager\Services\PostManager;
use PHPUnit\Framework\TestCase;

final class ContentAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        unset( $GLOBALS['wp_stub_post_type'], $GLOBALS['wp_query_last_args'] );
        parent::tearDown();
    }

    // =========================================================================
    // Posts
    // =========================================================================

    public function test_update_post_denied_without_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] ); // Contributor-equivalent.

        $result = PostManager::update_post( [ 'id' => 42, 'title' => 'Hijacked' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_delete_post_denied_without_capability_for_that_post(): void {
        grant_wp_caps( [ 'delete_posts' ] );

        $result = PostManager::delete_post( [ 'id' => 42 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_get_post_denied_without_read_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $result = PostManager::get_post( [ 'id' => 42 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    /**
     * bulk-posts must authorize every item, not just the primitive capability
     * once at registration. Previously it ran wp_delete_post( $id, true ) —
     * permanent, unrecoverable — for any ID the caller supplied.
     */
    public function test_bulk_delete_skips_posts_the_caller_may_not_delete(): void {
        grant_wp_caps( [ 'edit_posts', 'delete_posts', 'delete_post:7' ] );

        $result = PostManager::bulk_action( [ 'ids' => [ 7, 8, 9 ], 'action' => 'delete' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
        $this->assertSame( [ 7 ], $result['success_ids'], 'Only the permitted post may be deleted' );

        $failedIds = array_column( $result['failed_ids'], 'id' );
        $this->assertContains( 8, $failedIds );
        $this->assertContains( 9, $failedIds );
    }

    /**
     * list-posts defaulted post_status to 'any', which makes WP_Query skip its
     * private/protected capability mapping entirely, exposing every author's
     * drafts and private posts.
     */
    public function test_list_posts_does_not_default_to_any_status(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        PostManager::list_posts( [] );

        $this->assertNotSame(
            'any',
            $GLOBALS['wp_query_last_args']['post_status'] ?? null,
            "post_status must not default to 'any' — it bypasses WP_Query capability mapping"
        );
        $this->assertSame(
            'editable',
            $GLOBALS['wp_query_last_args']['perm'] ?? null,
            'perm must be set, otherwise WP_Query skips the status capability mapping'
        );
    }

    // =========================================================================
    // Media
    // =========================================================================

    public function test_delete_media_denied_without_capability_for_that_attachment(): void {
        $GLOBALS['wp_stub_post_type'] = 'attachment';
        grant_wp_caps( [ 'upload_files' ] ); // Author-equivalent.

        $result = MediaManager::delete_media( [ 'id' => 42 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_update_media_denied_without_capability_for_that_attachment(): void {
        $GLOBALS['wp_stub_post_type'] = 'attachment';
        grant_wp_caps( [ 'upload_files' ] );

        $result = MediaManager::update_media( [ 'id' => 42, 'title' => 'x' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }
}
