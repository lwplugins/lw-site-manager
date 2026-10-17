<?php
/**
 * Shipping zones and their methods.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityDefinition;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\CatalogSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ShippingMethodManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ShippingZoneManager;

final class ShippingAbilities {

    public static function register( PermissionManager $permissions ): void {
        self::zones( $permissions );
        self::methods( $permissions );
    }

    private static function zones( PermissionManager $permissions ): void {
        $zoneFields = [
            'name'      => [ 'type' => 'string' ],
            'order'     => [ 'type' => 'integer', 'description' => 'Lower is matched first' ],
            'locations' => [ 'type' => 'array', 'items' => CatalogSchema::location(), 'description' => 'Replaces all locations of the zone' ],
        ];

        AbilityDefinition::register(
            $permissions,
            'wc-list-shipping-zones',
            [
                'label'       => __( 'List Shipping Zones', 'lw-site-manager' ),
                'description' => __( 'List shipping zones with their locations and methods, including zone 0 ("rest of the world")', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'zones' => [ 'type' => 'array', 'items' => CatalogSchema::zone() ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'callback'    => [ ShippingZoneManager::class, 'list_zones' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-get-shipping-zone',
            [
                'label'       => __( 'Get Shipping Zone', 'lw-site-manager' ),
                'description' => __( 'Get one shipping zone (0 = rest of the world) with its locations and methods', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [ 'id' => [ 'type' => 'integer' ] ],
                'required'    => [ 'id' ],
                'output'      => [ 'type' => 'object', 'properties' => [ 'zone' => CatalogSchema::zone() ] ],
                'callback'    => [ ShippingZoneManager::class, 'get_zone' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-create-shipping-zone',
            [
                'label'       => __( 'Create Shipping Zone', 'lw-site-manager' ),
                'description' => __( 'Create a shipping zone with a name, order and locations (countries, states, continents, postcodes)', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => $zoneFields,
                'required'    => [ 'name' ],
                'output'      => CatalogSchema::mutation( 'zone', CatalogSchema::zone() ),
                'callback'    => [ ShippingZoneManager::class, 'create_zone' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-update-shipping-zone',
            [
                'label'       => __( 'Update Shipping Zone', 'lw-site-manager' ),
                'description' => __( 'Rename, reorder or replace the locations of a shipping zone', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => array_merge( [ 'id' => [ 'type' => 'integer' ] ], $zoneFields ),
                'required'    => [ 'id' ],
                'output'      => CatalogSchema::mutation( 'zone', CatalogSchema::zone() ),
                'callback'    => [ ShippingZoneManager::class, 'update_zone' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-delete-shipping-zone',
            [
                'label'       => __( 'Delete Shipping Zone', 'lw-site-manager' ),
                'description' => __( 'Permanently delete a shipping zone and its methods (needs confirm_delete: true)', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'id'             => [ 'type' => 'integer' ],
                    'confirm_delete' => CatalogSchema::confirmDelete(),
                ],
                'required'    => [ 'id' ],
                'output'      => CatalogSchema::deleted(),
                'callback'    => [ ShippingZoneManager::class, 'delete_zone' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function methods( PermissionManager $permissions ): void {
        $methodOut = CatalogSchema::mutation( 'method', CatalogSchema::method(), [ 'zone_id' => [ 'type' => 'integer' ] ] );

        AbilityDefinition::register(
            $permissions,
            'wc-list-shipping-method-types',
            [
                'label'       => __( 'List Shipping Method Types', 'lw-site-manager' ),
                'description' => __( 'List the shipping method types that can be added to a zone (flat_rate, free_shipping, local_pickup and any from extensions) and their setting fields', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'method_types' => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
                        'total'        => [ 'type' => 'integer' ],
                    ],
                ],
                'callback'    => [ ShippingMethodManager::class, 'list_method_types' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-add-shipping-zone-method',
            [
                'label'       => __( 'Add Shipping Method to Zone', 'lw-site-manager' ),
                'description' => __( 'Add a shipping method to a zone, optionally with its settings (e.g. {"title": "Courier", "cost": "1990"}) and enabled state', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'zone_id'   => [ 'type' => 'integer' ],
                    'method_id' => [ 'type' => 'string', 'description' => 'Method type, e.g. flat_rate, free_shipping, local_pickup' ],
                    'settings'  => [ 'type' => 'object' ],
                    'enabled'   => [ 'type' => 'boolean' ],
                ],
                'required'    => [ 'zone_id', 'method_id' ],
                'output'      => $methodOut,
                'callback'    => [ ShippingMethodManager::class, 'add_method' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-update-shipping-zone-method',
            [
                'label'       => __( 'Update Zone Shipping Method', 'lw-site-manager' ),
                'description' => __( 'Change a zone shipping method\'s settings, enabled state or order; WooCommerce validates each setting', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'zone_id'     => [ 'type' => 'integer' ],
                    'instance_id' => [ 'type' => 'integer' ],
                    'settings'    => [ 'type' => 'object', 'description' => 'Only the fields sent change; a masked ******** value is ignored' ],
                    'enabled'     => [ 'type' => 'boolean' ],
                    'order'       => [ 'type' => 'integer' ],
                ],
                'required'    => [ 'zone_id', 'instance_id' ],
                'output'      => $methodOut,
                'callback'    => [ ShippingMethodManager::class, 'update_method' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-delete-shipping-zone-method',
            [
                'label'       => __( 'Remove Zone Shipping Method', 'lw-site-manager' ),
                'description' => __( 'Permanently remove a shipping method from a zone (needs confirm_delete: true)', 'lw-site-manager' ),
                'category'    => 'wc-settings',
                'properties'  => [
                    'zone_id'        => [ 'type' => 'integer' ],
                    'instance_id'    => [ 'type' => 'integer' ],
                    'confirm_delete' => CatalogSchema::confirmDelete(),
                ],
                'required'    => [ 'zone_id', 'instance_id' ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'success'     => [ 'type' => 'boolean' ],
                        'message'     => [ 'type' => 'string' ],
                        'zone_id'     => [ 'type' => 'integer' ],
                        'instance_id' => [ 'type' => 'integer' ],
                    ],
                ],
                'callback'    => [ ShippingMethodManager::class, 'delete_method' ],
                'permission'  => 'can_manage_woocommerce',
                'meta'        => AbilityMeta::destructive( false ),
            ]
        );
    }
}
