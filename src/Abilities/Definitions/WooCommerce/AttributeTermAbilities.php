<?php
/**
 * Attribute term abilities (e.g. colors inside pa_color).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce;

use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas\AbilityMeta;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Services\WooCommerce\AttributeManager;

final class AttributeTermAbilities {

    public static function register( PermissionManager $permissions ): void {
        wp_register_ability(
            'site-manager/wc-list-attribute-terms',
            [
                'label'               => __( 'List Attribute Terms', 'lw-site-manager' ),
                'description'         => __( 'List terms (values) of a global attribute, e.g. all colors in pa_color', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'         => [ 'type' => 'integer', 'description' => 'Attribute id' ],
                        'slug'       => [ 'type' => 'string', 'description' => 'Attribute slug without pa_ prefix' ],
                        'search'     => [ 'type' => 'string' ],
                        'hide_empty' => [ 'type' => 'boolean', 'default' => false ],
                        'limit'      => [ 'type' => 'integer', 'default' => 100, 'minimum' => 1, 'maximum' => 500 ],
                        'offset'     => [ 'type' => 'integer', 'default' => 0 ],
                    ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'attribute_id'   => [ 'type' => 'integer' ],
                        'attribute_slug' => [ 'type' => 'string' ],
                        'terms'          => [ 'type' => 'array', 'items' => self::termSchema() ],
                        'total'          => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ AttributeManager::class, 'list_attribute_terms' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::readOnly(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-create-attribute-term',
            [
                'label'               => __( 'Create Attribute Term', 'lw-site-manager' ),
                'description'         => __( 'Add a new term (value) to a global attribute, e.g. add "Olive" to pa_color', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'          => [ 'type' => 'integer' ],
                        'slug'        => [ 'type' => 'string', 'description' => 'Attribute slug (e.g. "color")' ],
                        'name'        => [ 'type' => 'string', 'description' => 'Term display name' ],
                        'description' => [ 'type' => 'string' ],
                    ],
                    'required'   => [ 'name' ],
                ],
                'output_schema'       => self::termMutationSchema(),
                'execute_callback'    => [ AttributeManager::class, 'create_attribute_term' ],
                'permission_callback' => $permissions->callback( 'can_publish_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-update-attribute-term',
            [
                'label'               => __( 'Update Attribute Term', 'lw-site-manager' ),
                'description'         => __( 'Rename or re-slug an attribute term', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'          => [ 'type' => 'integer' ],
                        'slug'        => [ 'type' => 'string' ],
                        'term_id'     => [ 'type' => 'integer' ],
                        'name'        => [ 'type' => 'string' ],
                        'description' => [ 'type' => 'string' ],
                    ],
                    'required'   => [ 'term_id' ],
                ],
                'output_schema'       => self::termMutationSchema(),
                'execute_callback'    => [ AttributeManager::class, 'update_attribute_term' ],
                'permission_callback' => $permissions->callback( 'can_edit_posts' ),
                'meta'                => AbilityMeta::write(),
            ]
        );

        wp_register_ability(
            'site-manager/wc-delete-attribute-term',
            [
                'label'               => __( 'Delete Attribute Term', 'lw-site-manager' ),
                'description'         => __( 'Delete a term from a global attribute', 'lw-site-manager' ),
                'category'            => 'wc-attributes',
                'input_schema'        => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id'      => [ 'type' => 'integer' ],
                        'slug'    => [ 'type' => 'string' ],
                        'term_id' => [ 'type' => 'integer' ],
                    ],
                    'required'   => [ 'term_id' ],
                ],
                'output_schema'       => [
                    'type'       => 'object',
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'id'      => [ 'type' => 'integer' ],
                        'name'    => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ AttributeManager::class, 'delete_attribute_term' ],
                'permission_callback' => $permissions->callback( 'can_delete_posts' ),
                'meta'                => AbilityMeta::destructive( false ),
            ]
        );
    }

    private static function termSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'          => [ 'type' => 'integer' ],
                'name'        => [ 'type' => 'string' ],
                'slug'        => [ 'type' => 'string' ],
                'description' => [ 'type' => 'string' ],
                'count'       => [ 'type' => 'integer' ],
            ],
        ];
    }

    private static function termMutationSchema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'      => [ 'type' => 'boolean' ],
                'message'      => [ 'type' => 'string' ],
                'attribute_id' => [ 'type' => 'integer' ],
                'term'         => self::termSchema(),
            ],
        ];
    }
}
