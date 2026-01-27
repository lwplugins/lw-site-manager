<?php
/**
 * WordPress function stubs for unit testing.
 *
 * These stubs allow unit tests to run without a full WordPress installation.
 * They provide minimal implementations of commonly used WordPress functions.
 *
 * @package LightweightPlugins\SiteManager\Tests
 */

declare(strict_types=1);

// ============================================================================
// MIME Type Functions
// ============================================================================

if ( ! function_exists( 'get_allowed_mime_types' ) ) {
    /**
     * Get allowed MIME types.
     *
     * @param int|WP_User|null $user Optional user to check.
     * @return array<string, string> Array of mime types keyed by extension.
     */
    function get_allowed_mime_types( $user = null ): array {
        // Default WordPress MIME types.
        return apply_filters(
            'upload_mimes',
            [
                // Image formats.
                'jpg|jpeg|jpe'                 => 'image/jpeg',
                'gif'                          => 'image/gif',
                'png'                          => 'image/png',
                'bmp'                          => 'image/bmp',
                'tiff|tif'                     => 'image/tiff',
                'webp'                         => 'image/webp',
                'ico'                          => 'image/x-icon',
                'heic'                         => 'image/heic',
                // Video formats.
                'mp4|m4v'                      => 'video/mp4',
                'mpeg|mpg|mpe'                 => 'video/mpeg',
                'mov|qt'                       => 'video/quicktime',
                'avi'                          => 'video/avi',
                'wmv'                          => 'video/x-ms-wmv',
                'webm'                         => 'video/webm',
                // Audio formats.
                'mp3|m4a|m4b'                  => 'audio/mpeg',
                'wav'                          => 'audio/wav',
                'ogg|oga'                      => 'audio/ogg',
                // Documents.
                'pdf'                          => 'application/pdf',
                'doc'                          => 'application/msword',
                'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'                          => 'application/vnd.ms-excel',
                'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt'                          => 'application/vnd.ms-powerpoint',
                'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt|asc|c|cc|h|srt'           => 'text/plain',
                'csv'                          => 'text/csv',
                'rtf'                          => 'application/rtf',
                // Archives.
                'zip'                          => 'application/zip',
                'gz|gzip'                      => 'application/x-gzip',
                'rar'                          => 'application/rar',
                '7z'                           => 'application/x-7z-compressed',
            ],
            $user
        );
    }
}

if ( ! function_exists( 'wp_check_filetype' ) ) {
    /**
     * Retrieve the file type from the file name.
     *
     * @param string        $filename File name or path.
     * @param string[]|null $mimes    Optional array of allowed mime types.
     * @return array{ext: string|false, type: string|false} Values for the extension and mime type.
     */
    function wp_check_filetype( string $filename, ?array $mimes = null ): array {
        if ( empty( $mimes ) ) {
            $mimes = get_allowed_mime_types();
        }

        $type = false;
        $ext  = false;

        $extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        if ( $extension ) {
            foreach ( $mimes as $ext_preg => $mime_match ) {
                $ext_preg = '!^(' . $ext_preg . ')$!i';
                if ( preg_match( $ext_preg, $extension, $ext_matches ) ) {
                    $type = $mime_match;
                    $ext  = $ext_matches[1];
                    break;
                }
            }
        }

        return compact( 'ext', 'type' );
    }
}

// ============================================================================
// Filter/Action Functions
// ============================================================================

// Global filters storage.
global $wp_filter;
$wp_filter = $wp_filter ?? [];

