<?php
/**
 * Unit tests for BackupManager service.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Services\BackupManager;
use ReflectionClass;

/**
 * Tests for BackupManager service.
 */
final class BackupManagerTest extends TestCase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        reset_wp_filters();
        reset_wp_options();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        reset_wp_filters();
        reset_wp_options();
        parent::tearDown();
    }

    // =========================================================================
    // Input Validation Tests
    // =========================================================================

    /**
     * Test that get_backup_status returns error for missing backup_id.
     */
    public function test_get_backup_status_returns_error_for_missing_id(): void {
        $result = BackupManager::get_backup_status( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that get_backup_status returns error for non-existent backup.
     */
    public function test_get_backup_status_returns_error_for_nonexistent_backup(): void {
        $result = BackupManager::get_backup_status( [ 'backup_id' => 'nonexistent-id' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'job_not_found', $result->get_error_code() );
    }

    /**
     * Test that cancel_backup returns error for missing backup_id.
     */
    public function test_cancel_backup_returns_error_for_missing_id(): void {
        $result = BackupManager::cancel_backup( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that cancel_backup returns error for non-existent backup.
     */
    public function test_cancel_backup_returns_error_for_nonexistent_backup(): void {
        $result = BackupManager::cancel_backup( [ 'backup_id' => 'nonexistent-id' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'job_not_found', $result->get_error_code() );
    }

    /**
     * Test that restore_backup returns error for missing backup_id.
     */
    public function test_restore_backup_returns_error_for_missing_id(): void {
        $result = BackupManager::restore_backup( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that restore_backup returns error for non-existent backup.
     */
    public function test_restore_backup_returns_error_for_nonexistent_backup(): void {
        $result = BackupManager::restore_backup( [ 'backup_id' => 'nonexistent-id' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'backup_not_found', $result->get_error_code() );
    }

    /**
     * Test that delete_backup returns error for missing backup_id.
     */
    public function test_delete_backup_returns_error_for_missing_id(): void {
        $result = BackupManager::delete_backup( [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Test that delete_backup returns error for non-existent backup.
     */
    public function test_delete_backup_returns_error_for_nonexistent_backup(): void {
        $result = BackupManager::delete_backup( [ 'backup_id' => 'nonexistent-id' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'backup_not_found', $result->get_error_code() );
    }

    // =========================================================================
    // Path Exclusion Tests
    // =========================================================================

    /**
     * Test that should_skip_path excludes backup directory.
     *
     * @dataProvider excluded_paths_provider
     */
    public function test_should_skip_path_excludes_known_directories(
        string $path,
        bool $should_skip
    ): void {
        $result = $this->invoke_private_method( 'should_skip_path', [ $path ] );

        $this->assertEquals( $should_skip, $result );
    }

    /**
     * Data provider for excluded paths.
     *
     * @return array<array{string, bool}>
     */
    public static function excluded_paths_provider(): array {
        return [
            'backup directory'        => [ 'wp-content/uploads/wpsm-backups/file.zip', true ],
            'cache directory'         => [ 'wp-content/cache/file.html', true ],
            'nested cache'            => [ 'wp-content/plugins/someplugin/cache/data.json', true ],
            'normal plugin file'      => [ 'wp-content/plugins/my-plugin/plugin.php', false ],
            'normal theme file'       => [ 'wp-content/themes/my-theme/style.css', false ],
            'wp-includes file'        => [ 'wp-includes/version.php', false ],
            'upload file'             => [ 'wp-content/uploads/2024/01/image.jpg', false ],
            'case insensitive cache'  => [ 'wp-content/CACHE/file.html', true ],
        ];
    }

    // =========================================================================
    // Extension Exclusion Tests
    // =========================================================================

    /**
     * Test that should_skip_extension excludes known extensions.
     *
     * @dataProvider excluded_extensions_provider
     */
    public function test_should_skip_extension_excludes_known_types(
        string $filename,
        bool $should_skip
    ): void {
        $result = $this->invoke_private_method( 'should_skip_extension', [ $filename ] );

        $this->assertEquals( $should_skip, $result );
    }

    /**
     * Data provider for excluded extensions.
     *
     * @return array<array{string, bool}>
     */
    public static function excluded_extensions_provider(): array {
        return [
            'wpress file'           => [ 'backup.wpress', true ],
            'log file'              => [ 'debug.log', true ],
            'uppercase LOG'         => [ 'ERROR.LOG', true ],
            'php file'              => [ 'plugin.php', false ],
            'css file'              => [ 'style.css', false ],
            'js file'               => [ 'script.js', false ],
            'image file'            => [ 'photo.jpg', false ],
            'zip file'              => [ 'archive.zip', false ],
            'sql file'              => [ 'database.sql', false ],
            'json file'             => [ 'config.json', false ],
        ];
    }

    // =========================================================================
    // List Backups Tests
    // =========================================================================

    /**
     * Test that list_backups returns empty array when no backups exist.
     */
    public function test_list_backups_returns_empty_when_no_backups(): void {
        $result = BackupManager::list_backups( [] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'backups', $result );
        $this->assertArrayHasKey( 'total', $result );
        $this->assertEmpty( $result['backups'] );
        $this->assertEquals( 0, $result['total'] );
    }

    /**
     * Test that list_backups respects limit parameter.
     */
    public function test_list_backups_respects_limit(): void {
        // Set up mock backups.
        $backups = [];
        for ( $i = 1; $i <= 30; $i++ ) {
            $backups[] = [
                'file_path' => '/tmp/backup-' . $i . '.zip',
                'file_size' => 1024 * $i,
                'manifest'  => [
                    'backup_id'  => 'backup-' . $i,
                    'created_at' => date( 'Y-m-d H:i:s', strtotime( "-{$i} hours" ) ),
                ],
            ];
        }
        update_option( 'wpsm_backups', $backups );

        // Test with limit.
        $result = BackupManager::list_backups( [ 'limit' => 10 ] );

        $this->assertCount( 10, $result['backups'] );
    }

    /**
     * Test that list_backups sorts by date descending.
     */
    public function test_list_backups_sorts_by_date_descending(): void {
        // Set up mock backups with different dates.
        $backups = [
            [
                'file_path' => '/tmp/old-backup.zip',
                'file_size' => 1024,
                'manifest'  => [
                    'backup_id'  => 'old-backup',
                    'created_at' => '2024-01-01 00:00:00',
                ],
            ],
            [
                'file_path' => '/tmp/new-backup.zip',
                'file_size' => 2048,
                'manifest'  => [
                    'backup_id'  => 'new-backup',
                    'created_at' => '2024-12-31 23:59:59',
                ],
            ],
            [
                'file_path' => '/tmp/mid-backup.zip',
                'file_size' => 1536,
                'manifest'  => [
                    'backup_id'  => 'mid-backup',
                    'created_at' => '2024-06-15 12:00:00',
                ],
            ],
        ];
        update_option( 'wpsm_backups', $backups );

        $result = BackupManager::list_backups( [] );

        // Newest should be first.
        $this->assertEquals( 'new-backup', $result['backups'][0]['manifest']['backup_id'] );
        $this->assertEquals( 'mid-backup', $result['backups'][1]['manifest']['backup_id'] );
        $this->assertEquals( 'old-backup', $result['backups'][2]['manifest']['backup_id'] );
    }

    /**
     * Test that list_backups adds file_exists check.
     */
    public function test_list_backups_adds_file_exists_check(): void {
        $backups = [
            [
                'file_path' => '/tmp/nonexistent-backup.zip',
                'file_size' => 1024,
                'manifest'  => [
                    'backup_id'  => 'test-backup',
                    'created_at' => date( 'Y-m-d H:i:s' ),
                ],
            ],
        ];
        update_option( 'wpsm_backups', $backups );

        $result = BackupManager::list_backups( [] );

        $this->assertArrayHasKey( 'file_exists', $result['backups'][0] );
        $this->assertFalse( $result['backups'][0]['file_exists'] );
    }

    /**
     * Test that list_backups adds human readable file size.
     */
    public function test_list_backups_adds_human_readable_size(): void {
        $backups = [
            [
                'file_path' => '/tmp/test-backup.zip',
                'file_size' => 1048576, // 1 MB
                'manifest'  => [
                    'backup_id'  => 'test-backup',
                    'created_at' => date( 'Y-m-d H:i:s' ),
                ],
            ],
        ];
        update_option( 'wpsm_backups', $backups );

        $result = BackupManager::list_backups( [] );

        $this->assertArrayHasKey( 'file_size_human', $result['backups'][0] );
        $this->assertStringContainsString( 'MB', $result['backups'][0]['file_size_human'] );
    }

    // =========================================================================
    // Backup Status Tests
    // =========================================================================

    /**
     * Test that get_backup_status returns correct progress for completed backup.
     */
    public function test_get_backup_status_returns_100_progress_for_completed(): void {
        $job = [
            'backup_id'       => 'test-backup',
            'status'          => 'completed',
            'created_at'      => date( 'Y-m-d H:i:s' ),
            'started_at'      => date( 'Y-m-d H:i:s' ),
            'completed_at'    => date( 'Y-m-d H:i:s' ),
            'total_files'     => 0,
            'processed_files' => 0,
            'current_chunk'   => 0,
            'chunks_total'    => 0,
            'errors'          => [],
            'backup_path'     => '/tmp/test-backup.zip',
        ];
        update_option( 'wpsm_backup_job_test-backup', $job );

        $result = BackupManager::get_backup_status( [ 'backup_id' => 'test-backup' ] );

        $this->assertIsArray( $result );
        $this->assertEquals( 100, $result['progress'] );
    }

    /**
     * Test that get_backup_status calculates progress correctly.
     */
    public function test_get_backup_status_calculates_progress(): void {
        $job = [
            'backup_id'       => 'test-backup',
            'status'          => 'processing',
            'created_at'      => date( 'Y-m-d H:i:s' ),
            'started_at'      => date( 'Y-m-d H:i:s' ),
            'completed_at'    => null,
            'total_files'     => 100,
            'processed_files' => 50,
            'current_chunk'   => 1,
            'chunks_total'    => 2,
            'errors'          => [],
            'backup_path'     => '/tmp/test-backup.zip',
        ];
        update_option( 'wpsm_backup_job_test-backup', $job );

        $result = BackupManager::get_backup_status( [ 'backup_id' => 'test-backup' ] );

        $this->assertIsArray( $result );
        $this->assertEquals( 50, $result['progress'] );
    }

    /**
     * Test that get_backup_status returns last 10 errors only.
     */
    public function test_get_backup_status_returns_last_10_errors(): void {
        $errors = [];
        for ( $i = 1; $i <= 20; $i++ ) {
            $errors[] = 'Error ' . $i;
        }

        $job = [
            'backup_id'       => 'test-backup',
            'status'          => 'processing',
            'created_at'      => date( 'Y-m-d H:i:s' ),
            'started_at'      => date( 'Y-m-d H:i:s' ),
            'completed_at'    => null,
            'total_files'     => 100,
            'processed_files' => 50,
            'current_chunk'   => 1,
            'chunks_total'    => 2,
            'errors'          => $errors,
            'backup_path'     => '/tmp/test-backup.zip',
        ];
        update_option( 'wpsm_backup_job_test-backup', $job );

        $result = BackupManager::get_backup_status( [ 'backup_id' => 'test-backup' ] );

        $this->assertCount( 10, $result['errors'] );
        $this->assertEquals( 'Error 11', $result['errors'][0] );
        $this->assertEquals( 'Error 20', $result['errors'][9] );
    }

    // =========================================================================
    // Cancel Backup Tests
    // =========================================================================

    /**
     * Test that cancel_backup returns error for already completed backup.
     */
    public function test_cancel_backup_returns_error_for_completed_backup(): void {
        $job = [
            'backup_id'    => 'test-backup',
            'status'       => 'completed',
            'created_at'   => date( 'Y-m-d H:i:s' ),
            'started_at'   => date( 'Y-m-d H:i:s' ),
            'completed_at' => date( 'Y-m-d H:i:s' ),
            'backup_path'  => '/tmp/test-backup.zip',
        ];
        update_option( 'wpsm_backup_job_test-backup', $job );

        $result = BackupManager::cancel_backup( [ 'backup_id' => 'test-backup' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertEquals( 'already_completed', $result->get_error_code() );
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Invoke a private method on BackupManager for testing.
     *
     * @param string       $method_name Name of the method to invoke.
     * @param array<mixed> $args        Arguments to pass to the method.
     * @return mixed Method return value.
     */
    private function invoke_private_method( string $method_name, array $args = [] ): mixed {
        $reflection = new ReflectionClass( BackupManager::class );
        $method     = $reflection->getMethod( $method_name );
        $method->setAccessible( true );

        return $method->invokeArgs( null, $args );
    }
}
