<?php
/**
 * Create an order refund record.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

/**
 * Uses wc_create_refund() without refund_payment: the refund is recorded (and
 * items optionally restocked) but no money is sent back through the
 * gateway. The amount must be positive and at most what is still
 * refundable (defaults to that remainder), and the call needs
 * confirm_refund: true — without it a 409 previews the amount.
 */
final class RefundService {

    public static function create( \WC_Order $order, array $input ): array|\WP_Error {
        $remaining = (float) $order->get_remaining_refund_amount();
        $amount    = isset( $input['amount'] ) ? (float) $input['amount'] : $remaining;

        if ( $amount <= 0 || $amount > $remaining + 0.00001 ) {
            return ResponseFormatter::error(
                'invalid_refund_amount',
                sprintf( 'The refund amount must be more than 0 and at most %s (still refundable).', wc_format_decimal( $remaining ) ),
                400
            );
        }

        $unconfirmed = CatalogGuard::confirm(
            true,
            $input,
            'confirm_refund',
            sprintf( 'This records a refund of %s on order #%s; it cannot be undone here.', wc_format_decimal( $amount ), $order->get_order_number() ),
            [ 'amount' => $amount, 'refundable' => $remaining ]
        );
        if ( $unconfirmed ) {
            return $unconfirmed;
        }

        $lineItems = [];
        foreach ( (array) ( $input['line_items'] ?? [] ) as $item ) {
            $lineItems[ (int) ( $item['id'] ?? 0 ) ] = [
                'qty'          => (int) ( $item['quantity'] ?? 0 ),
                'refund_total' => (float) ( $item['total'] ?? 0 ),
            ];
        }

        $refund = wc_create_refund(
            [
                'order_id'      => $order->get_id(),
                'amount'        => $amount,
                'reason'        => sanitize_text_field( (string) ( $input['reason'] ?? '' ) ),
                'line_items'    => $lineItems,
                'restock_items' => (bool) ( $input['restock_items'] ?? true ),
            ]
        );
        if ( is_wp_error( $refund ) ) {
            return $refund;
        }

        return [
            'success'   => true,
            'message'   => 'Refund created successfully',
            'refund_id' => $refund->get_id(),
            'order_id'  => $order->get_id(),
            'amount'    => $amount,
            'reason'    => (string) ( $input['reason'] ?? '' ),
        ];
    }
}
