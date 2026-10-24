<?php
/**
 * Location of the plugin's own error log.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolves and prepares the file the error handler logs to.
 *
 * The log lives in its own directory under wp-content with a per-site random
 * file name. The directory carries deny rules for Apache; the random name
 * covers servers that ignore .htaccess.
 */
final class ErrorLogFile {

    /**
     * Option holding the random part of the file name.
     */
    public const OPTION = 'wpsm_error_log_key';

    /**
     * Directory name under wp-content.
     */
    private const DIR = 'wpsm-logs';

    /**
     * File name the plugin logged to before it had its own directory.
     */
    private const PREVIOUS_FILE = 'wpsm-error.log';

    /**
     * Option set once the previous log file was removed ('1').
     */
    private const PREVIOUS_REMOVED_OPTION = 'wpsm_error_log_previous_removed';

    /**
     * Path to log to; creates the random name on first use.
     */
    public static function path(): string {
        $key = get_option( self::OPTION, '' );

        if ( ! is_string( $key ) || '' === $key ) {
            $key = wp_generate_password( 24, false );
            update_option( self::OPTION, $key, false );
        }

        return self::directory() . '/error-' . $key . '.log';
    }

    /**
     * Path of the log when one was ever assigned, without creating a name.
     */
    public static function existingPath(): ?string {
        $key = get_option( self::OPTION, '' );

        return is_string( $key ) && '' !== $key ? self::directory() . '/error-' . $key . '.log' : null;
    }

    /**
     * Remove the plugin's previous log file, once per site (hooked on admin_init).
     */
    public static function removePreviousFile(): void {
        if ( '1' === get_option( self::PREVIOUS_REMOVED_OPTION, '' ) ) {
            return;
        }

        wp_delete_file( WP_CONTENT_DIR . '/' . self::PREVIOUS_FILE );
        update_option( self::PREVIOUS_REMOVED_OPTION, '1', false );
    }

    /**
     * Create the directory and its protection files when missing.
     *
     * @return bool Whether the directory is ready to write into.
     */
    public static function prepare(): bool {
        $dir = self::directory();

        if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
            return false;
        }

        $files = [
            '.htaccess' => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
            'index.php' => "<?php\n// Silence is golden.\n",
        ];

        foreach ( $files as $name => $contents ) {
            if ( ! file_exists( $dir . '/' . $name ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- runs inside a PHP error handler, where WP_Filesystem may be unavailable.
                file_put_contents( $dir . '/' . $name, $contents );
            }
        }

        return true;
    }

    /**
     * The log directory.
     */
    private static function directory(): string {
        return WP_CONTENT_DIR . '/' . self::DIR;
    }
}
