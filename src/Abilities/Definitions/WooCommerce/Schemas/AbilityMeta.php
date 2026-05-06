<?php
/**
 * WooCommerce ability meta annotations.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

final class AbilityMeta {

    public static function readOnly(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ],
        ];
    }

    public static function write(): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ],
        ];
    }

    public static function destructive( bool $idempotent = true ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => $idempotent,
            ],
        ];
    }
}
