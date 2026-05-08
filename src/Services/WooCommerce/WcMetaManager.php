<?php
/**
 * HPOS-aware meta management for WC_Data entities (products, orders, variations).
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class WcMetaManager extends AbstractService {

    public static function get_product_meta( array $input ): array|\WP_Error {
        return self::get_meta( self::resolve_product( $input ), $input );
    }

    public static function set_product_meta( array $input ): array|\WP_Error {
        return self::set_meta( self::resolve_product( $input ), $input );
    }

    public static function delete_product_meta( array $input ): array|\WP_Error {
        return self::delete_meta( self::resolve_product( $input ), $input );
    }

    public static function get_order_meta( array $input ): array|\WP_Error {
        return self::get_meta( self::resolve_order( $input ), $input );
    }

    public static function set_order_meta( array $input ): array|\WP_Error {
        return self::set_meta( self::resolve_order( $input ), $input );
    }

    public static function delete_order_meta( array $input ): array|\WP_Error {
        return self::delete_meta( self::resolve_order( $input ), $input );
    }

    /**
     * Apply a meta map to any WC_Data entity. Used inline by create/update services
     * (orders, variations, products) for the `meta` payload.
     *
     * @param array<string, mixed> $meta
     */
    public static function apply_meta_map( \WC_Data $entity, array $meta ): void {
        foreach ( $meta as $key => $value ) {
            $entity->update_meta_data( (string) $key, $value );
        }
    }

    private static function get_meta( \WC_Data|\WP_Error $entity, array $input ): array|\WP_Error {
        if ( $entity instanceof \WP_Error ) {
            return $entity;
        }

        $key = (string) ( $input['key'] ?? '' );

        if ( '' !== $key ) {
            $value = $entity->get_meta( $key, true );
            return [
                'success' => true,
                'id'      => $entity->get_id(),
                'key'     => $key,
                'value'   => $value,
                'exists'  => $entity->meta_exists( $key ),
            ];
        }

        $include_protected = (bool) ( $input['include_protected'] ?? false );
        $items             = [];

        foreach ( $entity->get_meta_data() as $meta ) {
            $meta_key = $meta->key;
            if ( ! $include_protected && self::is_protected( (string) $meta_key ) ) {
                continue;
            }
            $items[] = [
                'id'    => (int) $meta->id,
                'key'   => $meta_key,
                'value' => $meta->value,
            ];
        }

        return [
            'success' => true,
            'id'      => $entity->get_id(),
            'meta'    => $items,
            'total'   => count( $items ),
        ];
    }

    private static function set_meta( \WC_Data|\WP_Error $entity, array $input ): array|\WP_Error {
        if ( $entity instanceof \WP_Error ) {
            return $entity;
        }

        $meta = $input['meta'] ?? null;
        if ( ! is_array( $meta ) || empty( $meta ) ) {
            return self::errorResponse( 'invalid_meta', 'meta must be a non-empty object map of key => value', 400 );
        }

        $updated = [];
        foreach ( $meta as $key => $value ) {
            $key = (string) $key;
            if ( '' === $key ) {
                continue;
            }
            $entity->update_meta_data( $key, $value );
            $updated[] = $key;
        }

        $entity->save_meta_data();

        return [
            'success' => true,
            'message' => 'Meta updated',
            'id'      => $entity->get_id(),
            'updated' => $updated,
            'total'   => count( $updated ),
        ];
    }

    private static function delete_meta( \WC_Data|\WP_Error $entity, array $input ): array|\WP_Error {
        if ( $entity instanceof \WP_Error ) {
            return $entity;
        }

        $key = (string) ( $input['key'] ?? '' );
        if ( '' === $key ) {
            return self::errorResponse( 'invalid_key', 'key is required', 400 );
        }

        if ( ! $entity->meta_exists( $key ) ) {
            return self::errorResponse( 'meta_not_found', sprintf( 'Meta key "%s" not found on this entity', $key ), 404 );
        }

        $entity->delete_meta_data( $key );
        $entity->save_meta_data();

        return [
            'success' => true,
            'message' => 'Meta deleted',
            'id'      => $entity->get_id(),
            'key'     => $key,
        ];
    }

    private static function resolve_product( array $input ): \WC_Data|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $id = (int) ( $input['product_id'] ?? 0 );
        if ( $id <= 0 ) {
            return self::errorResponse( 'invalid_product_id', 'product_id is required', 400 );
        }

        $product = wc_get_product( $id );
        if ( ! $product instanceof \WC_Data ) {
            return self::errorResponse( 'product_not_found', 'Product not found', 404 );
        }

        return $product;
    }

    private static function resolve_order( array $input ): \WC_Data|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $id    = (int) ( $input['order_id'] ?? 0 );
        $order = OrderGuard::fetchOrder( $id );

        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        return $order;
    }

    private static function is_protected( string $key ): bool {
        return '' !== $key && '_' === $key[0];
    }
}