if ( ! function_exists( 'add_filter' ) ) {
    /**
     * Add a filter hook.
     *
     * @param string   $hook_name     The name of the filter to add the callback to.
     * @param callable $callback      The callback to be run when the filter is applied.
     * @param int      $priority      Optional. Priority. Default 10.
     * @param int      $accepted_args Optional. Number of accepted arguments. Default 1.
     * @return true
     */
    function add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            $wp_filter[ $hook_name ] = [];
        }

        if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) ) {
            $wp_filter[ $hook_name ][ $priority ] = [];
        }

        $wp_filter[ $hook_name ][ $priority ][] = [
            'function'      => $callback,
            'accepted_args' => $accepted_args,
        ];

        return true;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    /**
     * Call the functions added to a filter hook.
     *
     * @param string $hook_name The name of the filter hook.
     * @param mixed  $value     The value to filter.
     * @param mixed  ...$args   Additional parameters to pass to the callback functions.
     * @return mixed The filtered value after all hooked functions are applied.
     */
    function apply_filters( string $hook_name, $value, ...$args ) {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            return $value;
        }

        ksort( $wp_filter[ $hook_name ] );

        foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                $all_args = array_merge( [ $value ], $args );
                $value    = call_user_func_array(
                    $callback['function'],
                    array_slice( $all_args, 0, $callback['accepted_args'] )
                );
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'has_filter' ) ) {
    /**
     * Check if any filter has been registered for a hook.
     *
     * @param string        $hook_name The name of the filter hook.
     * @param callable|bool $callback  Optional. The callback to check for.
     * @return bool|int
     */
    function has_filter( string $hook_name, $callback = false ) {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook_name ] ) ) {
            return false;
        }

        if ( false === $callback ) {
            return ! empty( $wp_filter[ $hook_name ] );
        }

        foreach ( $wp_filter[ $hook_name ] as $priority => $callbacks ) {
            foreach ( $callbacks as $cb ) {
                if ( $cb['function'] === $callback ) {
                    return $priority;
                }
            }
        }

        return false;
    }
}

if ( ! function_exists( 'remove_filter' ) ) {
    /**
     * Remove a filter hook.
     *
     * @param string   $hook_name The filter hook to remove.
     * @param callable $callback  The callback to remove.
     * @param int      $priority  Optional. The priority. Default 10.
     * @return bool
     */
    function remove_filter( string $hook_name, callable $callback, int $priority = 10 ): bool {
        global $wp_filter;

        if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) ) {
            return false;
        }

        foreach ( $wp_filter[ $hook_name ][ $priority ] as $key => $cb ) {
            if ( $cb['function'] === $callback ) {
                unset( $wp_filter[ $hook_name ][ $priority ][ $key ] );
                return true;
            }
        }

        return false;
    }
}

// ============================================================================
// Error Handling
// ============================================================================

