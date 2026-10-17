<?php
/**
 * Minimal WooCommerce object doubles for unit tests.
 *
 * Products and orders are registered in $GLOBALS['wc_test_products'] /
 * $GLOBALS['wc_test_orders'] by ID; wc_get_product() / wc_get_order() return
 * them. delete() calls are recorded in $GLOBALS['wc_test_deleted'].
 *
 * @package LightweightPlugins\SiteManager\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WC_Data' ) ) {
    class WC_Data {
        public function __construct( protected int $id = 0, protected string $type = 'simple' ) {}
        public function get_id(): int {
            return $this->id;
        }
        public function delete( $force_delete = false ): bool {
            $GLOBALS['wc_test_deleted'][] = [ 'id' => $this->id, 'force' => (bool) $force_delete ];
            return true;
        }
    }
}

if ( ! class_exists( 'WC_Product' ) ) {
    class WC_Product extends WC_Data {
        public function get_name(): string {
            return 'Product ' . $this->id;
        }
        public function is_type( $type ): bool {
            return in_array( $this->type, (array) $type, true );
        }
        public function get_parent_id(): int {
            return 0;
        }
    }
}

if ( ! class_exists( 'WC_Order' ) ) {
    class WC_Order extends WC_Data {
        public float $total     = 100.0;
        public float $refunded  = 0.0;
        public string $status   = 'processing';
        public function get_order_number(): string {
            return (string) $this->id;
        }
        public function get_status(): string {
            return $this->status;
        }
        public function get_total(): float {
            return $this->total;
        }
        public function get_remaining_refund_amount(): float {
            return $this->total - $this->refunded;
        }
        public function update_status( $status, $note = '' ): bool {
            $GLOBALS['wc_test_status_updates'][] = [ 'id' => $this->id, 'status' => $status ];
            $this->status = $status;
            return true;
        }
    }
}

if ( ! function_exists( 'wc_get_product' ) ) {
    function wc_get_product( $id = false ) {
        return $GLOBALS['wc_test_products'][ (int) $id ] ?? false;
    }
}

if ( ! function_exists( 'wc_get_order' ) ) {
    function wc_get_order( $id = false ) {
        return $GLOBALS['wc_test_orders'][ (int) $id ] ?? false;
    }
}

if ( ! function_exists( 'wc_create_refund' ) ) {
    function wc_create_refund( $args = [] ) {
        $GLOBALS['wc_test_refunds'][] = $args;
        return new WC_Data( 999 );
    }
}

if ( ! function_exists( 'wc_format_decimal' ) ) {
    function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
        return (string) $number;
    }
}
