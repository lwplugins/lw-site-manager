<?php
/**
 * Product and variation deletes.
 *
 * Security regression guard: wc-delete-product deleted any product without
 * a delete_post check and deleted it for good on force without asking;
 * wc-delete-variation and the bulk "delete" action did the same. Deletes now
 * need delete_post on the object, trash by default, and a permanent delete
 * needs force plus confirm_delete: true.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\WooCommerce\ProductManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductVariationManager;
use PHPUnit\Framework\TestCase;

final class ProductDeleteTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wc_test_products']         = [
            5 => new \WC_Product( 5, 'simple' ),
            7 => new \WC_Product( 7, 'variable' ),
            9 => new \WC_Product_Variation( 9, 'variation' ),
        ];
        $GLOBALS['wc_test_variation_parent'] = 7;
        $GLOBALS['wc_test_deleted']          = [];
        $GLOBALS['wp_granted_caps']          = [ 'delete_products', 'edit_products', 'edit_others_products' ];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wc_test_products'], $GLOBALS['wc_test_deleted'], $GLOBALS['wp_granted_caps'], $GLOBALS['wc_test_variation_parent'], $GLOBALS['wc_test_synced'] );
        parent::tearDown();
    }

    public function test_delete_needs_delete_post_on_the_product(): void {
        $result = ProductManager::delete_product( [ 'id' => 5, 'force' => true, 'confirm_delete' => true ] );

        $this->assertSame( 'not_found_or_forbidden', $result->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );
    }

    public function test_delete_trashes_by_default(): void {
        $GLOBALS['wp_granted_caps'][] = 'delete_post:5';

        $result = ProductManager::delete_product( [ 'id' => 5 ] );

        $this->assertTrue( $result['trashed'] );
        $this->assertSame( [ [ 'id' => 5, 'force' => false ] ], $GLOBALS['wc_test_deleted'] );
    }

    public function test_permanent_delete_needs_confirmation(): void {
        $GLOBALS['wp_granted_caps'][] = 'delete_post:5';

        $refused = ProductManager::delete_product( [ 'id' => 5, 'force' => true ] );
        $this->assertSame( 'confirm_delete_required', $refused->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );

        $result = ProductManager::delete_product( [ 'id' => 5, 'force' => true, 'confirm_delete' => true ] );
        $this->assertFalse( $result['trashed'] );
        $this->assertSame( 5, $result['id'] );
        $this->assertSame( [ [ 'id' => 5, 'force' => true ] ], $GLOBALS['wc_test_deleted'] );
    }

    public function test_variation_delete_needs_delete_post_and_the_parent(): void {
        $this->assertSame( 'not_found_or_forbidden', ProductVariationManager::delete_variation( [ 'id' => 9 ] )->get_error_code() );

        $GLOBALS['wp_granted_caps'][] = 'delete_post:9';
        $this->assertSame( 'not_found_or_forbidden', ProductVariationManager::delete_variation( [ 'id' => 9 ] )->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );

        $GLOBALS['wp_granted_caps'][] = 'edit_post:7';
        $this->assertSame( 'confirm_delete_required', ProductVariationManager::delete_variation( [ 'id' => 9, 'force' => true ] )->get_error_code() );

        $result = ProductVariationManager::delete_variation( [ 'id' => 9 ] );
        $this->assertTrue( $result['trashed'] );
        $this->assertSame( [ [ 'id' => 9, 'force' => false ] ], $GLOBALS['wc_test_deleted'] );
    }

    public function test_bulk_delete_needs_confirmation(): void {
        $GLOBALS['wp_granted_caps'][] = 'delete_post:5';

        $result = ProductManager::bulk_products( [ 'ids' => [ 5 ], 'action' => 'delete' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'confirm_delete_required', $result->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );
    }
}
