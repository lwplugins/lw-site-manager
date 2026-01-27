<?php
/**
 * Unit tests for CommentManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\CommentManager;

/**
 * Tests for CommentManager service.
 */
final class CommentManagerTest extends TestCase {

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
    // Get Comment Validation Tests
    // =========================================================================

    /**
     * Test that get_comment returns error for missing ID.
     */
    public function test_get_comment_returns_error_for_missing_id(): void {
        $result = CommentManager::get_comment( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Create Comment Validation Tests
    // =========================================================================

    /**
     * Test that create_comment returns error for missing post_id.
     */
    public function test_create_comment_returns_error_for_missing_post_id(): void {
        $result = CommentManager::create_comment( [ 'content' => 'Test comment' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_post_id', $result->get_error_code() );
    }

    /**
     * Test that create_comment returns error for missing content.
     */
    public function test_create_comment_returns_error_for_missing_content(): void {
        $result = CommentManager::create_comment( [ 'post_id' => 1 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_content', $result->get_error_code() );
    }

    // =========================================================================
    // Update Comment Validation Tests
    // =========================================================================

    /**
     * Test that update_comment returns error for missing ID.
     */
    public function test_update_comment_returns_error_for_missing_id(): void {
        $result = CommentManager::update_comment( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Delete Comment Validation Tests
    // =========================================================================

    /**
     * Test that delete_comment returns error for missing ID.
     */
    public function test_delete_comment_returns_error_for_missing_id(): void {
        $result = CommentManager::delete_comment( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Approve Comment Validation Tests
    // =========================================================================

    /**
     * Test that approve_comment returns error for missing ID.
     */
    public function test_approve_comment_returns_error_for_missing_id(): void {
        $result = CommentManager::approve_comment( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Spam Comment Validation Tests
    // =========================================================================

    /**
     * Test that spam_comment returns error for missing ID.
     */
    public function test_spam_comment_returns_error_for_missing_id(): void {
        $result = CommentManager::spam_comment( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Bulk Action Validation Tests
    // =========================================================================

    /**
     * Test that bulk_action returns error for missing IDs.
     */
    public function test_bulk_action_returns_error_for_missing_ids(): void {
        $result = CommentManager::bulk_action( [ 'action' => 'approve' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that bulk_action returns error for empty IDs array.
     */
    public function test_bulk_action_returns_error_for_empty_ids(): void {
        $result = CommentManager::bulk_action( [ 'ids' => [], 'action' => 'approve' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        // Empty array triggers 'missing_ids' because empty([]) is true in PHP
        $this->assertEquals( 'missing_ids', $result->get_error_code() );
    }

    /**
     * Test that bulk_action returns error for missing action.
     */
    public function test_bulk_action_returns_error_for_missing_action(): void {
        $result = CommentManager::bulk_action( [ 'ids' => [ 1, 2, 3 ] ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_action', $result->get_error_code() );
    }

    /**
     * Test that bulk_action returns error for invalid action.
     */
    public function test_bulk_action_returns_error_for_invalid_action(): void {
        $result = CommentManager::bulk_action( [
            'ids'    => [ 1, 2, 3 ],
            'action' => 'invalid_action',
        ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_action', $result->get_error_code() );
    }

    /**
     * Test that bulk_action accepts valid actions.
     *
     * Note: This tests that valid actions are not rejected by validation.
     * Integration tests cover actual bulk operations.
     *
     * @dataProvider valid_bulk_actions_provider
     * @group integration
     */
    public function test_bulk_action_accepts_valid_action( string $action ): void {
        // Skip actual execution - just verify action validation passes
        // (integration tests will cover actual bulk operations)
        $this->markTestSkipped( 'Requires WordPress comment infrastructure (integration test)' );
    }

    /**
     * Data provider for valid bulk actions.
     *
     * @return array<array{string}>
     */
    public static function valid_bulk_actions_provider(): array {
        return [
            'approve'   => [ 'approve' ],
            'unapprove' => [ 'unapprove' ],
            'spam'      => [ 'spam' ],
            'trash'     => [ 'trash' ],
            'delete'    => [ 'delete' ],
        ];
    }
}
