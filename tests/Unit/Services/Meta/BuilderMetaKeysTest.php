<?php
/**
 * Page-builder storage keys through the generic meta writes.
 *
 * A builder keeps its layout, caches and generated CSS in post meta that only
 * makes sense together. The generic meta writes (set/delete-post-meta, the
 * create/update-post `meta` map, the WooCommerce product meta) let an
 * administrator hand-write _elementor_data or a Bricks tree, and anyone who
 * could edit the post write the non-underscore keys (Kirki, Brizy), leaving
 * stale CSS and broken editors behind. They now refuse these keys and point
 * to the builder abilities; reading stays allowed.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\Meta\BuilderMetaKeys;
use LightweightPlugins\SiteManager\Services\Meta\MetaGuard;
use LightweightPlugins\SiteManager\Services\MetaManager;
use LightweightPlugins\SiteManager\Services\PostManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductVariationManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\WcMetaManager;
use PHPUnit\Framework\TestCase;

final class BuilderMetaKeysTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_post_meta();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_filters();
        unset( $GLOBALS['wc_test_products'] );
        parent::tearDown();
    }

    /**
     * @dataProvider provide_builder_keys
     */
    public function test_admin_cannot_set_builder_storage( string $key, string $family ): void {
        $this->grantAdmin();

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => $key, 'value' => '[]' ] );

        $this->assertBuilderKey( $result, $family );
        $this->assertSame( [], $GLOBALS['wp_post_meta'][42] ?? [] );
    }

    public static function provide_builder_keys(): array {
        return [
            'elementor data'          => [ '_elementor_data', 'site-manager/el-*' ],
            'elementor css'           => [ '_elementor_css', 'site-manager/el-*' ],
            'bricks content'          => [ '_bricks_page_content_2', 'site-manager/br-*' ],
            'bricks footer scripts'   => [ '_bab_footer_scripts', 'site-manager/br-*' ],
            'breakdance data'         => [ '_breakdance_data', 'site-manager/bd-*' ],
            'oxygen data'             => [ '_oxygen_data', 'site-manager/oxy-*' ],
            'beaver data'             => [ '_fl_builder_data', 'site-manager/beaver-*' ],
            'wpbakery custom css'     => [ '_wpb_shortcodes_custom_css', 'site-manager/wpb-*' ],
            'wpbakery settings'       => [ '_vc_post_settings', 'site-manager/wpb-*' ],
            'divi builder flag'       => [ '_et_pb_use_builder', 'Divi builder' ],
            'divi feature cache'      => [ '_et_builder_module_features_cache', 'Divi builder' ],
            'brizy storage'           => [ 'brizy', 'Brizy editor' ],
            'brizy compiled'          => [ 'brizy-compiled-sections', 'Brizy editor' ],
            'kirki storage'           => [ 'kirki', 'Kirki editor' ],
            'kirki prefixed'          => [ 'kirki_global_style_block', 'Kirki editor' ],
            'kirki staged'            => [ 'staged_3_kirki', 'Kirki editor' ],
            'spectra page assets'     => [ '_uag_page_assets', 'site-manager/spectra-*' ],
            'spectra block counts'    => [ '_uagb_previous_block_counts', 'site-manager/spectra-*' ],
            'generateblocks css'      => [ 'gb_style_css', 'site-manager/gb-*' ],
            'lw layout snapshots'     => [ '_lw_sme_layout_snapshots', 'site-manager/*-snapshot*' ],
            'collation case spelling' => [ '_ELEMENTOR_Data', 'site-manager/el-*' ],
            'slashed spelling'        => [ '\\_elementor_data', 'site-manager/el-*' ],
        ];
    }

    public function test_without_extra_the_message_points_to_the_editor_and_extra(): void {
        $this->grantAdmin();

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '_elementor_data', 'value' => '[]' ] );

        $this->assertBuilderKey( $result, 'Edit it in the Elementor editor, or install LW Site Manager Extra, whose Elementor abilities (site-manager/el-*)' );
    }

    public function test_builder_without_dedicated_abilities_points_to_its_editor_only(): void {
        $this->grantAdmin();

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'brizy', 'value' => 'x' ] );

        $this->assertBuilderKey( $result, 'Edit it in the Brizy editor, so' );
        $this->assertStringNotContainsString( 'LW Site Manager Extra', $result->get_error_message() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_with_extra_active_the_message_names_its_abilities(): void {
        define( 'LW_SITE_MANAGER_EXTRA_VERSION', '1.0.0' );
        $this->grantAdmin();

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => '_bricks_page_content_2', 'value' => '[]' ] );

        $this->assertBuilderKey( $result, 'Write it through the Bricks abilities of LW Site Manager Extra (site-manager/br-*) instead' );
        $this->assertStringNotContainsString( 'install', $result->get_error_message() );
    }

    public function test_order_meta_keeps_builder_keys_writable(): void {
        $this->assertNull( MetaGuard::guardWcMap( [ '_elementor_data' => '[]' ], false ) );
    }

    /**
     * @dataProvider provide_wc_spellings
     */
    public function test_wc_product_map_refuses_other_spellings( string $key ): void {
        $error = MetaGuard::guardWcMap( [ $key => '[]' ], true );

        $this->assertInstanceOf( \WP_Error::class, $error );
        $this->assertSame( 'builder_meta_key', $error->get_error_code() );
    }

    public static function provide_wc_spellings(): array {
        return [
            'uppercase'        => [ '_ELEMENTOR_DATA' ],
            'trailing space'   => [ '_elementor_data ' ],
            'slashed'          => [ '\\_elementor_data' ],
        ];
    }

    public function test_admin_cannot_delete_builder_storage(): void {
        $this->grantAdmin();
        $GLOBALS['wp_post_meta'][42]['_elementor_data'] = [ '[]' ];

        $result = MetaManager::delete_post_meta( [ 'post_id' => 42, 'key' => '_elementor_data' ] );

        $this->assertBuilderKey( $result, 'site-manager/el-*' );
        $this->assertSame( [ '[]' ], $GLOBALS['wp_post_meta'][42]['_elementor_data'] );
    }

    public function test_editor_cannot_set_unprotected_builder_storage(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'kirki', 'value' => 'x' ] );

        $this->assertBuilderKey( $result, 'Kirki editor' );
    }

    public function test_reading_builder_storage_stays_allowed(): void {
        $this->grantAdmin();
        $GLOBALS['wp_post_meta'][42]['_elementor_data'] = [ '[]' ];

        $result = MetaManager::get_post_meta( [ 'post_id' => 42, 'key' => '_elementor_data' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    /**
     * @dataProvider provide_lookalike_keys
     */
    public function test_ordinary_keys_stay_writable( string $key ): void {
        $this->grantAdmin();

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => $key, 'value' => 'x' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public static function provide_lookalike_keys(): array {
        return [
            'plain key'                => [ 'subtitle' ],
            'elementor without prefix' => [ 'elementor_data' ],
            'kirki look-alike'         => [ 'kirkis' ],
            'staged but not kirki'     => [ 'staged_3_notes' ],
            'divi page setting'        => [ '_et_pb_page_layout' ],
        ];
    }

    public function test_create_post_map_refuses_builder_storage(): void {
        $this->grantAdmin();

        $result = PostManager::create_post( [ 'title' => 'Hi', 'meta' => [ 'subtitle' => 'x', '_bricks_page_content_2' => [] ] ] );

        $this->assertBuilderKey( $result, 'site-manager/br-*' );
    }

    public function test_update_post_map_refuses_builder_storage(): void {
        $this->grantAdmin();

        $result = PostManager::update_post( [ 'id' => 42, 'meta' => [ 'brizy' => 'x' ] ] );

        $this->assertBuilderKey( $result, 'Brizy editor' );
        $this->assertSame( [], $GLOBALS['wp_post_meta'][42] ?? [] );
    }

    public function test_duplicate_still_copies_builder_storage(): void {
        $this->grantAdmin();
        $GLOBALS['wp_post_meta'][42] = [ '_elementor_data' => [ '[]' ], 'kirki' => [ 'x' ] ];

        PostManager::duplicate_post( [ 'id' => 42 ] );

        $copied = array_keys( $GLOBALS['wp_post_meta'][1] ?? [] );
        $this->assertSame( [ '_elementor_data', 'kirki' ], $copied );
    }

    public function test_builder_rule_is_post_only(): void {
        $this->grantAdmin();

        foreach ( [ 'user', 'term', 'comment' ] as $type ) {
            $this->assertNull( MetaGuard::writeKey( $type, 'kirki' ), $type );
        }
        $this->assertInstanceOf( \WP_Error::class, MetaGuard::writeKey( 'post', 'kirki' ) );
    }

    public function test_site_owner_can_remove_a_group(): void {
        $this->grantAdmin();
        add_filter(
            'lw_site_manager_builder_meta_keys',
            static function ( array $groups ): array {
                unset( $groups['kirki'] );
                return $groups;
            }
        );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'kirki', 'value' => 'x' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_site_owner_can_add_a_group(): void {
        $this->grantAdmin();
        add_filter(
            'lw_site_manager_builder_meta_keys',
            static function ( array $groups ): array {
                $groups['acme'] = [ 'label' => 'Acme', 'abilities' => 'the Acme editor', 'prefixes' => [ 'acme_' ] ];
                return $groups;
            }
        );

        $result = MetaManager::set_post_meta( [ 'post_id' => 42, 'key' => 'ACME_layout', 'value' => 'x' ] );

        $this->assertBuilderKey( $result, 'Acme editor' );
    }

    public function test_malformed_filtered_pattern_never_matches(): void {
        add_filter(
            'lw_site_manager_builder_meta_keys',
            static fn(): array => [ 'bad' => [ 'label' => 'Bad', 'abilities' => 'x', 'patterns' => [ '/(unclosed' ] ] ]
        );

        $this->assertNull( BuilderMetaKeys::match( 'anything' ) );
    }

    /**
     * @dataProvider provide_wc_product_writers
     */
    public function test_woocommerce_product_meta_refuses_builder_storage( callable $write, array $input ): void {
        $this->grantAdmin();
        grant_wp_caps( [ 'manage_woocommerce', 'edit_products', 'unfiltered_html', 'edit_post:5', 'edit_post:7', 'edit_post:9' ] );
        $GLOBALS['wc_test_products'] = [
            5 => new \WC_Product( 5, 'simple' ),
            7 => new \WC_Product( 7, 'variable' ),
            9 => new \WC_Product_Variation( 9, 'variation' ),
        ];

        $result = $write( $input );

        $this->assertBuilderKey( $result, 'site-manager/el-*' );
    }

    public static function provide_wc_product_writers(): array {
        $meta = [ '_elementor_data' => '[]' ];

        return [
            'set product meta'    => [ [ WcMetaManager::class, 'set_product_meta' ], [ 'product_id' => 5, 'meta' => $meta ] ],
            'delete product meta' => [ [ WcMetaManager::class, 'delete_product_meta' ], [ 'product_id' => 5, 'key' => '_elementor_data' ] ],
            'create product'      => [ [ ProductManager::class, 'create_product' ], [ 'name' => 'Mug', 'meta' => $meta ] ],
            'update product'      => [ [ ProductManager::class, 'update_product' ], [ 'id' => 5, 'meta' => $meta ] ],
            'create variation'    => [ [ ProductVariationManager::class, 'create_variation' ], [ 'product_id' => 7, 'meta' => $meta ] ],
            'update variation'    => [ [ ProductVariationManager::class, 'update_variation' ], [ 'id' => 9, 'meta' => $meta ] ],
        ];
    }

    private function grantAdmin(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'manage_options', 'unfiltered_html', 'edit_post:42', 'read_post:42' ] );
    }

    /**
     * @param mixed $result Service result.
     */
    private function assertBuilderKey( $result, string $family ): void {
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'builder_meta_key', $result->get_error_code() );
        $this->assertStringContainsString( $family, $result->get_error_message() );
        $this->assertSame( 403, $result->get_error_data()['status'] ?? null );
    }
}
