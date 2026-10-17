<?php
/**
 * Schemas for product terms, store settings, payment gateways and shipping.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

use LightweightPlugins\SiteManager\Services\WooCommerce\ProductTermFields;

final class CatalogSchema {

    public static function taxonomyInput(): array {
        return [
            'type'        => 'string',
            'enum'        => ProductTermFields::taxonomies(),
            'description' => 'product_cat (categories), product_tag (tags), product_brand (brands), product_shipping_class (shipping classes)',
        ];
    }

    public static function confirmDelete(): array {
        return [ 'type' => 'boolean', 'default' => false, 'description' => 'Required (true) for a permanent delete' ];
    }

    public static function confirmChange( string $what ): array {
        return [ 'type' => 'boolean', 'default' => false, 'description' => 'Required (true) for ' . $what ];
    }

    public static function term(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => [ 'type' => 'integer' ],
                'name'         => [ 'type' => 'string' ],
                'slug'         => [ 'type' => 'string' ],
                'taxonomy'     => [ 'type' => 'string' ],
                'description'  => [ 'type' => 'string' ],
                'parent'       => [ 'type' => 'integer' ],
                'count'        => [ 'type' => 'integer' ],
                'image_id'     => [ 'type' => 'integer', 'description' => 'product_cat and product_brand only' ],
                'image_url'    => [ 'type' => [ 'string', 'null' ] ],
                'display_type' => [ 'type' => 'string', 'description' => 'product_cat only' ],
                'menu_order'   => [ 'type' => 'integer', 'description' => 'product_cat only' ],
                'is_default'   => [ 'type' => 'boolean', 'description' => 'product_cat only' ],
            ],
        ];
    }

    /**
     * Writable term fields (create and update).
     */
    public static function termFields(): array {
        return [
            'name'         => [ 'type' => 'string' ],
            'slug'         => [ 'type' => 'string' ],
            'description'  => [ 'type' => 'string' ],
            'parent'       => [ 'type' => 'integer', 'description' => 'Parent term ID, 0 for top level (product_cat, product_brand)' ],
            'image_id'     => [ 'type' => 'integer', 'description' => 'Media-library image ID, 0 clears (product_cat, product_brand)' ],
            'display_type' => [ 'type' => 'string', 'enum' => ProductTermFields::DISPLAY_TYPES, 'description' => 'Archive display: "" (shop default), products, subcategories, both (product_cat)' ],
            'menu_order'   => [ 'type' => 'integer', 'description' => 'Sort position, stored as WooCommerce term meta "order" (product_cat)' ],
        ];
    }

    public static function zone(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'        => [ 'type' => 'integer' ],
                'name'      => [ 'type' => 'string' ],
                'order'     => [ 'type' => 'integer' ],
                'locations' => [ 'type' => 'array', 'items' => self::location() ],
                'formatted' => [ 'type' => 'string' ],
                'methods'   => [ 'type' => 'array', 'items' => self::method() ],
            ],
        ];
    }

    public static function location(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'code' => [ 'type' => 'string', 'description' => 'Country "HU", state "US:CA", continent "EU" or postcode (wildcards "1*", ranges "1000...1999")' ],
                'type' => [ 'type' => 'string', 'enum' => [ 'country', 'state', 'continent', 'postcode' ] ],
            ],
            'required'   => [ 'code', 'type' ],
        ];
    }

    public static function method(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'instance_id' => [ 'type' => 'integer' ],
                'method_id'   => [ 'type' => 'string' ],
                'title'       => [ 'type' => 'string' ],
                'enabled'     => [ 'type' => 'boolean' ],
                'order'       => [ 'type' => 'integer' ],
                'settings'    => [ 'type' => 'object', 'description' => 'Instance settings; credential-like values are masked as ********' ],
            ],
        ];
    }

    public static function gateway(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'           => [ 'type' => 'string' ],
                'title'        => [ 'type' => 'string' ],
                'method_title' => [ 'type' => 'string' ],
                'description'  => [ 'type' => 'string' ],
                'enabled'      => [ 'type' => 'boolean' ],
                'needs_setup'  => [ 'type' => 'boolean' ],
                'supports'     => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
            ],
        ];
    }

    /**
     * Response with success, message and one entity.
     */
    public static function mutation( string $key, array $schema, array $extra = [] ): array {
        return [
            'type'       => 'object',
            'properties' => array_merge(
                [
                    'success' => [ 'type' => 'boolean' ],
                    'message' => [ 'type' => 'string' ],
                    $key      => $schema,
                ],
                $extra
            ),
        ];
    }

    public static function deleted(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'id'      => [ 'type' => 'integer' ],
                'name'    => [ 'type' => 'string' ],
            ],
        ];
    }
}
