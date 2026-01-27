<?php
/**
 * Unit tests for PaginationHelper.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Helpers
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Helpers\PaginationHelper;

/**
 * Tests for PaginationHelper.
 */
final class PaginationHelperTest extends TestCase {

    // =========================================================================
    // Constants Tests
    // =========================================================================

    /**
     * Test default constant values.
     */
    public function test_default_constants(): void {
        $this->assertEquals( 20, PaginationHelper::DEFAULT_LIMIT );
        $this->assertEquals( 0, PaginationHelper::DEFAULT_OFFSET );
        $this->assertEquals( 'DESC', PaginationHelper::DEFAULT_ORDER );
    }

    // =========================================================================
    // ExtractArgs Tests
    // =========================================================================

    /**
     * Test that extractArgs returns defaults when no input.
     */
    public function test_extract_args_returns_defaults(): void {
        $result = PaginationHelper::extractArgs( [] );

        $this->assertEquals( 20, $result['limit'] );
        $this->assertEquals( 0, $result['offset'] );
        $this->assertEquals( 'date', $result['orderby'] );
        $this->assertEquals( 'DESC', $result['order'] );
    }

    /**
     * Test that extractArgs extracts provided values.
     */
    public function test_extract_args_extracts_values(): void {
        $result = PaginationHelper::extractArgs( [
            'limit'   => 50,
            'offset'  => 100,
            'orderby' => 'title',
            'order'   => 'asc',
        ] );

        $this->assertEquals( 50, $result['limit'] );
        $this->assertEquals( 100, $result['offset'] );
        $this->assertEquals( 'title', $result['orderby'] );
        $this->assertEquals( 'ASC', $result['order'] );
    }

    /**
     * Test that extractArgs uses custom default limit.
     */
    public function test_extract_args_uses_custom_default_limit(): void {
        $result = PaginationHelper::extractArgs( [], 100 );

        $this->assertEquals( 100, $result['limit'] );
    }

    /**
     * Test that extractArgs converts order to uppercase.
     */
    public function test_extract_args_converts_order_to_uppercase(): void {
        $result = PaginationHelper::extractArgs( [ 'order' => 'asc' ] );
        $this->assertEquals( 'ASC', $result['order'] );

        $result = PaginationHelper::extractArgs( [ 'order' => 'Desc' ] );
        $this->assertEquals( 'DESC', $result['order'] );
    }

    /**
     * Test that extractArgs casts types correctly.
     */
    public function test_extract_args_casts_types(): void {
        $result = PaginationHelper::extractArgs( [
            'limit'  => '50',
            'offset' => '25',
        ] );

        $this->assertIsInt( $result['limit'] );
        $this->assertIsInt( $result['offset'] );
        $this->assertEquals( 50, $result['limit'] );
        $this->assertEquals( 25, $result['offset'] );
    }

    // =========================================================================
    // FormatResponse Tests
    // =========================================================================

    /**
     * Test that formatResponse returns proper structure.
     */
    public function test_format_response_returns_proper_structure(): void {
        $result = PaginationHelper::formatResponse( 100, 20, 0 );

        $this->assertArrayHasKey( 'total', $result );
        $this->assertArrayHasKey( 'total_pages', $result );
        $this->assertArrayHasKey( 'limit', $result );
        $this->assertArrayHasKey( 'offset', $result );
        $this->assertArrayHasKey( 'has_more', $result );
    }

    /**
     * Test that formatResponse calculates total_pages correctly.
     */
    public function test_format_response_calculates_total_pages(): void {
        // Exact division
        $result1 = PaginationHelper::formatResponse( 100, 20, 0 );
        $this->assertEquals( 5, $result1['total_pages'] );

        // With remainder
        $result2 = PaginationHelper::formatResponse( 105, 20, 0 );
        $this->assertEquals( 6, $result2['total_pages'] );

        // Single page
        $result3 = PaginationHelper::formatResponse( 15, 20, 0 );
        $this->assertEquals( 1, $result3['total_pages'] );
    }

