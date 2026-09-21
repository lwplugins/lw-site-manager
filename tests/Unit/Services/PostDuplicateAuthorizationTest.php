<?php
/**
 * duplicate-post: source authorization and exact meta copy.
 *
 * Security regression guard. duplicate_post never checked rights on the
 * source post, so an Author could copy (and publish) someone else's private
 * post. It also copied meta with add_post_meta() without wp_slash(), so core
 * unslashed every stored key and value a second time: a key stored as
 * "\_lw_seo_markdown_content" (which the key policy allows) became the real
 * protected "_lw_seo_markdown_content" on the copy, and backslashes in
 * values (JSON, paths) were lost.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\PostManager;
use PHPUnit\Framework\TestCase;

final class PostDuplicateAuthorizationTest extends TestCase {

    /**
     * ID the wp_insert_post() stub gives the copy.
     */
    private const COPY_ID = 1;

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_post_meta();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_post_meta();
        parent::tearDown();
    }

    public function test_duplicate_requires_edit_rights_on_the_source(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] ); // No rights on post 42.

        $result = PostManager::duplicate_post( [ 'id' => 42 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_duplicate_copies_meta_keys_exactly(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'edit_post:42' ] );
        $GLOBALS['wp_post_meta'][42] = [ '\\_lw_seo_markdown_content' => [ '# x' ] ];

        PostManager::duplicate_post( [ 'id' => 42 ] );

        $copied = array_keys( $GLOBALS['wp_post_meta'][ self::COPY_ID ] ?? [] );
        $this->assertSame( [ '\\_lw_seo_markdown_content' ], $copied );
    }

    public function test_duplicate_keeps_backslashes_in_meta_values(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'edit_post:42' ] );
        $GLOBALS['wp_post_meta'][42] = [ 'config' => [ '{"path":"C:\\\\data"}' ] ];

        PostManager::duplicate_post( [ 'id' => 42 ] );

        $this->assertSame( [ '{"path":"C:\\\\data"}' ], $GLOBALS['wp_post_meta'][ self::COPY_ID ]['config'] ?? null );
    }
}
