<?php
/**
 * Brands, shipping class and default variation on a product.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityDefinition;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\DefaultVariationManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductAssignmentManager;

final class ProductAssignmentAbilities {

    public static function register( PermissionManager $permissions ): void {
        AbilityDefinition::register(
            $permissions,
            'wc-set-product-brands',
            [
                'label'       => __( 'Set Product Brands', 'lw-site-manager' ),
                'description' => __( 'Assign brands (product_brand terms) to a product; replaces the current brands unless append is true, an empty list removes them', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => [
                    'product_id' => [ 'type' => 'integer' ],
                    'brand_ids'  => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                    'append'     => [ 'type' => 'boolean', 'default' => false ],
                ],
                'required'    => [ 'product_id', 'brand_ids' ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'product_id' => [ 'type' => 'integer' ],
                        'brands'     => [ 'type' => 'array', 'items' => [ 'type' => 'object' ] ],
                    ],
                ],
                'callback'    => [ ProductAssignmentManager::class, 'set_product_brands' ],
                'permission'  => 'can_manage_products',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-set-product-shipping-class',
            [
                'label'       => __( 'Set Product Shipping Class', 'lw-site-manager' ),
                'description' => __( 'Set the shipping class of a product or variation (0 clears it; a variation without one uses its parent\'s)', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => [
                    'product_id'        => [ 'type' => 'integer', 'description' => 'Product or variation ID' ],
                    'shipping_class_id' => [ 'type' => 'integer', 'description' => 'product_shipping_class term ID, 0 to clear' ],
                ],
                'required'    => [ 'product_id', 'shipping_class_id' ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'success'             => [ 'type' => 'boolean' ],
                        'message'             => [ 'type' => 'string' ],
                        'product_id'          => [ 'type' => 'integer' ],
                        'shipping_class_id'   => [ 'type' => 'integer' ],
                        'shipping_class_slug' => [ 'type' => 'string' ],
                    ],
                ],
                'callback'    => [ ProductAssignmentManager::class, 'set_product_shipping_class' ],
                'permission'  => 'can_manage_products',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-set-default-variation',
            [
                'label'       => __( 'Set Default Variation', 'lw-site-manager' ),
                'description' => __( 'Set which variation a variable product preselects: by attributes ({"pa_color": "red"}), by variation_id, or clear: true', 'lw-site-manager' ),
                'category'    => 'wc-attributes',
                'properties'  => [
                    'product_id'   => [ 'type' => 'integer', 'description' => 'Variable product ID' ],
                    'attributes'   => [ 'type' => 'object', 'description' => 'Attribute => value; global attributes take the term slug, an axis left out means "any"' ],
                    'variation_id' => [ 'type' => 'integer', 'description' => 'Copy the attributes of this variation' ],
                    'clear'        => [ 'type' => 'boolean', 'default' => false ],
                ],
                'required'    => [ 'product_id' ],
                'output'      => [
                    'type'       => 'object',
                    'properties' => [
                        'success'            => [ 'type' => 'boolean' ],
                        'message'            => [ 'type' => 'string' ],
                        'product_id'         => [ 'type' => 'integer' ],
                        'default_attributes' => [ 'type' => 'object' ],
                    ],
                ],
                'callback'    => [ DefaultVariationManager::class, 'set_default_variation' ],
                'permission'  => 'can_manage_products',
                'meta'        => AbilityMeta::write(),
            ]
        );
    }
}
