<?php
/**
 * Object-level authorization of the order abilities.
 *
 * Security regression guard: order services loaded an order by ID and read
 * or changed it without asking whether the current user may act on THAT
 * order. WooCommerce maps read_post / edit_post / delete_post on an order ID
 * to its order capabilities (also under HPOS), which role plugins use to
 * constrain delegated staff. A refusal must not reveal whether the order
 * exists.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\WooCommerce\OrderGuard;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderItemManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderModificationManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\OrderPaymentManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\WcMetaManager;
use PHPUnit\Framework\TestCase;

final class OrderObjectAccessTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wc_test_orders']  = [ 3 => new \WC_Order( 3 ) ];
        $GLOBALS['wp_granted_caps'] = [ 'edit_shop_orders' ];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wc_test_orders'], $GLOBALS['wp_granted_caps'], $GLOBALS['wc_test_status_updates'], $GLOBALS['wc_test_refunds'] );
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
            'get order'          => [ [ OrderManager::class, 'get_order' ], [ 'id' => 3 ] ],
            'update order'       => [ [ OrderManager::class, 'update_order' ], [ 'id' => 3, 'customer_note' => 'x' ] ],
            'update status'      => [ [ OrderManager::class, 'update_order_status' ], [ 'id' => 3, 'status' => 'completed' ] ],
            'list notes'         => [ [ OrderManager::class, 'list_order_notes' ], [ 'order_id' => 3 ] ],
            'add note'           => [ [ OrderManager::class, 'add_order_note' ], [ 'order_id' => 3, 'note' => 'x' ] ],
            'create refund'      => [ [ OrderManager::class, 'create_refund' ], [ 'order_id' => 3, 'amount' => 1 ] ],
            'list refunds'       => [ [ OrderManager::class, 'list_refunds' ], [ 'order_id' => 3 ] ],
            'payment url'        => [ [ OrderPaymentManager::class, 'get_payment_url' ], [ 'order_id' => 3 ] ],
            'send email'         => [ [ OrderPaymentManager::class, 'send_email' ], [ 'order_id' => 3, 'email_type' => 'customer_invoice' ] ],
            'mark paid'          => [ [ OrderPaymentManager::class, 'mark_paid' ], [ 'order_id' => 3 ] ],
            'recalculate'        => [ [ OrderModificationManager::class, 'recalculate' ], [ 'order_id' => 3 ] ],
            'remove item'        => [ [ OrderItemManager::class, 'remove_order_item' ], [ 'order_id' => 3, 'item_id' => 1 ] ],
            'get order meta'     => [ [ WcMetaManager::class, 'get_order_meta' ], [ 'order_id' => 3 ] ],
        ];
    }

    public function test_order_meta_read_needs_edit_not_read(): void {
        $GLOBALS['wp_granted_caps'][] = 'read_post:3';

        $this->assertSame( 'not_found_or_forbidden', WcMetaManager::get_order_meta( [ 'order_id' => 3, 'include_protected' => true ] )->get_error_code() );
    }

    public function test_fetch_order_checks_the_requested_capability(): void {
        $GLOBALS['wp_granted_caps'][] = 'read_post:3';

        $this->assertInstanceOf( \WC_Order::class, OrderGuard::fetchOrder( 3, 'read_post' ) );
        $this->assertInstanceOf( \WP_Error::class, OrderGuard::fetchOrder( 3 ) );
        $this->assertSame( OrderGuard::fetchOrder( 404 )->get_error_message(), OrderGuard::fetchOrder( 3 )->get_error_message() );
    }

    public function test_bulk_status_skips_orders_the_user_cannot_edit(): void {
        $result = OrderManager::bulk_orders( [ 'ids' => [ 3 ], 'status' => 'completed' ] );

        $this->assertSame( [ 3 ], $result['failed_ids'] );
        $this->assertArrayNotHasKey( 'wc_test_status_updates', $GLOBALS );
    }
}
