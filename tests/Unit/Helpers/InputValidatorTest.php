<?php
/**
 * Unit tests for InputValidator helper.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Helpers
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Helpers\InputValidator;

/**
 * Tests for InputValidator helper.
 */
final class InputValidatorTest extends TestCase {

    // =========================================================================
    // Required Field Tests
    // =========================================================================

    /**
     * Test that required returns null for present field.
     */
    public function test_required_returns_null_for_present_field(): void {
        $result = InputValidator::required( [ 'name' => 'John' ], 'name' );

        $this->assertNull( $result );
    }

    /**
     * Test that required returns error for missing field.
     */
    public function test_required_returns_error_for_missing_field(): void {
        $result = InputValidator::required( [], 'name' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_name', $result->get_error_code() );
    }

    /**
     * Test that required returns error for empty field.
     */
    public function test_required_returns_error_for_empty_field(): void {
        $result = InputValidator::required( [ 'name' => '' ], 'name' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_name', $result->get_error_code() );
    }

    /**
     * Test that required uses custom error message.
     */
    public function test_required_uses_custom_message(): void {
        $result = InputValidator::required( [], 'email', 'Please provide your email' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'Please provide your email', $result->get_error_message() );
    }

    /**
     * Test that required generates default message with underscores replaced.
     */
    public function test_required_generates_default_message(): void {
        $result = InputValidator::required( [], 'user_name' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( 'User name', $result->get_error_message() );
    }

    // =========================================================================
    // RequireFields Tests
    // =========================================================================

    /**
     * Test that requireFields returns null when all fields present.
     */
    public function test_require_fields_returns_null_when_all_present(): void {
        $result = InputValidator::requireFields(
            [ 'name' => 'John', 'email' => 'john@example.com' ],
            [ 'name', 'email' ]
        );

        $this->assertNull( $result );
    }

    /**
     * Test that requireFields returns error for first missing field.
     */
    public function test_require_fields_returns_error_for_first_missing(): void {
        $result = InputValidator::requireFields(
            [ 'name' => 'John' ],
            [ 'name', 'email', 'phone' ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_email', $result->get_error_code() );
    }

    // =========================================================================
    // Enum Validation Tests
    // =========================================================================

    /**
     * Test that validateEnum returns null for valid value.
     */
    public function test_validate_enum_returns_null_for_valid_value(): void {
        $result = InputValidator::validateEnum( 'publish', [ 'draft', 'publish', 'private' ], 'status' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateEnum returns null for null value.
     */
    public function test_validate_enum_returns_null_for_null(): void {
        $result = InputValidator::validateEnum( null, [ 'draft', 'publish' ], 'status' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateEnum returns error for invalid value.
     */
    public function test_validate_enum_returns_error_for_invalid(): void {
        $result = InputValidator::validateEnum( 'invalid', [ 'draft', 'publish' ], 'status' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_status', $result->get_error_code() );
        $this->assertStringContainsString( 'draft, publish', $result->get_error_message() );
    }

    // =========================================================================
    // Integer Validation Tests
    // =========================================================================

    /**
     * Test that validateInteger returns null for valid integer.
     */
    public function test_validate_integer_returns_null_for_valid(): void {
        $result = InputValidator::validateInteger( 42, 'count' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateInteger returns null for null.
     */
    public function test_validate_integer_returns_null_for_null(): void {
        $result = InputValidator::validateInteger( null, 'count' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateInteger returns null for numeric string.
     */
    public function test_validate_integer_accepts_numeric_string(): void {
        $result = InputValidator::validateInteger( '42', 'count' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateInteger returns error for non-numeric.
     */
    public function test_validate_integer_returns_error_for_non_numeric(): void {
        $result = InputValidator::validateInteger( 'abc', 'count' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_count', $result->get_error_code() );
    }

    /**
     * Test that validateInteger returns error when below minimum.
     */
    public function test_validate_integer_returns_error_below_min(): void {
        $result = InputValidator::validateInteger( 5, 'count', 10 );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( 'at least 10', $result->get_error_message() );
    }

    /**
     * Test that validateInteger returns error when above maximum.
     */
    public function test_validate_integer_returns_error_above_max(): void {
        $result = InputValidator::validateInteger( 150, 'count', null, 100 );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( 'at most 100', $result->get_error_message() );
    }

    /**
     * Test that validateInteger accepts value within bounds.
     */
    public function test_validate_integer_accepts_within_bounds(): void {
        $result = InputValidator::validateInteger( 50, 'count', 1, 100 );

        $this->assertNull( $result );
    }

    // =========================================================================
    // Email Validation Tests
    // =========================================================================

    /**
     * Test that validateEmail returns null for valid email.
     */
    public function test_validate_email_returns_null_for_valid(): void {
        $result = InputValidator::validateEmail( 'test@example.com' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateEmail returns null for null.
     */
    public function test_validate_email_returns_null_for_null(): void {
        $result = InputValidator::validateEmail( null );

        $this->assertNull( $result );
    }

    /**
     * Test that validateEmail returns null for empty string.
     */
    public function test_validate_email_returns_null_for_empty(): void {
        $result = InputValidator::validateEmail( '' );

        $this->assertNull( $result );
    }

    /**
     * Test that validateEmail returns error for invalid email.
     */
    public function test_validate_email_returns_error_for_invalid(): void {
        $result = InputValidator::validateEmail( 'not-an-email' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_email', $result->get_error_code() );
    }

    /**
     * Test that validateEmail uses custom field name.
     */
    public function test_validate_email_uses_custom_field_name(): void {
        $result = InputValidator::validateEmail( 'invalid', 'contact_email' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'invalid_contact_email', $result->get_error_code() );
    }

    // =========================================================================
    // ID Validation Tests
    // =========================================================================

    /**
     * Test that validateId returns null for valid ID.
     */
    public function test_validate_id_returns_null_for_valid(): void {
        $result = InputValidator::validateId( 123 );

        $this->assertNull( $result );
    }

    /**
     * Test that validateId returns null for null.
     */
    public function test_validate_id_returns_null_for_null(): void {
        $result = InputValidator::validateId( null );

        $this->assertNull( $result );
    }

    /**
     * Test that validateId returns error for zero.
     */
    public function test_validate_id_returns_error_for_zero(): void {
        $result = InputValidator::validateId( 0 );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that validateId returns error for negative.
     */
    public function test_validate_id_returns_error_for_negative(): void {
        $result = InputValidator::validateId( -5 );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // Max Length Validation Tests
    // =========================================================================

    /**
     * Test that validateMaxLength returns null for valid length.
     */
    public function test_validate_max_length_returns_null_for_valid(): void {
        $result = InputValidator::validateMaxLength( 'hello', 'title', 10 );

        $this->assertNull( $result );
    }

    /**
     * Test that validateMaxLength returns null for null.
     */
    public function test_validate_max_length_returns_null_for_null(): void {
        $result = InputValidator::validateMaxLength( null, 'title', 10 );

        $this->assertNull( $result );
    }

    /**
     * Test that validateMaxLength returns null for empty string.
     */
    public function test_validate_max_length_returns_null_for_empty(): void {
        $result = InputValidator::validateMaxLength( '', 'title', 10 );

        $this->assertNull( $result );
    }

    /**
     * Test that validateMaxLength returns error when exceeded.
     */
    public function test_validate_max_length_returns_error_when_exceeded(): void {
        $result = InputValidator::validateMaxLength( 'This is a very long string', 'title', 10 );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( '10 characters', $result->get_error_message() );
    }

    /**
     * Test that validateMaxLength handles multibyte characters.
     */
    public function test_validate_max_length_handles_multibyte(): void {
        // "héllo" is 5 characters even with multibyte
        $result = InputValidator::validateMaxLength( 'héllo', 'title', 5 );

        $this->assertNull( $result );
    }

    // =========================================================================
    // ID Array Validation Tests
    // =========================================================================

    /**
     * Test that validateIdArray returns null for valid array.
     */
    public function test_validate_id_array_returns_null_for_valid(): void {
        $result = InputValidator::validateIdArray( [ 1, 2, 3 ] );

        $this->assertNull( $result );
    }

    /**
     * Test that validateIdArray returns null for null.
     */
    public function test_validate_id_array_returns_null_for_null(): void {
        $result = InputValidator::validateIdArray( null );

        $this->assertNull( $result );
    }

    /**
     * Test that validateIdArray returns error for non-array.
     */
    public function test_validate_id_array_returns_error_for_non_array(): void {
        $result = InputValidator::validateIdArray( 'not-array' );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( 'must be an array', $result->get_error_message() );
    }

    /**
     * Test that validateIdArray returns error for empty array.
     */
    public function test_validate_id_array_returns_error_for_empty(): void {
        $result = InputValidator::validateIdArray( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'empty_ids', $result->get_error_code() );
    }

    /**
     * Test that validateIdArray returns error for invalid ID in array.
     */
    public function test_validate_id_array_returns_error_for_invalid_id(): void {
        $result = InputValidator::validateIdArray( [ 1, 'abc', 3 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertStringContainsString( 'index 1', $result->get_error_message() );
    }

    /**
     * Test that validateIdArray returns error for zero in array.
     */
    public function test_validate_id_array_returns_error_for_zero(): void {
        $result = InputValidator::validateIdArray( [ 1, 0, 3 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that validateIdArray accepts numeric strings.
     */
    public function test_validate_id_array_accepts_numeric_strings(): void {
        $result = InputValidator::validateIdArray( [ '1', '2', '3' ] );

        $this->assertNull( $result );
    }
}