if ( ! class_exists( 'WP_Error' ) ) {
    /**
     * WordPress Error class stub.
     */
    class WP_Error {
        /**
         * Stores the list of errors.
         *
         * @var array
         */
        public array $errors = [];

        /**
         * Stores the most recently added error data.
         *
         * @var array
         */
        public array $error_data = [];

        /**
         * Stores additional data for errors.
         *
         * @var array[]
         */
        protected array $additional_data = [];

        /**
         * Constructor.
         *
         * @param string|int $code    Error code.
         * @param string     $message Error message.
         * @param mixed      $data    Optional. Error data.
         */
        public function __construct( $code = '', string $message = '', $data = '' ) {
            if ( empty( $code ) ) {
                return;
            }

            $this->add( $code, $message, $data );
        }

        /**
         * Retrieves all error codes.
         *
         * @return array
         */
        public function get_error_codes(): array {
            return array_keys( $this->errors );
        }

        /**
         * Retrieves the first error code.
         *
         * @return string|int
         */
        public function get_error_code() {
            $codes = $this->get_error_codes();
            return $codes[0] ?? '';
        }

        /**
         * Retrieves all error messages.
         *
         * @param string|int $code Optional. Error code.
         * @return array
         */
        public function get_error_messages( $code = '' ): array {
            if ( empty( $code ) ) {
                $all_messages = [];
                foreach ( $this->errors as $messages ) {
                    $all_messages = array_merge( $all_messages, $messages );
                }
                return $all_messages;
            }

            return $this->errors[ $code ] ?? [];
        }

        /**
         * Gets the first error message.
         *
         * @param string|int $code Optional. Error code.
         * @return string
         */
        public function get_error_message( $code = '' ): string {
            if ( empty( $code ) ) {
                $code = $this->get_error_code();
            }

            $messages = $this->get_error_messages( $code );
            return $messages[0] ?? '';
        }

        /**
         * Retrieves the error data.
         *
         * @param string|int $code Optional. Error code.
         * @return mixed
         */
        public function get_error_data( $code = '' ) {
            if ( empty( $code ) ) {
                $code = $this->get_error_code();
            }

            return $this->error_data[ $code ] ?? null;
        }

        /**
         * Verifies if the instance contains errors.
         *
         * @return bool
         */
        public function has_errors(): bool {
            return ! empty( $this->errors );
        }

        /**
         * Adds an error or appends an additional message to an existing error.
         *
         * @param string|int $code    Error code.
         * @param string     $message Error message.
         * @param mixed      $data    Optional. Error data.
         */
        public function add( $code, string $message, $data = '' ): void {
            $this->errors[ $code ][] = $message;

            if ( ! empty( $data ) ) {
                $this->add_data( $data, $code );
            }
        }

        /**
         * Adds data to an error.
         *
         * @param mixed      $data Error data.
         * @param string|int $code Error code.
         */
        public function add_data( $data, $code = '' ): void {
            if ( empty( $code ) ) {
                $code = $this->get_error_code();
            }

            $this->error_data[ $code ] = $data;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    /**
     * Check whether a variable is a WP_Error.
     *
     * @param mixed $thing Check if variable is a WP_Error object.
     * @return bool
     */
    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }
}

// ============================================================================
// Internationalization Functions
// ============================================================================

if ( ! function_exists( '__' ) ) {
    /**
     * Retrieve the translation of $text.
     *
     * @param string $text   Text to translate.
     * @param string $domain Optional. Text domain.
     * @return string Translated text.
     */
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'is_email' ) ) {
    /**
     * Verifies that an email is valid.
     *
     * @param string $email Email address to verify.
     * @return string|false Valid email address on success, false on failure.
     */
    function is_email( string $email ) {
        if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            return $email;
        }
        return false;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    /**
     * Retrieve and escape the translation of $text.
     *
     * @param string $text   Text to translate.
     * @param string $domain Optional. Text domain.
     * @return string Escaped translated text.
     */
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

// ============================================================================
// Utility Functions
// ============================================================================

if ( ! function_exists( 'wp_tempnam' ) ) {
    /**
     * Create a temporary file name.
     *
     * @param string $filename Optional. Filename to base temp file on.
     * @param string $dir      Optional. Directory to put temp file in.
     * @return string|false Temporary file path or false on failure.
     */
    function wp_tempnam( string $filename = '', string $dir = '' ) {
        if ( empty( $dir ) ) {
            $dir = sys_get_temp_dir();
        }

        if ( empty( $filename ) ) {
            $filename = 'wp_temp_';
        }

        $filename = basename( $filename );
        if ( empty( $filename ) ) {
            $filename = 'wp_temp_';
        }

        return tempnam( $dir, $filename );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    /**
     * Encode a variable into JSON.
     *
     * @param mixed $data    Variable to encode.
     * @param int   $options Optional. JSON encode options.
     * @param int   $depth   Optional. Maximum depth.
     * @return string|false JSON string or false on failure.
     */
    function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

// ============================================================================
// Options Functions
// ============================================================================

// Global options storage.
global $wp_options;
$wp_options = $wp_options ?? [];

if ( ! function_exists( 'get_option' ) ) {
    /**
     * Retrieve option value.
     *
     * @param string $option  Option name.
     * @param mixed  $default Default value.
     * @return mixed Option value.
     */
    function get_option( string $option, $default = false ) {
        global $wp_options;
        return $wp_options[ $option ] ?? $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    /**
     * Update option value.
     *
     * @param string $option   Option name.
     * @param mixed  $value    Option value.
     * @param bool   $autoload Whether to autoload.
     * @return bool True if updated.
     */
    function update_option( string $option, $value, $autoload = null ): bool {
        global $wp_options;
        $wp_options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    /**
     * Delete option.
     *
     * @param string $option Option name.
     * @return bool True if deleted.
     */
    function delete_option( string $option ): bool {
        global $wp_options;
        if ( isset( $wp_options[ $option ] ) ) {
            unset( $wp_options[ $option ] );
            return true;
        }
        return false;
    }
}

// ============================================================================
// Filesystem Functions
// ============================================================================

if ( ! function_exists( 'wp_upload_dir' ) ) {
    /**
     * Get uploads directory info.
     *
     * @return array Upload directory info.
     */
    function wp_upload_dir(): array {
        $base = sys_get_temp_dir() . '/wp-uploads';
        return [
            'path'    => $base . '/' . date( 'Y/m' ),
            'url'     => 'http://example.com/wp-content/uploads/' . date( 'Y/m' ),
            'subdir'  => '/' . date( 'Y/m' ),
            'basedir' => $base,
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error'   => false,
        ];
    }
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
    /**
     * Create directory recursively.
     *
     * @param string $target Directory path.
     * @return bool True on success.
     */
    function wp_mkdir_p( string $target ): bool {
        if ( is_dir( $target ) ) {
            return true;
        }
        return mkdir( $target, 0755, true );
    }
}

if ( ! function_exists( 'size_format' ) ) {
    /**
     * Format file size in human readable format.
     *
     * @param int|string $bytes    Number of bytes.
     * @param int        $decimals Decimal places.
     * @return string Formatted size.
     */
    function size_format( $bytes, int $decimals = 0 ): string {
        $bytes = (int) $bytes;
        if ( $bytes < 1024 ) {
            return $bytes . ' B';
        }
        $units = [ 'KB', 'MB', 'GB', 'TB' ];
        $power = floor( log( $bytes, 1024 ) );
        return round( $bytes / pow( 1024, $power ), $decimals ) . ' ' . $units[ $power - 1 ];
    }
}

// ============================================================================
// WordPress Info Functions
// ============================================================================

if ( ! function_exists( 'get_bloginfo' ) ) {
    /**
     * Get blog info.
     *
     * @param string $show What to get.
     * @return string Value.
     */
    function get_bloginfo( string $show = '' ): string {
        return match ( $show ) {
            'version' => '6.4.0',
            'name'    => 'Test Site',
            'url'     => 'http://example.com',
            default   => '',
        };
    }
}

if ( ! function_exists( 'get_site_url' ) ) {
    /**
     * Get site URL.
     *
     * @param int|null $blog_id Blog ID.
     * @param string   $path    Path to append.
     * @param string   $scheme  Scheme.
     * @return string URL.
     */
    function get_site_url( ?int $blog_id = null, string $path = '', ?string $scheme = null ): string {
        return 'http://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
    }
}

// ============================================================================
// Cron Functions
// ============================================================================

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
    /**
     * Schedule a single event.
     *
     * @param int    $timestamp Timestamp.
     * @param string $hook      Hook name.
     * @param array  $args      Arguments.
     * @return bool|WP_Error True on success.
     */
    function wp_schedule_single_event( int $timestamp, string $hook, array $args = [] ) {
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    /**
     * Check next scheduled event.
     *
     * @param string $hook Hook name.
     * @param array  $args Arguments.
     * @return int|false Timestamp or false.
     */
    function wp_next_scheduled( string $hook, array $args = [] ) {
        return false;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    /**
     * Clear scheduled hook.
     *
     * @param string $hook Hook name.
     * @param array  $args Arguments.
     * @return int|false Number of events unscheduled.
     */
    function wp_clear_scheduled_hook( string $hook, array $args = [] ) {
        return 1;
    }
}

if ( ! function_exists( 'spawn_cron' ) ) {
    /**
     * Spawn cron.
     *
     * @return bool|WP_Error True on success.
     */
    function spawn_cron() {
        return true;
    }
}

// ============================================================================
// Date/Time Functions
// ============================================================================

if ( ! function_exists( 'current_time' ) ) {
    /**
     * Get current time.
     *
     * @param string $type Type (mysql, timestamp, etc).
     * @param bool   $gmt  Whether to use GMT.
     * @return int|string Current time.
     */
    function current_time( string $type, bool $gmt = false ) {
        if ( $type === 'mysql' ) {
            return date( 'Y-m-d H:i:s' );
        }
        if ( $type === 'timestamp' ) {
            return time();
        }
        return date( $type );
    }
}

// ============================================================================
// Misc Functions
// ============================================================================

if ( ! function_exists( 'wp_generate_password' ) ) {
    /**
     * Generate random password.
     *
     * @param int  $length         Length.
     * @param bool $special_chars  Include special chars.
     * @param bool $extra_special  Include extra special chars.
     * @return string Password.
     */
    function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special = false ): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ( $special_chars ) {
            $chars .= '!@#$%^&*()';
        }
        $password = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $password .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
        }
        return $password;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    /**
     * Add an action hook.
     *
     * @param string   $hook_name     Hook name.
     * @param callable $callback      Callback.
     * @param int      $priority      Priority.
     * @param int      $accepted_args Accepted args.
     * @return true
     */
    function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        return add_filter( $hook_name, $callback, $priority, $accepted_args );
    }
}

if ( ! function_exists( 'get_theme_root' ) ) {
    /**
     * Get theme root.
     *
     * @return string Theme root path.
     */
    function get_theme_root(): string {
        return '/tmp/wordpress/wp-content/themes';
    }
}

// ============================================================================
// User Permission Functions
// ============================================================================

if ( ! function_exists( 'current_user_can' ) ) {
    /**
     * Check if current user has capability.
     *
     * @param string $capability Capability to check.
     * @param mixed  ...$args    Additional args.
     * @return bool True if user has capability.
     */
    function current_user_can( string $capability, ...$args ): bool {
        // Return false in test environment by default.
        return false;
    }
}

// ============================================================================
// Cache Functions
// ============================================================================

if ( ! function_exists( 'wp_cache_flush' ) ) {
    /**
     * Flush WordPress object cache.
     *
     * @return bool True on success.
     */
    function wp_cache_flush(): bool {
        return true;
    }
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
    /**
     * Flush rewrite rules.
     *
     * @param bool $hard Whether to hard flush.
     */
    function flush_rewrite_rules( bool $hard = true ): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
    /**
     * Check if using external object cache.
     *
     * @return bool True if using external cache.
     */
    function wp_using_ext_object_cache(): bool {
        return false;
    }
}

if ( ! function_exists( 'delete_transient' ) ) {
    /**
     * Delete transient.
     *
     * @param string $transient Transient name.
     * @return bool True on success.
     */
    function delete_transient( string $transient ): bool {
        return delete_option( '_transient_' . $transient );
    }
}

if ( ! function_exists( 'get_site_transient' ) ) {
    /**
     * Get site transient.
     *
     * @param string $transient Transient name.
     * @return mixed Transient value.
     */
    function get_site_transient( string $transient ) {
        return get_option( '_site_transient_' . $transient, false );
    }
}

if ( ! function_exists( 'delete_site_transient' ) ) {
    /**
     * Delete site transient.
     *
     * @param string $transient Transient name.
     * @return bool True on success.
     */
    function delete_site_transient( string $transient ): bool {
        return delete_option( '_site_transient_' . $transient );
    }
}

// ============================================================================
// Roles Functions
// ============================================================================

if ( ! function_exists( 'wp_roles' ) ) {
    /**
     * Get WordPress roles.
     *
     * @return object Roles object.
     */
    function wp_roles(): object {
        return (object) [
            'roles' => [
                'administrator' => [ 'name' => 'Administrator' ],
                'editor'        => [ 'name' => 'Editor' ],
                'author'        => [ 'name' => 'Author' ],
                'contributor'   => [ 'name' => 'Contributor' ],
                'subscriber'    => [ 'name' => 'Subscriber' ],
            ],
        ];
    }
}

// ============================================================================
// Locale Functions
// ============================================================================

if ( ! function_exists( 'get_locale' ) ) {
    /**
     * Get locale.
     *
     * @return string Locale string.
     */
    function get_locale(): string {
        return 'en_US';
    }
}

// ============================================================================
// Post Functions
// ============================================================================

if ( ! function_exists( 'get_post' ) ) {
    /**
     * Get post object.
     *
     * @param int|null $post_id Post ID.
     * @return object|null Post object.
     */
    function get_post( ?int $post_id = null ): ?object {
        if ( $post_id === null ) {
            return null;
        }
        return (object) [
            'ID'          => $post_id,
            'post_title'  => 'Test Post',
            'post_type'   => 'post',
            'post_status' => 'publish',
        ];
    }
}

// ============================================================================
// URL Functions
// ============================================================================

if ( ! function_exists( 'home_url' ) ) {
    /**
     * Get home URL.
     *
     * @param string $path Path to append.
     * @return string URL.
     */
    function home_url( string $path = '' ): string {
        return 'http://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
    }
}

if ( ! function_exists( 'is_ssl' ) ) {
    /**
     * Check if SSL.
     *
     * @return bool True if using SSL.
     */
    function is_ssl(): bool {
        return false;
    }
}

// ============================================================================
// Memory Functions
// ============================================================================

if ( ! function_exists( 'wp_convert_hr_to_bytes' ) ) {
    /**
     * Convert human readable size to bytes.
     *
     * @param string $value Human readable size.
     * @return int Bytes.
     */
    function wp_convert_hr_to_bytes( string $value ): int {
        $value = strtolower( trim( $value ) );
        $bytes = (int) $value;

        if ( str_contains( $value, 'g' ) ) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif ( str_contains( $value, 'm' ) ) {
            $bytes *= 1024 * 1024;
        } elseif ( str_contains( $value, 'k' ) ) {
            $bytes *= 1024;
        }

        return $bytes;
    }
}

// ============================================================================
// Update Functions
// ============================================================================

if ( ! function_exists( 'get_core_updates' ) ) {
    /**
     * Get core updates.
     *
     * @return array Core updates.
     */
    function get_core_updates(): array {
        return [
            (object) [
                'response' => 'latest',
                'version'  => get_bloginfo( 'version' ),
            ],
        ];
    }
}

if ( ! function_exists( 'wp_update_plugins' ) ) {
    /**
     * Update plugins transient.
     */
    function wp_update_plugins(): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'wp_update_themes' ) ) {
    /**
     * Update themes transient.
     */
    function wp_update_themes(): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'wp_version_check' ) ) {
    /**
     * Check WordPress version.
     */
    function wp_version_check(): void {
        // No-op in tests.
    }
}

// ============================================================================
// Plugin Functions
// ============================================================================

if ( ! function_exists( 'get_plugins' ) ) {
    /**
     * Get installed plugins.
     *
     * @return array Plugins.
     */
    function get_plugins(): array {
        return [];
    }
}

if ( ! function_exists( 'is_plugin_active' ) ) {
    /**
     * Check if plugin is active.
     *
     * @param string $plugin Plugin file.
     * @return bool True if active.
     */
    function is_plugin_active( string $plugin ): bool {
        return in_array( $plugin, get_option( 'active_plugins', [] ), true );
    }
}

if ( ! function_exists( 'activate_plugin' ) ) {
    /**
     * Activate a plugin.
     *
     * @param string $plugin Plugin file.
     * @return null|WP_Error Null on success, WP_Error on failure.
     */
    function activate_plugin( string $plugin ) {
        return null;
    }
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
    /**
     * Deactivate plugins.
     *
     * @param string|array $plugins Plugins to deactivate.
     */
    function deactivate_plugins( $plugins ): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'delete_plugins' ) ) {
    /**
     * Delete plugins.
     *
     * @param array $plugins Plugins to delete.
     * @return bool|WP_Error True on success.
     */
    function delete_plugins( array $plugins ) {
        return true;
    }
}

