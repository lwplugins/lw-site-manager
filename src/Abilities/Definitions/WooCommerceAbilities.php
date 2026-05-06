<?php
/**
 * WooCommerce Abilities - Coordinator that delegates to focused registrars.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderCouponFeeAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderExtraAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderItemAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderPaymentAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\OrderShippingAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ProductExtraAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\ReportAbilities;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;

final class WooCommerceAbilities {

    public static function register( PermissionManager $permissions ): void {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        ProductAbilities::register( $permissions );
        ProductExtraAbilities::register( $permissions );
        OrderAbilities::register( $permissions );
        OrderExtraAbilities::register( $permissions );
        OrderItemAbilities::register( $permissions );
        OrderCouponFeeAbilities::register( $permissions );
        OrderShippingAbilities::register( $permissions );
        OrderPaymentAbilities::register( $permissions );
        ReportAbilities::register( $permissions );
    }
}
