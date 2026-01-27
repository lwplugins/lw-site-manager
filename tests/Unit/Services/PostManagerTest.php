<?php
/**
 * Unit tests for PostManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\PostManager;

/**
 * Tests for PostManager service.
 *
 * Note: Most PostManager operations require full WordPress integration.
 * These unit tests focus on input validation. Integration tests cover
 * the actual post operations with a WordPress test environment.
 */
final class PostManagerTest extends TestCase {

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
    // Get Post Validation Tests
    // =========================================================================

    /**
     * Test that get_post returns error when no identifier provided.
     */
    public function test_get_post_returns_error_without_identifier(): void {
        $result = PostManager::get_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'post_not_found', $result->get_error_code() );
    }

    // =========================================================================
    // Create Post Validation Tests
    // =========================================================================

    /**
     * Test that create_post returns error for missing title.
     */
    public function test_create_post_returns_error_for_missing_title(): void {
        $result = PostManager::create_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_title', $result->get_error_code() );
    }

    /**
     * Test that create_post returns error for empty title.
     */
    public function test_create_post_returns_error_for_empty_title(): void {
        $result = PostManager::create_post( [ 'title' => '' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_title', $result->get_error_code() );
    }

    // =========================================================================
    // Update Post Validation Tests
    // =========================================================================

    /**
     * Test that update_post returns error for missing ID.
     */
    public function test_update_post_returns_error_for_missing_id(): void {
        $result = PostManager::update_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that update_post returns error for invalid ID.
     */
    public function test_update_post_returns_error_for_invalid_id(): void {
        $result = PostManager::update_post( [ 'id' => 'invalid' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Delete Post Validation Tests
    // =========================================================================

    /**
     * Test that delete_post returns error for missing ID.
     */
    public function test_delete_post_returns_error_for_missing_id(): void {
        $result = PostManager::delete_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Restore Post Validation Tests
    // =========================================================================

    /**
     * Test that restore_post returns error for missing ID.
     */
    public function test_restore_post_returns_error_for_missing_id(): void {
        $result = PostManager::restore_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Duplicate Post Validation Tests
    // =========================================================================

    /**
     * Test that duplicate_post returns error for missing ID.
     */
    public function test_duplicate_post_returns_error_for_missing_id(): void {
        $result = PostManager::duplicate_post( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Bulk Action Validation Tests
    // =========================================================================

    /**
     * Test that bulk_action returns error for missing IDs.
     */
    public function test_bulk_action_returns_error_for_missing_ids(): void {
        $result = PostManager::bulk_action( [ 'action' => 'publish' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that bulk_action returns error for empty IDs array.
     */
    public function test_bulk_action_returns_error_for_empty_ids(): void {
        $result = PostManager::bulk_action( [ 'ids' => [], 'action' => 'publish' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        // Empty array triggers 'missing_ids' because empty([]) is true in PHP
        $this->assertEquals( 'missing_ids', $result->get_error_code() );
    }

    /**
     * Test that bulk_action returns error for missing action.
     */
    public function test_bulk_action_returns_error_for_missing_action(): void {
        $result = PostManager::bulk_action( [ 'ids' => [ 1, 2, 3 ] ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_action', $result->get_error_code() );
    }

    /**
     * Test that bulk_action returns error for invalid action.
     */
    public function test_bulk_action_returns_error_for_invalid_action(): void {
        $result = PostManager::bulk_action( [
            'ids'    => [ 1, 2, 3 ],
            'action' => 'invalid_action',
        ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_action', $result->get_error_code() );
    }

    /**
     * Test that bulk_action accepts valid actions.
     *
     * @dataProvider valid_bulk_actions_provider
     */
    public function test_bulk_action_accepts_valid_action( string $action ): void {
        // This will fail because posts don't exist, but it should pass validation
        $result = PostManager::bulk_action( [
            'ids'    => [ 1 ],
            'action' => $action,
        ] );

        // The result should either be a success array or a non-validation error
        if ( is_wp_error( $result ) ) {
            // Should not be a validation error - the action is valid
            $this->assertNotEquals( 'invalid_action', $result->get_error_code() );
        } else {
            // If successful, should have bulk response structure
            $this->assertIsArray( $result );
            $this->assertArrayHasKey( 'action', $result );
            $this->assertEquals( $action, $result['action'] );
        }
    }

    /**
     * Data provider for valid bulk actions.
     *
     * @return array<array{string}>
     */
    public static function valid_bulk_actions_provider(): array {
        return [
            'publish' => [ 'publish' ],
            'draft'   => [ 'draft' ],
            'trash'   => [ 'trash' ],
            'delete'  => [ 'delete' ],
            'restore' => [ 'restore' ],
        ];
    }

    // =========================================================================
    // Set Post Terms Validation Tests
    // =========================================================================

    /**
     * Test that set_post_terms returns error for missing ID.
     */
    public function test_set_post_terms_returns_error_for_missing_id(): void {
        $result = PostManager::set_post_terms( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Get Post Terms Validation Tests
    // =========================================================================

    /**
     * Test that get_post_terms returns error for missing ID.
     */
    public function test_get_post_terms_returns_error_for_missing_id(): void {
        $result = PostManager::get_post_terms( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }
}
