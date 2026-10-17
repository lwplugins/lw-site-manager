<?php
/**
 * Shipping methods inside a zone.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Adding and removing goes through WC_Shipping_Zone::add_shipping_method() /
 * delete_shipping_method(). WooCommerce has no CRUD setter for a method's
 * settings, enabled flag or order — its own screens and REST API write them
 * in the zone-methods controller — so updates are handed to that controller
 * (`PUT /wc/v3/shipping/zones/<zone>/methods/<instance>`) in-process. Its
 * per-field validation and its manage_woocommerce check then apply unchanged.
 */
class ShippingMethodManager extends AbstractService {

    public static function list_method_types( array $input = [] ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $types = [];
        foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
            if ( ! $method->supports( 'shipping-zones' ) ) {
                continue;
            }
            $types[] = [
                'method_id'   => (string) $method->id,
                'title'       => wp_strip_all_tags( (string) $method->get_method_title() ),
                'description' => wp_strip_all_tags( (string) $method->get_method_description() ),
                'fields'      => (object) ShippingFormatter::fields( $method ),
            ];
        }

        return [ 'method_types' => $types, 'total' => count( $types ) ];
    }

    public static function add_method( array $input ): array|\WP_Error {
        $zone = ShippingZoneManager::zoneFor( $input, true, 'zone_id' );
        if ( $zone instanceof \WP_Error ) {
            return $zone;
        }

        $type    = (string) ( $input['method_id'] ?? '' );
        $methods = WC()->shipping()->load_shipping_methods();
        if ( ! isset( $methods[ $type ] ) || ! $methods[ $type ]->supports( 'shipping-zones' ) ) {
            return self::errorResponse( 'invalid_method', 'method_id must be one of the zone method types (see wc-list-shipping-method-types).', 400 );
        }

        $instanceId = (int) $zone->add_shipping_method( $type );
        if ( $instanceId <= 0 ) {
            return self::errorResponse( 'add_failed', 'The shipping method could not be added.', 500 );
        }

        if ( isset( $input['settings'] ) || isset( $input['enabled'] ) ) {
            $updated = self::put( (int) $zone->get_id(), $instanceId, $input );
            if ( $updated instanceof \WP_Error ) {
                return $updated;
            }
        }

        return [
            'success' => true,
            'message' => 'Shipping method added.',
            'zone_id' => (int) $zone->get_id(),
            'method'  => self::reload( (int) $zone->get_id(), $instanceId ),
        ];
    }

    public static function update_method( array $input ): array|\WP_Error {
        $located = self::locate( $input );
        if ( $located instanceof \WP_Error ) {
            return $located;
        }
        [ $zoneId, $instanceId ] = $located;

        if ( ! isset( $input['settings'] ) && ! isset( $input['enabled'] ) && ! isset( $input['order'] ) ) {
            return self::errorResponse( 'nothing_to_update', 'Pass settings, enabled or order.', 400 );
        }

        $updated = self::put( $zoneId, $instanceId, $input );
        if ( $updated instanceof \WP_Error ) {
            return $updated;
        }

        return [
            'success' => true,
            'message' => 'Shipping method updated.',
            'zone_id' => $zoneId,
            'method'  => self::reload( $zoneId, $instanceId ),
        ];
    }

    public static function delete_method( array $input ): array|\WP_Error {
        $located = self::locate( $input );
        if ( $located instanceof \WP_Error ) {
            return $located;
        }
        [ $zoneId, $instanceId ] = $located;

        $unconfirmed = CatalogGuard::confirm( true, $input, 'confirm_delete', 'Removing a shipping method from a zone is permanent, including its settings.' );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        $zone = new \WC_Shipping_Zone( $zoneId );
        $zone->delete_shipping_method( $instanceId );

        return [ 'success' => true, 'message' => 'Shipping method removed.', 'zone_id' => $zoneId, 'instance_id' => $instanceId ];
    }

    /**
     * Check that the instance belongs to the given zone.
     *
     * @return array{0: int, 1: int}|\WP_Error
     */
    private static function locate( array $input ): array|\WP_Error {
        $zone = ShippingZoneManager::zoneFor( $input, true, 'zone_id' );
        if ( $zone instanceof \WP_Error ) {
            return $zone;
        }

        $instanceId = (int) ( $input['instance_id'] ?? 0 );
        foreach ( $zone->get_shipping_methods( false, 'admin' ) as $method ) {
            if ( (int) $method->instance_id === $instanceId && $instanceId > 0 ) {
                return [ (int) $zone->get_id(), $instanceId ];
            }
        }

        return self::errorResponse( 'method_not_found', 'No shipping method with that instance_id in this zone.', 404 );
    }

    /**
     * Hand settings / enabled / order to WooCommerce's zone-method controller.
     */
    private static function put( int $zoneId, int $instanceId, array $input ): bool|\WP_Error {
        $request = new \WP_REST_Request( 'PUT', sprintf( '/wc/v3/shipping/zones/%d/methods/%d', $zoneId, $instanceId ) );
        if ( isset( $input['settings'] ) ) {
            if ( ! is_array( $input['settings'] ) ) {
                return self::errorResponse( 'invalid_settings', 'settings must be an object of field => value.', 400 );
            }
            // A masked secret sent back unchanged must not overwrite the stored value.
            $settings = array_filter( $input['settings'], static fn( $value ) => ShippingFormatter::MASK !== $value );
            $request->set_param( 'settings', $settings );
        }
        if ( isset( $input['enabled'] ) ) {
            $request->set_param( 'enabled', (bool) $input['enabled'] );
        }
        if ( isset( $input['order'] ) ) {
            $request->set_param( 'order', max( 0, (int) $input['order'] ) );
        }

        $response = rest_do_request( $request );
        if ( $response->is_error() ) {
            $error = $response->as_error();
            return self::errorResponse( (string) $error->get_error_code(), $error->get_error_message(), $response->get_status() );
        }

        return true;
    }

    private static function reload( int $zoneId, int $instanceId ): ?array {
        foreach ( ( new \WC_Shipping_Zone( $zoneId ) )->get_shipping_methods( false, 'admin' ) as $method ) {
            if ( (int) $method->instance_id === $instanceId ) {
                return ShippingFormatter::method( $method );
            }
        }
        return null;
    }

    private static function guard(): ?\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();

        return $unavailable ? $unavailable : CatalogGuard::storeManager();
    }
}
