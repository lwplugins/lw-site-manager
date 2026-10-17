<?php
/**
 * Object-level authorization of the product abilities.
 *
 * Security regression guard: product services loaded the product and acted
 * on it without asking whether the current user may read or edit THAT
 * product (read_post / edit_post, which WooCommerce maps to the product
 * capabilities and role plugins constrain). A user allowed to edit some
 * products could change any product, including other users' drafts.
 * A refusal must not reveal whether the product exists.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\WooCommerce\ProductAttributeManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductVariationManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\WcMetaManager;
use PHPUnit\Framework\TestCase;

final class ProductObjectAccessTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wc_test_products'] = [
            5 => new \WC_Product( 5, 'simple' ),
            7 => new \WC_Product( 7, 'variable' ),
            9 => new \WC_Product_Variation( 9, 'variation' ),
        ];
        // The caller holds the primitive product caps, but no grant on these objects.
        $GLOBALS['wp_granted_caps'] = [ 'edit_products', 'read_private_products', 'publish_products', 'edit_others_products' ];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wc_test_products'], $GLOBALS['wp_granted_caps'], $GLOBALS['wc_test_deleted'] );
        parent::tearDown();
    }

    /**
     * @dataProvider provide_denied_calls
     */
    public function test_call_is_refused_without_object_capability( callable $call, array $input ): void {
        $result = $call( $input );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'not_found_or_forbidden', $result->get_error_code() );
    }

    public static function provide_denied_calls(): array {
        return [
            'get product'          => [ [ ProductManager::class, 'get_product' ], [ 'id' => 5 ] ],
            'update product'       => [ [ ProductManager::class, 'update_product' ], [ 'id' => 5, 'name' => 'x' ] ],
            'update stock'         => [ [ ProductManager::class, 'update_stock' ], [ 'id' => 5, 'quantity' => 1 ] ],
            'duplicate product'    => [ [ ProductManager::class, 'duplicate_product' ], [ 'id' => 5 ] ],
            'list variations'      => [ [ ProductManager::class, 'list_variations' ], [ 'product_id' => 7 ] ],
            'set attributes'       => [ [ ProductAttributeManager::class, 'set_product_attributes' ], [ 'product_id' => 5, 'attributes' => [] ] ],
            'remove attribute'     => [ [ ProductAttributeManager::class, 'remove_product_attribute' ], [ 'product_id' => 5, 'name' => 'Size' ] ],
            'create variation'     => [ [ ProductVariationManager::class, 'create_variation' ], [ 'product_id' => 7, 'attributes' => [] ] ],
            'generate variations'  => [ [ ProductVariationManager::class, 'generate_variations' ], [ 'product_id' => 7 ] ],
            'update variation'     => [ [ ProductVariationManager::class, 'update_variation' ], [ 'id' => 9, 'regular_price' => '1' ] ],
            'get product meta'     => [ [ WcMetaManager::class, 'get_product_meta' ], [ 'product_id' => 5 ] ],
            'set product meta'     => [ [ WcMetaManager::class, 'set_product_meta' ], [ 'product_id' => 5, 'meta' => [ 'x' => 'y' ] ] ],
            'delete product meta'  => [ [ WcMetaManager::class, 'delete_product_meta' ], [ 'product_id' => 5, 'key' => 'x' ] ],
        ];
    }

    public function test_missing_and_forbidden_products_look_the_same(): void {
        $missing   = ProductManager::update_stock( [ 'id' => 404, 'quantity' => 1 ] );
        $forbidden = ProductManager::update_stock( [ 'id' => 5, 'quantity' => 1 ] );

        $this->assertSame( $missing->get_error_code(), $forbidden->get_error_code() );
        $this->assertSame( $missing->get_error_message(), $forbidden->get_error_message() );
    }

    /**
     * Meta can hold internal and protected values, so reading it needs the
     * same right as changing the product — read_post would expose it to any
     * reader of a published product.
     */
    public function test_product_meta_read_needs_edit_post(): void {
        $GLOBALS['wp_granted_caps'][] = 'read_post:5';

        $result = WcMetaManager::get_product_meta( [ 'product_id' => 5, 'include_protected' => true ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'not_found_or_forbidden', $result->get_error_code() );
    }

    public function test_variation_update_also_needs_the_parent(): void {
        $GLOBALS['wp_granted_caps'][]          = 'edit_post:9';
        $GLOBALS['wc_test_variation_parent'] = 7;

        $result = ProductVariationManager::update_variation( [ 'id' => 9, 'regular_price' => '1' ] );

        unset( $GLOBALS['wc_test_variation_parent'] );
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'not_found_or_forbidden', $result->get_error_code() );
    }

    public function test_bulk_publish_skips_products_the_user_cannot_edit(): void {
        $result = ProductManager::bulk_products( [ 'ids' => [ 5 ], 'action' => 'publish' ] );

        $this->assertSame( [ 5 ], $result['failed_ids'] ?? null );
    }
}
