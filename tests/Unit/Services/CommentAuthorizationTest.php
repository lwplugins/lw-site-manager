<?php
/**
 * Per-comment capability checks for the comment abilities.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\CommentManager;
use PHPUnit\Framework\TestCase;

final class CommentAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
        reset_wp_comments();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_comments();
        unset( $GLOBALS['wp_inserted_comments'], $GLOBALS['wp_missing_posts'] );
        parent::tearDown();
    }

    private function assertForbidden( mixed $result ): void {
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function provide_single_comment_operations(): array {
        return [
            'get'     => [ 'get_comment', [ 'id' => 9 ] ],
            'update'  => [ 'update_comment', [ 'id' => 9, 'content' => 'Changed' ] ],
            'delete'  => [ 'delete_comment', [ 'id' => 9 ] ],
            'approve' => [ 'approve_comment', [ 'id' => 9 ] ],
            'spam'    => [ 'spam_comment', [ 'id' => 9 ] ],
        ];
    }

    /**
     * @dataProvider provide_single_comment_operations
     *
     * @param array<string, mixed> $input Ability input.
     */
    public function test_operation_denied_without_edit_comment_for_that_comment( string $method, array $input ): void {
        grant_wp_caps( [ 'moderate_comments' ] );

        $this->assertForbidden( CommentManager::$method( $input ) );
    }

    /**
     * @dataProvider provide_single_comment_operations
     *
     * @param array<string, mixed> $input Ability input.
     */
    public function test_operation_allowed_with_edit_comment_for_that_comment( string $method, array $input ): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_comment:9' ] );

        $this->assertNotInstanceOf( \WP_Error::class, CommentManager::$method( $input ) );
    }

    public function test_bulk_action_skips_comments_without_edit_comment(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_comment:9' ] );

        $result = CommentManager::bulk_action( [ 'ids' => [ 9, 10 ], 'action' => 'delete' ] );

        $this->assertSame( [ 9 ], $result['success_ids'] );
        $this->assertSame( [ 10 ], $result['failed_ids'] );
    }

    public function test_create_comment_requires_edit_post_for_the_target_post(): void {
        grant_wp_caps( [ 'moderate_comments' ] );

        $this->assertForbidden( CommentManager::create_comment( [ 'post_id' => 42, 'content' => 'Hi' ] ) );
        $this->assertArrayNotHasKey( 'wp_inserted_comments', $GLOBALS );
    }

    public function test_create_comment_on_missing_post_is_not_found(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_post:42' ] );
        $GLOBALS['wp_missing_posts'] = [ 42 ];

        $result = CommentManager::create_comment( [ 'post_id' => 42, 'content' => 'Hi' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 404, $result->get_error_data()['status'] );
    }

    public function test_create_comment_allowed_with_edit_post(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_post:42' ] );

        $result = CommentManager::create_comment( [ 'post_id' => 42, 'content' => 'Hi' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
        $this->assertCount( 1, $GLOBALS['wp_inserted_comments'] );
    }
}
