<?php
/**
 * Reusable WooCommerce ability response schemas.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerce\Schemas;

final class ResponseSchema {

    public static function list( string $key, array $itemSchema ): array {
        return [
            'type'       => 'object',
            'properties' => [
                $key          => [ 'type' => 'array', 'items' => $itemSchema ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
                'has_more'    => [ 'type' => 'boolean' ],
            ],
        ];
    }

    public static function entity( string $key, array $entitySchema, bool $includeId = false ): array {
        $properties = [
            'success' => [ 'type' => 'boolean' ],
            'message' => [ 'type' => 'string' ],
            $key      => $entitySchema,
        ];

        if ( $includeId ) {
            $properties['id'] = [ 'type' => 'integer' ];
        }

        return [
            'type'       => 'object',
            'properties' => $properties,
        ];
    }

    public static function delete(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                'id'      => [ 'type' => 'integer' ],
                'name'    => [ 'type' => 'string' ],
                'trashed' => [ 'type' => 'boolean' ],
            ],
        ];
    }

    public static function bulk(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'success'     => [ 'type' => 'boolean' ],
                'action'      => [ 'type' => 'string' ],
                'processed'   => [ 'type' => 'integer' ],
                'failed'      => [ 'type' => 'integer' ],
                'total'       => [ 'type' => 'integer' ],
                'success_ids' => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                'failed_ids'  => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ] ],
                'message'     => [ 'type' => 'string' ],
            ],
        ];
    }
}