if ( ! function_exists( 'get_plugin_data' ) ) {
    /**
     * Get plugin data.
     *
     * @param string $plugin_file Plugin file.
     * @param bool   $markup      Apply markup.
     * @param bool   $translate   Translate.
     * @return array Plugin data.
     */
    function get_plugin_data( string $plugin_file, bool $markup = true, bool $translate = true ): array {
        return [
            'Name'    => 'Test Plugin',
            'Version' => '1.0.0',
        ];
    }
}

if ( ! function_exists( 'wp_clean_plugins_cache' ) ) {
    /**
     * Clean plugins cache.
     */
    function wp_clean_plugins_cache(): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'plugins_api' ) ) {
    /**
     * Get plugin API info.
     *
     * @param string $action Action.
     * @param array  $args   Args.
     * @return object|WP_Error Plugin info.
     */
    function plugins_api( string $action, array $args = [] ) {
        return (object) [
            'name'          => 'Test Plugin',
            'version'       => '1.0.0',
            'download_link' => 'http://example.com/plugin.zip',
        ];
    }
}

// ============================================================================
// Theme Functions
// ============================================================================

if ( ! function_exists( 'wp_get_themes' ) ) {
    /**
     * Get installed themes.
     *
     * @return array Themes.
     */
    function wp_get_themes(): array {
        return [];
    }
}

