<?php
/**
 * Capability-gated meta keys through the meta abilities.
 *
 * Security regression guard. LW SEO serves _lw_seo_markdown_content verbatim
 * at /md and only lets unfiltered_html users set it. The generic meta
 * abilities let any administrator write protected keys, and an administrator
 * does not hold unfiltered_html on multisite or with DISALLOW_UNFILTERED_HTML,
 * so they were a way around LW SEO's own rule.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\MetaManager;
use LightweightPlugins\SiteManager\Services\TaxonomyManager;
use PHPUnit\Framework\TestCase;

final class GatedMetaKeysTest extends TestCase {

    private const KEY = '_lw_seo_markdown_content';

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_post_meta();
        reset_wp_terms( [ 7 => new \WP_Term( [ 'term_id' => 7, 'name' => 'News', 'slug' => 'news' ] ) ] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_terms();
        parent::tearDown();
    }

    public function test_admin_without_unfiltered_html_cannot_set_the_markdown_override(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => self::KEY, 'value' => '[x](javascript:alert(1))' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_admin_with_unfiltered_html_can_set_the_markdown_override(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'unfiltered_html', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => self::KEY, 'value' => '# Hi' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_admin_without_unfiltered_html_cannot_delete_the_markdown_override(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );

        $result = MetaManager::delete_post_meta( [ 'post_id' => 42, 'key' => self::KEY ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_admin_without_unfiltered_html_cannot_set_the_term_markdown_override(): void {
        grant_wp_caps( [ 'manage_options', 'edit_term:7' ] );

        $result = MetaManager::set_term_meta( [ 'term_id' => 7, 'key' => self::KEY, 'value' => '# Hi' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_term_meta_map_cannot_carry_the_markdown_override(): void {
        grant_wp_caps( [ 'manage_categories', 'manage_options' ] );

        $result = TaxonomyManager::create_term(
            [ 'name' => 'Fresh', 'taxonomy' => 'category', 'meta' => [ self::KEY => '# Hi' ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
        $this->assertCount( 1, $GLOBALS['wp_terms'] );
    }

    /**
     * @dataProvider provide_spelling_variants
     */
    public function test_spelling_variants_of_the_key_are_refused( string $key ): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => $key, 'value' => '# Hi' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public static function provide_spelling_variants(): array {
        return [
            'upper case' => [ '_LW_SEO_Markdown_Content' ],
            'slashed'    => [ '_lw_seo_markdown\\_content' ],
        ];
    }

    public function test_accented_spelling_of_the_key_is_refused(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '_lw_seo_markdówn_content', 'value' => '# Hi' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'invalid_meta_key', $result->get_error_code() );
    }

    public function test_unrelated_protected_key_stays_writable_for_admin(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '_lw_seo_title', 'value' => 'Title' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_filter_adds_a_gated_key(): void {
        grant_wp_caps( [ 'edit_posts', 'manage_options', 'edit_post:42' ] );
        add_filter(
            'lw_site_manager_gated_meta_keys',
            static fn( array $keys ): array => $keys + [ '_raw_banner_html' => 'unfiltered_html' ]
        );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '_raw_banner_html', 'value' => '<b>x</b>' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }
}
