<?php
/**
 * Unit tests for PageManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\PageManager;

/**
 * Tests for PageManager service.
 *
 * Note: PageManager is a wrapper around PostManager with page-specific functionality.
 * These unit tests focus on input validation. Integration tests cover
 * the actual page operations with a WordPress test environment.
 */
final class PageManagerTest extends TestCase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        reset_wp_filters();
        reset_wp_options();
        reset_wp_post_meta();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        reset_wp_filters();
        reset_wp_options();
        reset_wp_post_meta();
        parent::tearDown();
    }

    // =========================================================================
    // Get Page Validation Tests
    // =========================================================================

    /**
     * Test that get_page returns error when no identifier provided.
     */
    public function test_get_page_returns_error_without_identifier(): void {
        $result = PageManager::get_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Create Page Validation Tests
    // =========================================================================

    /**
     * Test that create_page returns error for missing title.
     */
    public function test_create_page_returns_error_for_missing_title(): void {
        $result = PageManager::create_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_title', $result->get_error_code() );
    }

    // =========================================================================
    // Template application (issue #17)
    // =========================================================================

    /**
     * Invoke the private apply_template() helper that create_page/update_page
     * use to honour the `template` input (create_page's full PostManager chain
     * needs a WordPress environment — covered by integration tests).
     *
     * @param int                  $page_id Page ID.
     * @param array<string, mixed> $input   Ability input.
     * @return void
     */
    private function apply_template( int $page_id, array $input ): void {
        ( new \ReflectionMethod( PageManager::class, 'apply_template' ) )
            ->invoke( null, $page_id, $input );
    }

    /**
     * Regression for issue #17: a `template` input must set _wp_page_template
     * instead of being silently dropped.
     */
    public function test_apply_template_sets_meta_from_template_input(): void {
        $this->apply_template( 42, [ 'template' => 'elementor_canvas' ] );

        $this->assertSame( 'elementor_canvas', get_post_meta( 42, '_wp_page_template', true ) );
    }

    /**
     * Without a `template` key, an existing template is left untouched (so a
     * plain page update never wipes the template).
     */
    public function test_apply_template_without_template_key_is_a_noop(): void {
        update_post_meta( 42, '_wp_page_template', 'existing_template' );

        $this->apply_template( 42, [ 'title' => 'no template key' ] );

        $this->assertSame( 'existing_template', get_post_meta( 42, '_wp_page_template', true ) );
    }

    /**
     * An explicit 'default' (or empty) template clears the custom template.
     */
    public function test_apply_template_with_default_clears_meta(): void {
        update_post_meta( 42, '_wp_page_template', 'elementor_canvas' );

        $this->apply_template( 42, [ 'template' => 'default' ] );

        $this->assertSame( '', get_post_meta( 42, '_wp_page_template', true ) );
    }

    // =========================================================================
    // Update Page Validation Tests
    // =========================================================================

    /**
     * Test that update_page returns error for missing ID.
     */
    public function test_update_page_returns_error_for_missing_id(): void {
        $result = PageManager::update_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Delete Page Validation Tests
    // =========================================================================

    /**
     * Test that delete_page returns error for missing ID.
     */
    public function test_delete_page_returns_error_for_missing_id(): void {
        $result = PageManager::delete_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Restore Page Validation Tests
    // =========================================================================

    /**
     * Test that restore_page returns error for missing ID.
     */
    public function test_restore_page_returns_error_for_missing_id(): void {
        $result = PageManager::restore_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Duplicate Page Validation Tests
    // =========================================================================

    /**
     * Test that duplicate_page returns error for missing ID.
     */
    public function test_duplicate_page_returns_error_for_missing_id(): void {
        $result = PageManager::duplicate_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Reorder Pages Validation Tests
    // =========================================================================

    /**
     * Test that reorder_pages returns error for missing order.
     */
    public function test_reorder_pages_returns_error_for_missing_order(): void {
        $result = PageManager::reorder_pages( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_order', $result->get_error_code() );
    }

    /**
     * Test that reorder_pages returns error for non-array order.
     */
    public function test_reorder_pages_returns_error_for_invalid_order(): void {
        $result = PageManager::reorder_pages( [ 'order' => 'not-an-array' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_order', $result->get_error_code() );
    }

    // =========================================================================
    // Set Homepage Validation Tests
    // =========================================================================

    /**
     * Test that set_homepage returns error for missing ID.
     */
    public function test_set_homepage_returns_error_for_missing_id(): void {
        $result = PageManager::set_homepage( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Set Posts Page Validation Tests
    // =========================================================================

    /**
     * Test that set_posts_page returns error for missing ID.
     */
    public function test_set_posts_page_returns_error_for_missing_id(): void {
        $result = PageManager::set_posts_page( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Set Template Validation Tests
    // =========================================================================

    /**
     * Test that set_template returns error for missing ID.
     */
    public function test_set_template_returns_error_for_missing_id(): void {
        $result = PageManager::set_template( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Get Front Page Settings Tests
    // =========================================================================

    /**
     * Test that get_front_page_settings returns proper structure.
     */
    public function test_get_front_page_settings_returns_structure(): void {
        $result = PageManager::get_front_page_settings();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'display_mode', $result );
        $this->assertArrayHasKey( 'homepage', $result );
        $this->assertArrayHasKey( 'posts_page', $result );
    }
}
