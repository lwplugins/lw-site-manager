<?php
/**
 * Order payment workflow: mark paid, send emails, generate pay-for-order URL.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

use LightweightPlugins\SiteManager\Services\AbstractService;

class OrderPaymentManager extends AbstractService {

    /**
     * Map of email type slug → WC email class name.
     */
    private const EMAIL_TYPES = [
        'new_order'                 => 'WC_Email_New_Order',
        'customer_invoice'          => 'WC_Email_Customer_Invoice',
        'customer_processing_order' => 'WC_Email_Customer_Processing_Order',
        'customer_completed_order'  => 'WC_Email_Customer_Completed_Order',
        'customer_on_hold_order'    => 'WC_Email_Customer_On_Hold_Order',
        'customer_refunded_order'   => 'WC_Email_Customer_Refunded_Order',
    ];

    /**
     * Mark an order as paid (drives WC_Order::payment_complete()).
     */
    public static function mark_paid( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $locked = OrderGuard::ensureModifiable( $order );
        if ( $locked ) {
            return $locked;
        }

        if ( $order->is_paid() ) {
            return self::errorResponse(
                'order_already_paid',
                sprintf( 'Order #%d is already paid', $order->get_id() ),
                409
            );
        }

        if ( ! empty( $input['payment_method'] ) ) {
            $order->set_payment_method( (string) $input['payment_method'] );
        }
        if ( ! empty( $input['payment_method_title'] ) ) {
            $order->set_payment_method_title( (string) $input['payment_method_title'] );
        }

        $silent = (bool) ( $input['silent'] ?? false );
        $emails_to_suppress = [
            'woocommerce_order_status_pending_to_processing_notification',
            'woocommerce_order_status_pending_to_on-hold_notification',
            'woocommerce_order_status_failed_to_processing_notification',
            'woocommerce_order_status_failed_to_on-hold_notification',
            'woocommerce_order_status_completed_notification',
        ];

        if ( $silent ) {
            foreach ( $emails_to_suppress as $hook ) {
                remove_all_actions( $hook );
            }
        }

        $transaction_id = (string) ( $input['transaction_id'] ?? '' );
        $order->payment_complete( $transaction_id );

        $order->add_order_note(
            sprintf(
                'Marked paid via Site Manager%s%s',
                '' !== $transaction_id ? sprintf( ' (txn: %s)', $transaction_id ) : '',
                $silent ? ' [silent]' : ''
            )
        );

        $fresh = wc_get_order( $order->get_id() );

        return [
            'success'        => true,
            'message'        => 'Order marked paid',
            'order_id'       => $order->get_id(),
            'transaction_id' => $transaction_id,
            'silent'         => $silent,
            'new_status'     => $fresh ? $fresh->get_status() : $order->get_status(),
            'date_paid'      => $fresh && $fresh->get_date_paid()
                ? $fresh->get_date_paid()->format( 'Y-m-d H:i:s' )
                : null,
            'order'          => OrderManager::format_order( $fresh ?: $order, true ),
        ];
    }

    /**
     * Re-send a WooCommerce order email to the customer (or admin for new_order).
     */
    public static function send_email( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $type = (string) ( $input['email_type'] ?? '' );
        if ( ! isset( self::EMAIL_TYPES[ $type ] ) ) {
            return self::errorResponse(
                'invalid_email_type',
                sprintf(
                    'email_type must be one of: %s',
                    implode( ', ', array_keys( self::EMAIL_TYPES ) )
                ),
                400
            );
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        $mailer = WC()->mailer();

        $emails    = $mailer->get_emails();
        $email_key = self::EMAIL_TYPES[ $type ];

        if ( ! isset( $emails[ $email_key ] ) ) {
            return self::errorResponse(
                'email_handler_missing',
                sprintf( 'Email handler %s is not registered', $email_key ),
                500
            );
        }

        $email = $emails[ $email_key ];
        $email->trigger( $order->get_id(), $order );
        $order->add_order_note( sprintf( 'Email "%s" re-sent via Site Manager', $type ) );

        return [
            'success'    => true,
            'message'    => 'Email sent',
            'order_id'   => $order->get_id(),
            'email_type' => $type,
            'recipient'  => 'customer_invoice' === $type || str_starts_with( $type, 'customer_' )
                ? $order->get_billing_email()
                : (string) get_option( 'admin_email' ),
        ];
    }

    /**
     * Return the customer-facing pay-for-order URL.
     */
    public static function get_payment_url( array $input ): array|\WP_Error {
        $unavailable = OrderGuard::ensureAvailable();
        if ( $unavailable ) {
            return $unavailable;
        }

        $order = OrderGuard::fetchOrder( (int) ( $input['order_id'] ?? 0 ) );
        if ( $order instanceof \WP_Error ) {
            return $order;
        }

        if ( $order->is_paid() ) {
            return self::errorResponse(
                'order_already_paid',
                sprintf( 'Order #%d is already paid', $order->get_id() ),
                409
            );
        }

        return [
            'success'   => true,
            'order_id'  => $order->get_id(),
            'pay_url'   => $order->get_checkout_payment_url(),
            'order_key' => $order->get_order_key(),
            'status'    => $order->get_status(),
            'total'     => (string) $order->get_total(),
            'currency'  => $order->get_currency(),
        ];
    }
}
