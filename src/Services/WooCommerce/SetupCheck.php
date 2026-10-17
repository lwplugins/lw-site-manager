<?php
/**
 * Read-only WooCommerce setup check.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * One call that tells an agent what the store runs on and what is missing
 * before it can sell: pages, payment methods, shipping, address, database
 * version. Nothing is written.
 */
class SetupCheck extends AbstractService {

    /** Detected by class or constant; label => markers. */
    private const EXTENSIONS = [
        'subscriptions'   => [ 'WC_Subscriptions', 'WC_SUBSCRIPTIONS_VERSION' ],
        'bookings'        => [ 'WC_Bookings', 'WC_BOOKINGS_VERSION' ],
        'memberships'     => [ 'WC_Memberships', 'WC_MEMBERSHIPS_VERSION' ],
        'product_addons'  => [ 'WC_Product_Addons', 'WC_PRODUCT_ADDONS_VERSION' ],
        'stripe'          => [ 'WC_Stripe', 'WC_STRIPE_VERSION' ],
        'woopayments'     => [ 'WC_Payments', 'WCPAY_VERSION_NUMBER' ],
        'paypal_payments' => [ 'WooCommerce\PayPalCommerce\PPCP', 'PAYPAL_INTEGRATION_DATE' ],
    ];

    private const PAGES = [ 'shop', 'cart', 'checkout', 'myaccount', 'terms' ];

    public static function check_setup( array $input = [] ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }
        $denied = CatalogGuard::storeManager();
        if ( $denied ) {
            return $denied;
        }

        $issues   = [];
        $pages    = self::pages( $issues );
        $payments = self::payments( $issues );
        $shipping = self::shipping( $issues );
        $base     = wc_get_base_location();
        $dbVer    = (string) get_option( 'woocommerce_db_version', '' );

        if ( '' === trim( (string) get_option( 'woocommerce_store_address', '' ) ) ) {
            $issues[] = [ 'code' => 'no_store_address', 'message' => 'The store address is empty (used for taxes, shipping origin and emails).' ];
        }
        if ( '' !== $dbVer && version_compare( $dbVer, WC_VERSION, '<' ) ) {
            $issues[] = [ 'code' => 'db_update_needed', 'message' => 'The WooCommerce database needs an update.' ];
        }
        if ( '' === (string) get_option( 'permalink_structure', '' ) ) {
            $issues[] = [ 'code' => 'plain_permalinks', 'message' => 'Plain permalinks are in use; product and endpoint URLs are not pretty.' ];
        }

        return [
            'woocommerce' => [ 'version' => WC_VERSION, 'db_version' => $dbVer ],
            'store'       => [
                'currency'           => get_woocommerce_currency(),
                'base_country'       => (string) $base['country'],
                'base_state'         => (string) $base['state'],
                'calc_taxes'         => wc_tax_enabled(),
                'prices_include_tax' => wc_prices_include_tax(),
                'coming_soon'        => 'yes' === get_option( 'woocommerce_coming_soon', 'no' ),
            ],
            'pages'       => $pages,
            'payments'    => $payments,
            'shipping'    => $shipping,
            'catalog'     => [
                'published_products'  => (int) ( wp_count_posts( 'product' )->publish ?? 0 ),
                'default_category_id' => (int) get_option( 'default_product_cat', 0 ),
                'brands_available'    => taxonomy_exists( 'product_brand' ),
            ],
            'hpos_enabled' => self::hposEnabled(),
            'extensions'   => self::extensions(),
            'issues'       => $issues,
            'ready'        => [] === $issues,
        ];
    }

    private static function pages( array &$issues ): array {
        $out = [];
        foreach ( self::PAGES as $name ) {
            $id     = (int) wc_get_page_id( $name );
            $status = $id > 0 ? (string) get_post_status( $id ) : '';
            $out[ $name ] = [ 'id' => max( 0, $id ), 'status' => $status ];
            if ( 'terms' !== $name && 'publish' !== $status ) {
                $issues[] = [ 'code' => 'page_missing_' . $name, 'message' => sprintf( 'The %s page is not set or not published.', $name ) ];
            }
        }

        $checkout = $out['checkout']['id'] ? get_post( $out['checkout']['id'] ) : null;
        $out['checkout_uses_block'] = $checkout instanceof \WP_Post && has_block( 'woocommerce/checkout', $checkout );

        return $out;
    }

    private static function payments( array &$issues ): array {
        $enabled = [];
        foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
            if ( 'yes' === $gateway->enabled ) {
                $enabled[] = (string) $gateway->id;
            }
        }
        if ( [] === $enabled ) {
            $issues[] = [ 'code' => 'no_payment_method', 'message' => 'No payment method is enabled.' ];
        }

        return [ 'enabled' => $enabled ];
    }

    private static function shipping( array &$issues ): array {
        $zones       = \WC_Shipping_Zones::get_zones( 'admin' );
        $withMethods = 0;
        foreach ( $zones as $zone ) {
            if ( ! empty( $zone['shipping_methods'] ) ) {
                ++$withMethods;
            }
        }
        $rest = count( ( new \WC_Shipping_Zone( 0 ) )->get_shipping_methods( true ) );

        if ( 0 === $withMethods && 0 === $rest && wc_shipping_enabled() ) {
            $issues[] = [ 'code' => 'no_shipping_method', 'message' => 'Shipping is enabled but no zone has a shipping method.' ];
        }

        return [
            'enabled'                    => wc_shipping_enabled(),
            'zones'                      => count( $zones ),
            'zones_with_methods'         => $withMethods,
            'rest_of_world_methods'      => $rest,
        ];
    }

    private static function hposEnabled(): bool {
        $class = 'Automattic\WooCommerce\Utilities\OrderUtil';

        return class_exists( $class ) && $class::custom_orders_table_usage_is_enabled();
    }

    private static function extensions(): array {
        $out = [];
        foreach ( self::EXTENSIONS as $name => [ $class, $constant ] ) {
            $out[ $name ] = class_exists( $class ) || defined( $constant );
        }
        return $out;
    }
}
