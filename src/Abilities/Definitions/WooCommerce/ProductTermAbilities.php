<?php
/**
 * Product categories, tags, brands and shipping classes (wc-*-product-term).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityDefinition;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\CatalogSchema;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\ResponseSchema;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\ProductTermManager;

final class ProductTermAbilities {

    public static function register( PermissionManager $permissions ): void {
        AbilityDefinition::register(
            $permissions,
            'wc-list-product-terms',
            [
                'label'       => __( 'List Product Terms', 'lw-site-manager' ),
                'description' => __( 'List product categories, tags, brands or shipping classes, with image, display type and sort order for categories', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => [
                    'taxonomy'   => CatalogSchema::taxonomyInput(),
                    'search'     => [ 'type' => 'string' ],
                    'parent'     => [ 'type' => 'integer', 'description' => 'Only direct children of this term (hierarchical taxonomies)' ],
                    'hide_empty' => [ 'type' => 'boolean', 'default' => false ],
                    'orderby'    => [ 'type' => 'string', 'enum' => [ 'name', 'slug', 'count', 'id', 'menu_order' ], 'default' => 'name' ],
                    'order'      => [ 'type' => 'string', 'enum' => [ 'ASC', 'DESC' ], 'default' => 'ASC' ],
                    'limit'      => [ 'type' => 'integer', 'default' => 100, 'maximum' => 200 ],
                    'offset'     => [ 'type' => 'integer', 'default' => 0 ],
                ],
                'required'    => [ 'taxonomy' ],
                'output'      => ResponseSchema::list( 'terms', CatalogSchema::term() ),
                'callback'    => [ ProductTermManager::class, 'list_terms' ],
                'permission'  => 'can_manage_products',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-get-product-term',
            [
                'label'       => __( 'Get Product Term', 'lw-site-manager' ),
                'description' => __( 'Get one product category, tag, brand or shipping class by ID', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => [
                    'taxonomy' => CatalogSchema::taxonomyInput(),
                    'id'       => [ 'type' => 'integer' ],
                ],
                'required'    => [ 'taxonomy', 'id' ],
                'output'      => [ 'type' => 'object', 'properties' => [ 'term' => CatalogSchema::term() ] ],
                'callback'    => [ ProductTermManager::class, 'get_term' ],
                'permission'  => 'can_manage_products',
                'meta'        => AbilityMeta::readOnly(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-create-product-term',
            [
                'label'       => __( 'Create Product Term', 'lw-site-manager' ),
                'description' => __( 'Create a product category (with parent, image, display type, sort order), tag, brand (with image) or shipping class', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => array_merge( [ 'taxonomy' => CatalogSchema::taxonomyInput() ], CatalogSchema::termFields() ),
                'required'    => [ 'taxonomy', 'name' ],
                'output'      => CatalogSchema::mutation( 'term', CatalogSchema::term() ),
                'callback'    => [ ProductTermManager::class, 'create_term' ],
                'permission'  => 'can_manage_product_terms',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-update-product-term',
            [
                'label'       => __( 'Update Product Term', 'lw-site-manager' ),
                'description' => __( 'Update a product category, tag, brand or shipping class; only the fields sent change', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => array_merge(
                    [
                        'taxonomy' => CatalogSchema::taxonomyInput(),
                        'id'       => [ 'type' => 'integer' ],
                    ],
                    CatalogSchema::termFields()
                ),
                'required'    => [ 'taxonomy', 'id' ],
                'output'      => CatalogSchema::mutation( 'term', CatalogSchema::term() ),
                'callback'    => [ ProductTermManager::class, 'update_term' ],
                'permission'  => 'can_manage_product_terms',
                'meta'        => AbilityMeta::write(),
            ]
        );

        AbilityDefinition::register(
            $permissions,
            'wc-delete-product-term',
            [
                'label'       => __( 'Delete Product Term', 'lw-site-manager' ),
                'description' => __( 'Permanently delete a product category, tag, brand or shipping class (needs confirm_delete: true; the default category cannot be deleted)', 'lw-site-manager' ),
                'category'    => 'wc-products',
                'properties'  => [
                    'taxonomy'       => CatalogSchema::taxonomyInput(),
                    'id'             => [ 'type' => 'integer' ],
                    'confirm_delete' => CatalogSchema::confirmDelete(),
                ],
                'required'    => [ 'taxonomy', 'id' ],
                'output'      => CatalogSchema::deleted(),
                'callback'    => [ ProductTermManager::class, 'delete_term' ],
                'permission'  => 'can_manage_product_terms',
                'meta'        => AbilityMeta::destructive( false ),
            ]
        );
    }
}