if ( ! function_exists( 'wp_get_theme' ) ) {
    /**
     * Get theme.
     *
     * @param string|null $stylesheet Theme stylesheet.
     * @return object Theme object.
     */
    function wp_get_theme( ?string $stylesheet = null ): object {
        return new class( $stylesheet ) {
            private ?string $stylesheet;

            public function __construct( ?string $stylesheet ) {
                $this->stylesheet = $stylesheet;
            }

            public function exists(): bool {
                return $this->stylesheet === 'twentytwentyfour';
            }

            public function get( string $header ): string {
                return match ( $header ) {
                    'Name'    => 'Test Theme',
                    'Version' => '1.0.0',
                    default   => '',
                };
            }

            public function parent(): ?object {
                return null;
            }

            public function get_stylesheet(): string {
                return $this->stylesheet ?? 'twentytwentyfour';
            }
        };
    }
}

if ( ! function_exists( 'get_stylesheet' ) ) {
    /**
     * Get current stylesheet.
     *
     * @return string Stylesheet name.
     */
    function get_stylesheet(): string {
        return 'twentytwentyfour';
    }
}

if ( ! function_exists( 'get_template' ) ) {
    /**
     * Get current template.
     *
     * @return string Template name.
     */
    function get_template(): string {
        return 'twentytwentyfour';
    }
}

