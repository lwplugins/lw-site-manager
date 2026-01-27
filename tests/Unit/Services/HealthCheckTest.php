<?php
/**
 * Unit tests for HealthCheck service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\HealthCheck;

/**
 * Tests for HealthCheck service.
 */
final class HealthCheckTest extends TestCase {

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
    // Run Check Tests (Integration - require actual filesystem/disk access)
    // =========================================================================

    /**
     * Test that run_check returns proper structure.
     *
     * @group integration
     */
    public function test_run_check_returns_structure(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check returns valid status.
     *
     * @group integration
     */
    public function test_run_check_returns_valid_status(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check score is between 0 and 100.
     *
     * @group integration
     */
    public function test_run_check_score_is_valid(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check issues is an array.
     *
     * @group integration
     */
    public function test_run_check_issues_is_array(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check has disk_usage info.
     *
     * @group integration
     */
    public function test_run_check_has_disk_usage(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check has memory info.
     *
     * @group integration
     */
    public function test_run_check_has_memory_info(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check has server info.
     *
     * @group integration
     */
    public function test_run_check_has_server_info(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    /**
     * Test that run_check has paths info.
     *
     * @group integration
     */
    public function test_run_check_has_paths_info(): void {
        $this->markTestSkipped( 'Requires actual filesystem access (integration test)' );
    }

    // =========================================================================
    // Get Error Log Tests
    // =========================================================================

    /**
     * Test that get_error_log returns proper structure.
     */
    public function test_get_error_log_returns_structure(): void {
        $result = HealthCheck::get_error_log( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'errors', $result );
        $this->assertArrayHasKey( 'total', $result );
    }

    /**
     * Test that get_error_log errors is an array.
     */
    public function test_get_error_log_errors_is_array(): void {
        $result = HealthCheck::get_error_log( [] );

        $this->assertIsArray( $result['errors'] );
    }

    /**
     * Test that get_error_log total is an integer.
     */
    public function test_get_error_log_total_is_integer(): void {
        $result = HealthCheck::get_error_log( [] );

        $this->assertIsInt( $result['total'] );
    }

    /**
     * Test that get_error_log accepts lines parameter.
     */
    public function test_get_error_log_accepts_lines_parameter(): void {
        $result = HealthCheck::get_error_log( [ 'lines' => 50 ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'errors', $result );
    }

    /**
     * Test that get_error_log accepts filter parameter.
     */
    public function test_get_error_log_accepts_filter_parameter(): void {
        $result = HealthCheck::get_error_log( [ 'filter' => 'Fatal' ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'errors', $result );
    }
}
