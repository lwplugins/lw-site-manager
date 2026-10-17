<?php
/**
 * Unit tests for the pure rules behind the WooCommerce catalog, settings and
 * shipping abilities: term extras, store-setting validation, confirmations,
 * secret masking and the capabilities that gate them.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\CatalogGuard;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductTermFields;
use LightweightPlugins\SiteManager\Services\WooCommerce\ShippingFormatter;
use LightweightPlugins\SiteManager\Services\WooCommerce\StoreSettingsFields;
use LightweightPlugins\SiteManager\Services\WooCommerce\StoreSettingsValidator;
use PHPUnit\Framework\TestCase;

final class CatalogRulesTest extends TestCase {

    private const COUNTRIES = [ 'HU' => 'Hungary', 'US' => 'United States', 'AT' => 'Austria' ];
    private const STATES    = [ 'US' => [ 'CA' => 'California' ], 'HU' => [ 'BU' => 'Budapest' ], 'AT' => [] ];

    protected function tearDown(): void {
        unset( $GLOBALS['wp_granted_caps'] );
        parent::tearDown();
    }

    // ----- Term extras -----------------------------------------------------

    public function test_category_extras_map_to_woocommerce_term_meta_keys(): void {
        $meta = ProductTermFields::metaFromInput( 'product_cat', [ 'image_id' => 12, 'display_type' => 'both', 'menu_order' => '3' ] );

        $this->assertSame( [ 'thumbnail_id' => 12, 'display_type' => 'both', 'order' => 3 ], $meta );
    }

    public function test_only_sent_fields_are_returned(): void {
        $this->assertSame( [ 'thumbnail_id' => 0 ], ProductTermFields::metaFromInput( 'product_brand', [ 'image_id' => 0, 'name' => 'x' ] ) );
    }

    public function test_unsupported_extra_is_an_error_not_a_silent_no_op(): void {
        $result = ProductTermFields::metaFromInput( 'product_tag', [ 'image_id' => 5 ] );
        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'unsupported_field', $result->get_error_code() );

        $brand = ProductTermFields::metaFromInput( 'product_brand', [ 'display_type' => 'products' ] );
        $this->assertSame( 'unsupported_field', $brand->get_error_code() );
    }

    public function test_invalid_display_type_and_negative_image_are_refused(): void {
        $this->assertSame( 'invalid_display_type', ProductTermFields::metaFromInput( 'product_cat', [ 'display_type' => 'grid' ] )->get_error_code() );
        $this->assertSame( 'invalid_image_id', ProductTermFields::metaFromInput( 'product_cat', [ 'image_id' => -1 ] )->get_error_code() );
        $this->assertSame( 'invalid_menu_order', ProductTermFields::metaFromInput( 'product_cat', [ 'menu_order' => '1.5' ] )->get_error_code() );
    }

    public function test_only_the_four_product_taxonomies_are_managed(): void {
        $this->assertSame( [ 'product_cat', 'product_tag', 'product_brand', 'product_shipping_class' ], ProductTermFields::taxonomies() );
        $this->assertFalse( ProductTermFields::isManaged( 'category' ) );
    }

    // ----- Store settings ----------------------------------------------------

    public function test_select_value_outside_the_options_is_refused_instead_of_defaulted(): void {
        $field = [ 'type' => 'select', 'options' => [ 'USD' => 'Dollar', 'HUF' => 'Forint' ], 'default' => 'USD' ];

        $this->assertSame( 'HUF', StoreSettingsValidator::validate( 'currency', $field, 'HUF', self::COUNTRIES, self::STATES ) );
        $error = StoreSettingsValidator::validate( 'currency', $field, 'XXX', self::COUNTRIES, self::STATES );
        $this->assertInstanceOf( \WP_Error::class, $error );
        $this->assertSame( 'invalid_setting', $error->get_error_code() );
    }

    public function test_checkbox_takes_booleans_and_stores_yes_no(): void {
        $field = [ 'type' => 'checkbox' ];

        $this->assertSame( 'yes', StoreSettingsValidator::validate( 'calc_taxes', $field, true, [], [] ) );
        $this->assertSame( 'no', StoreSettingsValidator::validate( 'calc_taxes', $field, false, [], [] ) );
        $this->assertInstanceOf( \WP_Error::class, StoreSettingsValidator::validate( 'calc_taxes', $field, 'maybe', [], [] ) );
        $this->assertTrue( StoreSettingsValidator::present( $field, 'yes' ) );
    }

    public function test_country_and_state_are_checked(): void {
        $field = [ 'type' => 'single_select_country' ];

        $this->assertSame( 'US:CA', StoreSettingsValidator::validate( 'default_country', $field, 'us:ca', self::COUNTRIES, self::STATES ) );
        $this->assertSame( 'HU', StoreSettingsValidator::validate( 'default_country', $field, 'HU', self::COUNTRIES, self::STATES ) );
        $this->assertInstanceOf( \WP_Error::class, StoreSettingsValidator::validate( 'default_country', $field, 'US:ZZ', self::COUNTRIES, self::STATES ) );
        $this->assertInstanceOf( \WP_Error::class, StoreSettingsValidator::validate( 'default_country', $field, 'XX', self::COUNTRIES, self::STATES ) );
    }

    public function test_country_lists_reject_unknown_codes(): void {
        $field = [ 'type' => 'multi_select_countries' ];

        $this->assertSame( [ 'HU', 'AT' ], StoreSettingsValidator::validate( 'specific_allowed_countries', $field, [ 'hu', 'AT', 'HU' ], self::COUNTRIES, self::STATES ) );
        $this->assertInstanceOf( \WP_Error::class, StoreSettingsValidator::validate( 'specific_allowed_countries', $field, [ 'HU', 'ZZ' ], self::COUNTRIES, self::STATES ) );
        $this->assertInstanceOf( \WP_Error::class, StoreSettingsValidator::validate( 'specific_allowed_countries', $field, 'HU', self::COUNTRIES, self::STATES ) );
    }

    public function test_unexposed_field_types_cannot_be_written(): void {
        $error = StoreSettingsValidator::validate( 'tax_classes', [ 'type' => 'textarea' ], 'x', [], [] );
        $this->assertSame( 'unsupported_setting', $error->get_error_code() );
    }

    public function test_risky_settings_and_unknown_keys(): void {
        $this->assertTrue( StoreSettingsFields::isRisky( 'currency' ) );
        $this->assertTrue( StoreSettingsFields::isRisky( 'weight_unit' ) );
        $this->assertFalse( StoreSettingsFields::isRisky( 'store_city' ) );
        $this->assertSame( [ 'woocommerce_api_key' ], StoreSettingsFields::unknownKeys( [ 'store_city' => 'x', 'woocommerce_api_key' => 'y' ] ) );
        foreach ( StoreSettingsFields::RISKY as $key ) {
            $this->assertArrayHasKey( $key, StoreSettingsFields::FIELDS, $key . ' must be an exposed field' );
        }
    }

    // ----- Confirmation and masking -----------------------------------------

    public function test_confirm_blocks_until_the_flag_is_strictly_true(): void {
        $this->assertNull( CatalogGuard::confirm( false, [], 'confirm_delete', 'x' ) );
        $this->assertSame( 'confirm_delete_required', CatalogGuard::confirm( true, [], 'confirm_delete', 'x' )->get_error_code() );
        $this->assertSame( 409, CatalogGuard::confirm( true, [ 'confirm_delete' => 'true' ], 'confirm_delete', 'x' )->get_error_data()['status'] );
        $this->assertNull( CatalogGuard::confirm( true, [ 'confirm_delete' => true ], 'confirm_delete', 'x' ) );
    }

    public function test_confirm_error_carries_the_preview(): void {
        $error = CatalogGuard::confirm( true, [], 'confirm_change', 'x', [ 'changes' => [ 'currency' => [ 'from' => 'USD', 'to' => 'HUF' ] ] ] );
        $this->assertSame( 'HUF', $error->get_error_data()['changes']['currency']['to'] );
    }

    public function test_credential_like_shipping_fields_are_secret(): void {
        $this->assertTrue( ShippingFormatter::isSecret( 'api_key', [ 'type' => 'text' ] ) );
        $this->assertTrue( ShippingFormatter::isSecret( 'client_secret', [ 'type' => 'text' ] ) );
        $this->assertTrue( ShippingFormatter::isSecret( 'login', [ 'type' => 'password' ] ) );
        $this->assertFalse( ShippingFormatter::isSecret( 'cost', [ 'type' => 'text' ] ) );
        $this->assertFalse( ShippingFormatter::isSecret( 'title', [ 'type' => 'text' ] ) );
    }

    // ----- Capabilities -----------------------------------------------------

    public function test_store_settings_need_manage_woocommerce(): void {
        $manager = new PermissionManager();

        $GLOBALS['wp_granted_caps'] = [ 'edit_products', 'manage_product_terms' ];
        $this->assertFalse( $manager->can_manage_woocommerce() );
        $this->assertNotNull( CatalogGuard::storeManager() );

        $GLOBALS['wp_granted_caps'] = [ 'manage_woocommerce' ];
        $this->assertTrue( $manager->can_manage_woocommerce() );
        $this->assertNull( CatalogGuard::storeManager() );
    }

    public function test_product_terms_need_a_woocommerce_term_capability_not_edit_posts(): void {
        $manager = new PermissionManager();

        $GLOBALS['wp_granted_caps'] = [ 'edit_posts', 'manage_categories' ];
        $this->assertFalse( $manager->can_edit_product_terms() );

        $GLOBALS['wp_granted_caps'] = [ 'edit_product_terms' ];
        $this->assertTrue( $manager->can_edit_product_terms() );
    }
}