if ( ! function_exists( 'switch_theme' ) ) {
    /**
     * Switch theme.
     *
     * @param string $stylesheet Theme stylesheet.
     */
    function switch_theme( string $stylesheet ): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'delete_theme' ) ) {
    /**
     * Delete theme.
     *
     * @param string $stylesheet Theme stylesheet.
     * @return bool|WP_Error True on success.
     */
    function delete_theme( string $stylesheet ) {
        return true;
    }
}

if ( ! function_exists( 'wp_clean_themes_cache' ) ) {
    /**
     * Clean themes cache.
     */
    function wp_clean_themes_cache(): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'themes_api' ) ) {
    /**
     * Get theme API info.
     *
     * @param string $action Action.
     * @param array  $args   Args.
     * @return object|WP_Error Theme info.
     */
    function themes_api( string $action, array $args = [] ) {
        return (object) [
            'name'          => 'Test Theme',
            'version'       => '1.0.0',
            'download_link' => 'http://example.com/theme.zip',
        ];
    }
}

// ============================================================================
// Sanitization Functions
// ============================================================================

if ( ! function_exists( 'sanitize_text_field' ) ) {
    /**
     * Sanitize text field.
     *
     * @param string $str String to sanitize.
     * @return string Sanitized string.
     */
    function sanitize_text_field( string $str ): string {
        return trim( strip_tags( $str ) );
    }
}

