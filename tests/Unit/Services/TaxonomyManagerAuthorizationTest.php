<?php
/**
 * Taxonomy and term capability checks for the term abilities.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\TaxonomyManager;
use PHPUnit\Framework\TestCase;

final class TaxonomyManagerAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_terms();
        parent::tearDown();
    }

    private function assertForbidden( mixed $result ): void {
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_create_term_requires_edit_terms_of_the_taxonomy(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $this->assertForbidden( TaxonomyManager::create_term( [ 'name' => 'Sport', 'taxonomy' => 'category' ] ) );
        $this->assertCount( 1, $GLOBALS['wp_terms'] );
    }

    public function test_create_term_allowed_with_edit_terms(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $result = TaxonomyManager::create_term( [ 'name' => 'Sport', 'taxonomy' => 'category' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_update_term_requires_edit_term_for_that_term(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $this->assertForbidden( TaxonomyManager::update_term( [ 'id' => 7, 'name' => 'Renamed' ] ) );
        $this->assertSame( 'News', get_term( 7 )->name );
    }

    public function test_update_term_allowed_with_edit_term(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        TaxonomyManager::update_term( [ 'id' => 7, 'name' => 'Renamed' ] );

        $this->assertSame( 'Renamed', get_term( 7 )->name );
    }

    public function test_delete_term_requires_delete_term_for_that_term(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        $this->assertForbidden( TaxonomyManager::delete_term( [ 'id' => 7 ] ) );
        $this->assertNotNull( get_term( 7 ) );
    }

    public function test_delete_term_allowed_with_delete_term(): void {
        grant_wp_caps( [ 'manage_categories', 'delete_term:7' ] );

        $result = TaxonomyManager::delete_term( [ 'id' => 7 ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
        $this->assertNull( get_term( 7 ) );
    }
}
