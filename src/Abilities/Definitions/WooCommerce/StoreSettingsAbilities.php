<?php
/**
 * Store settings, setup check and payment gateways.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityDefinition;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\CatalogSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\PaymentGatewayManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\SetupCheck;
use LightweightPlugins\SiteManager\Services\WooCommerce\StoreSettingsManager;

final class StoreSettingsAbilities {

    public static function register( PermissionManager $permissions ): void {
        AbilityDefinition::register(
            $permissions,
            'wc-get-store-settings',
            [
                'label'       => __( 'Get Store Settings', 'lw-site-manager' ),
                'description' => __( 'Read the store address, selling and shipping locations, currency and price format, weight and dimension units, and tax calculation options', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'include_fields' => [ 'type' => 'boolean', 'default' => false, 'description' => 'Also return each setting\'s type, allowed options and whether changing it is risky' ],
                ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'settings' => [ 'type' => 'object' ],
                        'context'  => [ 'type' => 'object' ],
                        'fields'   => [ 'type' => 'object' ],
                    ],
                ],
                'callback'    => [ StoreSettingsManager::class, 'get_store_settings' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-update-store-settings',
            [
                'label'       => __( 'Update Store Settings', 'lw-site-manager' ),
                'description' => __( 'Change store settings through WooCommerce\'s settings API (same keys as wc-get-store-settings). Currency, tax, unit and selling-location changes need confirm_change: true; without it the call returns a preview of those changes', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'settings'       => [ 'type' => 'object', 'description' => 'Key => value, e.g. {"store_city": "Budapest", "currency": "HUF"}; checkboxes take true/false, country lists arrays of ISO codes' ],
                    'confirm_change' => CatalogSchema::confirmChange( 'currency, tax, unit or selling-location changes' ),
                ],
                'required'    => [ 'settings' ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'message'  => [ 'type' => 'string' ],
                        'changes'  => [ 'type' => [ 'object', 'array' ] ],
                        'settings' => [ 'type' => 'object' ],
                    ],
                ],
                'callback'    => [ StoreSettingsManager::class, 'update_store_settings' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-check-setup',
            [
                'label'       => __( 'Check Store Setup', 'lw-site-manager' ),
                'description' => __( 'Read-only overview of the store setup — WooCommerce and database version, store pages, enabled payment methods, shipping zones, HPOS, known extensions — with a list of issues that block selling', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'woocommerce' => [ 'type' => 'object' ],
                        'store'       => [ 'type' => 'object' ],
                        'pages'       => [ 'type' => 'object' ],
                        'payments'    => [ 'type' => 'object' ],
                        'shipping'    => [ 'type' => 'object' ],
                        'catalog'     => [ 'type' => 'object' ],
                        'issues'      => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
                        'ready'       => [ 'type' => 'boolean' ],
                    ],
                ],
                'callback'    => [ SetupCheck::class, 'check_setup' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-list-payment-gateways',
            [
                'label'       => __( 'List Payment Gateways', 'lw-site-manager' ),
                'description' => __( 'List payment methods with their enabled state and whether they still need setup; gateway credentials are never returned', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'gateways' => [ 'type' => 'array', 'items' => CatalogSchema::gateway() ],
                        'total'    => [ 'type' => 'integer' ],
                    ],
                ],
                'callback'    => [ PaymentGatewayManager::class, 'list_gateways' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-set-payment-gateway-enabled',
            [
                'label'       => __( 'Enable or Disable Payment Gateway', 'lw-site-manager' ),
                'description' => __( 'Turn a payment method on or off. A gateway that needs setup cannot be enabled; turning off the last enabled one needs confirm_change: true', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'id'             => [ 'type' => 'string', 'description' => 'Gateway ID, e.g. bacs, cheque, cod' ],
                    'enabled'        => [ 'type' => 'boolean' ],
                    'confirm_change' => CatalogSchema::confirmChange( 'disabling the last enabled payment method' ),
                ],
                'required'    => [ 'id', 'enabled' ],
                'output'      => CatalogSchema::mutation( 'gateway', CatalogSchema::gateway() ),
                'callback'    => [ PaymentGatewayManager::class, 'set_gateway_enabled' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );
    }
}
