<?php
/**
 * Backup Manager Service - Handles site backups with background processing
 *
 * Supports large sites (50GB+) through chunked background processing.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

use ZipArchive;

class BackupManager extends AbstractService {

    private const BACKUP_DIR = 'wpsm-backups';
    private const CHUNK_SIZE = 500; // Files per chunk
    private const CRON_HOOK = 'wpsm_process_backup_chunk';

    /**
     * File extensions to exclude from backup
     */
    private const EXCLUDED_EXTENSIONS = [
        'wpress',      // All-in-One WP Migration
        'log',         // Log files
    ];

    /**
     * Directory names to exclude from backup
     */
    private const EXCLUDED_DIRS = [
        'wpsm-backups',           // Our own backups
        'cache',                  // Cache directories
    ];

    /**
     * Initialize backup system hooks
     */
    public static function init(): void {
        add_action( self::CRON_HOOK, [ self::class, 'process_backup_chunk' ] );
    }

    /**
     * Start a new backup job (returns immediately)
     */
    public static function create_backup( array $input ): array|\WP_Error {
        $include_database = $input['include_database'] ?? true;
        $include_files = $input['include_files'] ?? true;

        // Create backup directory
        $backup_dir = self::get_backup_dir();
        if ( ! wp_mkdir_p( $backup_dir ) ) {
            return self::errorResponse( 'backup_dir_failed', __( 'Could not create backup directory', 'lw-site-manager' ), 500 );
        }

        // Protect backup directory
        self::protect_backup_dir( $backup_dir );

        // Generate backup ID
        $backup_id = date( 'Y-m-d_H-i-s' ) . '_' . wp_generate_password( 8, false );

        // Build file list if including files
        $file_list = [];
        $total_size = 0;

        if ( $include_files ) {
            $scan_result = self::scan_wordpress_files( $backup_dir );
            $file_list = $scan_result['files'];
            $total_size = $scan_result['total_size'];
        }

        // Create job state
        $job = [
            'backup_id'        => $backup_id,
            'status'           => 'pending',
            'created_at'       => current_time( 'mysql' ),
            'started_at'       => null,
            'completed_at'     => null,
            'include_database' => $include_database,
            'include_files'    => $include_files,
            'file_list'        => $file_list,
            'total_files'      => count( $file_list ),
            'processed_files'  => 0,
            'total_size'       => $total_size,
            'current_chunk'    => 0,
            'chunks_total'     => (int) ceil( count( $file_list ) / self::CHUNK_SIZE ),
            'skipped_files'    => [],
            'errors'           => [],
            'backup_path'      => $backup_dir . '/' . $backup_id . '.zip',
            'manifest'         => [
                'backup_id'   => $backup_id,
                'created_at'  => current_time( 'mysql' ),
                'wp_version'  => get_bloginfo( 'version' ),
                'php_version' => PHP_VERSION,
                'site_url'    => get_site_url(),
                'abspath'     => ABSPATH,
                'includes'    => [
                    'database'  => $include_database,
                    'wordpress' => $include_files,
                ],
            ],
        ];

        // Save job state
        update_option( 'wpsm_backup_job_' . $backup_id, $job, false );

        // Schedule first chunk processing
        if ( ! wp_next_scheduled( self::CRON_HOOK, [ $backup_id ] ) ) {
            wp_schedule_single_event( time(), self::CRON_HOOK, [ $backup_id ] );
        }

        // Try to trigger immediately if possible
        spawn_cron();

        return [
            'success'       => true,
            'message'       => __( 'Backup job started', 'lw-site-manager' ),
            'backup_id'     => $backup_id,
            'status'        => 'pending',
            'total_files'   => count( $file_list ),
            'total_size'    => $total_size,
            'total_size_human' => size_format( $total_size ),
            'chunks_total'  => $job['chunks_total'],
        ];
    }

    /**
     * Get backup job status
     */
    public static function get_backup_status( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'backup_id', 'Backup ID is required' );
        if ( $error ) {
            return $error;
        }

        $backup_id = $input['backup_id'];
        $job = get_option( 'wpsm_backup_job_' . $backup_id );

        if ( ! $job ) {
            return self::errorResponse( 'job_not_found', __( 'Backup job not found', 'lw-site-manager' ), 404 );
        }

        $progress = 0;
        if ( $job['total_files'] > 0 ) {
            $progress = round( ( $job['processed_files'] / $job['total_files'] ) * 100, 1 );
        } elseif ( $job['status'] === 'completed' ) {
            $progress = 100;
        }

        $response = [
            'backup_id'       => $backup_id,
            'status'          => $job['status'],
            'progress'        => $progress,
            'total_files'     => $job['total_files'],
            'processed_files' => $job['processed_files'],
            'current_chunk'   => $job['current_chunk'],
            'chunks_total'    => $job['chunks_total'],
            'created_at'      => $job['created_at'],
            'started_at'      => $job['started_at'],
            'completed_at'    => $job['completed_at'],
            'errors'          => array_slice( $job['errors'], -10 ), // Last 10 errors
        ];

        // Add file info if completed
        if ( $job['status'] === 'completed' && file_exists( $job['backup_path'] ) ) {
            $file_size = filesize( $job['backup_path'] );
            $response['file_path'] = $job['backup_path'];
            $response['file_size'] = $file_size;
            $response['file_size_human'] = size_format( $file_size );
        }

        return $response;
    }

    /**
     * Process a backup chunk (called by WP Cron)
     */
    public static function process_backup_chunk( string $backup_id ): void {
        $job = get_option( 'wpsm_backup_job_' . $backup_id );

        if ( ! $job || $job['status'] === 'completed' || $job['status'] === 'failed' ) {
            return;
        }

        // Update status to processing
        if ( $job['status'] === 'pending' ) {
            $job['status'] = 'processing';
            $job['started_at'] = current_time( 'mysql' );
        }

        // Check if ZipArchive is available
        if ( ! class_exists( 'ZipArchive' ) ) {
            $job['status'] = 'failed';
            $job['errors'][] = 'ZipArchive extension is not available';
            update_option( 'wpsm_backup_job_' . $backup_id, $job, false );
            return;
        }

        $zip = new ZipArchive();
        $zip_flags = file_exists( $job['backup_path'] ) ? ZipArchive::CREATE : ZipArchive::CREATE | ZipArchive::OVERWRITE;

        if ( $zip->open( $job['backup_path'], $zip_flags ) !== true ) {
            $job['status'] = 'failed';
            $job['errors'][] = 'Could not open/create backup file';
            update_option( 'wpsm_backup_job_' . $backup_id, $job, false );
            return;
        }

        // First chunk: add database if requested
        if ( $job['current_chunk'] === 0 && $job['include_database'] ) {
            $db_backup = self::backup_database();
            if ( $db_backup['success'] ) {
                $zip->addFromString( 'database.sql', $db_backup['sql'] );
            } else {
                $job['errors'][] = 'Database backup failed';
            }
        }

        // Process file chunk
        $start_index = $job['current_chunk'] * self::CHUNK_SIZE;
        $chunk_files = array_slice( $job['file_list'], $start_index, self::CHUNK_SIZE );

        foreach ( $chunk_files as $file_info ) {
            $real_path = $file_info['path'];
            $zip_path = 'wordpress/' . $file_info['relative'];

            if ( ! file_exists( $real_path ) ) {
                $job['skipped_files'][] = $file_info['relative'] . ' (not found)';
                continue;
            }

            if ( ! is_readable( $real_path ) ) {
                $job['skipped_files'][] = $file_info['relative'] . ' (not readable)';
                continue;
            }

            try {
                $zip->addFile( $real_path, $zip_path );
                $job['processed_files']++;
            } catch ( \Exception $e ) {
                $job['errors'][] = $file_info['relative'] . ': ' . $e->getMessage();
            }
        }

        $job['current_chunk']++;

        // Check if all chunks are processed
        if ( $job['current_chunk'] >= $job['chunks_total'] ) {
            // Add manifest
            $job['manifest']['completed_at'] = current_time( 'mysql' );
            $job['manifest']['stats'] = [
                'files_count'   => $job['processed_files'],
                'skipped_count' => count( $job['skipped_files'] ),
                'errors_count'  => count( $job['errors'] ),
            ];
            $zip->addFromString( 'manifest.json', json_encode( $job['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

            $zip->close();

            // Mark as completed
            $job['status'] = 'completed';
            $job['completed_at'] = current_time( 'mysql' );

            // Save to backups list
            $file_size = file_exists( $job['backup_path'] ) ? filesize( $job['backup_path'] ) : 0;
            self::save_backup_meta( $backup_id, [
                'file_path' => $job['backup_path'],
                'file_size' => $file_size,
                'manifest'  => $job['manifest'],
            ]);

            // Clear file list from job to save memory
            $job['file_list'] = [];

            update_option( 'wpsm_backup_job_' . $backup_id, $job, false );
        } else {
            $zip->close();
            update_option( 'wpsm_backup_job_' . $backup_id, $job, false );

            // Schedule next chunk
            wp_schedule_single_event( time() + 1, self::CRON_HOOK, [ $backup_id ] );
            spawn_cron();
        }
    }

    /**
     * Cancel a running backup job
     */
    public static function cancel_backup( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'backup_id', 'Backup ID is required' );
        if ( $error ) {
            return $error;
        }

        $backup_id = $input['backup_id'];
        $job = get_option( 'wpsm_backup_job_' . $backup_id );

        if ( ! $job ) {
            return self::errorResponse( 'job_not_found', __( 'Backup job not found', 'lw-site-manager' ), 404 );
        }

        if ( $job['status'] === 'completed' ) {
            return self::errorResponse( 'already_completed', __( 'Backup is already completed', 'lw-site-manager' ), 400 );
        }

        // Unschedule cron
        wp_clear_scheduled_hook( self::CRON_HOOK, [ $backup_id ] );

        // Delete partial backup file
        if ( file_exists( $job['backup_path'] ) ) {
            unlink( $job['backup_path'] );
        }

        // Update job status
        $job['status'] = 'cancelled';
        $job['completed_at'] = current_time( 'mysql' );
        update_option( 'wpsm_backup_job_' . $backup_id, $job, false );

        return self::successResponse(
            [ 'backup_id' => $backup_id ],
            __( 'Backup cancelled', 'lw-site-manager' )
        );
    }

    /**
     * List all backups
     */
    public static function list_backups( array $input ): array {
        $limit = $input['limit'] ?? 20;
        $backups = get_option( 'wpsm_backups', [] );

        // Sort by date descending
        usort( $backups, fn( $a, $b ) => strtotime( $b['manifest']['created_at'] ?? 0 ) - strtotime( $a['manifest']['created_at'] ?? 0 ) );

        // Limit results
        $backups = array_slice( $backups, 0, $limit );

        // Add file exists check
        foreach ( $backups as &$backup ) {
            $backup['file_exists'] = file_exists( $backup['file_path'] );
            $backup['file_size_human'] = size_format( $backup['file_size'] );
        }

        return [
            'backups' => $backups,
            'total'   => count( $backups ),
        ];
    }

    /**
     * Restore from backup
     */
    public static function restore_backup( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'backup_id', 'Backup ID is required' );
        if ( $error ) {
            return $error;
        }

        $backup_id = $input['backup_id'];
        $restore_database = $input['restore_database'] ?? true;
        $restore_files = $input['restore_files'] ?? true;

        $backups = get_option( 'wpsm_backups', [] );
        $backup = null;

        foreach ( $backups as $b ) {
            if ( ( $b['manifest']['backup_id'] ?? '' ) === $backup_id ) {
                $backup = $b;
                break;
            }
        }

        if ( ! $backup ) {
            return self::errorResponse( 'backup_not_found', __( 'Backup not found', 'lw-site-manager' ), 404 );
        }

        if ( ! file_exists( $backup['file_path'] ) ) {
            return self::errorResponse( 'backup_file_missing', __( 'Backup file not found on disk', 'lw-site-manager' ), 404 );
        }

        $zip = new ZipArchive();
        if ( $zip->open( $backup['file_path'] ) !== true ) {
            return self::errorResponse( 'backup_open_failed', __( 'Could not open backup file', 'lw-site-manager' ), 500 );
        }

        $restored = [];

        // Restore database
        if ( $restore_database && ! empty( $backup['manifest']['includes']['database'] ) ) {
            $sql = $zip->getFromName( 'database.sql' );
            if ( $sql ) {
                $result = self::restore_database( $sql );
                $restored['database'] = $result['success'];
            }
        }

        // Restore WordPress files
        if ( $restore_files && ! empty( $backup['manifest']['includes']['wordpress'] ) ) {
            self::extract_directory_from_zip( $zip, 'wordpress', ABSPATH );
            $restored['wordpress'] = true;
        }

        // Legacy support
        if ( $restore_files ) {
            if ( ! empty( $backup['manifest']['includes']['uploads'] ) ) {
                $upload_dir = wp_upload_dir();
                self::extract_directory_from_zip( $zip, 'uploads', $upload_dir['basedir'] );
                $restored['uploads'] = true;
            }
            if ( ! empty( $backup['manifest']['includes']['plugins'] ) ) {
                self::extract_directory_from_zip( $zip, 'plugins', WP_PLUGIN_DIR );
                $restored['plugins'] = true;
            }
            if ( ! empty( $backup['manifest']['includes']['themes'] ) ) {
                self::extract_directory_from_zip( $zip, 'themes', get_theme_root() );
                $restored['themes'] = true;
            }
        }

        $zip->close();

        return self::successResponse(
            [ 'restored' => $restored ],
            __( 'Backup restored successfully', 'lw-site-manager' )
        );
    }

    /**
     * Delete a backup
     */
    public static function delete_backup( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'backup_id', 'Backup ID is required' );
        if ( $error ) {
            return $error;
        }

        $backup_id = $input['backup_id'];
        $backups = get_option( 'wpsm_backups', [] );
        $found = false;

        foreach ( $backups as $key => $backup ) {
            if ( ( $backup['manifest']['backup_id'] ?? '' ) === $backup_id ) {
                if ( file_exists( $backup['file_path'] ) ) {
                    unlink( $backup['file_path'] );
                }
                unset( $backups[ $key ] );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return self::errorResponse( 'backup_not_found', __( 'Backup not found', 'lw-site-manager' ), 404 );
        }

        update_option( 'wpsm_backups', array_values( $backups ) );

        // Also delete job state if exists
        delete_option( 'wpsm_backup_job_' . $backup_id );

        return self::successResponse(
            [ 'deleted_id' => $backup_id ],
            __( 'Backup deleted successfully', 'lw-site-manager' )
        );
    }

    /**
     * Scan WordPress files and build file list
     */
    private static function scan_wordpress_files( string $backup_dir ): array {
        $wp_root = ABSPATH;
        $files = [];
        $total_size = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $wp_root, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $item ) {
            if ( $item->isDir() ) {
                continue;
            }

            $real_path = $item->getPathname();
            $relative_path = substr( $real_path, strlen( $wp_root ) );

            // Skip our backup directory
            if ( strpos( $real_path, $backup_dir ) === 0 ) {
                continue;
            }

            // Skip excluded directories
            if ( self::should_skip_path( $relative_path ) ) {
                continue;
            }

            // Skip excluded extensions
            if ( self::should_skip_extension( $item->getFilename() ) ) {
                continue;
            }

            $file_size = $item->getSize();

            $files[] = [
                'path'     => $real_path,
                'relative' => $relative_path,
                'size'     => $file_size,
            ];

            $total_size += $file_size;
        }

        return [
            'files'      => $files,
            'total_size' => $total_size,
        ];
    }

    /**
     * Check if path should be skipped
     */
    private static function should_skip_path( string $path ): bool {
        $path_parts = explode( DIRECTORY_SEPARATOR, $path );

        foreach ( $path_parts as $part ) {
            if ( in_array( strtolower( $part ), self::EXCLUDED_DIRS, true ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file should be skipped based on extension
     */
    private static function should_skip_extension( string $filename ): bool {
        $filename_lower = strtolower( $filename );

        foreach ( self::EXCLUDED_EXTENSIONS as $ext ) {
            if ( str_ends_with( $filename_lower, '.' . $ext ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get backup directory path
     */
    private static function get_backup_dir(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/' . self::BACKUP_DIR;
    }

    /**
     * Protect backup directory
     */
    private static function protect_backup_dir( string $dir ): void {
        $htaccess = $dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "deny from all\n" );
        }

        $index = $dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden.\n" );
        }
    }

    /**
     * Backup database to SQL string
     */
    private static function backup_database(): array {
        global $wpdb;

        $tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
        $sql = "-- WP Site Manager Database Backup\n";
        $sql .= "-- Generated: " . current_time( 'mysql' ) . "\n";
        $sql .= "-- WordPress Version: " . get_bloginfo( 'version' ) . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ( $tables as $table ) {
            $table_name = $table[0];

            if ( strpos( $table_name, $wpdb->prefix ) !== 0 ) {
                continue;
            }

            $create = $wpdb->get_row( "SHOW CREATE TABLE `{$table_name}`", ARRAY_N );
            $sql .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
            $sql .= $create[1] . ";\n\n";

            $rows = $wpdb->get_results( "SELECT * FROM `{$table_name}`", ARRAY_A );
            if ( ! empty( $rows ) ) {
                $columns = array_keys( $rows[0] );
                $columns_str = '`' . implode( '`, `', $columns ) . '`';

                foreach ( $rows as $row ) {
                    $values = array_map( function( $v ) use ( $wpdb ) {
                        if ( $v === null ) {
                            return 'NULL';
                        }
                        return "'" . $wpdb->_real_escape( $v ) . "'";
                    }, array_values( $row ) );

                    $sql .= "INSERT INTO `{$table_name}` ({$columns_str}) VALUES (" . implode( ', ', $values ) . ");\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return [
            'success' => true,
            'sql'     => $sql,
        ];
    }

    /**
     * Restore database from SQL
     */
    private static function restore_database( string $sql ): array {
        global $wpdb;

        $queries = preg_split( '/;\s*\n/', $sql );
        $errors = [];

        foreach ( $queries as $query ) {
            $query = trim( $query );
            if ( empty( $query ) || strpos( $query, '--' ) === 0 ) {
                continue;
            }

            $result = $wpdb->query( $query );
            if ( $result === false ) {
                $errors[] = $wpdb->last_error;
            }
        }

        return [
            'success' => empty( $errors ),
            'errors'  => $errors,
        ];
    }

    /**
     * Extract directory from zip
     */
    private static function extract_directory_from_zip( ZipArchive $zip, string $prefix, string $destination ): void {
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex( $i );

            if ( strpos( $name, $prefix . '/' ) !== 0 ) {
                continue;
            }

            $relative_path = substr( $name, strlen( $prefix ) + 1 );
            if ( empty( $relative_path ) ) {
                continue;
            }

            $target_path = $destination . '/' . $relative_path;

            if ( substr( $name, -1 ) === '/' ) {
                wp_mkdir_p( $target_path );
            } else {
                wp_mkdir_p( dirname( $target_path ) );
                $content = $zip->getFromIndex( $i );
                file_put_contents( $target_path, $content );
            }
        }
    }

    /**
     * Save backup metadata
     */
    private static function save_backup_meta( string $backup_id, array $meta ): void {
        $backups = get_option( 'wpsm_backups', [] );
        $backups[] = $meta;
        update_option( 'wpsm_backups', $backups );
    }
}
