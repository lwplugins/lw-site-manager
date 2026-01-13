<?php
/**
 * Pagination Helper - Reusable pagination logic
 */

declare(strict_types=1);

namespace WPSiteManager\Helpers;

class PaginationHelper {

    /**
     * Default pagination values
     */
    public const DEFAULT_LIMIT = 20;
    public const DEFAULT_OFFSET = 0;
    public const DEFAULT_ORDER = 'DESC';

    /**
     * Extract pagination arguments from input
     *
     * @param array $input        Input array containing pagination params
     * @param int   $defaultLimit Default limit if not specified
     * @return array Normalized pagination args [limit, offset, orderby, order]
     */
    public static function extractArgs( array $input, int $defaultLimit = self::DEFAULT_LIMIT ): array {
        return [
            'limit'   => (int) ( $input['limit'] ?? $defaultLimit ),
            'offset'  => (int) ( $input['offset'] ?? self::DEFAULT_OFFSET ),
            'orderby' => $input['orderby'] ?? 'date',
            'order'   => strtoupper( $input['order'] ?? self::DEFAULT_ORDER ),
        ];
    }

    /**
     * Format pagination response data
     *
     * @param int $total  Total number of items
     * @param int $limit  Items per page
     * @param int $offset Current offset
     * @return array Pagination metadata
     */
    public static function formatResponse( int $total, int $limit, int $offset ): array {
        return [
            'total'       => $total,
            'total_pages' => $limit > 0 ? (int) ceil( $total / $limit ) : 1,
            'limit'       => $limit,
            'offset'      => $offset,
            'has_more'    => ( $offset + $limit ) < $total,
        ];
    }

    /**
     * Apply pagination to WP_Query args
     *
     * @param array $args  WP_Query args array (modified by reference)
     * @param array $input Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    public static function applyToWPQuery( array &$args, array $input, int $defaultLimit = self::DEFAULT_LIMIT ): void {
        $pagination = self::extractArgs( $input, $defaultLimit );

        $args['posts_per_page'] = $pagination['limit'];
        $args['offset']         = $pagination['offset'];
        $args['orderby']        = $pagination['orderby'];
        $args['order']          = $pagination['order'];
    }

    /**
     * Apply pagination to WP_User_Query args
     *
     * @param array $args  WP_User_Query args array (modified by reference)
     * @param array $input Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    public static function applyToUserQuery( array &$args, array $input, int $defaultLimit = 50 ): void {
        $pagination = self::extractArgs( $input, $defaultLimit );

        $args['number']  = $pagination['limit'];
        $args['offset']  = $pagination['offset'];
        $args['orderby'] = $pagination['orderby'];
        $args['order']   = $pagination['order'];
    }

    /**
     * Apply pagination to comment query args
     *
     * @param array $args  get_comments args array (modified by reference)
     * @param array $input Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    public static function applyToCommentQuery( array &$args, array $input, int $defaultLimit = 50 ): void {
        $pagination = self::extractArgs( $input, $defaultLimit );

        $args['number']  = $pagination['limit'];
        $args['offset']  = $pagination['offset'];
        $args['orderby'] = $pagination['orderby'];
        $args['order']   = $pagination['order'];
    }

    /**
     * Get count of items without pagination (for totals)
     *
     * @param array $args Original query args
     * @return array Args suitable for counting
     */
    public static function getCountArgs( array $args ): array {
        $countArgs = $args;
        unset( $countArgs['number'], $countArgs['offset'], $countArgs['posts_per_page'] );
        $countArgs['count'] = true;
        return $countArgs;
    }

    /**
     * Build pagination schema for ability input_schema
     *
     * @param int $defaultLimit Default limit value
     * @return array JSON Schema properties for pagination
     */
    public static function getSchema( int $defaultLimit = self::DEFAULT_LIMIT ): array {
        return [
            'limit' => [
                'type'        => 'integer',
                'default'     => $defaultLimit,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => 'Number of items to return',
            ],
            'offset' => [
                'type'        => 'integer',
                'default'     => 0,
                'minimum'     => 0,
                'description' => 'Number of items to skip',
            ],
        ];
    }

    /**
     * Build ordering schema for ability input_schema
     *
     * @param string $defaultOrderBy Default order by field
     * @param string $defaultOrder   Default order direction
     * @param array  $allowedOrderBy Allowed orderby values (optional)
     * @return array JSON Schema properties for ordering
     */
    public static function getOrderingSchema(
        string $defaultOrderBy = 'date',
        string $defaultOrder = 'DESC',
        array $allowedOrderBy = []
    ): array {
        $schema = [
            'orderby' => [
                'type'        => 'string',
                'default'     => $defaultOrderBy,
                'description' => 'Field to order by',
            ],
            'order' => [
                'type'        => 'string',
                'enum'        => [ 'ASC', 'DESC' ],
                'default'     => $defaultOrder,
                'description' => 'Sort direction',
            ],
        ];

        if ( ! empty( $allowedOrderBy ) ) {
            $schema['orderby']['enum'] = $allowedOrderBy;
        }

        return $schema;
    }

    /**
     * Build combined pagination + ordering schema
     *
     * @param int    $defaultLimit   Default limit value
     * @param string $defaultOrderBy Default order by field
     * @param string $defaultOrder   Default order direction
     * @return array JSON Schema properties
     */
    public static function getFullSchema(
        int $defaultLimit = self::DEFAULT_LIMIT,
        string $defaultOrderBy = 'date',
        string $defaultOrder = 'DESC'
    ): array {
        return array_merge(
            self::getSchema( $defaultLimit ),
            self::getOrderingSchema( $defaultOrderBy, $defaultOrder )
        );
    }
}
