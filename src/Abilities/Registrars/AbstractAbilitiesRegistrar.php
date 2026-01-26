<?php
/**
 * Abstract Abilities Registrar - Base class for all ability registrars
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Registrars;

use LightweightPlugins\SiteManager\Abilities\PermissionManager;
use LightweightPlugins\SiteManager\Helpers\PaginationHelper;

abstract class AbstractAbilitiesRegistrar {

    protected PermissionManager $permissions;

    public function __construct( PermissionManager $permissions ) {
        $this->permissions = $permissions;
    }

    /**
     * Register all abilities for this registrar
     */
    abstract public function register(): void;

    // =========================================================================
    // Meta Builder Helpers
    // =========================================================================

    /**
     * Build meta array with proper annotations structure (WordPress Abilities API compliant)
     *
     * @param bool $readonly    Whether the ability only reads data
     * @param bool $destructive Whether the ability can cause data loss
     * @param bool $idempotent  Whether multiple calls produce same result
     * @return array Meta configuration array
     */
    protected function buildMeta( bool $readonly = false, bool $destructive = false, bool $idempotent = false ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'    => $readonly,
                'destructive' => $destructive,
                'idempotent'  => $idempotent,
            ],
        ];
    }

    /**
     * Build meta for read-only abilities
     *
     * @param bool $idempotent Whether multiple calls produce same result (default: true for reads)
     * @return array Meta configuration array
     */
    protected function readOnlyMeta( bool $idempotent = true ): array {
        return $this->buildMeta( readonly: true, destructive: false, idempotent: $idempotent );
    }

    /**
     * Build meta for write abilities (non-destructive)
     *
     * @param bool $idempotent Whether multiple calls produce same result
     * @return array Meta configuration array
     */
    protected function writeMeta( bool $idempotent = false ): array {
        return $this->buildMeta( readonly: false, destructive: false, idempotent: $idempotent );
    }

    /**
     * Build meta for destructive abilities
     *
     * @param bool $idempotent Whether multiple calls produce same result
     * @return array Meta configuration array
     */
    protected function destructiveMeta( bool $idempotent = true ): array {
        return $this->buildMeta( readonly: false, destructive: true, idempotent: $idempotent );
    }

    // =========================================================================
    // Schema Builder Helpers
    // =========================================================================

    /**
     * Get pagination schema properties
     *
     * @param int $defaultLimit Default limit value
     * @return array JSON Schema properties for pagination
     */
    protected function paginationSchema( int $defaultLimit = 20 ): array {
        return PaginationHelper::getSchema( $defaultLimit );
    }

    /**
     * Get ordering schema properties
     *
     * @param string $defaultBy    Default orderby field
     * @param string $defaultOrder Default order direction
     * @param array  $allowedBy    Allowed orderby values
     * @return array JSON Schema properties for ordering
     */
    protected function orderingSchema( string $defaultBy = 'date', string $defaultOrder = 'DESC', array $allowedBy = [] ): array {
        return PaginationHelper::getOrderingSchema( $defaultBy, $defaultOrder, $allowedBy );
    }

    /**
     * Get combined pagination and ordering schema
     *
     * @param int    $defaultLimit Default limit value
     * @param string $defaultBy    Default orderby field
     * @param string $defaultOrder Default order direction
     * @return array JSON Schema properties
     */
    protected function listingSchema( int $defaultLimit = 20, string $defaultBy = 'date', string $defaultOrder = 'DESC' ): array {
        return array_merge(
            $this->paginationSchema( $defaultLimit ),
            $this->orderingSchema( $defaultBy, $defaultOrder )
        );
    }

    /**
     * Build a status filter schema
     *
     * @param array  $allowed Allowed status values
     * @param string $default Default status value
     * @return array JSON Schema property for status
     */
    protected function statusSchema( array $allowed, string $default = 'any' ): array {
        return [
            'status' => [
                'type'        => 'string',
                'enum'        => $allowed,
                'default'     => $default,
                'description' => 'Filter by status',
            ],
        ];
    }

    /**
     * Build a search filter schema
     *
     * @return array JSON Schema property for search
     */
    protected function searchSchema(): array {
        return [
            'search' => [
                'type'        => 'string',
                'description' => 'Search query',
            ],
        ];
    }

    /**
     * Build an ID input schema
     *
     * @param string $description Description of the ID
     * @param bool   $required    Whether the ID is required
     * @return array JSON Schema property for ID
     */
    protected function idSchema( string $description = 'Item ID', bool $required = true ): array {
        $schema = [
            'id' => [
                'type'        => 'integer',
                'description' => $description,
                'minimum'     => 1,
            ],
        ];

        if ( $required ) {
            $schema['id']['required'] = true;
        }

        return $schema;
    }

    /**
     * Build a slug input schema
     *
     * @param string $description Description of the slug
     * @return array JSON Schema property for slug
     */
    protected function slugSchema( string $description = 'Item slug' ): array {
        return [
            'slug' => [
                'type'        => 'string',
                'description' => $description,
            ],
        ];
    }

    // =========================================================================
    // Output Schema Helpers
    // =========================================================================

    /**
     * Build a success response schema
     *
     * @param array $additionalProperties Additional properties for the response
     * @return array JSON Schema for success response
     */
    protected function successOutputSchema( array $additionalProperties = [] ): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => array_merge(
                [
                    'success' => [ 'type' => 'boolean' ],
                    'message' => [ 'type' => 'string' ],
                ],
                $additionalProperties
            ),
        ];
    }

    /**
     * Build a list response schema
     *
     * @param string $key        Key name for items array
     * @param array  $itemSchema Schema for individual items
     * @return array JSON Schema for list response
     */
    protected function listOutputSchema( string $key, array $itemSchema ): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                $key => [
                    'type'  => 'array',
                    'items' => $itemSchema,
                ],
                'total'       => [ 'type' => 'integer' ],
                'total_pages' => [ 'type' => 'integer' ],
                'limit'       => [ 'type' => 'integer' ],
                'offset'      => [ 'type' => 'integer' ],
                'has_more'    => [ 'type' => 'boolean' ],
            ],
        ];
    }

    /**
     * Build an entity response schema
     *
     * @param string $key          Key name for entity
     * @param array  $entitySchema Schema for the entity
     * @return array JSON Schema for entity response
     */
    protected function entityOutputSchema( string $key, array $entitySchema ): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success' => [ 'type' => 'boolean' ],
                'message' => [ 'type' => 'string' ],
                $key      => $entitySchema,
            ],
        ];
    }

    /**
     * Build an update result schema (for plugin/theme updates)
     *
     * @return array JSON Schema for update result
     */
    protected function updateResultSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success'     => [ 'type' => 'boolean' ],
                'message'     => [ 'type' => 'string' ],
                'old_version' => [ 'type' => 'string' ],
                'new_version' => [ 'type' => 'string' ],
                'php_errors'  => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
            ],
        ];
    }

    /**
     * Build a bulk action result schema
     *
     * @return array JSON Schema for bulk action result
     */
    protected function bulkResultSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'success'     => [ 'type' => 'boolean' ],
                'action'      => [ 'type' => 'string' ],
                'processed'   => [ 'type' => 'integer' ],
                'failed'      => [ 'type' => 'integer' ],
                'total'       => [ 'type' => 'integer' ],
                'success_ids' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
                'failed_ids' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
                'message' => [ 'type' => 'string' ],
            ],
        ];
    }
}
