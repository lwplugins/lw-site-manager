<?php
/**
 * Unit tests for TaxonomyManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\TaxonomyManager;

/**
 * Tests for TaxonomyManager service.
 */
final class TaxonomyManagerTest extends TestCase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        reset_wp_filters();
        reset_wp_options();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        reset_wp_filters();
        reset_wp_options();
        parent::tearDown();
    }

    // =========================================================================
    // Get Term Validation Tests
    // =========================================================================

    /**
     * Test that get_term returns error for missing ID.
     */
    public function test_get_term_returns_error_for_missing_id(): void {
        $result = TaxonomyManager::get_term( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }

    // =========================================================================
    // Create Term Validation Tests
    // =========================================================================

    /**
     * Test that create_term returns error for missing name.
     */
    public function test_create_term_returns_error_for_missing_name(): void {
        $result = TaxonomyManager::create_term( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_name', $result->get_error_code() );
    }

    // =========================================================================
    // Update Term Validation Tests
    // =========================================================================

    /**
     * Test that update_term returns error for missing ID.
     */
    public function test_update_term_returns_error_for_missing_id(): void {
        $result = TaxonomyManager::update_term( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }

    // =========================================================================
    // Delete Term Validation Tests
    // =========================================================================

    /**
     * Test that delete_term returns error for missing ID.
     */
    public function test_delete_term_returns_error_for_missing_id(): void {
        $result = TaxonomyManager::delete_term( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }
}
