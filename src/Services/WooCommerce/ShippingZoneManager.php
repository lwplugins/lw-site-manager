<?php
/**
 * Shipping zones through WooCommerce's WC_Shipping_Zone CRUD.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Zone 0 is WooCommerce's "rest of the world" zone: it can carry methods but
 * has no name, order or locations of its own and cannot be deleted.
 */
class ShippingZoneManager extends AbstractService {

    private const LOCATION_TYPES = [ 'postcode', 'state', 'country', 'continent' ];

    public static function list_zones( array $input = [] ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $zones = [];
        foreach ( \WC_Shipping_Zones::get_zones( 'admin' ) as $row ) {
            $zones[] = ShippingFormatter::zone( new \WC_Shipping_Zone( (int) $row['id'] ) );
        }
        $zones[] = ShippingFormatter::zone( new \WC_Shipping_Zone( 0 ) );

        return [ 'zones' => $zones, 'total' => count( $zones ) ];
    }

    public static function get_zone( array $input ): array|\WP_Error {
        $zone = self::zoneFor( $input, true );
        if ( $zone instanceof \WP_Error ) {
            return $zone;
        }

        return [ 'zone' => ShippingFormatter::zone( $zone ) ];
    }

    public static function create_zone( array $input ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $name = trim( (string) ( $input['name'] ?? '' ) );
        if ( '' === $name ) {
            return self::errorResponse( 'missing_name', 'name is required.', 400 );
        }

        $zone  = new \WC_Shipping_Zone();
        $error = self::apply( $zone, $input );
        if ( $error ) {
            return $error;
        }
        $zone->save();

        return [
            'success' => true,
            'message' => 'Shipping zone created.',
            'zone'    => ShippingFormatter::zone( new \WC_Shipping_Zone( $zone->get_id() ) ),
        ];
    }

    public static function update_zone( array $input ): array|\WP_Error {
        $zone = self::zoneFor( $input, false );
        if ( $zone instanceof \WP_Error ) {
            return $zone;
        }

        $error = self::apply( $zone, $input );
        if ( $error ) {
            return $error;
        }
        $zone->save();

        return [
            'success' => true,
            'message' => 'Shipping zone updated.',
            'zone'    => ShippingFormatter::zone( new \WC_Shipping_Zone( $zone->get_id() ) ),
        ];
    }

    public static function delete_zone( array $input ): array|\WP_Error {
        $zone = self::zoneFor( $input, false );
        if ( $zone instanceof \WP_Error ) {
            return $zone;
        }

        $name        = (string) $zone->get_zone_name( 'edit' );
        $unconfirmed = CatalogGuard::confirm(
            true,
            $input,
            'confirm_delete',
            sprintf( 'Deleting zone "%s" is permanent and removes its %d shipping method(s).', $name, count( $zone->get_shipping_methods() ) )
        );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        $id = (int) $zone->get_id();
        $zone->delete();

        return [ 'success' => true, 'message' => 'Shipping zone deleted permanently.', 'id' => $id, 'name' => $name ];
    }

    /**
     * Load a zone; zone 0 only when the caller allows it.
     */
    public static function zoneFor( array $input, bool $allowRestOfWorld, string $key = 'id' ): \WC_Shipping_Zone|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $id = (int) ( $input[ $key ] ?? -1 );
        if ( 0 === $id && ! $allowRestOfWorld ) {
            return self::errorResponse( 'rest_of_world_zone', 'The "rest of the world" zone (0) has no name or locations and cannot be deleted.', 400 );
        }
        if ( $id < 0 ) {
            return self::errorResponse( 'missing_id', $key . ' is required.', 400 );
        }

        $zone = 0 === $id ? new \WC_Shipping_Zone( 0 ) : \WC_Shipping_Zones::get_zone( $id );
        if ( ! $zone instanceof \WC_Shipping_Zone || ( 0 !== $id && (int) $zone->get_id() !== $id ) ) {
            return self::errorResponse( 'zone_not_found', 'Shipping zone not found.', 404 );
        }

        return $zone;
    }

    /**
     * Set name, order and (replacing) locations from input.
     */
    private static function apply( \WC_Shipping_Zone $zone, array $input ): ?\WP_Error {
        if ( isset( $input['name'] ) ) {
            $name = trim( wp_strip_all_tags( (string) $input['name'] ) );
            if ( '' === $name ) {
                return self::errorResponse( 'missing_name', 'name cannot be empty.', 400 );
            }
            $zone->set_zone_name( $name );
        }
        if ( isset( $input['order'] ) ) {
            $zone->set_zone_order( (int) $input['order'] );
        }
        if ( isset( $input['locations'] ) ) {
            $locations = self::locations( $input['locations'] );
            if ( $locations instanceof \WP_Error ) {
                return $locations;
            }
            $zone->set_locations( $locations );
        }

        return null;
    }

    /**
     * Validate locations against WooCommerce's countries, states and continents.
     */
    private static function locations( mixed $given ): array|\WP_Error {
        if ( ! is_array( $given ) ) {
            return self::errorResponse( 'invalid_locations', 'locations must be an array of { code, type }.', 400 );
        }

        $countries  = WC()->countries->get_countries();
        $continents = WC()->countries->get_continents();
        $out        = [];
        foreach ( $given as $location ) {
            $type = (string) ( $location['type'] ?? '' );
            $code = trim( (string) ( $location['code'] ?? '' ) );
            if ( ! in_array( $type, self::LOCATION_TYPES, true ) || '' === $code ) {
                return self::errorResponse( 'invalid_location', 'Each location needs a code and a type (postcode, state, country, continent).', 400 );
            }
            if ( 'country' === $type ) {
                $code = strtoupper( $code );
                $ok   = isset( $countries[ $code ] );
            } elseif ( 'continent' === $type ) {
                $code = strtoupper( $code );
                $ok   = isset( $continents[ $code ] );
            } elseif ( 'state' === $type ) {
                [ $country, $state ] = array_pad( explode( ':', $code, 2 ), 2, '' );
                $ok = '' !== $state && isset( WC()->countries->get_states( strtoupper( $country ) )[ $state ] );
                $code = strtoupper( $country ) . ':' . $state;
            } else {
                $ok = strlen( $code ) <= 100;
            }
            if ( ! $ok ) {
                return self::errorResponse( 'invalid_location', sprintf( 'Unknown %s: %s', $type, $code ), 400 );
            }
            $out[] = [ 'code' => $code, 'type' => $type ];
        }

        return $out;
    }

    private static function guard(): ?\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();

        return $unavailable ? $unavailable : CatalogGuard::storeManager();
    }
}
