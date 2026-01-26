<?php
/**
 * Input Validator - Reusable input validation helper
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Helpers;

class InputValidator {

    /**
     * Validate a single required field
     *
     * @param array  $input   Input array to validate
     * @param string $field   Field name to check
     * @param string $message Error message if validation fails
     * @return \WP_Error|null Returns WP_Error if validation fails, null if valid
     */
    public static function required( array $input, string $field, ?string $message = null ): ?\WP_Error {
        if ( empty( $input[ $field ] ) ) {
            $message = $message ?? sprintf( '%s is required', ucfirst( str_replace( '_', ' ', $field ) ) );
            return new \WP_Error(
                'missing_' . $field,
                $message,
                [ 'status' => 400 ]
            );
        }
        return null;
    }

    /**
     * Validate multiple required fields
     *
     * @param array $input  Input array to validate
     * @param array $fields Array of field names to check
     * @return \WP_Error|null Returns WP_Error for first failing field, null if all valid
     */
    public static function requireFields( array $input, array $fields ): ?\WP_Error {
        foreach ( $fields as $field ) {
            $error = self::required( $input, $field );
            if ( $error ) {
                return $error;
            }
        }
        return null;
    }

    /**
     * Validate that a value is within an allowed enum
     *
     * @param mixed  $value   Value to validate
     * @param array  $allowed Array of allowed values
     * @param string $field   Field name for error message
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateEnum( mixed $value, array $allowed, string $field ): ?\WP_Error {
        if ( $value !== null && ! in_array( $value, $allowed, true ) ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf(
                    'Invalid %s. Allowed values: %s',
                    str_replace( '_', ' ', $field ),
                    implode( ', ', $allowed )
                ),
                [ 'status' => 400 ]
            );
        }
        return null;
    }

    /**
     * Validate that a value is an integer within optional bounds
     *
     * @param mixed    $value Value to validate
     * @param string   $field Field name for error message
     * @param int|null $min   Minimum value (optional)
     * @param int|null $max   Maximum value (optional)
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateInteger( mixed $value, string $field, ?int $min = null, ?int $max = null ): ?\WP_Error {
        if ( $value === null ) {
            return null;
        }

        if ( ! is_numeric( $value ) ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must be an integer', ucfirst( str_replace( '_', ' ', $field ) ) ),
                [ 'status' => 400 ]
            );
        }

        $intValue = (int) $value;

        if ( $min !== null && $intValue < $min ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must be at least %d', ucfirst( str_replace( '_', ' ', $field ) ), $min ),
                [ 'status' => 400 ]
            );
        }

        if ( $max !== null && $intValue > $max ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must be at most %d', ucfirst( str_replace( '_', ' ', $field ) ), $max ),
                [ 'status' => 400 ]
            );
        }

        return null;
    }

    /**
     * Validate that a value is a valid email address
     *
     * @param mixed  $value Value to validate
     * @param string $field Field name for error message
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateEmail( mixed $value, string $field = 'email' ): ?\WP_Error {
        if ( $value === null || $value === '' ) {
            return null;
        }

        if ( ! is_email( $value ) ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must be a valid email address', ucfirst( str_replace( '_', ' ', $field ) ) ),
                [ 'status' => 400 ]
            );
        }

        return null;
    }

    /**
     * Validate that a value is a positive integer (ID)
     *
     * @param mixed  $value Value to validate
     * @param string $field Field name for error message
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateId( mixed $value, string $field = 'id' ): ?\WP_Error {
        if ( $value === null ) {
            return null;
        }

        return self::validateInteger( $value, $field, 1 );
    }

    /**
     * Validate that a string is not longer than max length
     *
     * @param mixed    $value     Value to validate
     * @param string   $field     Field name for error message
     * @param int      $maxLength Maximum allowed length
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateMaxLength( mixed $value, string $field, int $maxLength ): ?\WP_Error {
        if ( $value === null || $value === '' ) {
            return null;
        }

        if ( is_string( $value ) && mb_strlen( $value ) > $maxLength ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must not exceed %d characters', ucfirst( str_replace( '_', ' ', $field ) ), $maxLength ),
                [ 'status' => 400 ]
            );
        }

        return null;
    }

    /**
     * Validate an array of IDs
     *
     * @param mixed  $value Value to validate (should be array)
     * @param string $field Field name for error message
     * @return \WP_Error|null Returns WP_Error if invalid, null if valid
     */
    public static function validateIdArray( mixed $value, string $field = 'ids' ): ?\WP_Error {
        if ( $value === null ) {
            return null;
        }

        if ( ! is_array( $value ) ) {
            return new \WP_Error(
                'invalid_' . $field,
                sprintf( '%s must be an array', ucfirst( str_replace( '_', ' ', $field ) ) ),
                [ 'status' => 400 ]
            );
        }

        if ( empty( $value ) ) {
            return new \WP_Error(
                'empty_' . $field,
                sprintf( '%s cannot be empty', ucfirst( str_replace( '_', ' ', $field ) ) ),
                [ 'status' => 400 ]
            );
        }

        foreach ( $value as $index => $id ) {
            if ( ! is_numeric( $id ) || (int) $id < 1 ) {
                return new \WP_Error(
                    'invalid_' . $field,
                    sprintf( 'Invalid ID at index %d in %s', $index, $field ),
                    [ 'status' => 400 ]
                );
            }
        }

        return null;
    }
}
