<?php
/**
 * List payment gateways and switch them on or off.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Only the public face of a gateway is returned: id, titles, enabled state,
 * whether it still needs setup. Gateway settings (API keys, webhook secrets,
 * account details) are never read into a response.
 *
 * The switch mirrors WooCommerce's own Payments-screen toggle: a gateway that
 * reports needs_setup() cannot be enabled, and the change goes through
 * WC_Settings_API::update_option( 'enabled' ). Turning off the last enabled
 * gateway needs confirm_change: true, since checkout then has no way to pay.
 */
class PaymentGatewayManager extends AbstractService {

    public static function list_gateways( array $input = [] ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $items = [];
        foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
            $items[] = self::format( $gateway );
        }

        return [ 'gateways' => $items, 'total' => count( $items ) ];
    }

    public static function set_gateway_enabled( array $input ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        if ( ! isset( $input['enabled'] ) || ! is_bool( $input['enabled'] ) ) {
            return self::errorResponse( 'missing_enabled', 'enabled (true or false) is required.', 400 );
        }

        $gateways = WC()->payment_gateways()->payment_gateways();
        $id       = (string) ( $input['id'] ?? '' );
        $gateway  = $gateways[ $id ] ?? null;
        if ( ! $gateway instanceof \WC_Payment_Gateway ) {
            return self::errorResponse( 'gateway_not_found', 'Payment gateway not found.', 404 );
        }

        $enable = $input['enabled'];
        $was    = 'yes' === $gateway->get_option( 'enabled', 'no' );
        if ( $was === $enable ) {
            return [ 'success' => true, 'message' => 'No change.', 'gateway' => self::format( $gateway ) ];
        }

        if ( $enable && $gateway->needs_setup() ) {
            return self::errorResponse( 'needs_setup', 'This gateway needs setup in WooCommerce → Settings → Payments before it can be enabled.', 409 );
        }

        $others = array_filter(
            $gateways,
            static fn( $other ) => $other->id !== $gateway->id && 'yes' === $other->enabled
        );
        $unconfirmed = CatalogGuard::confirm(
            ! $enable && [] === $others,
            $input,
            'confirm_change',
            'This is the last enabled payment method; customers will not be able to pay for orders.'
        );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        do_action( 'woocommerce_update_option', [ 'id' => $gateway->get_option_key() ] );
        $gateway->update_option( 'enabled', $enable ? 'yes' : 'no' );
        do_action( 'woocommerce_update_options' );

        return [
            'success' => true,
            'message' => $enable ? 'Payment gateway enabled.' : 'Payment gateway disabled.',
            'gateway' => self::format( $gateway ),
        ];
    }

    private static function format( \WC_Payment_Gateway $gateway ): array {
        return [
            'id'           => (string) $gateway->id,
            'title'        => wp_strip_all_tags( (string) $gateway->get_title() ),
            'method_title' => wp_strip_all_tags( (string) $gateway->get_method_title() ),
            'description'  => wp_strip_all_tags( (string) $gateway->get_method_description() ),
            'enabled'      => 'yes' === $gateway->get_option( 'enabled', 'no' ),
            'needs_setup'  => (bool) $gateway->needs_setup(),
            'supports'     => array_values( array_map( 'strval', (array) $gateway->supports ) ),
        ];
    }

    private static function guard(): ?\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();

        return $unavailable ? $unavailable : CatalogGuard::storeManager();
    }
}
