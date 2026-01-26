<?php
/**
 * Health Check Service - Site diagnostics and monitoring
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

use LightweightPlugins\SiteManager\Handlers\ErrorHandler;

class HealthCheck extends AbstractService {

    /**
     * Run comprehensive health check
     */
    public static function run_check( array $input = [] ): array {
        // Load required update functions
        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $issues = [];
        $score = 100;

        // PHP Version check
        $php_version = PHP_VERSION;
        $php_min = '8.0';
        if ( version_compare( $php_version, $php_min, '<' ) ) {
            $issues[] = [
                'type'     => 'critical',
                'message'  => sprintf( 'PHP version %s is below recommended %s', $php_version, $php_min ),
                'category' => 'environment',
            ];
            $score -= 20;
        }

        // WordPress Version check
        $wp_version = get_bloginfo( 'version' );
        $core_updates = get_core_updates();
        if ( ! empty( $core_updates[0] ) && $core_updates[0]->response === 'upgrade' ) {
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( 'WordPress update available: %s → %s', $wp_version, $core_updates[0]->version ),
                'category' => 'updates',
            ];
            $score -= 10;
        }

        // Plugin updates
        $plugin_updates = get_site_transient( 'update_plugins' );
        if ( ! empty( $plugin_updates->response ) ) {
            $count = count( $plugin_updates->response );
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( '%d plugin update(s) available', $count ),
                'category' => 'updates',
            ];
            $score -= min( $count * 2, 15 );
        }

        // Theme updates
        $theme_updates = get_site_transient( 'update_themes' );
        if ( ! empty( $theme_updates->response ) ) {
            $count = count( $theme_updates->response );
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( '%d theme update(s) available', $count ),
                'category' => 'updates',
            ];
            $score -= min( $count * 2, 10 );
        }

        // Memory check
        $memory_limit = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
        $memory_usage = memory_get_usage( true );
        $memory_percent = ( $memory_usage / $memory_limit ) * 100;

        if ( $memory_percent > 80 ) {
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( 'Memory usage is high: %d%%', round( $memory_percent ) ),
                'category' => 'performance',
            ];
            $score -= 10;
        }

        // Disk space check
        $disk_usage = self::get_disk_usage();
        if ( $disk_usage['percent_used'] > 90 ) {
            $issues[] = [
                'type'     => 'critical',
                'message'  => sprintf( 'Disk space critically low: %d%% used', $disk_usage['percent_used'] ),
                'category' => 'storage',
            ];
            $score -= 20;
        } elseif ( $disk_usage['percent_used'] > 80 ) {
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( 'Disk space running low: %d%% used', $disk_usage['percent_used'] ),
                'category' => 'storage',
            ];
            $score -= 10;
        }

        // SSL check
        if ( ! is_ssl() ) {
            $issues[] = [
                'type'     => 'warning',
                'message'  => 'Site is not using HTTPS',
                'category' => 'security',
            ];
            $score -= 15;
        }

        // Debug mode check
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $issues[] = [
                'type'     => 'info',
                'message'  => 'WP_DEBUG is enabled (should be disabled in production)',
                'category' => 'security',
            ];
            $score -= 5;
        }

        // Check for recent PHP errors
        $error_handler = ErrorHandler::instance();
        $recent_errors = $error_handler->read_error_log( 10 );
        if ( ! empty( $recent_errors ) ) {
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( '%d recent PHP error(s) in log', count( $recent_errors ) ),
                'category' => 'errors',
            ];
            $score -= 5;
        }

        // Database check
        $db_issues = self::check_database();
        if ( ! empty( $db_issues ) ) {
            $issues = array_merge( $issues, $db_issues );
            $score -= count( $db_issues ) * 5;
        }

        // Determine status
        $status = 'good';
        if ( $score < 50 ) {
            $status = 'critical';
        } elseif ( $score < 70 ) {
            $status = 'should_be_improved';
        } elseif ( $score < 90 ) {
            $status = 'recommended';
        }

        return [
            'status'      => $status,
            'score'       => max( 0, $score ),
            'issues'      => $issues,
            'php_version' => $php_version,
            'wp_version'  => $wp_version,
            'disk_usage'  => $disk_usage,
            'memory'      => [
                'limit'   => size_format( $memory_limit ),
                'usage'   => size_format( $memory_usage ),
                'percent' => round( $memory_percent, 2 ),
            ],
            'server'      => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'hostname' => gethostname(),
            ],
            'paths'       => [
                'wordpress'  => ABSPATH,
                'wp_content' => WP_CONTENT_DIR,
                'uploads'    => wp_upload_dir()['basedir'],
                'plugins'    => WP_PLUGIN_DIR,
                'themes'     => get_theme_root(),
            ],
        ];
    }

    /**
     * Get PHP error log contents
     */
    public static function get_error_log( array $input ): array {
        $lines = (int) ( $input['lines'] ?? 100 );
        $filter = $input['filter'] ?? '';

        $errors = [];

        // Check WP debug log
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $log_file = WP_DEBUG_LOG === true
                ? WP_CONTENT_DIR . '/debug.log'
                : WP_DEBUG_LOG;

            if ( file_exists( $log_file ) ) {
                $errors = array_merge( $errors, self::read_log_file( $log_file, $lines ) );
            }
        }

        // Check PHP error log
        $php_log = ini_get( 'error_log' );
        if ( $php_log && file_exists( $php_log ) ) {
            $errors = array_merge( $errors, self::read_log_file( $php_log, $lines ) );
        }

        // Check our custom log
        $error_handler = ErrorHandler::instance();
        $custom_errors = $error_handler->read_error_log( $lines );
        $errors = array_merge( $errors, $custom_errors );

        // Remove duplicates
        $errors = array_unique( $errors );

        // Apply filter
        if ( ! empty( $filter ) ) {
            $errors = array_filter( $errors, fn( $e ) => stripos( $e, $filter ) !== false );
        }

        // Sort by most recent first and limit
        $errors = array_reverse( $errors );
        $errors = array_slice( $errors, 0, $lines );

        return [
            'errors' => array_values( $errors ),
            'total'  => count( $errors ),
        ];
    }

    /**
     * Get disk usage information
     */
    private static function get_disk_usage(): array {
        $path = ABSPATH;

        $total = disk_total_space( $path );
        $free = disk_free_space( $path );
        $used = $total - $free;

        // WordPress specific sizes
        $wp_size = self::get_directory_size( ABSPATH );
        $uploads_size = self::get_directory_size( wp_upload_dir()['basedir'] );
        $plugins_size = self::get_directory_size( WP_PLUGIN_DIR );
        $themes_size = self::get_directory_size( get_theme_root() );

        return [
            'total'        => $total,
            'total_human'  => size_format( $total ),
            'free'         => $free,
            'free_human'   => size_format( $free ),
            'used'         => $used,
            'used_human'   => size_format( $used ),
            'percent_used' => round( ( $used / $total ) * 100, 2 ),
            'wordpress'    => [
                'total'   => size_format( $wp_size ),
                'uploads' => size_format( $uploads_size ),
                'plugins' => size_format( $plugins_size ),
                'themes'  => size_format( $themes_size ),
            ],
        ];
    }

    /**
     * Get directory size
     */
    private static function get_directory_size( string $path ): int {
        $size = 0;

        if ( ! is_dir( $path ) ) {
            return $size;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $path, \RecursiveDirectoryIterator::SKIP_DOTS ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ( $iterator as $file ) {
            if ( $file->isFile() ) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Read log file (tail)
     */
    private static function read_log_file( string $file, int $lines ): array {
        if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
            return [];
        }

        $file_size = filesize( $file );
        if ( $file_size === 0 ) {
            return [];
        }

        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            return [];
        }

        $result = [];

        // Go to end and read backwards
        $pos = $file_size - 1;
        $line_count = 0;
        $buffer = '';

        while ( $pos >= 0 && $line_count < $lines ) {
            fseek( $handle, $pos );
            $char = fgetc( $handle );

            if ( $char === "\n" ) {
                if ( ! empty( trim( $buffer ) ) ) {
                    $result[] = trim( strrev( $buffer ) );
                    $line_count++;
                }
                $buffer = '';
            } else {
                $buffer .= $char;
            }

            $pos--;
        }

        // Don't forget the last line
        if ( ! empty( trim( $buffer ) ) && $line_count < $lines ) {
            $result[] = trim( strrev( $buffer ) );
        }

        fclose( $handle );

        return array_reverse( $result );
    }

    /**
     * Check database for issues
     */
    private static function check_database(): array {
        global $wpdb;

        $issues = [];

        // Check for orphaned postmeta
        $orphaned = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL"
        );

        if ( $orphaned > 100 ) {
            $issues[] = [
                'type'     => 'info',
                'message'  => sprintf( '%d orphaned postmeta entries', $orphaned ),
                'category' => 'database',
            ];
        }

        // Check for excessive revisions
        $revisions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );

        if ( $revisions > 500 ) {
            $issues[] = [
                'type'     => 'info',
                'message'  => sprintf( '%d post revisions (consider cleanup)', $revisions ),
                'category' => 'database',
            ];
        }

        // Check for expired transients
        $transients = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_timeout_%'
             AND option_value < UNIX_TIMESTAMP()"
        );

        if ( $transients > 50 ) {
            $issues[] = [
                'type'     => 'info',
                'message'  => sprintf( '%d expired transients', $transients ),
                'category' => 'database',
            ];
        }

        // Check autoload size
        $autoload_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'"
        );

        if ( $autoload_size > 1000000 ) { // 1MB
            $issues[] = [
                'type'     => 'warning',
                'message'  => sprintf( 'Autoload options size is large: %s', size_format( $autoload_size ) ),
                'category' => 'performance',
            ];
        }

        return $issues;
    }
}
