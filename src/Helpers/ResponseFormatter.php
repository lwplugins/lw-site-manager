<?php
/**
 * Response Formatter - Standardized response structure
 */

declare(strict_types=1);

namespace WPSiteManager\Helpers;

class ResponseFormatter {

    /**
     * Create a success response
     *
     * @param array  $data    Response data
     * @param string $message Success message
     * @return array Formatted success response
     */
    public static function success( array $data, string $message = 'Operation completed successfully' ): array {
        return array_merge(
            [
                'success' => true,
                'message' => $message,
            ],
            $data
        );
    }

    /**
     * Create an error response as WP_Error
     *
     * @param string $code    Error code
     * @param string $message Error message
     * @param int    $status  HTTP status code
     * @return \WP_Error
     */
    public static function error( string $code, string $message, int $status = 400 ): \WP_Error {
        return new \WP_Error( $code, $message, [ 'status' => $status ] );
    }

    /**
     * Create a paginated list response
     *
     * @param string $key        Key name for the items array
     * @param array  $items      Array of items
     * @param int    $total      Total number of items
     * @param int    $limit      Items per page
     * @param int    $offset     Current offset
     * @return array Formatted list response
     */
    public static function list( string $key, array $items, int $total, int $limit, int $offset ): array {
        return [
            $key          => $items,
            'total'       => $total,
            'total_pages' => $limit > 0 ? (int) ceil( $total / $limit ) : 1,
            'limit'       => $limit,
            'offset'      => $offset,
            'has_more'    => ( $offset + $limit ) < $total,
        ];
    }

    /**
     * Create a bulk action result response
     *
     * @param array  $successIds Array of successfully processed IDs
     * @param array  $failedIds  Array of failed IDs
     * @param string $action     Action that was performed
     * @return array Formatted bulk result response
     */
    public static function bulkResult( array $successIds, array $failedIds, string $action ): array {
        $totalProcessed = count( $successIds ) + count( $failedIds );
        $allSuccess     = empty( $failedIds );

        return [
            'success'     => $allSuccess,
            'action'      => $action,
            'processed'   => count( $successIds ),
            'failed'      => count( $failedIds ),
            'total'       => $totalProcessed,
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
            'message'     => $allSuccess
                ? sprintf( '%d items processed successfully', count( $successIds ) )
                : sprintf( '%d succeeded, %d failed', count( $successIds ), count( $failedIds ) ),
        ];
    }

    /**
     * Create a single entity response
     *
     * @param string $key     Key name for the entity
     * @param array  $entity  Entity data
     * @param string $message Success message
     * @return array Formatted entity response
     */
    public static function entity( string $key, array $entity, string $message = '' ): array {
        $response = [
            'success' => true,
            $key      => $entity,
        ];

        if ( $message ) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Create a created entity response
     *
     * @param string   $key      Key name for the entity
     * @param array    $entity   Entity data
     * @param int|null $entityId The created entity's ID
     * @return array Formatted created response
     */
    public static function created( string $key, array $entity, ?int $entityId = null ): array {
        $response = [
            'success' => true,
            'message' => ucfirst( $key ) . ' created successfully',
            $key      => $entity,
        ];

        if ( $entityId !== null ) {
            $response['id'] = $entityId;
        }

        return $response;
    }

    /**
     * Create an updated entity response
     *
     * @param string $key    Key name for the entity
     * @param array  $entity Entity data
     * @return array Formatted updated response
     */
    public static function updated( string $key, array $entity ): array {
        return [
            'success' => true,
            'message' => ucfirst( $key ) . ' updated successfully',
            $key      => $entity,
        ];
    }

    /**
     * Create a deleted entity response
     *
     * @param string $key Key name for the entity type
     * @param int    $id  Deleted entity ID
     * @return array Formatted deleted response
     */
    public static function deleted( string $key, int $id ): array {
        return [
            'success' => true,
            'message' => ucfirst( $key ) . ' deleted successfully',
            'id'      => $id,
        ];
    }

    /**
     * Create a not found error response
     *
     * @param string $entity Entity type name
     * @param int    $id     Entity ID that wasn't found
     * @return \WP_Error
     */
    public static function notFound( string $entity, int $id ): \WP_Error {
        return self::error(
            $entity . '_not_found',
            sprintf( '%s with ID %d not found', ucfirst( $entity ), $id ),
            404
        );
    }

    /**
     * Create a permission denied error response
     *
     * @param string $action Action that was denied
     * @return \WP_Error
     */
    public static function permissionDenied( string $action = 'perform this action' ): \WP_Error {
        return self::error(
            'permission_denied',
            sprintf( 'You do not have permission to %s', $action ),
            403
        );
    }

    /**
     * Create an update result response (for plugin/theme updates)
     *
     * @param bool        $success     Whether update succeeded
     * @param string      $message     Result message
     * @param string|null $oldVersion  Previous version
     * @param string|null $newVersion  New version
     * @param array       $phpErrors   Array of PHP errors captured
     * @return array Formatted update result
     */
    public static function updateResult(
        bool $success,
        string $message,
        ?string $oldVersion = null,
        ?string $newVersion = null,
        array $phpErrors = []
    ): array {
        $response = [
            'success'    => $success,
            'message'    => $message,
            'php_errors' => $phpErrors,
        ];

        if ( $oldVersion !== null ) {
            $response['old_version'] = $oldVersion;
        }

        if ( $newVersion !== null ) {
            $response['new_version'] = $newVersion;
        }

        return $response;
    }

    /**
     * Wrap a WP_Error or success response into consistent format
     *
     * @param array|\WP_Error $result   Result to wrap
     * @param string          $errorKey Key name for error context
     * @return array|\WP_Error Wrapped result
     */
    public static function wrap( array|\WP_Error $result, string $errorKey = 'error' ): array|\WP_Error {
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( ! isset( $result['success'] ) ) {
            $result['success'] = true;
        }

        return $result;
    }
}
