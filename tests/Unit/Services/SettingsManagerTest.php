<?php
/**
 * Unit tests for SettingsManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\SettingsManager;

/**
 * Tests for SettingsManager service.
 */
final class SettingsManagerTest extends TestCase {

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
    // Get General Settings Tests
    // =========================================================================

    /**
     * Test that get_general_settings returns proper structure.
     */
    public function test_get_general_settings_returns_structure(): void {
        $result = SettingsManager::get_general_settings( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'settings', $result );
        $this->assertTrue( $result['success'] );
    }

    /**
     * Test that get_general_settings returns expected keys.
     */
    public function test_get_general_settings_has_expected_keys(): void {
        $result = SettingsManager::get_general_settings( [] );

        $expected_keys = [
            'blogname',
            'blogdescription',
            'siteurl',
            'home',
            'admin_email',
            'users_can_register',
            'default_role',
            'timezone_string',
            'date_format',
            'time_format',
            'start_of_week',
            'WPLANG',
            'site_language',
            'available_roles',
        ];

        foreach ( $expected_keys as $key ) {
            $this->assertArrayHasKey( $key, $result['settings'], "Missing key: $key" );
        }
    }

    // =========================================================================
    // Update General Settings Tests
    // =========================================================================

    /**
     * Test that update_general_settings validates email.
     */
    public function test_update_general_settings_validates_email(): void {
        $result = SettingsManager::update_general_settings( [
            'admin_email' => 'invalid-email',
        ] );

        // When all updates fail, returns WP_Error
        // When some updates succeed but email fails, returns array with 'failed' key
        if ( is_wp_error( $result ) ) {
            $this->assertEquals( 'update_failed', $result->get_error_code() );
        } else {
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'failed', $result );
        }
    }

    /**
     * Test that update_general_settings validates role.
     */
    public function test_update_general_settings_validates_role(): void {
        $result = SettingsManager::update_general_settings( [
            'default_role' => 'nonexistent_role',
        ] );

        // When all updates fail, returns WP_Error
        if ( is_wp_error( $result ) ) {
            $this->assertEquals( 'update_failed', $result->get_error_code() );
        } else {
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'failed', $result );
        }
    }

    /**
     * Test that update_general_settings clamps start_of_week.
     */
    public function test_update_general_settings_clamps_start_of_week(): void {
        // Set option to see if value gets clamped
        $result = SettingsManager::update_general_settings( [
            'start_of_week' => 10,
        ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
    }

    // =========================================================================
    // Get Reading Settings Tests
    // =========================================================================

    /**
     * Test that get_reading_settings returns proper structure.
     */
    public function test_get_reading_settings_returns_structure(): void {
        $result = SettingsManager::get_reading_settings( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'settings', $result );
        $this->assertTrue( $result['success'] );
    }

    /**
     * Test that get_reading_settings returns expected keys.
     */
    public function test_get_reading_settings_has_expected_keys(): void {
        $result = SettingsManager::get_reading_settings( [] );

        $expected_keys = [
            'posts_per_page',
            'posts_per_rss',
            'rss_use_excerpt',
            'show_on_front',
            'page_on_front',
            'page_for_posts',
            'blog_public',
        ];

        foreach ( $expected_keys as $key ) {
            $this->assertArrayHasKey( $key, $result['settings'], "Missing key: $key" );
        }
    }

    // =========================================================================
    // Update Reading Settings Tests
    // =========================================================================

    /**
     * Test that update_reading_settings validates show_on_front.
     */
    public function test_update_reading_settings_validates_show_on_front(): void {
        $result = SettingsManager::update_reading_settings( [
            'show_on_front' => 'invalid',
        ] );

        // When all updates fail, returns WP_Error
        if ( is_wp_error( $result ) ) {
            $this->assertEquals( 'update_failed', $result->get_error_code() );
        } else {
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'failed', $result );
            $failed_keys = array_column( $result['failed'], 'key' );
            $this->assertContains( 'show_on_front', $failed_keys );
        }
    }

    /**
     * Test that update_reading_settings accepts valid show_on_front values.
     */
    public function test_update_reading_settings_accepts_posts(): void {
        $result = SettingsManager::update_reading_settings( [
            'show_on_front' => 'posts',
        ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
    }

    /**
     * Test that update_reading_settings clamps posts_per_page.
     */
    public function test_update_reading_settings_clamps_posts_per_page(): void {
        $result = SettingsManager::update_reading_settings( [
            'posts_per_page' => 500,
        ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
    }

    // =========================================================================
    // Get Discussion Settings Tests
    // =========================================================================

    /**
     * Test that get_discussion_settings returns proper structure.
     */
    public function test_get_discussion_settings_returns_structure(): void {
        $result = SettingsManager::get_discussion_settings( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'settings', $result );
        $this->assertTrue( $result['success'] );
    }

    // =========================================================================
    // Update Discussion Settings Tests
    // =========================================================================

    /**
     * Test that update_discussion_settings validates comment_order.
     */
    public function test_update_discussion_settings_validates_comment_order(): void {
        $result = SettingsManager::update_discussion_settings( [
            'comment_order' => 'invalid',
        ] );

        // When all updates fail, returns WP_Error
        // When some succeed, returns array with 'failed' key
        if ( is_wp_error( $result ) ) {
            $this->assertEquals( 'update_failed', $result->get_error_code() );
        } else {
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'failed', $result );
            $failed_keys = array_column( $result['failed'], 'key' );
            $this->assertContains( 'comment_order', $failed_keys );
        }
    }

    /**
     * Test that update_discussion_settings validates default_comments_page.
     */
    public function test_update_discussion_settings_validates_default_comments_page(): void {
        $result = SettingsManager::update_discussion_settings( [
            'default_comments_page' => 'invalid',
        ] );

        // When all updates fail, returns WP_Error
        if ( is_wp_error( $result ) ) {
            $this->assertEquals( 'update_failed', $result->get_error_code() );
        } else {
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'failed', $result );
            $failed_keys = array_column( $result['failed'], 'key' );
            $this->assertContains( 'default_comments_page', $failed_keys );
        }
    }

    /**
     * Test that update_discussion_settings accepts valid comment_order.
     */
    public function test_update_discussion_settings_accepts_valid_order(): void {
        $result = SettingsManager::update_discussion_settings( [
            'comment_order' => 'asc',
        ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
    }

    // =========================================================================
    // Get Permalink Settings Tests
    // =========================================================================

    /**
     * Test that get_permalink_settings returns proper structure.
     */
    public function test_get_permalink_settings_returns_structure(): void {
        $result = SettingsManager::get_permalink_settings( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'settings', $result );
        $this->assertTrue( $result['success'] );
    }

    /**
     * Test that get_permalink_settings includes common structures.
     */
    public function test_get_permalink_settings_has_common_structures(): void {
        $result = SettingsManager::get_permalink_settings( [] );

        $this->assertArrayHasKey( 'common_structures', $result['settings'] );
        $this->assertIsArray( $result['settings']['common_structures'] );
        $this->assertArrayHasKey( 'plain', $result['settings']['common_structures'] );
        $this->assertArrayHasKey( 'post_name', $result['settings']['common_structures'] );
    }

    // =========================================================================
    // Update Permalink Settings Tests
    // =========================================================================

    /**
     * Test that update_permalink_settings returns proper structure.
     */
    public function test_update_permalink_settings_returns_structure(): void {
        $result = SettingsManager::update_permalink_settings( [
            'permalink_structure' => '/%postname%/',
        ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'updated', $result );
        $this->assertArrayHasKey( 'failed', $result );
    }
}
