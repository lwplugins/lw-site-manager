<?php
/**
 * Abstract Service - Base class for all service classes
 *
 * Provides common functionality for validation, response formatting,
 * and pagination handling.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

use LightweightPlugins\SiteManager\Helpers\InputValidator;
use LightweightPlugins\SiteManager\Helpers\PaginationHelper;
use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

abstract class AbstractService {

    // =========================================================================
    // Validation Helpers
    // =========================================================================

    /**
     * Validate required fields in input
     *
     * @param array $input  Input array to validate
     * @param array $fields Required field names
     * @return \WP_Error|null Returns WP_Error if validation fails, null if valid
     */
    protected static function validateRequired( array $input, array $fields ): ?\WP_Error {
        return InputValidator::requireFields( $input, $fields );
    }

    /**
     * Validate a single required field
     *
     * @param array       $input   Input array to validate
     * @param string      $field   Field name to check
     * @param string|null $message Custom error message
     * @return \WP_Error|null
     */
    protected static function validateRequiredField( array $input, string $field, ?string $message = null ): ?\WP_Error {
        return InputValidator::required( $input, $field, $message );
    }

    /**
     * Validate ID field
     *
     * @param array  $input Input array containing ID
     * @param string $field Field name (default: 'id')
     * @return \WP_Error|null
     */
    protected static function validateId( array $input, string $field = 'id' ): ?\WP_Error {
        $error = InputValidator::required( $input, $field );
        if ( $error ) {
            return $error;
        }
        return InputValidator::validateId( $input[ $field ], $field );
    }

    /**
     * Validate enum value
     *
     * @param mixed  $value   Value to validate
     * @param array  $allowed Allowed values
     * @param string $field   Field name for error message
     * @return \WP_Error|null
     */
    protected static function validateEnum( mixed $value, array $allowed, string $field ): ?\WP_Error {
        return InputValidator::validateEnum( $value, $allowed, $field );
    }

    /**
     * Validate email field
     *
     * @param array  $input Input array containing email
     * @param string $field Field name (default: 'email')
     * @return \WP_Error|null
     */
    protected static function validateEmail( array $input, string $field = 'email' ): ?\WP_Error {
        if ( empty( $input[ $field ] ) ) {
            return null; // Optional field, skip if empty
        }
        return InputValidator::validateEmail( $input[ $field ], $field );
    }

    /**
     * Validate array of IDs
     *
     * @param array  $input Input array containing IDs array
     * @param string $field Field name (default: 'ids')
     * @return \WP_Error|null
     */
    protected static function validateIdArray( array $input, string $field = 'ids' ): ?\WP_Error {
        $error = InputValidator::required( $input, $field );
        if ( $error ) {
            return $error;
        }
        return InputValidator::validateIdArray( $input[ $field ], $field );
    }

    // =========================================================================
    // Response Helpers
    // =========================================================================

    /**
     * Create a success response
     *
     * @param array  $data    Response data
     * @param string $message Success message
     * @return array
     */
    protected static function successResponse( array $data, string $message = 'Operation completed successfully' ): array {
        return ResponseFormatter::success( $data, $message );
    }

    /**
     * Create an error response
     *
     * @param string $code    Error code
     * @param string $message Error message
     * @param int    $status  HTTP status code
     * @return \WP_Error
     */
    protected static function errorResponse( string $code, string $message, int $status = 400 ): \WP_Error {
        return ResponseFormatter::error( $code, $message, $status );
    }

    /**
     * Create a paginated list response
     *
     * @param string $key        Key name for the items array
     * @param array  $items      Array of items
     * @param int    $total      Total number of items
     * @param int    $limit      Items per page
     * @param int    $offset     Current offset
     * @return array
     */
    protected static function listResponse( string $key, array $items, int $total, int $limit, int $offset ): array {
        return ResponseFormatter::list( $key, $items, $total, $limit, $offset );
    }

    /**
     * Create a single entity response
     *
     * @param string $key     Key name for the entity
     * @param array  $entity  Entity data
     * @param string $message Success message
     * @return array
     */
    protected static function entityResponse( string $key, array $entity, string $message = '' ): array {
        return ResponseFormatter::entity( $key, $entity, $message );
    }

    /**
     * Create a created entity response
     *
     * @param string   $key      Key name for the entity
     * @param array    $entity   Entity data
     * @param int|null $entityId Created entity ID
     * @return array
     */
    protected static function createdResponse( string $key, array $entity, ?int $entityId = null ): array {
        return ResponseFormatter::created( $key, $entity, $entityId );
    }

    /**
     * Create an updated entity response
     *
     * @param string $key    Key name for the entity
     * @param array  $entity Entity data
     * @return array
     */
    protected static function updatedResponse( string $key, array $entity ): array {
        return ResponseFormatter::updated( $key, $entity );
    }

    /**
     * Create a deleted entity response
     *
     * @param string $key Key name for the entity type
     * @param int    $id  Deleted entity ID
     * @return array
     */
    protected static function deletedResponse( string $key, int $id ): array {
        return ResponseFormatter::deleted( $key, $id );
    }

    /**
     * Create a not found error response
     *
     * @param string $entity Entity type name
     * @param int    $id     Entity ID that wasn't found
     * @return \WP_Error
     */
    protected static function notFoundError( string $entity, int $id ): \WP_Error {
        return ResponseFormatter::notFound( $entity, $id );
    }

    /**
     * Create a bulk action result response
     *
     * @param array  $successIds Array of successfully processed IDs
     * @param array  $failedIds  Array of failed IDs
     * @param string $action     Action that was performed
     * @return array
     */
    protected static function bulkResponse( array $successIds, array $failedIds, string $action ): array {
        return ResponseFormatter::bulkResult( $successIds, $failedIds, $action );
    }

    /**
     * Create an update result response (for plugin/theme updates)
     *
     * @param bool        $success     Whether update succeeded
     * @param string      $message     Result message
     * @param string|null $oldVersion  Previous version
     * @param string|null $newVersion  New version
     * @param array       $phpErrors   Array of PHP errors captured
     * @return array
     */
    protected static function updateResultResponse(
        bool $success,
        string $message,
        ?string $oldVersion = null,
        ?string $newVersion = null,
        array $phpErrors = []
    ): array {
        return ResponseFormatter::updateResult( $success, $message, $oldVersion, $newVersion, $phpErrors );
    }

    // =========================================================================
    // Pagination Helpers
    // =========================================================================

    /**
     * Extract pagination arguments from input
     *
     * @param array $input        Input array containing pagination params
     * @param int   $defaultLimit Default limit if not specified
     * @return array Normalized pagination args
     */
    protected static function getPaginationArgs( array $input, int $defaultLimit = 20 ): array {
        return PaginationHelper::extractArgs( $input, $defaultLimit );
    }

    /**
     * Apply pagination to WP_Query args
     *
     * @param array $args         WP_Query args (modified by reference)
     * @param array $input        Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    protected static function applyPaginationToQuery( array &$args, array $input, int $defaultLimit = 20 ): void {
        PaginationHelper::applyToWPQuery( $args, $input, $defaultLimit );
    }

    /**
     * Apply pagination to WP_User_Query args
     *
     * @param array $args         WP_User_Query args (modified by reference)
     * @param array $input        Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    protected static function applyPaginationToUserQuery( array &$args, array $input, int $defaultLimit = 50 ): void {
        PaginationHelper::applyToUserQuery( $args, $input, $defaultLimit );
    }

    /**
     * Apply pagination to comment query args
     *
     * @param array $args         Comment query args (modified by reference)
     * @param array $input        Input array containing pagination params
     * @param int   $defaultLimit Default limit
     */
    protected static function applyPaginationToCommentQuery( array &$args, array $input, int $defaultLimit = 50 ): void {
        PaginationHelper::applyToCommentQuery( $args, $input, $defaultLimit );
    }

    // =========================================================================
    // Entity Helpers
    // =========================================================================

    /**
     * Check if a post exists and is accessible
     *
     * @param int         $postId   Post ID to check
     * @param string|null $postType Expected post type (optional)
     * @return \WP_Post|\WP_Error
     */
    protected static function getPostOrError( int $postId, ?string $postType = null ): \WP_Post|\WP_Error {
        $post = get_post( $postId );

        if ( ! $post ) {
            return self::notFoundError( 'post', $postId );
        }

        if ( $postType !== null && $post->post_type !== $postType ) {
            return self::errorResponse(
                'invalid_post_type',
                sprintf( 'Post %d is not a %s', $postId, $postType ),
                400
            );
        }

        return $post;
    }

    /**
     * Check if a user exists
     *
     * @param int $userId User ID to check
     * @return \WP_User|\WP_Error
     */
    protected static function getUserOrError( int $userId ): \WP_User|\WP_Error {
        $user = get_user_by( 'id', $userId );

        if ( ! $user ) {
            return self::notFoundError( 'user', $userId );
        }

        return $user;
    }

    /**
     * Check if a comment exists
     *
     * @param int $commentId Comment ID to check
     * @return \WP_Comment|\WP_Error
     */
    protected static function getCommentOrError( int $commentId ): \WP_Comment|\WP_Error {
        $comment = get_comment( $commentId );

        if ( ! $comment ) {
            return self::notFoundError( 'comment', $commentId );
        }

        return $comment;
    }

    // =========================================================================
    // Utility Helpers
    // =========================================================================

    /**
     * Sanitize and prepare post data for insertion/update
     *
     * @param array  $input    Input data
     * @param string $postType Post type
     * @return array Prepared post data
     */
    protected static function preparePostData( array $input, string $postType = 'post' ): array {
        $data = [
            'post_type' => $postType,
        ];

        $mappings = [
            'title'      => 'post_title',
            'content'    => 'post_content',
            'excerpt'    => 'post_excerpt',
            'status'     => 'post_status',
            'slug'       => 'post_name',
            'author'     => 'post_author',
            'parent'     => 'post_parent',
            'menu_order' => 'menu_order',
            'date'       => 'post_date',
        ];

        foreach ( $mappings as $inputKey => $postKey ) {
            if ( isset( $input[ $inputKey ] ) ) {
                $data[ $postKey ] = $input[ $inputKey ];
            }
        }

        return $data;
    }

    /**
     * Handle WordPress error result
     *
     * If the result is a WP_Error, return it directly.
     * Otherwise, return null to indicate success.
     *
     * @param mixed $result WordPress function result
     * @return \WP_Error|null
     */
    protected static function handleWPError( mixed $result ): ?\WP_Error {
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return null;
    }
}
