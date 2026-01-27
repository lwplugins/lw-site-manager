<?php
/**
 * Unit tests for CacheManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\CacheManager;

/**
 * Tests for CacheManager service.
 */
final class CacheManagerTest extends TestCase {

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
    // Flush Cache Tests
    // =========================================================================

    /**
     * Test that flush returns proper structure.
     */
    public function test_flush_returns_structure(): void {
        $result = CacheManager::flush( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'message', $result );
        $this->assertArrayHasKey( 'flushed', $result );
        $this->assertTrue( $result['success'] );
    }

    /**
     * Test that flush includes object_cache when enabled.
     */
    public function test_flush_includes_object_cache(): void {
        $result = CacheManager::flush( [ 'object_cache' => true ] );

        $flushed = $result['flushed'] ?? [];
        $this->assertIsArray( $flushed );
        $this->assertContains( 'object_cache', $flushed );
    }

    /**
     * Test that flush skips object_cache when disabled.
     */
    public function test_flush_skips_object_cache_when_disabled(): void {
        $result = CacheManager::flush( [ 'object_cache' => false ] );

        $flushed = $result['flushed'] ?? [];
        $this->assertIsArray( $flushed );
        $this->assertNotContains( 'object_cache', $flushed );
    }

    /**
     * Test that flush always includes rewrite_rules.
     */
    public function test_flush_always_includes_rewrite_rules(): void {
        $result = CacheManager::flush( [] );

        $flushed = $result['flushed'] ?? [];
        $this->assertIsArray( $flushed );
        $this->assertContains( 'rewrite_rules', $flushed );
    }

    /**
     * Test that flush returns flushed as array.
     */
    public function test_flush_returns_flushed_array(): void {
        $result = CacheManager::flush( [] );

        $this->assertArrayHasKey( 'flushed', $result );
        $this->assertIsArray( $result['flushed'] );
    }

    // =========================================================================
    // Get Status Tests
    // =========================================================================

    /**
     * Test that get_status returns proper structure.
     */
    public function test_get_status_returns_structure(): void {
        $result = CacheManager::get_status();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'object_cache', $result );
        $this->assertArrayHasKey( 'opcache', $result );
        $this->assertArrayHasKey( 'page_cache', $result );
    }

    /**
     * Test that get_status object_cache has expected keys.
     */
    public function test_get_status_object_cache_structure(): void {
        $result = CacheManager::get_status();

        $this->assertArrayHasKey( 'enabled', $result['object_cache'] );
        $this->assertArrayHasKey( 'type', $result['object_cache'] );
    }

    /**
     * Test that get_status opcache has expected keys.
     */
    public function test_get_status_opcache_structure(): void {
        $result = CacheManager::get_status();

        $this->assertArrayHasKey( 'enabled', $result['opcache'] );
        $this->assertArrayHasKey( 'stats', $result['opcache'] );
    }

    /**
     * Test that get_status page_cache has expected keys.
     */
    public function test_get_status_page_cache_structure(): void {
        $result = CacheManager::get_status();

        $this->assertArrayHasKey( 'detected', $result['page_cache'] );
        $this->assertIsArray( $result['page_cache']['detected'] );
    }

    // =========================================================================
    // Preload Tests
    // =========================================================================

    /**
     * Test that preload returns proper structure.
     */
    public function test_preload_returns_structure(): void {
        $result = CacheManager::preload();

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertTrue( $result['success'] );
    }

    /**
     * Test that preload returns preloaded info.
     */
    public function test_preload_returns_preloaded_info(): void {
        $result = CacheManager::preload();

        // In test environment, no caching plugins are active
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'preloaded', $result );
        $this->assertIsArray( $result['preloaded'] );
    }
}
