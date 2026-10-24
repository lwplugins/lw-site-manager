<?php
/**
 * Per-page capability checks for reorder-pages and set-page-template.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\PageManager;
use PHPUnit\Framework\TestCase;

final class PageAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
        reset_wp_post_meta();
        $GLOBALS['wp_stub_post_type'] = 'page';
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_post_meta();
        unset( $GLOBALS['wp_stub_post_type'] );
        parent::tearDown();
    }

    public function test_reorder_pages_skips_pages_the_caller_may_not_edit(): void {
        grant_wp_caps( [ 'edit_pages', 'edit_post:3' ] );

        $result = PageManager::reorder_pages( [ 'order' => [ 3, 4 ] ] );

        $this->assertSame( [ 3 ], $result['updated'] );
        $this->assertSame( [ 4 ], $result['failed'] );
        $this->assertFalse( $result['success'] );
    }

    public function test_reorder_pages_skips_ids_that_are_not_pages(): void {
        grant_wp_caps( [ 'edit_pages', 'edit_post:3' ] );
        $GLOBALS['wp_stub_post_type'] = 'post';

        $result = PageManager::reorder_pages( [ 'order' => [ 3 ] ] );

        $this->assertSame( [], $result['updated'] );
        $this->assertSame( [ 3 ], $result['failed'] );
    }

    public function test_set_template_requires_edit_post_for_that_page(): void {
        grant_wp_caps( [ 'edit_pages' ] );

        $result = PageManager::set_template( [ 'id' => 3, 'template' => 'full-width.php' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
        $this->assertSame( '', get_post_meta( 3, '_wp_page_template', true ) );
    }

    public function test_set_template_allowed_with_edit_post(): void {
        grant_wp_caps( [ 'edit_pages', 'edit_post:3' ] );

        $result = PageManager::set_template( [ 'id' => 3, 'template' => 'full-width.php' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'full-width.php', get_post_meta( 3, '_wp_page_template', true ) );
    }
}
