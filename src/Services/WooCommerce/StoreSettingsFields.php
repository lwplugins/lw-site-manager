<?php
/**
 * The store settings the abilities expose, and which of them are risky.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

/**
 * Pure data: ability key => [ WooCommerce settings page, option id ].
 *
 * The key is the option id without the `woocommerce_` prefix. Field types,
 * labels and allowed options are not copied here: they are read from
 * WooCommerce's own settings pages at runtime (StoreSettingsManager).
 */
final class StoreSettingsFields {

    public const FIELDS = [
        // General: store address.
        'store_address'               => 'general',
        'store_address_2'             => 'general',
        'store_city'                  => 'general',
        'default_country'             => 'general',
        'store_postcode'              => 'general',
        // General: selling and shipping locations.
        'allowed_countries'           => 'general',
        'all_except_countries'        => 'general',
        'specific_allowed_countries'  => 'general',
        'ship_to_countries'           => 'general',
        'specific_ship_to_countries'  => 'general',
        'default_customer_address'    => 'general',
        // General: taxes and coupons.
        'calc_taxes'                  => 'general',
        'enable_coupons'              => 'general',
        'calc_discounts_sequentially' => 'general',
        // General: currency.
        'currency'                    => 'general',
        'currency_pos'                => 'general',
        'price_thousand_sep'          => 'general',
        'price_decimal_sep'           => 'general',
        'price_num_decimals'          => 'general',
        // Products: units.
        'weight_unit'                 => 'products',
        'dimension_unit'              => 'products',
        // Tax: calculation and display.
        'prices_include_tax'          => 'tax',
        'tax_based_on'                => 'tax',
        'shipping_tax_class'          => 'tax',
        'tax_round_at_subtotal'       => 'tax',
        'tax_display_shop'            => 'tax',
        'tax_display_cart'            => 'tax',
        'price_display_suffix'        => 'tax',
        'tax_total_display'           => 'tax',
    ];

    /**
     * Changes that alter money, tax or where the store sells. Existing prices,
     * weights and dimensions are not converted when these change, so a change
     * needs confirm_change: true.
     */
    public const RISKY = [
        'currency',
        'price_num_decimals',
        'calc_taxes',
        'prices_include_tax',
        'tax_based_on',
        'weight_unit',
        'dimension_unit',
        'default_country',
        'allowed_countries',
        'all_except_countries',
        'specific_allowed_countries',
        'ship_to_countries',
        'specific_ship_to_countries',
    ];

    public static function optionId( string $key ): string {
        return 'woocommerce_' . $key;
    }

    public static function isRisky( string $key ): bool {
        return in_array( $key, self::RISKY, true );
    }

    /**
     * Input keys that are not exposed.
     *
     * @return string[] Keys of the input that are not exposed settings.
     */
    public static function unknownKeys( array $settings ): array {
        return array_values( array_diff( array_map( 'strval', array_keys( $settings ) ), array_keys( self::FIELDS ) ) );
    }
}
