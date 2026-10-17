<?php
/**
 * Strict validation of store-setting values against WooCommerce field definitions.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

/**
 * WC_Admin_Settings::save_fields() silently falls back to the default when a
 * select value is not allowed (a mistyped currency would become USD). This
 * validator runs first and rejects such values, so the save only ever sees
 * values WooCommerce accepts; save_fields() then applies WooCommerce's own
 * cleaning and sanitize filters.
 *
 * Pure: the country and state lists are passed in.
 */
final class StoreSettingsValidator {

    private const MAX_TEXT = 200;

    /**
     * Validate one value and return it in the form save_fields() expects.
     *
     * @param array $field     WooCommerce field definition (type, options …).
     * @param mixed $value     Value from the ability input.
     * @param array $countries Country code => name.
     * @param array $states    Country code => [ state code => name ].
     * @return string|string[]|\WP_Error
     */
    public static function validate( string $key, array $field, mixed $value, array $countries, array $states ): string|array|\WP_Error {
        switch ( $field['type'] ?? '' ) {
            case 'checkbox':
                if ( is_bool( $value ) ) {
                    return $value ? 'yes' : 'no';
                }
                return in_array( $value, [ 'yes', 'no' ], true ) ? $value : self::invalid( $key, 'true or false' );

            case 'select':
            case 'radio':
                $allowed = array_map( 'strval', array_keys( (array) ( $field['options'] ?? [] ) ) );
                return is_scalar( $value ) && in_array( (string) $value, $allowed, true )
                    ? (string) $value
                    : self::invalid( $key, 'one of: ' . implode( ', ', array_slice( $allowed, 0, 20 ) ) . ( count( $allowed ) > 20 ? ', …' : '' ) );

            case 'multi_select_countries':
                if ( ! is_array( $value ) ) {
                    return self::invalid( $key, 'an array of country codes' );
                }
                $codes = array_values( array_unique( array_map( 'strtoupper', array_map( 'strval', $value ) ) ) );
                $bad   = array_diff( $codes, array_keys( $countries ) );
                return [] === $bad ? $codes : self::invalid( $key, 'country codes only (unknown: ' . implode( ', ', $bad ) . ')' );

            case 'single_select_country':
                return self::country( $key, $value, $countries, $states );

            case 'number':
                return is_numeric( $value ) && (int) $value == $value && (int) $value >= 0 && (int) $value <= 10
                    ? (string) (int) $value
                    : self::invalid( $key, 'a whole number from 0 to 10' );

            case 'text':
                return is_scalar( $value ) && strlen( (string) $value ) <= self::MAX_TEXT
                    ? (string) $value
                    : self::invalid( $key, 'text of at most ' . self::MAX_TEXT . ' characters' );
        }

        return ResponseFormatter::error( 'unsupported_setting', sprintf( '%s cannot be changed here.', $key ), 400 );
    }

    /**
     * Stored option value => the value the abilities return.
     */
    public static function present( array $field, mixed $stored ): mixed {
        switch ( $field['type'] ?? '' ) {
            case 'checkbox':
                return 'yes' === $stored;
            case 'multi_select_countries':
                return array_values( array_map( 'strval', (array) ( $stored ? $stored : [] ) ) );
        }

        return is_scalar( $stored ) ? (string) $stored : '';
    }

    /**
     * "CC" or "CC:STATE", with the state checked when the country has states.
     */
    private static function country( string $key, mixed $value, array $countries, array $states ): string|\WP_Error {
        $parts   = is_string( $value ) ? explode( ':', $value, 2 ) : [ '' ];
        $country = strtoupper( $parts[0] );
        $state   = $parts[1] ?? '';
        if ( '' !== $state && ! isset( $states[ $country ][ $state ] ) && isset( $states[ $country ][ strtoupper( $state ) ] ) ) {
            $state = strtoupper( $state );
        }

        if ( ! isset( $countries[ $country ] ) ) {
            return self::invalid( $key, 'a country code, optionally with a state ("US:CA")' );
        }
        if ( '' !== $state && ! isset( $states[ $country ][ $state ] ) ) {
            return self::invalid( $key, sprintf( 'a state code of %s', $country ) );
        }

        return '' === $state ? $country : $country . ':' . $state;
    }

    private static function invalid( string $key, string $expected ): \WP_Error {
        return ResponseFormatter::error( 'invalid_setting', sprintf( '%s must be %s.', $key, $expected ), 400, [ 'setting' => $key ] );
    }
}
