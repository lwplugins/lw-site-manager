<?php
/**
 * Unit tests for UserManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\UserManager;

/**
 * Tests for UserManager service.
 */
final class UserManagerTest extends TestCase {

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
    // Get User Validation Tests
    // =========================================================================

    /**
     * Test that get_user returns error when no identifier provided.
     */
    public function test_get_user_returns_error_without_identifier(): void {
        $result = UserManager::get_user( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'user_not_found', $result->get_error_code() );
    }

    // =========================================================================
    // Create User Validation Tests
    // =========================================================================

    /**
     * Test that create_user returns error for missing username.
     */
    public function test_create_user_returns_error_for_missing_username(): void {
        $result = UserManager::create_user( [ 'email' => 'test@example.com' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_username', $result->get_error_code() );
    }

    /**
     * Test that create_user returns error for missing email.
     */
    public function test_create_user_returns_error_for_missing_email(): void {
        $result = UserManager::create_user( [ 'username' => 'testuser' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_email', $result->get_error_code() );
    }

    // =========================================================================
    // Update User Validation Tests
    // =========================================================================

    /**
     * Test that update_user returns error for missing ID.
     */
    public function test_update_user_returns_error_for_missing_id(): void {
        $result = UserManager::update_user( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Delete User Validation Tests
    // =========================================================================

    /**
     * Test that delete_user returns error for missing ID.
     */
    public function test_delete_user_returns_error_for_missing_id(): void {
        $result = UserManager::delete_user( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Reset Password Validation Tests
    // =========================================================================

    /**
     * Test that reset_password returns error when no identifier provided.
     */
    public function test_reset_password_returns_error_without_identifier(): void {
        $result = UserManager::reset_password( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'user_not_found', $result->get_error_code() );
    }
}