// ============================================================================
// WordPress Abilities API Functions
// ============================================================================

if ( ! function_exists( 'register_site_ability' ) ) {
    /**
     * Register a site ability.
     *
     * @param string   $name     Ability name.
     * @param callable $callback Ability callback.
     * @param array    $args     Additional args.
     */
    function register_site_ability( string $name, callable $callback, array $args = [] ): void {
        // No-op in tests - store for verification if needed.
    }
}

if ( ! function_exists( 'wp_register_ability' ) ) {
    /**
     * Register a site ability (alternative name).
     *
     * @param string $name     Ability name.
     * @param array  $args     Args including callback.
     */
    function wp_register_ability( string $name, array $args = [] ): void {
        // No-op in tests.
    }
}

if ( ! function_exists( 'get_comment' ) ) {
    /**
     * Get comment.
     *
     * @param int|null $comment_id Comment ID.
     * @return object|null Comment object.
     */
    function get_comment( ?int $comment_id = null ): ?object {
        if ( $comment_id === null || $comment_id < 1 ) {
            return null;
        }
        return (object) [
            'comment_ID'      => $comment_id,
            'comment_content' => 'Test comment',
            'comment_approved' => '1',
        ];
    }
}

if ( ! function_exists( 'wp_update_comment' ) ) {
    /**
     * Update comment.
     *
     * @param array $commentarr Comment data.
     * @return int|false Comment ID on success.
     */
    function wp_update_comment( array $commentarr ) {
        return $commentarr['comment_ID'] ?? false;
    }
}

