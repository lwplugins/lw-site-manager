<?php
/**
 * Location of the plugin's own error log.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Handlers
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Handlers;

use LightweightPlugins\SiteManager\Handlers\ErrorLogFile;
use PHPUnit\Framework\TestCase;

final class ErrorLogFileTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_options();
        unset( $GLOBALS['wp_deleted_files'] );
    }

    public function test_previous_log_file_is_removed_once(): void {
        ErrorLogFile::removePreviousFile();
        ErrorLogFile::removePreviousFile();

        $this->assertSame( [ WP_CONTENT_DIR . '/wpsm-error.log' ], $GLOBALS['wp_deleted_files'] ?? [] );
    }

    public function test_creating_the_log_path_does_not_remove_anything(): void {
        ErrorLogFile::path();

        $this->assertArrayNotHasKey( 'wp_deleted_files', $GLOBALS );
    }

    public function test_existing_path_does_not_remove_anything(): void {
        ErrorLogFile::existingPath();

        $this->assertArrayNotHasKey( 'wp_deleted_files', $GLOBALS );
    }

    public function test_no_path_exists_before_anything_was_logged(): void {
        $this->assertNull( ErrorLogFile::existingPath() );
    }

    public function test_path_is_inside_the_log_directory(): void {
        $path = ErrorLogFile::path();

        $this->assertStringStartsWith( WP_CONTENT_DIR . '/wpsm-logs/error-', $path );
        $this->assertStringEndsWith( '.log', $path );
        $this->assertNotSame( WP_CONTENT_DIR . '/wpsm-error.log', $path );
    }

    public function test_path_carries_an_unguessable_key(): void {
        $key = basename( ErrorLogFile::path(), '.log' );

        $this->assertMatchesRegularExpression( '/^error-[A-Za-z0-9]{16,}$/', $key );
    }

    public function test_path_is_stable_once_created(): void {
        $first = ErrorLogFile::path();

        $this->assertSame( $first, ErrorLogFile::path() );
        $this->assertSame( $first, ErrorLogFile::existingPath() );
    }
}
