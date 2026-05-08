<?php
/**
 * Product variation CRUD + combinatorial generation abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductVariationManager;

final class VariationAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-generate-variations',
            [
                'label'               => __( 'Generate Variations', 'lw-site-manager' ),
                'description'         => __( 'Auto-generate every missing variation combination from variation-flagged attributes', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'product_id'    => [ 'type' => 'integer', 'description' => 'Variable product ID' ],
                        'default_price' => [ 'type' => 'string', 'description' => 'Optional regular_price applied to each new variation' ],
                    ],
                    'required'   => [ 'product_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'product_id' => [ 'type' => 'integer' ],
                        'created'    => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                        'skipped'    => [ 'type' => 'integer' ],
                        'total'      => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ ProductVariationManager::class, 'generate_variations' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-variation',
            [
                'label'               => __( 'Create Variation', 'lw-site-manager' ),
                'description'         => __( 'Create a single product variation with explicit attribute values and pricing', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        [ 'product_id' => [ 'type' => 'integer' ] ],
                        self::variationFieldSchema(),
                        [
                            'attributes' => [
                                'type'        => 'object',
                                'description' => 'Attribute map: { "pa_color": "red", "pa_size": "M" }',
                            ],
                            'meta'       => [
                                'type'        => 'object',
                                'description' => 'Map of meta_key => value applied to the variation before save (via WC_Data API)',
                            ],
                        ]
                    ),
                    'required'   => [ 'product_id', 'attributes' ],
                ],
                'output_schema'       => self::variationMutationSchema( true ),
                'execute_callback'    => [ ProductVariationManager::class, 'create_variation' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-variation',
            [
                'label'               => __( 'Update Variation', 'lw-site-manager' ),
                'description'         => __( 'Update price, stock, attributes, or other fields of an existing variation', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        [ 'id' => [ 'type' => 'integer', 'description' => 'Variation ID' ] ],
                        self::variationFieldSchema(),
                        [
                            'attributes' => [ 'type' => 'object' ],
                            'meta'       => [
                                'type'        => 'object',
                                'description' => 'Map of meta_key => value applied to the variation (via WC_Data API)',
                            ],
                        ]
                    ),
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => self::variationMutationSchema(),
                'execute_callback'    => [ ProductVariationManager::class, 'update_variation' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-variation',
            [
                'label'               => __( 'Delete Variation', 'lw-site-manager' ),
                'description'         => __( 'Trash or permanently delete a product variation', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'    => [ 'type' => 'integer' ],
                        'force' => [ 'type' => 'boolean', 'default' => false ],
                    ],
                    'required'   => [ 'id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'id'         => [ 'type' => 'integer' ],
                        'product_id' => [ 'type' => 'integer' ],
                        'trashed'    => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ ProductVariationManager::class, 'delete_variation' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    /**
     * Field properties shared between create and update (without attributes / id / product_id).
     */
    private static function variationFieldSchema(): array {
        return [
            'sku'            => [ 'type' => 'string' ],
            'regular_price'  => [ 'type' => 'string' ],
            'sale_price'     => [ 'type' => 'string' ],
            'status'         => [ 'type' => 'string', 'enum' => [ 'publish', 'private', 'draft' ] ],
            'description'    => [ 'type' => 'string' ],
            'stock_quantity' => [ 'type' => 'integer' ],
            'stock_status'   => [ 'type' => 'string', 'enum' => [ 'instock', 'outofstock', 'onbackorder' ] ],
            'manage_stock'   => [ 'type' => 'boolean' ],
            'weight'         => [ 'type' => 'string' ],
            'length'         => [ 'type' => 'string' ],
            'width'          => [ 'type' => 'string' ],
            'height'         => [ 'type' => 'string' ],
            'image_id'       => [ 'type' => 'integer' ],
            'menu_order'     => [ 'type' => 'integer' ],
            'virtual'        => [ 'type' => 'boolean' ],
            'downloadable'   => [ 'type' => 'boolean' ],
            'tax_class'      => [ 'type' => 'string' ],
            'tax_status'     => [ 'type' => 'string', 'enum' => [ 'taxable', 'shipping', 'none' ] ],
        ];
    }

    private static function variationMutationSchema( bool $includeProductId = false ): array {
        $properties = [
            'success'   => [ 'type' => 'boolean' ],
            'message'   => [ 'type' => 'string' ],
            'id'        => [ 'type' => 'integer' ],
            'variation' => [ 'type' => [ 'object', 'null' ] ],
        ];

        if ( $includeProductId ) {
            $properties['product_id'] = [ 'type' => 'integer' ];
        }

        return [
            'type'       => 'object',
            'properties' => $properties,
        ];
    }
}
