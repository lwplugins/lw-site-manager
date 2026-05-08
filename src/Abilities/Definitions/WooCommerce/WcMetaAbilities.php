<?php
/**
 * Standalone meta abilities for WooCommerce products and orders (HPOS-aware).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\WcMetaManager;

final class WcMetaAbilities {

    public static function register( PermissionManager $permissions ): void {
        self::register_product_meta( $permissions );
        self::register_order_meta( $permissions );
    }

    private static function register_product_meta( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-get-product-meta',
            [
                'label'               => __( 'Get Product Meta', 'lw-site-manager' ),
                'description'         => __( 'Read one meta key (with `key`) or list all meta on a product/variation. Hides leading-underscore keys unless include_protected=true.', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id'        => [ 'type' => 'integer' ],
                        'key'               => [ 'type' => 'string', 'description' => 'Optional single meta key' ],
                        'include_protected' => [ 'type' => 'boolean', 'default' => false ],
                    ],
                    'required'   => [ 'product_id' ],
                ],
                'output_schema'       => self::getOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'get_product_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-set-product-meta',
            [
                'label'               => __( 'Set Product Meta', 'lw-site-manager' ),
                'description'         => __( 'Bulk update meta on a product/variation. Accepts a `meta` map of key => value pairs.', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [ 'type' => 'integer' ],
                        'meta'       => [ 'type' => 'object', 'description' => 'Map of meta_key => value' ],
                    ],
                    'required'   => [ 'product_id', 'meta' ],
                ],
                'output_schema'       => self::setOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'set_product_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-product-meta',
            [
                'label'               => __( 'Delete Product Meta', 'lw-site-manager' ),
                'description'         => __( 'Delete a single meta key from a product/variation', 'lw-site-manager' ),
                'category'            => 'wc-products',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id' => [ 'type' => 'integer' ],
                        'key'        => [ 'type' => 'string' ],
                    ],
                    'required'   => [ 'product_id', 'key' ],
                ],
                'output_schema'       => self::deleteOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'delete_product_meta' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function register_order_meta( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-get-order-meta',
            [
                'label'               => __( 'Get Order Meta', 'lw-site-manager' ),
                'description'         => __( 'Read one meta key (with `key`) or list all meta on an order. HPOS-aware via WC_Data API.', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id'          => [ 'type' => 'integer' ],
                        'key'               => [ 'type' => 'string' ],
                        'include_protected' => [ 'type' => 'boolean', 'default' => false ],
                    ],
                    'required'   => [ 'order_id' ],
                ],
                'output_schema'       => self::getOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'get_order_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-set-order-meta',
            [
                'label'               => __( 'Set Order Meta', 'lw-site-manager' ),
                'description'         => __( 'Bulk update meta on an order. HPOS-aware. Accepts a `meta` map of key => value pairs.', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [ 'type' => 'integer' ],
                        'meta'     => [ 'type' => 'object' ],
                    ],
                    'required'   => [ 'order_id', 'meta' ],
                ],
                'output_schema'       => self::setOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'set_order_meta' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-order-meta',
            [
                'label'               => __( 'Delete Order Meta', 'lw-site-manager' ),
                'description'         => __( 'Delete a single meta key from an order. HPOS-aware.', 'lw-site-manager' ),
                'category'            => 'wc-orders',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'order_id' => [ 'type' => 'integer' ],
                        'key'      => [ 'type' => 'string' ],
                    ],
                    'required'   => [ 'order_id', 'key' ],
                ],
                'output_schema'       => self::deleteOutputSchema(),
                'execute_callback'    => [ WcMetaManager::class, 'delete_order_meta' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function getOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'id'      => [ 'type' => 'integer' ],
                'key'     => [ 'type' => 'string' ],
                'value'   => [],
                'exists'  => [ 'type' => 'boolean' ],
                'meta'    => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'id'    => [ 'type' => 'integer' ],
                            'key'   => [ 'type' => 'string' ],
                            'value' => [],
                        ],
                    ],
                ],
                'total'   => [ 'type' => 'integer' ],
            ],
        ];
    }

    private static function setOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'id'      => [ 'type' => 'integer' ],
                'updated' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
                'total'   => [ 'type' => 'integer' ],
            ],
        ];
    }

    private static function deleteOutputSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'id'      => [ 'type' => 'integer' ],
                'key'     => [ 'type' => 'string' ],
            ],
        ];
    }
}
