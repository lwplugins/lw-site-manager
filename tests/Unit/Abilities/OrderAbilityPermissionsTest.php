<?php
/**
 * Authorization wiring for the WooCommerce order abilities.
 *
 * Security regression guard: order-scoped abilities were gated on
 * can_edit_posts (current_user_can('edit_posts')), which the default
 * Contributor role holds. Orders are not posts — they must be gated on
 * can_manage_orders (edit_shop_orders || manage_woocommerce), which is what
 * the sibling order-item / coupon / shipping / payment abilities already use.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Abilities;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderExtraAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\WcMetaAbilities;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use PHPUnit\Framework\TestCase;

final class OrderAbilityPermissionsTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_registered_abilities();

        $permissions = new PermissionManager();
        OrderAbilities::register( $permissions );
        OrderExtraAbilities::register( $permissions );
        WcMetaAbilities::register( $permissions );
    }

    protected function tearDown(): void {
        reset_registered_abilities();
        parent::tearDown();
    }

    /**
     * Every order-scoped ability must use the order capability, never a post one.
     *
     * @dataProvider provide_order_abilities
     */
    public function test_order_abilities_require_order_capability( string $ability, string $method = 'can_manage_orders' ): void {
        $registered = get_registered_abilities();

        $this->assertArrayHasKey( $ability, $registered, "Ability {$ability} was not registered" );

        $callback = $registered[ $ability ]['permission_callback'];
        $this->assertIsArray( $callback );
        $this->assertSame(
            $method,
            $callback[1],
            "{$ability} must be gated on {$method}, not " . $callback[1]
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function provide_order_abilities(): array {
        $abilities = [
            // Core CRUD.
            'site-manager/wc-list-orders',
            'site-manager/wc-get-order',
            'site-manager/wc-create-order',
            'site-manager/wc-update-order',
            'site-manager/wc-delete-order',
            'site-manager/wc-update-order-status',
            // Extras.
            'site-manager/wc-list-order-statuses',
            'site-manager/wc-create-refund',
            'site-manager/wc-list-order-notes',
            'site-manager/wc-add-order-note',
            'site-manager/wc-bulk-orders',
            // Order meta.
            'site-manager/wc-get-order-meta',
            'site-manager/wc-set-order-meta',
            'site-manager/wc-delete-order-meta',
        ];

        // Where WooCommerce's REST orders controller asks for a narrower
        // capability than edit_shop_orders, mirror it.
        $specific = [
            'site-manager/wc-list-orders'  => 'can_read_orders',
            'site-manager/wc-create-order' => 'can_create_orders',
            'site-manager/wc-delete-order' => 'can_delete_orders',
            'site-manager/wc-bulk-orders'  => 'can_batch_orders',
        ];

        $cases = [];
        foreach ( $abilities as $ability ) {
            $cases[ $ability ] = [ $ability, $specific[ $ability ] ?? 'can_manage_orders' ];
        }

        return $cases;
    }
}
