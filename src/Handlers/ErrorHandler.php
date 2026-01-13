<?php
/**
 * Error Handler - Captures PHP errors during operations
 */

declare(strict_types=1);

namespace WPSiteManager\Handlers;

class ErrorHandler {

    private static ?ErrorHandler $instance = null;

    private bool $is_monitoring = false;
    private array $captured_errors = [];
    private ?string $previous_error_handler = null;
    private string $log_file;

    public static function instance(): ErrorHandler {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->log_file = WP_CONTENT_DIR . '/wpsm-error.log';
    }

    /**
     * Initialize the error handler
     */
    public function init(): void {
        // Register shutdown function to catch fatal errors
        register_shutdown_function( [ $this, 'handle_shutdown' ] );
    }

    /**
     * Start monitoring for PHP errors
     */
    public function start_monitoring(): void {
        $this->is_monitoring = true;
        $this->captured_errors = [];

        // Capture the error log before operation
        $this->capture_error_log_snapshot();

        // Set custom error handler
        set_error_handler( [ $this, 'handle_error' ] );
    }

    /**
     * Stop monitoring and return captured errors
     */
    public function stop_monitoring(): array {
        $this->is_monitoring = false;

        // Restore previous error handler
        restore_error_handler();

        // Check for new errors in error log
        $new_log_errors = $this->get_new_log_errors();
        $this->captured_errors = array_merge( $this->captured_errors, $new_log_errors );

        return array_unique( $this->captured_errors );
    }

    /**
     * Custom error handler
     */
    public function handle_error( int $errno, string $errstr, string $errfile, int $errline ): bool {
        if ( ! $this->is_monitoring ) {
            return false;
        }

        $error_type = $this->get_error_type_string( $errno );
        $error_message = sprintf(
            '[%s] %s in %s on line %d',
            $error_type,
            $errstr,
            $errfile,
            $errline
        );

        $this->captured_errors[] = $error_message;

        // Log to our custom file
        $this->log_error( $error_message );

        // Don't suppress the error
        return false;
    }

    /**
     * Handle shutdown - catch fatal errors
     */
    public function handle_shutdown(): void {
        if ( ! $this->is_monitoring ) {
            return;
        }

        $error = error_get_last();
        if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
            $error_message = sprintf(
                '[Fatal Error] %s in %s on line %d',
                $error['message'],
                $error['file'],
                $error['line']
            );

            $this->captured_errors[] = $error_message;
            $this->log_error( $error_message );

            // Store errors for later retrieval (in case of fatal)
            update_option( 'wpsm_last_fatal_errors', $this->captured_errors );
        }
    }

    /**
     * Get error type string
     */
    private function get_error_type_string( int $type ): string {
        $types = [
            E_ERROR             => 'Fatal Error',
            E_WARNING           => 'Warning',
            E_PARSE             => 'Parse Error',
            E_NOTICE            => 'Notice',
            E_CORE_ERROR        => 'Core Error',
            E_CORE_WARNING      => 'Core Warning',
            E_COMPILE_ERROR     => 'Compile Error',
            E_COMPILE_WARNING   => 'Compile Warning',
            E_USER_ERROR        => 'User Error',
            E_USER_WARNING      => 'User Warning',
            E_USER_NOTICE       => 'User Notice',
            E_STRICT            => 'Strict',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED        => 'Deprecated',
            E_USER_DEPRECATED   => 'User Deprecated',
        ];

        return $types[ $type ] ?? 'Unknown Error';
    }

    /**
     * Log error to custom file
     */
    private function log_error( string $message ): void {
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $log_entry = "[{$timestamp}] {$message}\n";

        error_log( $log_entry, 3, $this->log_file );
    }

    /**
     * Capture snapshot of current error log position
     */
    private int $log_position = 0;

    private function capture_error_log_snapshot(): void {
        $error_log = ini_get( 'error_log' );

        if ( $error_log && file_exists( $error_log ) ) {
            $this->log_position = filesize( $error_log );
        } else {
            $this->log_position = 0;
        }
    }

    /**
     * Get new errors from log since snapshot
     */
    private function get_new_log_errors(): array {
        $error_log = ini_get( 'error_log' );
        $errors = [];

        if ( ! $error_log || ! file_exists( $error_log ) ) {
            return $errors;
        }

        $current_size = filesize( $error_log );

        if ( $current_size <= $this->log_position ) {
            return $errors;
        }

        // Read new content
        $handle = fopen( $error_log, 'r' );
        if ( $handle ) {
            fseek( $handle, $this->log_position );
            $new_content = fread( $handle, $current_size - $this->log_position );
            fclose( $handle );

            if ( $new_content ) {
                $lines = explode( "\n", trim( $new_content ) );
                foreach ( $lines as $line ) {
                    $line = trim( $line );
                    if ( ! empty( $line ) ) {
                        $errors[] = $line;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Get last fatal errors (stored before crash)
     */
    public function get_last_fatal_errors(): array {
        return get_option( 'wpsm_last_fatal_errors', [] );
    }

    /**
     * Clear stored fatal errors
     */
    public function clear_fatal_errors(): void {
        delete_option( 'wpsm_last_fatal_errors' );
    }

    /**
     * Read custom error log
     */
    public function read_error_log( int $lines = 100 ): array {
        if ( ! file_exists( $this->log_file ) ) {
            return [];
        }

        $content = file_get_contents( $this->log_file );
        $all_lines = explode( "\n", trim( $content ) );

        return array_slice( $all_lines, -$lines );
    }

    /**
     * Clear custom error log
     */
    public function clear_error_log(): bool {
        if ( file_exists( $this->log_file ) ) {
            return unlink( $this->log_file );
        }
        return true;
    }

    /**
     * Check site health after update
     * Makes HTTP request to check for fatal errors
     */
    public function check_site_health(): array {
        $response = wp_remote_get( home_url( '/' ), [
            'timeout'   => 30,
            'sslverify' => false,
        ]);

        if ( is_wp_error( $response ) ) {
            return [
                'healthy' => false,
                'error'   => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        // Check for common error patterns
        $error_patterns = [
            'Fatal error',
            'Parse error',
            'syntax error',
            'Call to undefined',
            'Class .* not found',
            'Maximum execution time',
            'Allowed memory size',
        ];

        $found_errors = [];
        foreach ( $error_patterns as $pattern ) {
            if ( preg_match( '/' . $pattern . '/i', $body, $matches ) ) {
                $found_errors[] = $matches[0];
            }
        }

        return [
            'healthy'     => $status_code >= 200 && $status_code < 400 && empty( $found_errors ),
            'status_code' => $status_code,
            'errors'      => $found_errors,
        ];
    }
}
