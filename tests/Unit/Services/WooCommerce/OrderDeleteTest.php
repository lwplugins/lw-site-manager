<?php
/**
 * Order deletes.
 *
 * Security regression guard: wc-delete-order deleted an order for good on
 * force without asking, and trashed through wp_trash_post(), which does not
 * touch an HPOS order at all. Deletes now go through WC_Order::delete()
 * (trash by default, both storages) and a permanent delete needs force plus
 * confirm_delete: true.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;
use PHPUnit\Framework\TestCase;

final class OrderDeleteTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wc_test_orders']  = [ 3 => new \WC_Order( 3 ) ];
        $GLOBALS['wc_test_deleted'] = [];
        $GLOBALS['wp_granted_caps'] = [ 'edit_shop_orders', 'delete_post:3' ];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wc_test_orders'], $GLOBALS['wc_test_deleted'], $GLOBALS['wp_granted_caps'] );
        parent::tearDown();
    }

    public function test_delete_trashes_through_woocommerce(): void {
        $result = OrderManager::delete_order( [ 'id' => 3 ] );

        $this->assertTrue( $result['trashed'] );
        $this->assertSame( [ [ 'id' => 3, 'force' => false ] ], $GLOBALS['wc_test_deleted'] );
    }

    public function test_permanent_delete_needs_confirmation(): void {
        $refused = OrderManager::delete_order( [ 'id' => 3, 'force' => true ] );
        $this->assertSame( 'confirm_delete_required', $refused->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );

        $result = OrderManager::delete_order( [ 'id' => 3, 'force' => true, 'confirm_delete' => true ] );
        $this->assertFalse( $result['trashed'] );
        $this->assertSame( 3, $result['id'] );
        $this->assertSame( [ [ 'id' => 3, 'force' => true ] ], $GLOBALS['wc_test_deleted'] );
    }

    public function test_delete_needs_delete_post_on_the_order(): void {
        $GLOBALS['wp_granted_caps'] = [ 'edit_shop_orders', 'edit_post:3' ];

        $this->assertSame( 'not_found_or_forbidden', OrderManager::delete_order( [ 'id' => 3, 'force' => true, 'confirm_delete' => true ] )->get_error_code() );
        $this->assertSame( [], $GLOBALS['wc_test_deleted'] );
    }
}
