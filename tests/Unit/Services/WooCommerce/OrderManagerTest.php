<?php
/**
 * Unit tests for the WooCommerce OrderManager service.
 *
 * Regression guard for issue #21: list_orders() must restrict wc_get_orders()
 * to the `shop_order` type. Under HPOS, an unrestricted query also returns
 * WC_Order_Refund objects, and format_order() (typed \WC_Order) then fatals.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;

/**
 * Tests for OrderManager::list_orders().
 *
 * Relies on the `WooCommerce` class stub and the arg-capturing `wc_get_orders`
 * stub defined in tests/stubs/wordpress-functions.php.
 */
final class OrderManagerTest extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['wc_get_orders_last_args'] );
        parent::tearDown();
    }

    /**
     * Issue #21: the query must be restricted to orders so HPOS never hands a
     * refund (WC_Order_Refund) object to format_order().
     */
    public function test_list_orders_restricts_query_to_shop_order_type(): void {
        OrderManager::list_orders( [ 'limit' => 1 ] );

        $this->assertArrayHasKey( 'wc_get_orders_last_args', $GLOBALS );
        $this->assertArrayHasKey( 'type', $GLOBALS['wc_get_orders_last_args'] );
        $this->assertSame( 'shop_order', $GLOBALS['wc_get_orders_last_args']['type'] );
    }
}
