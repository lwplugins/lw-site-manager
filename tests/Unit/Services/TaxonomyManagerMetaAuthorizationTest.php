<?php
/**
 * Authorization of the inline meta map on create/update term.
 *
 * Security regression guard. The create-* and update-* term abilities accept a
 * `meta` map that was written with update_term_meta() directly, so it skipped
 * the protected-key policy the set-term-meta ability enforces: anyone with
 * manage_categories (editors) could write plugin-internal term meta.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\TaxonomyManager;
use PHPUnit\Framework\TestCase;

final class TaxonomyManagerMetaAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_terms();
        parent::tearDown();
    }

    public function test_update_term_refuses_protected_meta_key_for_non_admin(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        $result = TaxonomyManager::update_term(
            [ 'id' => 7, 'taxonomy' => 'category', 'meta' => [ '_plugin_state' => 'x' ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_update_term_writes_nothing_when_one_meta_key_is_refused(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        TaxonomyManager::update_term(
            [
                'id'       => 7,
                'taxonomy' => 'category',
                'name'     => 'Renamed',
                'meta'     => [ 'colour' => 'red', '_plugin_state' => 'x' ],
            ]
        );

        $this->assertSame( [], $GLOBALS['wp_term_meta'] );
        $this->assertSame( 'News', get_term( 7 )->name );
    }

    public function test_update_term_refuses_meta_without_edit_term_for_that_term(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $result = TaxonomyManager::update_term(
            [ 'id' => 7, 'taxonomy' => 'category', 'meta' => [ 'colour' => 'red' ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_update_term_writes_plain_meta_for_editor(): void {
        grant_wp_caps( [ 'manage_categories', 'edit_term:7' ] );

        TaxonomyManager::update_term(
            [ 'id' => 7, 'taxonomy' => 'category', 'meta' => [ 'colour' => 'red' ] ]
        );

        $this->assertSame( 'red', $GLOBALS['wp_term_meta'][7]['colour'] ?? null );
    }

    public function test_update_term_writes_protected_meta_for_admin(): void {
        grant_wp_caps( [ 'manage_categories', 'manage_options', 'edit_term:7' ] );

        TaxonomyManager::update_term(
            [ 'id' => 7, 'taxonomy' => 'category', 'meta' => [ '_plugin_state' => 'x' ] ]
        );

        $this->assertSame( 'x', $GLOBALS['wp_term_meta'][7]['_plugin_state'] ?? null );
    }

    public function test_create_term_refuses_protected_meta_key_before_creating_the_term(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $result = TaxonomyManager::create_term(
            [ 'name' => 'Fresh', 'taxonomy' => 'category', 'meta' => [ '_plugin_state' => 'x' ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
        $this->assertCount( 1, $GLOBALS['wp_terms'] );
    }

    public function test_create_term_writes_plain_meta_for_editor(): void {
        grant_wp_caps( [ 'manage_categories' ] );

        $result = TaxonomyManager::create_term(
            [ 'name' => 'Fresh', 'taxonomy' => 'category', 'meta' => [ 'colour' => 'red' ] ]
        );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'red', $GLOBALS['wp_term_meta'][101]['colour'] ?? null );
    }
}