if ( ! function_exists( 'wp_set_comment_status' ) ) {
    /**
     * Set comment status.
     *
     * @param int    $comment_id Comment ID.
     * @param string $status     Status.
     * @return bool True on success.
     */
    function wp_set_comment_status( int $comment_id, string $status ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_trash_comment' ) ) {
    /**
     * Trash comment.
     *
     * @param int $comment_id Comment ID.
     * @return bool True on success.
     */
    function wp_trash_comment( int $comment_id ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_delete_comment' ) ) {
    /**
     * Delete comment.
     *
     * @param int  $comment_id   Comment ID.
     * @param bool $force_delete Force delete.
     * @return bool True on success.
     */
    function wp_delete_comment( int $comment_id, bool $force_delete = false ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_spam_comment' ) ) {
    /**
     * Mark comment as spam.
     *
     * @param int $comment_id Comment ID.
     * @return bool True on success.
     */
    function wp_spam_comment( int $comment_id ): bool {
        return true;
    }
}

// ============================================================================
// Post Functions (CRUD)
// ============================================================================

if ( ! function_exists( 'wp_update_post' ) ) {
    /**
     * Update a post.
     *
     * @param array $postarr Post data.
     * @return int|WP_Error Post ID on success.
     */
    function wp_update_post( array $postarr ) {
        return $postarr['ID'] ?? 0;
    }
}

if ( ! function_exists( 'wp_insert_post' ) ) {
    /**
     * Insert a post.
     *
     * @param array $postarr Post data.
     * @return int|WP_Error Post ID on success.
     */
    function wp_insert_post( array $postarr ) {
        return 1;
    }
}

if ( ! function_exists( 'wp_trash_post' ) ) {
    /**
     * Trash a post.
     *
     * @param int $post_id Post ID.
     * @return object|false Post data on success.
     */
    function wp_trash_post( int $post_id ) {
        return (object) [ 'ID' => $post_id ];
    }
}

if ( ! function_exists( 'wp_delete_post' ) ) {
    /**
     * Delete a post.
     *
     * @param int  $post_id      Post ID.
     * @param bool $force_delete Force delete.
     * @return object|false Post data on success.
     */
    function wp_delete_post( int $post_id, bool $force_delete = false ) {
        return (object) [ 'ID' => $post_id ];
    }
}

if ( ! function_exists( 'wp_untrash_post' ) ) {
    /**
     * Restore a post from trash.
     *
     * @param int $post_id Post ID.
     * @return object|false Post data on success.
     */
    function wp_untrash_post( int $post_id ) {
        return (object) [ 'ID' => $post_id ];
    }
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
    /**
     * Get post thumbnail (featured image) ID.
     *
     * @param int|WP_Post|null $post Post ID or post object.
     * @return int|false Thumbnail ID or false if none.
     */
    function get_post_thumbnail_id( $post = null ) {
        // In tests, return false (no featured image) by default.
        return false;
    }
}

// ============================================================================
// Constants
// ============================================================================

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// wpdb result types
if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'ARRAY_N' ) ) {
    define( 'ARRAY_N', 'ARRAY_N' );
}

// ============================================================================
// Database Stub
// ============================================================================

if ( ! isset( $GLOBALS['wpdb'] ) ) {
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';
        public string $posts = 'wp_posts';
        public string $postmeta = 'wp_postmeta';
        public string $comments = 'wp_comments';
        public string $commentmeta = 'wp_commentmeta';
        public string $options = 'wp_options';
        public string $term_relationships = 'wp_term_relationships';

        public function get_results( string $query, $output = OBJECT ) {
            return [];
        }

        public function get_var( string $query ) {
            return 0;
        }

        public function get_col( string $query ): array {
            return [];
        }

        public function query( string $query ) {
            return 0;
        }

        public function prepare( string $query, ...$args ): string {
            return $query;
        }
    };
}

// ============================================================================
// Reset Filters Helper (for testing)
// ============================================================================

/**
 * Reset all WordPress filters.
 * Useful for cleaning up between tests.
 */
function reset_wp_filters(): void {
    global $wp_filter;
    $wp_filter = [];
}

/**
 * Reset all WordPress options.
 * Useful for cleaning up between tests.
 */
function reset_wp_options(): void {
    global $wp_options;
    $wp_options = [];
}
