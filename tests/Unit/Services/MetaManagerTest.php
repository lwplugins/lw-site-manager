<?php
/**
 * Unit tests for MetaManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\MetaManager;

/**
 * Tests for MetaManager service.
 */
final class MetaManagerTest extends TestCase {

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
    // Post Meta Validation Tests
    // =========================================================================

    /**
     * Test that get_post_meta returns error for missing post_id.
     */
    public function test_get_post_meta_returns_error_for_missing_post_id(): void {
        $result = MetaManager::get_post_meta( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_post_id', $result->get_error_code() );
    }

    /**
     * Test that set_post_meta returns error for missing post_id.
     */
    public function test_set_post_meta_returns_error_for_missing_post_id(): void {
        $result = MetaManager::set_post_meta( [ 'key' => 'test', 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_post_id', $result->get_error_code() );
    }

    /**
     * Test that set_post_meta returns error for missing key.
     */
    public function test_set_post_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::set_post_meta( [ 'post_id' => 1, 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }

    /**
     * Test that set_post_meta returns error for missing value.
     */
    public function test_set_post_meta_returns_error_for_missing_value(): void {
        $result = MetaManager::set_post_meta( [ 'post_id' => 1, 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_value', $result->get_error_code() );
    }

    /**
     * Test that delete_post_meta returns error for missing post_id.
     */
    public function test_delete_post_meta_returns_error_for_missing_post_id(): void {
        $result = MetaManager::delete_post_meta( [ 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_post_id', $result->get_error_code() );
    }

    /**
     * Test that delete_post_meta returns error for missing key.
     */
    public function test_delete_post_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::delete_post_meta( [ 'post_id' => 1 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }

    // =========================================================================
    // User Meta Validation Tests
    // =========================================================================

    /**
     * Test that get_user_meta returns error for missing user_id.
     */
    public function test_get_user_meta_returns_error_for_missing_user_id(): void {
        $result = MetaManager::get_user_meta( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_user_id', $result->get_error_code() );
    }

    /**
     * Test that set_user_meta returns error for missing user_id.
     */
    public function test_set_user_meta_returns_error_for_missing_user_id(): void {
        $result = MetaManager::set_user_meta( [ 'key' => 'test', 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_user_id', $result->get_error_code() );
    }

    /**
     * Test that set_user_meta returns error for missing key.
     */
    public function test_set_user_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::set_user_meta( [ 'user_id' => 1, 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }

    /**
     * Test that set_user_meta returns error for missing value.
     */
    public function test_set_user_meta_returns_error_for_missing_value(): void {
        $result = MetaManager::set_user_meta( [ 'user_id' => 1, 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_value', $result->get_error_code() );
    }

    /**
     * Test that delete_user_meta returns error for missing user_id.
     */
    public function test_delete_user_meta_returns_error_for_missing_user_id(): void {
        $result = MetaManager::delete_user_meta( [ 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_user_id', $result->get_error_code() );
    }

    /**
     * Test that delete_user_meta returns error for missing key.
     */
    public function test_delete_user_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::delete_user_meta( [ 'user_id' => 1 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }

    // =========================================================================
    // Term Meta Validation Tests
    // =========================================================================

    /**
     * Test that get_term_meta returns error for missing term_id.
     */
    public function test_get_term_meta_returns_error_for_missing_term_id(): void {
        $result = MetaManager::get_term_meta( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_term_id', $result->get_error_code() );
    }

    /**
     * Test that set_term_meta returns error for missing term_id.
     */
    public function test_set_term_meta_returns_error_for_missing_term_id(): void {
        $result = MetaManager::set_term_meta( [ 'key' => 'test', 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_term_id', $result->get_error_code() );
    }

    /**
     * Test that set_term_meta returns error for missing key.
     */
    public function test_set_term_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::set_term_meta( [ 'term_id' => 1, 'value' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }

    /**
     * Test that set_term_meta returns error for missing value.
     */
    public function test_set_term_meta_returns_error_for_missing_value(): void {
        $result = MetaManager::set_term_meta( [ 'term_id' => 1, 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_value', $result->get_error_code() );
    }

    /**
     * Test that delete_term_meta returns error for missing term_id.
     */
    public function test_delete_term_meta_returns_error_for_missing_term_id(): void {
        $result = MetaManager::delete_term_meta( [ 'key' => 'test' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_term_id', $result->get_error_code() );
    }

    /**
     * Test that delete_term_meta returns error for missing key.
     */
    public function test_delete_term_meta_returns_error_for_missing_key(): void {
        $result = MetaManager::delete_term_meta( [ 'term_id' => 1 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_key', $result->get_error_code() );
    }
}
