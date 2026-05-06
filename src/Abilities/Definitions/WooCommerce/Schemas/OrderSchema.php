<?php
/**
 * WooCommerce order + address schemas.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

final class OrderSchema {

    public static function order( bool $detailed = false ): array {
        $schema = [
            'type'       => 'object',
            'properties' => [
                'id'                   => [ 'type' => 'integer' ],
                'order_number'         => [ 'type' => 'string' ],
                'status'               => [ 'type' => 'string' ],
                'currency'             => [ 'type' => 'string' ],
                'total'                => [ 'type' => 'string' ],
                'subtotal'             => [ 'type' => 'string' ],
                'total_tax'            => [ 'type' => 'string' ],
                'shipping_total'       => [ 'type' => 'string' ],
                'discount_total'       => [ 'type' => 'string' ],
                'customer_id'          => [ 'type' => 'integer' ],
                'date_created'         => [ 'type' => 'string' ],
                'date_modified'        => [ 'type' => 'string' ],
                'payment_method'       => [ 'type' => 'string' ],
                'payment_method_title' => [ 'type' => 'string' ],
                'items_count'          => [ 'type' => 'integer' ],
                'billing'              => [ 'type' => 'object' ],
            ],
        ];

        if ( $detailed ) {
            $schema['properties']['shipping']       = [ 'type' => 'object' ];
            $schema['properties']['line_items']     = [ 'type' => 'array' ];
            $schema['properties']['shipping_lines'] = [ 'type' => 'array' ];
            $schema['properties']['coupon_lines']   = [ 'type' => 'array' ];
            $schema['properties']['customer_note']  = [ 'type' => 'string' ];
            $schema['properties']['refunds_total']  = [ 'type' => 'string' ];
            $schema['properties']['date_completed'] = [ 'type' => [ 'string', 'null' ] ];
            $schema['properties']['date_paid']      = [ 'type' => [ 'string', 'null' ] ];
        }

        return $schema;
    }

    public static function address( bool $with_contact ): array {
        $properties = [
            'first_name' => [ 'type' => 'string' ],
            'last_name'  => [ 'type' => 'string' ],
            'company'    => [ 'type' => 'string' ],
            'address_1'  => [ 'type' => 'string' ],
            'address_2'  => [ 'type' => 'string' ],
            'city'       => [ 'type' => 'string' ],
            'state'      => [ 'type' => 'string' ],
            'postcode'   => [ 'type' => 'string' ],
            'country'    => [ 'type' => 'string' ],
        ];

        if ( $with_contact ) {
            $properties['email'] = [ 'type' => 'string' ];
            $properties['phone'] = [ 'type' => 'string' ];
        }

        return [
            'type'       => 'object',
            'properties' => $properties,
        ];
    }
}
