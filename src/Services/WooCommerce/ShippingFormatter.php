<?php
/**
 * Output shapes for shipping zones and their methods.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

/**
 * Shipping method settings are returned so an agent can read a flat rate's
 * cost, but a value is masked when its field is a password field or its key
 * looks like a credential — third-party carrier methods keep API keys there.
 */
final class ShippingFormatter {

    public const MASK = '********';

    private const SECRET_KEY = '/(secret|password|passwd|api[_-]?key|token|private|credential)/i';

    public static function zone( \WC_Shipping_Zone $zone, bool $withMethods = true ): array {
        $data = [
            'id'        => (int) $zone->get_id(),
            'name'      => (string) $zone->get_zone_name( 'edit' ),
            'order'     => (int) $zone->get_zone_order( 'edit' ),
            'locations' => array_map(
                static fn( $location ) => [ 'code' => (string) $location->code, 'type' => (string) $location->type ],
                array_values( $zone->get_zone_locations( 'edit' ) )
            ),
            'formatted' => wp_strip_all_tags( (string) $zone->get_formatted_location() ),
        ];

        if ( $withMethods ) {
            $data['methods'] = array_values( array_map( [ self::class, 'method' ], $zone->get_shipping_methods( false, 'admin' ) ) );
        }

        return $data;
    }

    public static function method( \WC_Shipping_Method $method ): array {
        $settings = [];
        foreach ( $method->get_instance_form_fields() as $key => $field ) {
            if ( ! is_array( $field ) || in_array( $field['type'] ?? '', [ 'title', 'sectionend' ], true ) ) {
                continue;
            }
            $value            = $method->instance_settings[ $key ] ?? ( $field['default'] ?? '' );
            $settings[ $key ] = self::isSecret( (string) $key, $field ) && '' !== $value ? self::MASK : $value;
        }

        return [
            'instance_id' => (int) $method->instance_id,
            'method_id'   => (string) $method->id,
            'title'       => (string) ( $method->instance_settings['title'] ?? $method->get_method_title() ),
            'enabled'     => 'yes' === $method->enabled,
            'order'       => (int) $method->method_order,
            'settings'    => (object) $settings,
        ];
    }

    /**
     * Field descriptions of a method type (no values).
     */
    public static function fields( \WC_Shipping_Method $method ): array {
        $out = [];
        foreach ( $method->get_instance_form_fields() as $key => $field ) {
            if ( ! is_array( $field ) || in_array( $field['type'] ?? '', [ 'title', 'sectionend' ], true ) ) {
                continue;
            }
            $info = [
                'type'  => (string) ( $field['type'] ?? 'text' ),
                'label' => wp_strip_all_tags( (string) ( $field['title'] ?? $key ) ),
            ];
            if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
                $info['options'] = array_map( 'strval', array_keys( $field['options'] ) );
            }
            $out[ $key ] = $info;
        }
        return $out;
    }

    public static function isSecret( string $key, array $field ): bool {
        return 'password' === ( $field['type'] ?? '' ) || 1 === preg_match( self::SECRET_KEY, $key );
    }
}
