<?php
/**
 * WooCommerce product schemas (output + input).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

final class ProductSchema {

    public static function product( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
            'properties' => [
                'id'             => [ 'type' => 'integer' ],
                'name'           => [ 'type' => 'string' ],
                'slug'           => [ 'type' => 'string' ],
                'type'           => [ 'type' => 'string' ],
                'status'         => [ 'type' => 'string' ],
                'sku'            => [ 'type' => 'string' ],
                'price'          => [ 'type' => 'string' ],
                'regular_price'  => [ 'type' => 'string' ],
                'sale_price'     => [ 'type' => 'string' ],
                'on_sale'        => [ 'type' => 'boolean' ],
                'stock_quantity' => [ 'type' => [ 'integer', 'null' ] ],
                'stock_status'   => [ 'type' => 'string' ],
                'manage_stock'   => [ 'type' => 'boolean' ],
                'featured'       => [ 'type' => 'boolean' ],
                'virtual'        => [ 'type' => 'boolean' ],
                'downloadable'   => [ 'type' => 'boolean' ],
                'permalink'      => [ 'type' => 'string' ],
                'date_created'   => [ 'type' => 'string' ],
                'date_modified'  => [ 'type' => 'string' ],
                'image'          => [ 'type' => [ 'object', 'null' ], 'default' => null ],
                'categories'     => [ 'type' => 'array' ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['description']       = [ 'type' => 'string' ];
            $schema['properties']['short_description'] = [ 'type' => 'string' ];
            $schema['properties']['weight']            = [ 'type' => 'string' ];
            $schema['properties']['length']            = [ 'type' => 'string' ];
            $schema['properties']['width']             = [ 'type' => 'string' ];
            $schema['properties']['height']            = [ 'type' => 'string' ];
            $schema['properties']['gallery']           = [ 'type' => 'array' ];
            $schema['properties']['tags']              = [ 'type' => 'array' ];
            $schema['properties']['attributes']        = [ 'type' => 'array' ];
            $schema['properties']['total_sales']       = [ 'type' => 'integer' ];
            $schema['properties']['average_rating']    = [ 'type' => 'string' ];
            $schema['properties']['review_count']      = [ 'type' => 'integer' ];
        }

        return $schema;
    }

    public static function input(): array {
        return [
            'name'               => [
                'type'        => 'string',
                'description' => 'Product name',
            ],
            'type'               => [
                'type'    => 'string',
                'enum'    => [ 'simple', 'variable', 'grouped', 'external' ],
                'default' => 'simple',
            ],
            'slug'               => [ 'type' => 'string' ],
            'description'        => [ 'type' => 'string' ],
            'short_description'  => [ 'type' => 'string' ],
            'status'             => [
                'type'    => 'string',
                'enum'    => [ 'draft', 'pending', 'private', 'publish' ],
                'default' => 'publish',
            ],
            'featured'           => [ 'type' => 'boolean' ],
            'catalog_visibility' => [
                'type' => 'string',
                'enum' => [ 'visible', 'catalog', 'search', 'hidden' ],
            ],
            'regular_price'      => [ 'type' => 'string' ],
            'sale_price'         => [ 'type' => 'string' ],
            'sku'                => [ 'type' => 'string' ],
            'manage_stock'       => [ 'type' => 'boolean' ],
            'stock_quantity'     => [ 'type' => 'integer' ],
            'stock_status'       => [
                'type' => 'string',
                'enum' => [ 'instock', 'outofstock', 'onbackorder' ],
            ],
            'backorders'         => [
                'type' => 'string',
                'enum' => [ 'no', 'notify', 'yes' ],
            ],
            'weight'             => [ 'type' => 'string' ],
            'length'             => [ 'type' => 'string' ],
            'width'              => [ 'type' => 'string' ],
            'height'             => [ 'type' => 'string' ],
            'tax_status'         => [
                'type' => 'string',
                'enum' => [ 'taxable', 'shipping', 'none' ],
            ],
            'tax_class'          => [ 'type' => 'string' ],
            'virtual'            => [ 'type' => 'boolean' ],
            'downloadable'       => [ 'type' => 'boolean' ],
            'categories'         => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'tags'               => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'image_id'           => [ 'type' => 'integer' ],
            'gallery_image_ids'  => [
                'type'  => 'array',
                'items' => [ 'type' => 'integer' ],
            ],
            'menu_order'         => [ 'type' => 'integer' ],
            'meta'               => [ 'type' => 'object' ],
        ];
    }
}