    /**
     * Test that formatResponse handles zero limit.
     */
    public function test_format_response_handles_zero_limit(): void {
        $result = PaginationHelper::formatResponse( 100, 0, 0 );

        $this->assertEquals( 1, $result['total_pages'] );
    }

    /**
     * Test that formatResponse calculates has_more correctly.
     */
    public function test_format_response_calculates_has_more(): void {
        // Has more
        $result1 = PaginationHelper::formatResponse( 100, 20, 0 );
        $this->assertTrue( $result1['has_more'] );

        // No more
        $result2 = PaginationHelper::formatResponse( 100, 20, 80 );
        $this->assertFalse( $result2['has_more'] );

        // Exactly at end
        $result3 = PaginationHelper::formatResponse( 100, 20, 100 );
        $this->assertFalse( $result3['has_more'] );
    }

    // =========================================================================
    // ApplyToWPQuery Tests
    // =========================================================================

    /**
     * Test that applyToWPQuery modifies args.
     */
    public function test_apply_to_wp_query_modifies_args(): void {
        $args = [];
        PaginationHelper::applyToWPQuery( $args, [
            'limit'   => 30,
            'offset'  => 10,
            'orderby' => 'title',
            'order'   => 'asc',
        ] );

        $this->assertEquals( 30, $args['posts_per_page'] );
        $this->assertEquals( 10, $args['offset'] );
        $this->assertEquals( 'title', $args['orderby'] );
        $this->assertEquals( 'ASC', $args['order'] );
    }

    /**
     * Test that applyToWPQuery uses defaults.
     */
    public function test_apply_to_wp_query_uses_defaults(): void {
        $args = [];
        PaginationHelper::applyToWPQuery( $args, [] );

        $this->assertEquals( 20, $args['posts_per_page'] );
        $this->assertEquals( 0, $args['offset'] );
        $this->assertEquals( 'date', $args['orderby'] );
        $this->assertEquals( 'DESC', $args['order'] );
    }

    /**
     * Test that applyToWPQuery preserves existing args.
     */
    public function test_apply_to_wp_query_preserves_existing(): void {
        $args = [
            'post_type'   => 'post',
            'post_status' => 'publish',
        ];
        PaginationHelper::applyToWPQuery( $args, [] );

        $this->assertEquals( 'post', $args['post_type'] );
        $this->assertEquals( 'publish', $args['post_status'] );
        $this->assertArrayHasKey( 'posts_per_page', $args );
    }

    // =========================================================================
    // ApplyToUserQuery Tests
    // =========================================================================

    /**
     * Test that applyToUserQuery modifies args.
     */
    public function test_apply_to_user_query_modifies_args(): void {
        $args = [];
        PaginationHelper::applyToUserQuery( $args, [
            'limit'   => 25,
            'offset'  => 50,
            'orderby' => 'display_name',
            'order'   => 'asc',
        ] );

        $this->assertEquals( 25, $args['number'] );
        $this->assertEquals( 50, $args['offset'] );
        $this->assertEquals( 'display_name', $args['orderby'] );
        $this->assertEquals( 'ASC', $args['order'] );
    }

    /**
     * Test that applyToUserQuery uses default limit of 50.
     */
    public function test_apply_to_user_query_default_limit(): void {
        $args = [];
        PaginationHelper::applyToUserQuery( $args, [] );

        $this->assertEquals( 50, $args['number'] );
    }

    // =========================================================================
    // ApplyToCommentQuery Tests
    // =========================================================================

    /**
     * Test that applyToCommentQuery modifies args.
     */
    public function test_apply_to_comment_query_modifies_args(): void {
        $args = [];
        PaginationHelper::applyToCommentQuery( $args, [
            'limit'   => 30,
            'offset'  => 15,
            'orderby' => 'comment_date',
            'order'   => 'desc',
        ] );

        $this->assertEquals( 30, $args['number'] );
        $this->assertEquals( 15, $args['offset'] );
        $this->assertEquals( 'comment_date', $args['orderby'] );
        $this->assertEquals( 'DESC', $args['order'] );
    }

