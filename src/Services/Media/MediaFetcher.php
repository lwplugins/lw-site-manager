<?php
/**
 * Media Fetcher - Resolves an upload source URL into a local temp file.
 *
 * Split out of MediaManager so the decision layer (plan()) stays pure and
 * unit-testable, while the I/O layer (fetch()) stays thin.
 *
 * @package LightweightPlugins\SiteManager\Services\Media
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Media;

use LightweightPlugins\SiteManager\Helpers\ResponseFormatter;

final class MediaFetcher {

    /**
     * Decide how to obtain the source file. Pure — performs no I/O.
     *
     * Two rules, both driven by "is this the site talking to itself?":
     *
     * 1. A same-host URL pointing inside the uploads directory needs no HTTP
     *    round-trip at all — the file is already on disk. This also sidesteps
     *    TLS, loopback and WAF/rate-limit interference entirely.
     * 2. A same-host URL that is not an uploads file is still the site
     *    downloading from itself, so certificate verification buys nothing
     *    (there is no MITM position) and breaks self-signed staging setups.
     *
     * @param string                                                                $url        Source URL.
     * @param array{home_url: string, uploads_baseurl: string, uploads_basedir: string} $site       Site context.
     * @param bool|null                                                             $verify_ssl Explicit override; null means "decide automatically".
     * @return array{local_path: string|null, sslverify: bool}
     */
    public static function plan( string $url, array $site, ?bool $verify_ssl = null ): array {
        $same_host = '' !== self::host_of( $url )
            && self::host_of( $url ) === self::host_of( $site['home_url'] );

        $local_path = $same_host
            ? self::local_path_for( $url, $site['uploads_baseurl'], $site['uploads_basedir'] )
            : null;

        return [
            'local_path' => $local_path,
            'sslverify'  => $verify_ssl ?? ! $same_host,
        ];
    }

    /**
     * Fetch the source into a temp file that the caller owns and must clean up.
     *
     * @param string    $url        Source URL.
     * @param bool|null $verify_ssl Explicit SSL verification override.
     * @return string|\WP_Error Absolute path to the temp file.
     */
    public static function fetch( string $url, ?bool $verify_ssl = null ): string|\WP_Error {
        $url  = esc_url_raw( $url );
        $plan = self::plan( $url, self::site_context(), $verify_ssl );

        if ( null !== $plan['local_path'] && is_file( $plan['local_path'] ) && is_readable( $plan['local_path'] ) ) {
            return self::copy_to_temp( $plan['local_path'] );
        }

        return self::download( $url, $plan['sslverify'] );
    }

    /**
     * Decode a base64 payload into a temp file that the caller owns.
     *
     * @param string $data     Base64 encoded file contents.
     * @param string $filename Original filename, used to seed the temp name.
     * @return string|\WP_Error Absolute path to the temp file.
     */
    public static function from_base64( string $data, string $filename ): string|\WP_Error {
        $decoded = base64_decode( $data, true );

        if ( false === $decoded ) {
            return ResponseFormatter::error( 'invalid_base64', 'Invalid base64 data', 400 );
        }

        $tmp = wp_tempnam( $filename );

        if ( ! $tmp ) {
            return ResponseFormatter::error( 'temp_file_failed', 'Could not create temporary file', 500 );
        }

        if ( false === file_put_contents( $tmp, $decoded ) ) {
            wp_delete_file( $tmp );
            return ResponseFormatter::error( 'write_failed', 'Could not write to temporary file', 500 );
        }

        return $tmp;
    }

    /**
     * Copy a local file into a temp file.
     *
     * WordPress moves the file handed to media_handle_sideload(), so passing
     * the original would consume the source. Always work on a copy.
     *
     * @param string $path Absolute path to an existing readable file.
     * @return string|\WP_Error
     */
    private static function copy_to_temp( string $path ): string|\WP_Error {
        $tmp = wp_tempnam( basename( $path ) );

        if ( ! $tmp ) {
            return ResponseFormatter::error( 'temp_file_failed', 'Could not create temporary file', 500 );
        }

        if ( ! copy( $path, $tmp ) ) {
            wp_delete_file( $tmp );
            return ResponseFormatter::error( 'local_copy_failed', 'Could not copy the local source file', 500 );
        }

        return $tmp;
    }

    /**
     * Download a remote URL, optionally without certificate verification.
     *
     * WordPress exposes no sslverify parameter on download_url(), so the
     * setting is injected through a narrowly scoped http_request_args filter
     * that is always removed again.
     *
     * @param string $url       Source URL.
     * @param bool   $sslverify Whether to verify the TLS certificate.
     * @return string|\WP_Error
     */
    private static function download( string $url, bool $sslverify ): string|\WP_Error {
        if ( $sslverify ) {
            $tmp = download_url( $url );
            return is_wp_error( $tmp )
                ? ResponseFormatter::error( 'download_failed', $tmp->get_error_message(), 500 )
                : $tmp;
        }

        $filter = static function ( $args, $request_url = '' ) use ( $url ) {
            if ( $request_url === $url && is_array( $args ) ) {
                $args['sslverify'] = false;
            }
            return $args;
        };

        add_filter( 'http_request_args', $filter, 10, 2 );

        try {
            $tmp = download_url( $url );
        } finally {
            remove_filter( 'http_request_args', $filter, 10 );
        }

        return is_wp_error( $tmp )
            ? ResponseFormatter::error( 'download_failed', $tmp->get_error_message(), 500 )
            : $tmp;
    }

    /**
     * Build the site context used by plan().
     *
     * @return array{home_url: string, uploads_baseurl: string, uploads_basedir: string}
     */
    private static function site_context(): array {
        $uploads = wp_upload_dir();

        return [
            'home_url'        => (string) home_url(),
            'uploads_baseurl' => (string) $uploads['baseurl'],
            'uploads_basedir' => (string) $uploads['basedir'],
        ];
    }

    /**
     * Map a URL to a path inside the uploads directory, or null when it does
     * not resolve to one.
     *
     * Traversal-safe: the path is URL-decoded and lexically normalized, then
     * checked for containment, so "..", "%2e%2e" and friends cannot escape.
     *
     * @param string $url      Source URL.
     * @param string $baseurl  Uploads base URL.
     * @param string $basedir  Uploads base directory.
     * @return string|null
     */
    private static function local_path_for( string $url, string $baseurl, string $basedir ): ?string {
        $base_path = rtrim( self::path_of( $baseurl ), '/' );
        $url_path  = self::path_of( $url );

        if ( '' === $base_path || '' === $basedir || ! str_starts_with( $url_path, $base_path . '/' ) ) {
            return null;
        }

        $relative  = rawurldecode( substr( $url_path, strlen( $base_path ) ) );
        $candidate = self::normalize_path( rtrim( $basedir, '/' ) . $relative );
        $root      = self::normalize_path( $basedir );

        if ( $candidate === $root || ! str_starts_with( $candidate . '/', $root . '/' ) ) {
            return null;
        }

        return $candidate;
    }

    /**
     * Lexically resolve "." and ".." segments without touching the filesystem.
     *
     * @param string $path Path to normalize.
     * @return string
     */
    private static function normalize_path( string $path ): string {
        $parts = [];

        foreach ( explode( '/', str_replace( '\\', '/', $path ) ) as $segment ) {
            if ( '' === $segment || '.' === $segment ) {
                continue;
            }

            if ( '..' === $segment ) {
                array_pop( $parts );
                continue;
            }

            $parts[] = $segment;
        }

        return '/' . implode( '/', $parts );
    }

    /**
     * Extract the lowercased host from a URL.
     *
     * @param string $url URL to inspect.
     * @return string
     */
    private static function host_of( string $url ): string {
        return strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    }

    /**
     * Extract the path component from a URL.
     *
     * @param string $url URL to inspect.
     * @return string
     */
    private static function path_of( string $url ): string {
        return (string) wp_parse_url( $url, PHP_URL_PATH );
    }
}
