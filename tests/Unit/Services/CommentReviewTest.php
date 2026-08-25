<?php
/**
 * WooCommerce product reviews in the comment abilities.
 *
 * Reviews are comments with comment_type = 'review'. WooCommerce hides them
 * from general comment queries, but its filter bails out whenever any of a
 * dozen query vars is set — `type__not_in` among them, which other plugins
 * (LearnDash sets `ld_review`) populate. The same list-comments call therefore
 * returned different rows depending on which plugins a site happened to run.
 * These tests pin the behaviour down so it no longer depends on the site.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\CommentManager;
use PHPUnit\Framework\TestCase;

final class CommentReviewTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_comments();
        grant_wp_caps( [ 'moderate_comments', 'edit_posts' ] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_comments();
        parent::tearDown();
    }

    // =========================================================================
    // Deterministic listing
    // =========================================================================

    /**
     * Without an explicit type the query must pin itself to real comments, so
     * reviews cannot leak in on sites where WooCommerce's own filter bails.
     * WordPress maps type => 'comment' to comment_type IN ('', 'comment'), so
     * legacy comments with an empty type are still returned.
     */
    public function test_list_comments_defaults_to_comment_type(): void {
        CommentManager::list_comments( [] );

        $this->assertSame( 'comment', $GLOBALS['wp_get_comments_last_args']['type'] ?? null );
    }

    public function test_list_comments_honours_an_explicit_review_type(): void {
        CommentManager::list_comments( [ 'type' => 'review' ] );

        $this->assertSame( 'review', $GLOBALS['wp_get_comments_last_args']['type'] ?? null );
    }

    // =========================================================================
    // Review payload
    // =========================================================================

    public function test_review_output_includes_rating_and_verified(): void {
        $GLOBALS['wp_comments_stub'] = [
            new \WP_Comment( [ 'comment_ID' => 23, 'comment_type' => 'review', 'comment_post_ID' => 63 ] ),
        ];
        $GLOBALS['wp_comment_meta'][23] = [ 'rating' => 5, 'verified' => 1 ];

        $result = CommentManager::list_comments( [ 'type' => 'review' ] );
        $review = $result['comments'][0];

        $this->assertSame( 'review', $review['type'] );
        $this->assertSame( 5, $review['rating'] );
        $this->assertTrue( $review['verified'] );
    }

    public function test_review_without_stored_rating_reports_null(): void {
        $GLOBALS['wp_comments_stub'] = [
            new \WP_Comment( [ 'comment_ID' => 24, 'comment_type' => 'review' ] ),
        ];

        $result = CommentManager::list_comments( [ 'type' => 'review' ] );

        $this->assertNull( $result['comments'][0]['rating'] );
        $this->assertFalse( $result['comments'][0]['verified'] );
    }

    /**
     * A plain comment must not grow review-only fields.
     */
    public function test_plain_comment_has_no_rating_fields(): void {
        $GLOBALS['wp_comments_stub'] = [
            new \WP_Comment( [ 'comment_ID' => 1, 'comment_type' => 'comment' ] ),
        ];

        $result = CommentManager::list_comments( [] );

        $this->assertArrayNotHasKey( 'rating', $result['comments'][0] );
        $this->assertArrayNotHasKey( 'verified', $result['comments'][0] );
    }

    // =========================================================================
    // Counts
    // =========================================================================

    /**
     * Counts must not mix reviews into the comment totals — previously the two
     * were summed together, and whether reviews were included at all depended
     * on the site's other plugins.
     */
    public function test_counts_separate_reviews_from_comments(): void {
        $GLOBALS['wp_comment_counts_stub'] = [
            'comment:approve' => 4,
            'comment:hold'    => 1,
            'comment:spam'    => 2,
            'review:approve'  => 7,
            'review:hold'     => 3,
            'review:spam'     => 0,
        ];

        $counts = CommentManager::get_counts();

        $this->assertSame( 4, $counts['approved'] );
        $this->assertSame( 1, $counts['awaiting'] );
        $this->assertSame( 5, $counts['total'], 'total is approved + awaiting, reviews excluded' );

        $this->assertArrayHasKey( 'reviews', $counts );
        $this->assertSame( 7, $counts['reviews']['approved'] );
        $this->assertSame( 3, $counts['reviews']['awaiting'] );
        $this->assertSame( 10, $counts['reviews']['total'] );
    }

    public function test_counts_query_each_type_explicitly(): void {
        CommentManager::get_counts();

        $types = array_column( $GLOBALS['wp_get_comments_calls'] ?? [], 'type' );

        $this->assertContains( 'comment', $types );
        $this->assertContains( 'review', $types );
    }
}
