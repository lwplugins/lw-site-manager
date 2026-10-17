<?php
/**
 * Refund creation.
 *
 * Security regression guard: wc-create-refund refunded the full order total
 * when no amount was given, accepted any amount, and ran without a
 * confirmation although a refund cannot be undone from the ability.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\WooCommerce\OrderManager;
use PHPUnit\Framework\TestCase;

final class RefundTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $order                      = new \WC_Order( 3 );
        $order->refunded            = 40.0;
        $GLOBALS['wc_test_orders']  = [ 3 => $order ];
        $GLOBALS['wp_granted_caps'] = [ 'edit_shop_orders', 'edit_post:3' ];
        $GLOBALS['wc_test_refunds'] = [];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wc_test_orders'], $GLOBALS['wp_granted_caps'], $GLOBALS['wc_test_refunds'] );
        parent::tearDown();
    }

    public function test_refund_needs_confirmation_and_previews_the_amount(): void {
        $result = OrderManager::create_refund( [ 'order_id' => 3, 'amount' => 10 ] );

        $this->assertSame( 'confirm_refund_required', $result->get_error_code() );
        $this->assertSame( 10.0, $result->get_error_data()['amount'] );
        $this->assertSame( [], $GLOBALS['wc_test_refunds'] );
    }

    public function test_amount_must_be_positive_and_within_the_refundable_remainder(): void {
        foreach ( [ 0, -5, 60.01 ] as $amount ) {
            $result = OrderManager::create_refund( [ 'order_id' => 3, 'amount' => $amount, 'confirm_refund' => true ] );
            $this->assertSame( 'invalid_refund_amount', $result->get_error_code(), (string) $amount );
        }
        $this->assertSame( [], $GLOBALS['wc_test_refunds'] );
    }

    public function test_confirmed_refund_defaults_to_the_remaining_amount(): void {
        $result = OrderManager::create_refund( [ 'order_id' => 3, 'confirm_refund' => true ] );

        $this->assertTrue( $result['success'] );
        $this->assertSame( 60.0, $GLOBALS['wc_test_refunds'][0]['amount'] );
        $this->assertArrayNotHasKey( 'refund_payment', $GLOBALS['wc_test_refunds'][0] );
    }
}
