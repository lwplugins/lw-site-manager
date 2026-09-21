<?php
/**
 * Inline `meta` maps of the create/update abilities.
 *
 * Security regression guard. The post, comment, user and WooCommerce
 * create/update abilities wrote their `meta` map with update_*_meta() or the
 * WC_Data API directly, skipping the key policy set-*-meta enforces: an
 * Author could set protected keys (including the LW SEO Markdown override,
 * which LW SEO serves verbatim at /md) through create-post, and anyone
 * allowed to create or update a user could hand out a role via the map.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Meta
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Meta;

use LightweightPlugins\SiteManager\Services\CommentManager;
use LightweightPlugins\SiteManager\Services\PostManager;
use LightweightPlugins\SiteManager\Services\UserManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductVariationManager;
use PHPUnit\Framework\TestCase;

final class MetaMapGuardTest extends TestCase {

    private const OVERRIDE = '_lw_seo_markdown_content';

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        reset_wp_post_meta();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        parent::tearDown();
    }

    public function test_create_post_refuses_protected_key_for_author(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts' ] );

        $result = PostManager::create_post( [ 'title' => 'Hi', 'meta' => [ self::OVERRIDE => '[x](javascript:alert(1))' ] ] );

        $this->assertForbiddenKey( $result );
    }

    public function test_create_post_refuses_the_override_for_admin_without_unfiltered_html(): void {
        grant_wp_caps( [ 'edit_posts', 'publish_posts', 'manage_options' ] );

        $result = PostManager::create_post( [ 'title' => 'Hi', 'meta' => [ self::OVERRIDE => '# Hi' ] ] );

        $this->assertForbiddenKey( $result );
    }

    public function test_update_post_refuses_protected_key_for_author(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = PostManager::update_post( [ 'id' => 42, 'meta' => [ '_plugin_state' => 'x' ] ] );

        $this->assertForbiddenKey( $result );
        $this->assertSame( [], $GLOBALS['wp_post_meta'][42] ?? [] );
    }

    public function test_create_comment_refuses_protected_key_for_non_admin(): void {
        grant_wp_caps( [ 'moderate_comments' ] );

        $result = CommentManager::create_comment( [ 'post_id' => 42, 'content' => 'Hi', 'meta' => [ '_plugin_state' => 'x' ] ] );

        $this->assertForbiddenKey( $result );
    }

    public function test_update_comment_refuses_protected_key_for_non_admin(): void {
        grant_wp_caps( [ 'moderate_comments', 'edit_comment:5' ] );

        $result = CommentManager::update_comment( [ 'id' => 5, 'meta' => [ '_plugin_state' => 'x' ] ] );

        $this->assertForbiddenKey( $result );
    }

    /**
     * @dataProvider provide_role_keys
     */
    public function test_create_user_refuses_role_keys_in_the_map( string $key ): void {
        grant_wp_caps( [ 'create_users', 'manage_options' ] );

        $result = UserManager::create_user(
            [ 'username' => 'eve', 'email' => 'eve@example.com', 'meta' => [ $key => [ 'administrator' => true ] ] ]
        );

        $this->assertForbiddenKey( $result );
    }

    /**
     * @dataProvider provide_role_keys
     */
    public function test_update_user_refuses_role_keys_in_the_map( string $key ): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = UserManager::update_user( [ 'id' => 2, 'meta' => [ $key => [ 'administrator' => true ] ] ] );

        $this->assertForbiddenKey( $result );
    }

    public static function provide_role_keys(): array {
        return [
            'plain'   => [ 'wp_capabilities' ],
            'slashed' => [ 'wp_capabilities\\' ],
        ];
    }

    /**
     * @dataProvider provide_wc_writers
     */
    public function test_woocommerce_maps_refuse_the_override_without_unfiltered_html( callable $write ): void {
        grant_wp_caps( [ 'manage_woocommerce', 'edit_products', 'manage_options' ] );

        $result = $write( [ 'id' => 9, 'product_id' => 8, 'name' => 'Mug', 'meta' => [ self::OVERRIDE => '# Hi' ] ] );

        $this->assertForbiddenKey( $result );
    }

    public static function provide_wc_writers(): array {
        return [
            'create product'   => [ [ ProductManager::class, 'create_product' ] ],
            'update product'   => [ [ ProductManager::class, 'update_product' ] ],
            'create order'     => [ [ OrderManager::class, 'create_order' ] ],
            'update order'     => [ [ OrderManager::class, 'update_order' ] ],
            'create variation' => [ [ ProductVariationManager::class, 'create_variation' ] ],
            'update variation' => [ [ ProductVariationManager::class, 'update_variation' ] ],
        ];
    }

    /**
     * @param mixed $result Service result.
     */
    private function assertForbiddenKey( $result ): void {
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }
}
