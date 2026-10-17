<?php
/**
 * Trash or permanently delete a product or variation.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

/**
 * Deletes go through WC_Product::delete(), which trashes when not forced and
 * otherwise removes the product for good (WooCommerce deletes a variable
 * product's variations with it). The caller needs delete_post on the
 * product; a permanent delete — force, or a site without trash
 * (EMPTY_TRASH_DAYS 0) — needs confirm_delete: true.
 */
final class ProductDeletion {

    /** Days WordPress keeps trash (0 = no trash, trashing deletes). */
    public static function trashDays(): int {
        return defined( 'EMPTY_TRASH_DAYS' ) ? (int) EMPTY_TRASH_DAYS : 30;
    }

    /** Pure: does a delete with this force flag remove the product for good? */
    public static function permanentFor( bool $force, int $trashDays ): bool {
        return $force || $trashDays < 1;
    }

    /** Whether this request deletes for good. */
    public static function permanent( array $input ): bool {
        return self::permanentFor( ! empty( $input['force'] ), self::trashDays() );
    }

    /**
     * Confirmation for a bulk action: "delete" is always permanent, "trash"
     * is permanent on a site without trash.
     */
    public static function confirmBulk( string $action, array $input, int $trashDays ): ?\WP_Error {
        $permanent = 'delete' === $action || ( 'trash' === $action && $trashDays < 1 );

        return CatalogGuard::confirm(
            $permanent,
            $input,
            'confirm_delete',
            sprintf( 'This permanently deletes %d product(s); they cannot be restored from the trash.', count( (array) ( $input['ids'] ?? [] ) ) )
        );
    }

    /**
     * Refuse a permanent delete until it is confirmed.
     */
    public static function confirm( array $input, string $what ): ?\WP_Error {
        return CatalogGuard::confirm(
            self::permanent( $input ),
            $input,
            'confirm_delete',
            sprintf( 'This permanently deletes %s; it cannot be restored from the trash.', $what )
        );
    }

    /**
     * Delete one product or variation the user may delete.
     *
     * @return array{id: int, name: string, trashed: bool}|\WP_Error
     */
    public static function delete( int $productId, array $input, bool $variation = false ): array|\WP_Error {
        $product = CatalogGuard::product( $productId, 'delete_post' );
        if ( $product instanceof \WP_Error ) {
            return $product;
        }
        if ( $product->is_type( 'variation' ) !== $variation ) {
            return $variation
                ? CatalogGuard::unavailable( 'Product' )
                : new \WP_Error( 'use_variation_ability', 'This is a variation; delete it with wc-delete-variation.', [ 'status' => 400 ] );
        }

        $unconfirmed = self::confirm( $input, sprintf( 'product #%d', $productId ) );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        // Read these first: a permanent delete resets the object's ID to 0.
        $done      = [ 'id' => $product->get_id(), 'name' => $product->get_name(), 'trashed' => ! self::permanent( $input ) ];
        if ( ! $product->delete( ! $done['trashed'] ) ) {
            return new \WP_Error( 'product_delete_failed', 'Failed to delete product', [ 'status' => 500 ] );
        }

        return $done;
    }
}
