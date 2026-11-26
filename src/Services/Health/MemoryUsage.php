<?php
/**
 * PHP memory figures for health-check.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Health;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Memory limit, usage and usage percentage, with an unlimited limit
 * (memory_limit = -1) reported as such instead of a negative size.
 */
final class MemoryUsage {

    /**
     * Build the `memory` block of the health-check result.
     *
     * @param string $iniLimit The memory_limit ini value, e.g. "256M" or "-1".
     * @param int    $usage    Current usage in bytes.
     * @return array{limit: string, usage: string, percent: float|null, unlimited: bool}
     */
    public static function report( string $iniLimit, int $usage ): array {
        $limit     = wp_convert_hr_to_bytes( $iniLimit );
        $unlimited = $limit <= 0;

        return [
            'limit'     => $unlimited ? 'unlimited' : (string) size_format( $limit ),
            'usage'     => (string) size_format( max( 0, $usage ) ),
            'percent'   => $unlimited ? null : round( $usage / $limit * 100, 2 ),
            'unlimited' => $unlimited,
        ];
    }
}
