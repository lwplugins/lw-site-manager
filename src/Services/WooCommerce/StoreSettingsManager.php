<?php
/**
 * Read and change the core WooCommerce store settings.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

/**
 * Goes through WooCommerce's own settings API: the field definitions come
 * from the General, Products and Tax settings pages, and the save is
 * WC_Admin_Settings::save_fields() with those definitions, so WooCommerce's
 * cleaning and its `woocommerce_admin_settings_sanitize_option` filters run
 * exactly as on the settings screen. Values are validated strictly first
 * (StoreSettingsValidator) and risky changes need confirm_change: true.
 */
class StoreSettingsManager extends AbstractService {

    public static function get_store_settings( array $input = [] ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $definitions = self::definitions();
        $settings    = [];
        $fields      = [];
        foreach ( StoreSettingsFields::FIELDS as $key => $page ) {
            $field = $definitions[ StoreSettingsFields::optionId( $key ) ] ?? null;
            if ( null === $field ) {
                continue;
            }
            $settings[ $key ] = self::current( $key, $field );
            if ( ! empty( $input['include_fields'] ) ) {
                $fields[ $key ] = self::describe( $key, $page, $field );
            }
        }

        $result = [
            'settings' => (object) $settings,
            'context'  => [
                'currency_symbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
                'base_location'   => wc_get_base_location(),
                'price_decimals'  => wc_get_price_decimals(),
            ],
        ];
        if ( ! empty( $input['include_fields'] ) ) {
            $result['fields'] = (object) $fields;
        }

        return $result;
    }

    public static function update_store_settings( array $input ): array|\WP_Error {
        $denied = self::guard();
        if ( $denied ) {
            return $denied;
        }

        $given = $input['settings'] ?? null;
        if ( ! is_array( $given ) || [] === $given ) {
            return self::errorResponse( 'missing_settings', 'Pass settings as an object of key => value.', 400 );
        }
        $unknown = StoreSettingsFields::unknownKeys( $given );
        if ( [] !== $unknown ) {
            return self::errorResponse( 'unknown_setting', 'Not an exposed store setting: ' . implode( ', ', $unknown ), 400 );
        }

        $plan = self::plan( $given );
        if ( $plan instanceof \WP_Error ) {
            return $plan;
        }
        if ( [] === $plan['changes'] ) {
            return [ 'success' => true, 'message' => 'Nothing to change.', 'changes' => [] ];
        }

        $risky       = array_intersect_key( $plan['changes'], array_flip( StoreSettingsFields::RISKY ) );
        $unconfirmed = CatalogGuard::confirm(
            [] !== $risky,
            $input,
            'confirm_change',
            'This changes currency, tax, unit or selling-location settings: existing prices, weights and dimensions are not converted.',
            [ 'changes' => $risky ]
        );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        \WC_Admin_Settings::save_fields( $plan['fields'], wp_slash( $plan['data'] ) );

        return [
            'success'  => true,
            'message'  => 'Store settings saved.',
            'changes'  => $plan['changes'],
            'settings' => (object) self::readBack( array_keys( $plan['changes'] ), $plan['fields'] ),
        ];
    }

    /**
     * Validate every given value and work out what actually changes.
     *
     * @return array{fields: array, data: array, changes: array}|\WP_Error
     */
    private static function plan( array $given ): array|\WP_Error {
        $definitions = self::definitions();
        $countries   = WC()->countries->get_countries();
        $states      = WC()->countries->get_states();
        $plan        = [ 'fields' => [], 'data' => [], 'changes' => [] ];

        foreach ( $given as $key => $value ) {
            $optionId = StoreSettingsFields::optionId( (string) $key );
            $field    = $definitions[ $optionId ] ?? null;
            if ( null === $field ) {
                return self::errorResponse( 'setting_unavailable', sprintf( '%s is not available on this store.', $key ), 400 );
            }

            $raw = StoreSettingsValidator::validate( (string) $key, $field, $value, $countries, $states );
            if ( $raw instanceof \WP_Error ) {
                return $raw;
            }

            $before = self::current( (string) $key, $field );
            $after  = StoreSettingsValidator::present( $field, $raw );
            if ( $before === $after ) {
                continue;
            }

            $plan['fields'][]           = $field;
            $plan['data'][ $optionId ]  = $raw;
            $plan['changes'][ $key ]    = [ 'from' => $before, 'to' => $after ];
        }

        return $plan;
    }

    /**
     * WooCommerce field definitions of the exposed settings, by option id.
     *
     * @return array<string, array>
     */
    private static function definitions(): array {
        $wanted = array_unique( array_values( StoreSettingsFields::FIELDS ) );
        $byId   = [];

        foreach ( \WC_Admin_Settings::get_settings_pages() as $page ) {
            if ( ! $page instanceof \WC_Settings_Page || ! in_array( $page->get_id(), $wanted, true ) ) {
                continue;
            }
            foreach ( $page->get_settings_for_section( '' ) as $field ) {
                if ( isset( $field['id'], $field['type'] ) ) {
                    $byId[ (string) $field['id'] ] = $field;
                }
            }
        }

        return $byId;
    }

    private static function current( string $key, array $field ): mixed {
        return StoreSettingsValidator::present( $field, get_option( StoreSettingsFields::optionId( $key ), $field['default'] ?? '' ) );
    }

    private static function readBack( array $keys, array $fields ): array {
        $byId = array_column( $fields, null, 'id' );
        $out  = [];
        foreach ( $keys as $key ) {
            $field       = $byId[ StoreSettingsFields::optionId( (string) $key ) ] ?? [ 'type' => 'text' ];
            $out[ $key ] = self::current( (string) $key, $field );
        }
        return $out;
    }

    private static function describe( string $key, string $page, array $field ): array {
        $type = (string) $field['type'];
        $info = [
            'label' => wp_strip_all_tags( (string) ( $field['title'] ?? $field['desc'] ?? $key ) ),
            'type'  => $type,
            'page'  => $page,
            'risky' => StoreSettingsFields::isRisky( $key ),
        ];
        if ( in_array( $type, [ 'select', 'radio' ], true ) ) {
            $info['options'] = array_map( 'strval', array_keys( (array) ( $field['options'] ?? [] ) ) );
        }
        if ( in_array( $type, [ 'multi_select_countries', 'single_select_country' ], true ) ) {
            $info['format'] = 'multi_select_countries' === $type ? 'array of ISO country codes' : 'CC or CC:STATE';
        }

        return $info;
    }

    private static function guard(): ?\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();

        return $unavailable ? $unavailable : CatalogGuard::storeManager();
    }
}
