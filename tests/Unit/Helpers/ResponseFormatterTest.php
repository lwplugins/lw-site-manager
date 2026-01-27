<?php
/**
 * Unit tests for ResponseFormatter helper.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Helpers
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

/**
 * Tests for ResponseFormatter helper.
 */
final class ResponseFormatterTest extends TestCase {

    // =========================================================================
    // Success Response Tests
    // =========================================================================

    /**
     * Test that success returns proper structure.
     */
    public function test_success_returns_proper_structure(): void {
        $result = ResponseFormatter::success( [ 'id' => 123 ] );

        $this->assertIsArray( $result );
        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'Operation completed successfully', $result['message'] );
        $this->assertEquals( 123, $result['id'] );
    }

    /**
     * Test that success uses custom message.
     */
    public function test_success_uses_custom_message(): void {
        $result = ResponseFormatter::success( [], 'Custom success message' );

        $this->assertEquals( 'Custom success message', $result['message'] );
    }

    /**
     * Test that success merges data correctly.
     */
    public function test_success_merges_data(): void {
        $result = ResponseFormatter::success( [
            'user' => [ 'id' => 1, 'name' => 'John' ],
            'token' => 'abc123',
        ] );

        $this->assertArrayHasKey( 'user', $result );
        $this->assertArrayHasKey( 'token', $result );
        $this->assertEquals( 'John', $result['user']['name'] );
    }

    // =========================================================================
    // Error Response Tests
    // =========================================================================

    /**
     * Test that error returns WP_Error.
     */
    public function test_error_returns_wp_error(): void {
        $result = ResponseFormatter::error( 'test_error', 'Test error message' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'test_error', $result->get_error_code() );
        $this->assertEquals( 'Test error message', $result->get_error_message() );
    }

    /**
     * Test that error includes status code.
     */
    public function test_error_includes_status_code(): void {
        $result = ResponseFormatter::error( 'not_found', 'Not found', 404 );

        $data = $result->get_error_data();
        $this->assertEquals( 404, $data['status'] );
    }

    /**
     * Test that error uses default status 400.
     */
    public function test_error_uses_default_status(): void {
        $result = ResponseFormatter::error( 'bad_request', 'Bad request' );

        $data = $result->get_error_data();
        $this->assertEquals( 400, $data['status'] );
    }

    // =========================================================================
    // List Response Tests
    // =========================================================================

    /**
     * Test that list returns proper structure.
     */
    public function test_list_returns_proper_structure(): void {
        $items = [ [ 'id' => 1 ], [ 'id' => 2 ] ];
        $result = ResponseFormatter::list( 'posts', $items, 100, 20, 0 );

        $this->assertArrayHasKey( 'posts', $result );
        $this->assertEquals( $items, $result['posts'] );
        $this->assertEquals( 100, $result['total'] );
        $this->assertEquals( 5, $result['total_pages'] );
        $this->assertEquals( 20, $result['limit'] );
        $this->assertEquals( 0, $result['offset'] );
        $this->assertTrue( $result['has_more'] );
    }

    /**
     * Test that list calculates has_more correctly.
     */
    public function test_list_calculates_has_more(): void {
        // Has more items
        $result1 = ResponseFormatter::list( 'items', [], 100, 20, 0 );
        $this->assertTrue( $result1['has_more'] );

        // No more items
        $result2 = ResponseFormatter::list( 'items', [], 100, 20, 80 );
        $this->assertFalse( $result2['has_more'] );

        // Exactly at the end
        $result3 = ResponseFormatter::list( 'items', [], 100, 20, 100 );
        $this->assertFalse( $result3['has_more'] );
    }

    /**
     * Test that list handles zero limit.
     */
    public function test_list_handles_zero_limit(): void {
        $result = ResponseFormatter::list( 'items', [], 100, 0, 0 );

        $this->assertEquals( 1, $result['total_pages'] );
    }

    /**
     * Test that list calculates total_pages correctly.
     */
    public function test_list_calculates_total_pages(): void {
        // Exact division
        $result1 = ResponseFormatter::list( 'items', [], 100, 20, 0 );
        $this->assertEquals( 5, $result1['total_pages'] );

        // With remainder
        $result2 = ResponseFormatter::list( 'items', [], 95, 20, 0 );
        $this->assertEquals( 5, $result2['total_pages'] );

        // Less than one page
        $result3 = ResponseFormatter::list( 'items', [], 5, 20, 0 );
        $this->assertEquals( 1, $result3['total_pages'] );
    }

    // =========================================================================
    // Bulk Result Tests
    // =========================================================================

    /**
     * Test that bulkResult returns success when no failures.
     */
    public function test_bulk_result_returns_success_when_no_failures(): void {
        $result = ResponseFormatter::bulkResult( [ 1, 2, 3 ], [], 'delete' );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'delete', $result['action'] );
        $this->assertEquals( 3, $result['processed'] );
        $this->assertEquals( 0, $result['failed'] );
        $this->assertEquals( 3, $result['total'] );
        $this->assertEquals( [ 1, 2, 3 ], $result['success_ids'] );
        $this->assertEmpty( $result['failed_ids'] );
    }

    /**
     * Test that bulkResult returns failure when has failures.
     */
    public function test_bulk_result_returns_failure_when_has_failures(): void {
        $result = ResponseFormatter::bulkResult( [ 1, 2 ], [ 3, 4 ], 'update' );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( 2, $result['processed'] );
        $this->assertEquals( 2, $result['failed'] );
        $this->assertEquals( 4, $result['total'] );
        $this->assertStringContainsString( '2 succeeded', $result['message'] );
        $this->assertStringContainsString( '2 failed', $result['message'] );
    }

    /**
     * Test that bulkResult handles all failures.
     */
    public function test_bulk_result_handles_all_failures(): void {
        $result = ResponseFormatter::bulkResult( [], [ 1, 2, 3 ], 'delete' );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( 0, $result['processed'] );
        $this->assertEquals( 3, $result['failed'] );
    }

    // =========================================================================
    // Entity Response Tests
    // =========================================================================

    /**
     * Test that entity returns proper structure.
     */
    public function test_entity_returns_proper_structure(): void {
        $user = [ 'id' => 1, 'name' => 'John' ];
        $result = ResponseFormatter::entity( 'user', $user );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( $user, $result['user'] );
        $this->assertArrayNotHasKey( 'message', $result );
    }

    /**
     * Test that entity includes message when provided.
     */
    public function test_entity_includes_message(): void {
        $result = ResponseFormatter::entity( 'user', [], 'User retrieved' );

        $this->assertEquals( 'User retrieved', $result['message'] );
    }

    // =========================================================================
    // Created Response Tests
    // =========================================================================

    /**
     * Test that created returns proper structure.
     */
    public function test_created_returns_proper_structure(): void {
        $post = [ 'id' => 123, 'title' => 'Test' ];
        $result = ResponseFormatter::created( 'post', $post, 123 );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'Post created successfully', $result['message'] );
        $this->assertEquals( $post, $result['post'] );
        $this->assertEquals( 123, $result['id'] );
    }

    /**
     * Test that created works without ID.
     */
    public function test_created_works_without_id(): void {
        $result = ResponseFormatter::created( 'setting', [ 'key' => 'value' ] );

        $this->assertArrayNotHasKey( 'id', $result );
    }

    // =========================================================================
    // Updated Response Tests
    // =========================================================================

    /**
     * Test that updated returns proper structure.
     */
    public function test_updated_returns_proper_structure(): void {
        $user = [ 'id' => 1, 'name' => 'Updated Name' ];
        $result = ResponseFormatter::updated( 'user', $user );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'User updated successfully', $result['message'] );
        $this->assertEquals( $user, $result['user'] );
    }

    // =========================================================================
    // Deleted Response Tests
    // =========================================================================

    /**
     * Test that deleted returns proper structure.
     */
    public function test_deleted_returns_proper_structure(): void {
        $result = ResponseFormatter::deleted( 'comment', 456 );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'Comment deleted successfully', $result['message'] );
        $this->assertEquals( 456, $result['id'] );
    }

    // =========================================================================
    // Not Found Tests
    // =========================================================================

    /**
     * Test that notFound returns WP_Error with 404.
     */
    public function test_not_found_returns_wp_error(): void {
        $result = ResponseFormatter::notFound( 'post', 999 );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'post_not_found', $result->get_error_code() );
        $this->assertStringContainsString( 'Post', $result->get_error_message() );
        $this->assertStringContainsString( '999', $result->get_error_message() );

        $data = $result->get_error_data();
        $this->assertEquals( 404, $data['status'] );
    }

    // =========================================================================
    // Permission Denied Tests
    // =========================================================================

    /**
     * Test that permissionDenied returns WP_Error with 403.
     */
    public function test_permission_denied_returns_wp_error(): void {
        $result = ResponseFormatter::permissionDenied( 'edit this post' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'permission_denied', $result->get_error_code() );
        $this->assertStringContainsString( 'edit this post', $result->get_error_message() );

        $data = $result->get_error_data();
        $this->assertEquals( 403, $data['status'] );
    }

    /**
     * Test that permissionDenied uses default action.
     */
    public function test_permission_denied_uses_default_action(): void {
        $result = ResponseFormatter::permissionDenied();

        $this->assertStringContainsString( 'perform this action', $result->get_error_message() );
    }

    // =========================================================================
    // Update Result Tests
    // =========================================================================

    /**
     * Test that updateResult returns proper structure.
     */
    public function test_update_result_returns_proper_structure(): void {
        $result = ResponseFormatter::updateResult(
            true,
            'Plugin updated',
            '1.0.0',
            '2.0.0',
            []
        );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'Plugin updated', $result['message'] );
        $this->assertEquals( '1.0.0', $result['old_version'] );
        $this->assertEquals( '2.0.0', $result['new_version'] );
        $this->assertEmpty( $result['php_errors'] );
    }

    /**
     * Test that updateResult handles failure.
     */
    public function test_update_result_handles_failure(): void {
        $errors = [ 'Error 1', 'Error 2' ];
        $result = ResponseFormatter::updateResult( false, 'Update failed', null, null, $errors );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( $errors, $result['php_errors'] );
        $this->assertArrayNotHasKey( 'old_version', $result );
        $this->assertArrayNotHasKey( 'new_version', $result );
    }

    // =========================================================================
    // Wrap Tests
    // =========================================================================

    /**
     * Test that wrap returns WP_Error unchanged.
     */
    public function test_wrap_returns_wp_error_unchanged(): void {
        $error = new \WP_Error( 'test', 'Test error' );
        $result = ResponseFormatter::wrap( $error );

        $this->assertSame( $error, $result );
    }

    /**
     * Test that wrap adds success flag if missing.
     */
    public function test_wrap_adds_success_flag(): void {
        $result = ResponseFormatter::wrap( [ 'data' => 'value' ] );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 'value', $result['data'] );
    }

    /**
     * Test that wrap preserves existing success flag.
     */
    public function test_wrap_preserves_success_flag(): void {
        $result = ResponseFormatter::wrap( [ 'success' => false, 'data' => 'value' ] );

        $this->assertFalse( $result['success'] );
    }
}