    /**
     * Test that applyToCommentQuery uses default limit of 50.
     */
    public function test_apply_to_comment_query_default_limit(): void {
        $args = [];
        PaginationHelper::applyToCommentQuery( $args, [] );

        $this->assertEquals( 50, $args['number'] );
    }

    // =========================================================================
    // GetCountArgs Tests
    // =========================================================================

    /**
     * Test that getCountArgs removes pagination params.
     */
    public function test_get_count_args_removes_pagination(): void {
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => 20,
            'number'         => 50,
            'offset'         => 10,
        ];

        $result = PaginationHelper::getCountArgs( $args );

        $this->assertEquals( 'post', $result['post_type'] );
        $this->assertArrayNotHasKey( 'posts_per_page', $result );
        $this->assertArrayNotHasKey( 'number', $result );
        $this->assertArrayNotHasKey( 'offset', $result );
        $this->assertTrue( $result['count'] );
    }

    /**
     * Test that getCountArgs does not modify original array.
     */
    public function test_get_count_args_does_not_modify_original(): void {
        $args = [
            'posts_per_page' => 20,
            'offset'         => 10,
        ];

        PaginationHelper::getCountArgs( $args );

        $this->assertEquals( 20, $args['posts_per_page'] );
        $this->assertEquals( 10, $args['offset'] );
    }

    // =========================================================================
    // Schema Tests
    // =========================================================================

    /**
     * Test that getSchema returns proper structure.
     */
    public function test_get_schema_returns_proper_structure(): void {
        $result = PaginationHelper::getSchema();

        $this->assertArrayHasKey( 'limit', $result );
        $this->assertArrayHasKey( 'offset', $result );
        $this->assertEquals( 'integer', $result['limit']['type'] );
        $this->assertEquals( 'integer', $result['offset']['type'] );
        $this->assertEquals( 20, $result['limit']['default'] );
        $this->assertEquals( 0, $result['offset']['default'] );
    }

    /**
     * Test that getSchema uses custom default limit.
     */
    public function test_get_schema_uses_custom_default(): void {
        $result = PaginationHelper::getSchema( 50 );

        $this->assertEquals( 50, $result['limit']['default'] );
    }

    /**
     * Test that getOrderingSchema returns proper structure.
     */
    public function test_get_ordering_schema_returns_proper_structure(): void {
        $result = PaginationHelper::getOrderingSchema();

        $this->assertArrayHasKey( 'orderby', $result );
        $this->assertArrayHasKey( 'order', $result );
        $this->assertEquals( 'string', $result['orderby']['type'] );
        $this->assertEquals( 'string', $result['order']['type'] );
        $this->assertEquals( [ 'ASC', 'DESC' ], $result['order']['enum'] );
    }

    /**
     * Test that getOrderingSchema uses custom defaults.
     */
    public function test_get_ordering_schema_uses_custom_defaults(): void {
        $result = PaginationHelper::getOrderingSchema( 'title', 'ASC' );

        $this->assertEquals( 'title', $result['orderby']['default'] );
        $this->assertEquals( 'ASC', $result['order']['default'] );
    }

    /**
     * Test that getOrderingSchema includes allowed orderby values.
     */
    public function test_get_ordering_schema_includes_allowed_orderby(): void {
        $result = PaginationHelper::getOrderingSchema( 'date', 'DESC', [ 'date', 'title', 'author' ] );

        $this->assertEquals( [ 'date', 'title', 'author' ], $result['orderby']['enum'] );
    }

    /**
     * Test that getFullSchema combines pagination and ordering.
     */
    public function test_get_full_schema_combines_all(): void {
        $result = PaginationHelper::getFullSchema( 30, 'title', 'ASC' );

        $this->assertArrayHasKey( 'limit', $result );
        $this->assertArrayHasKey( 'offset', $result );
        $this->assertArrayHasKey( 'orderby', $result );
        $this->assertArrayHasKey( 'order', $result );
        $this->assertEquals( 30, $result['limit']['default'] );
        $this->assertEquals( 'title', $result['orderby']['default'] );
        $this->assertEquals( 'ASC', $result['order']['default'] );
    }
}
