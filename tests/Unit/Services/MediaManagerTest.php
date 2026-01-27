<?php
/**
 * Unit tests for MediaManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\MediaManager;
use ReflectionClass;

/**
 * Tests for MediaManager service.
 */
final class MediaManagerTest extends TestCase {

    /**
     * Path to test fixtures.
     *
     * @var string
     */
    private string $fixtures_path;

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->fixtures_path = dirname( __DIR__, 2 ) . '/Fixtures/';
        reset_wp_filters();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        reset_wp_filters();
        parent::tearDown();
    }

    // =========================================================================
    // MIME Type Detection Tests
    // =========================================================================

    /**
     * Test that get_mime_type_for_file returns correct MIME type for common image formats.
     *
     * @dataProvider common_image_formats_provider
     */
    public function test_get_mime_type_for_file_returns_correct_mime_for_images(
        string $filename,
        string $expected_mime
    ): void {
        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ $filename, '/tmp/test' ] );

        $this->assertEquals( $expected_mime, $mime );
    }

    /**
     * Data provider for common image formats.
     *
     * @return array<array{string, string}>
     */
    public static function common_image_formats_provider(): array {
        return [
            'jpeg'   => [ 'photo.jpg', 'image/jpeg' ],
            'jpeg2'  => [ 'photo.jpeg', 'image/jpeg' ],
            'png'    => [ 'image.png', 'image/png' ],
            'gif'    => [ 'animation.gif', 'image/gif' ],
            'webp'   => [ 'modern.webp', 'image/webp' ],
            'bmp'    => [ 'bitmap.bmp', 'image/bmp' ],
        ];
    }

    /**
     * Test that get_mime_type_for_file returns correct MIME type for documents.
     *
     * @dataProvider document_formats_provider
     */
    public function test_get_mime_type_for_file_returns_correct_mime_for_documents(
        string $filename,
        string $expected_mime
    ): void {
        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ $filename, '/tmp/test' ] );

        $this->assertEquals( $expected_mime, $mime );
    }

    /**
     * Data provider for document formats.
     *
     * @return array<array{string, string}>
     */
    public static function document_formats_provider(): array {
        return [
            'pdf'  => [ 'document.pdf', 'application/pdf' ],
            'txt'  => [ 'readme.txt', 'text/plain' ],
            'csv'  => [ 'data.csv', 'text/csv' ],
            'zip'  => [ 'archive.zip', 'application/zip' ],
        ];
    }

    /**
     * Test that get_mime_type_for_file returns SVG MIME type when SVG is in allowed mimes.
     */
    public function test_get_mime_type_for_file_returns_svg_mime_when_allowed(): void {
        // Add SVG to allowed mimes (simulating an SVG plugin).
        add_filter(
            'upload_mimes',
            function ( array $mimes ): array {
                $mimes['svg'] = 'image/svg+xml';
                return $mimes;
            }
        );

        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ 'icon.svg', '/tmp/test' ] );

        $this->assertEquals( 'image/svg+xml', $mime );
    }

    /**
     * Test that get_mime_type_for_file returns SVG MIME type even when not in allowed mimes.
     * This is because we have special handling for SVG.
     */
    public function test_get_mime_type_for_file_returns_svg_mime_with_special_handling(): void {
        // Don't add SVG to allowed mimes - test the fallback.
        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ 'icon.svg', '/tmp/test' ] );

        $this->assertEquals( 'image/svg+xml', $mime );
    }

    /**
     * Test that get_mime_type_for_file returns SVGZ MIME type.
     */
    public function test_get_mime_type_for_file_returns_svgz_mime(): void {
        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ 'icon.svgz', '/tmp/test' ] );

        $this->assertEquals( 'image/svg+xml', $mime );
    }

    /**
     * Test that get_mime_type_for_file returns fallback for unknown extensions.
     */
    public function test_get_mime_type_for_file_returns_fallback_for_unknown(): void {
        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ 'file.xyz', '/tmp/test' ] );

        $this->assertEquals( 'application/octet-stream', $mime );
    }

    /**
     * Test that get_mime_type_for_file is case insensitive for extensions.
     */
    public function test_get_mime_type_for_file_is_case_insensitive(): void {
        $mime_lower = $this->invoke_private_method( 'get_mime_type_for_file', [ 'photo.jpg', '/tmp/test' ] );
        $mime_upper = $this->invoke_private_method( 'get_mime_type_for_file', [ 'photo.JPG', '/tmp/test' ] );
        $mime_mixed = $this->invoke_private_method( 'get_mime_type_for_file', [ 'photo.JpG', '/tmp/test' ] );

        $this->assertEquals( $mime_lower, $mime_upper );
        $this->assertEquals( $mime_lower, $mime_mixed );
    }

    /**
     * Test that upload_mimes filter is respected.
     */
    public function test_get_mime_type_for_file_respects_upload_mimes_filter(): void {
        // Add a custom MIME type.
        add_filter(
            'upload_mimes',
            function ( array $mimes ): array {
                $mimes['custom'] = 'application/x-custom';
                return $mimes;
            }
        );

        $mime = $this->invoke_private_method( 'get_mime_type_for_file', [ 'file.custom', '/tmp/test' ] );

        $this->assertEquals( 'application/x-custom', $mime );
    }

    // =========================================================================
    // Input Validation Tests
    // =========================================================================

    /**
     * Test that list_media returns proper structure.
     */
    public function test_list_media_returns_proper_structure(): void {
        // This test requires WordPress to be loaded, so we mark it as incomplete
        // for pure unit testing. In integration tests, we'd test the full flow.
        $this->markTestIncomplete(
            'list_media requires WordPress database - move to integration tests.'
        );
    }

    /**
     * Test that get_media returns error for missing ID.
     */
    public function test_get_media_returns_error_for_missing_id(): void {
        $result = MediaManager::get_media( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }

    /**
     * Test that upload_media returns error when no source provided.
     */
    public function test_upload_media_returns_error_without_source(): void {
        $result = MediaManager::upload_media( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_source', $result->get_error_code() );
    }

    /**
     * Test that upload_media returns error when only data provided without filename.
     */
    public function test_upload_media_returns_error_with_data_but_no_filename(): void {
        $result = MediaManager::upload_media( [ 'data' => base64_encode( 'test content' ) ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_source', $result->get_error_code() );
    }

    /**
     * Test that update_media returns error for missing ID.
     */
    public function test_update_media_returns_error_for_missing_id(): void {
        $result = MediaManager::update_media( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }

    /**
     * Test that delete_media returns error for missing ID.
     */
    public function test_delete_media_returns_error_for_missing_id(): void {
        $result = MediaManager::delete_media( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'missing_id', $result->get_error_code() );
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Invoke a private method on MediaManager for testing.
     *
     * @param string       $method_name Name of the method to invoke.
     * @param array<mixed> $args        Arguments to pass to the method.
     * @return mixed Method return value.
     */
    private function invoke_private_method( string $method_name, array $args = [] ): mixed {
        $reflection = new ReflectionClass( MediaManager::class );
        $method     = $reflection->getMethod( $method_name );
        $method->setAccessible( true );

        return $method->invokeArgs( null, $args );
    }
}
