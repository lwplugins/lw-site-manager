<?php
/**
 * Media Manager Service - Handles media/attachment operations
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

class MediaManager extends AbstractService {

    /**
     * List media items
     */
    public static function list_media( array $input ): array {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $input['limit'] ?? 20,
            'offset'         => $input['offset'] ?? 0,
            'orderby'        => $input['orderby'] ?? 'date',
            'order'          => $input['order'] ?? 'DESC',
        ];

        if ( ! empty( $input['mime_type'] ) ) {
            $args['post_mime_type'] = $input['mime_type'];
        }

        if ( ! empty( $input['search'] ) ) {
            $args['s'] = $input['search'];
        }

        $query = new \WP_Query( $args );
        $items = [];

        foreach ( $query->posts as $attachment ) {
            $items[] = self::format_media( $attachment );
        }

        return [
            'media'       => $items,
            'total'       => $query->found_posts,
            'total_pages' => ceil( $query->found_posts / ( $input['limit'] ?? 20 ) ),
            'limit'       => $input['limit'] ?? 20,
            'offset'      => $input['offset'] ?? 0,
        ];
    }

    /**
     * Get single media item
     */
    public static function get_media( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Media ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $attachment = get_post( $input['id'] );

        if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
            return self::errorResponse( 'not_found', 'Media not found', 404 );
        }

        return self::entityResponse( 'media', self::format_media( $attachment, true ) );
    }

    /**
     * Upload media from URL or base64 data
     */
    public static function upload_media( array $input ): array|\WP_Error {
        $has_url = ! empty( $input['url'] );
        $has_data = ! empty( $input['data'] ) && ! empty( $input['filename'] );

        if ( ! $has_url && ! $has_data ) {
            return self::errorResponse( 'missing_source', 'Either URL or data+filename is required', 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $title = $input['title'] ?? '';
        $alt = $input['alt'] ?? '';
        $caption = $input['caption'] ?? '';
        $description = $input['description'] ?? '';

        if ( $has_data ) {
            // Upload from base64 data
            $result = self::upload_from_base64( $input['data'], $input['filename'], $title );
        } else {
            // Upload from URL
            $result = self::upload_from_url_internal( $input['url'], $title );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $attachment_id = $result;

        // Update metadata
        if ( ! empty( $alt ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
        }

        if ( ! empty( $caption ) || ! empty( $description ) ) {
            wp_update_post( [
                'ID'           => $attachment_id,
                'post_excerpt' => $caption,
                'post_content' => $description,
            ] );
        }

        $attachment = get_post( $attachment_id );

        return self::entityResponse( 'media', self::format_media( $attachment, true ) );
    }

    /**
     * Upload from URL (internal helper)
     */
    private static function upload_from_url_internal( string $url, string $title ): int|\WP_Error {
        $url = esc_url_raw( $url );

        // Download file to temp location
        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            return self::errorResponse( 'download_failed', $tmp->get_error_message(), 500 );
        }

        // Get filename from URL
        $filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
        if ( empty( $filename ) || strpos( $filename, '.' ) === false ) {
            $filename = 'uploaded-file.jpg';
        }

        $file_array = [
            'name'     => $filename,
            'tmp_name' => $tmp,
            'type'     => self::get_mime_type_for_file( $filename, $tmp ),
        ];

        // Upload to media library
        $attachment_id = media_handle_sideload( $file_array, 0, $title );

        // Clean up temp file
        if ( file_exists( $tmp ) ) {
            @unlink( $tmp );
        }

        if ( is_wp_error( $attachment_id ) ) {
            return self::errorResponse( 'upload_failed', $attachment_id->get_error_message(), 500 );
        }

        return $attachment_id;
    }

    /**
     * Upload from base64 data (internal helper)
     */
    private static function upload_from_base64( string $data, string $filename, string $title ): int|\WP_Error {
        // Decode base64 data
        $decoded = base64_decode( $data, true );
        if ( $decoded === false ) {
            return self::errorResponse( 'invalid_base64', 'Invalid base64 data', 400 );
        }

        // Create temp file
        $tmp = wp_tempnam( $filename );
        if ( ! $tmp ) {
            return self::errorResponse( 'temp_file_failed', 'Could not create temporary file', 500 );
        }

        // Write decoded data to temp file
        $written = file_put_contents( $tmp, $decoded );
        if ( $written === false ) {
            @unlink( $tmp );
            return self::errorResponse( 'write_failed', 'Could not write to temporary file', 500 );
        }

        $file_array = [
            'name'     => $filename,
            'tmp_name' => $tmp,
            'type'     => self::get_mime_type_for_file( $filename, $tmp ),
        ];

        // Upload to media library
        $attachment_id = media_handle_sideload( $file_array, 0, $title );

        // Clean up temp file
        if ( file_exists( $tmp ) ) {
            @unlink( $tmp );
        }

        if ( is_wp_error( $attachment_id ) ) {
            return self::errorResponse( 'upload_failed', $attachment_id->get_error_message(), 500 );
        }

        return $attachment_id;
    }

    /**
     * Get MIME type for a file based on extension and content
     *
     * @param string $filename The filename with extension.
     * @param string $filepath The path to the file.
     * @return string The MIME type.
     */
    private static function get_mime_type_for_file( string $filename, string $filepath ): string {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

        // Check allowed mimes first (respects plugins that add custom types)
        $allowed_mimes = get_allowed_mime_types();

        // Direct extension match
        if ( isset( $allowed_mimes[ $ext ] ) ) {
            return $allowed_mimes[ $ext ];
        }

        // Check for extension in combined keys (e.g., 'jpg|jpeg|jpe' => 'image/jpeg')
        foreach ( $allowed_mimes as $exts => $mime ) {
            $ext_list = explode( '|', $exts );
            if ( in_array( $ext, $ext_list, true ) ) {
                return $mime;
            }
        }

        // SVG special handling
        if ( 'svg' === $ext || 'svgz' === $ext ) {
            return 'image/svg+xml';
        }

        // Fallback to WordPress mime type detection
        $filetype = wp_check_filetype( $filename );
        if ( ! empty( $filetype['type'] ) ) {
            return $filetype['type'];
        }

        // Last resort: try finfo if available
        if ( function_exists( 'finfo_open' ) && file_exists( $filepath ) ) {
            $finfo = finfo_open( FILEINFO_MIME_TYPE );
            $mime  = finfo_file( $finfo, $filepath );
            finfo_close( $finfo );
            if ( $mime ) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Update media item
     */
    public static function update_media( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Media ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $attachment = get_post( $input['id'] );

        if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
            return self::errorResponse( 'not_found', 'Media not found', 404 );
        }

        $post_data = [ 'ID' => $input['id'] ];

        if ( isset( $input['title'] ) ) {
            $post_data['post_title'] = $input['title'];
        }
        if ( isset( $input['caption'] ) ) {
            $post_data['post_excerpt'] = $input['caption'];
        }
        if ( isset( $input['description'] ) ) {
            $post_data['post_content'] = $input['description'];
        }

        if ( count( $post_data ) > 1 ) {
            wp_update_post( $post_data );
        }

        if ( isset( $input['alt'] ) ) {
            update_post_meta( $input['id'], '_wp_attachment_image_alt', $input['alt'] );
        }

        $attachment = get_post( $input['id'] );

        return self::entityResponse( 'media', self::format_media( $attachment, true ) );
    }

    /**
     * Delete media item
     */
    public static function delete_media( array $input ): array|\WP_Error {
        if ( empty( $input['id'] ) ) {
            return self::errorResponse( 'missing_id', 'Media ID is required', 400 );
        }

        $input['id'] = (int) $input['id'];
        $attachment = get_post( $input['id'] );

        if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
            return self::errorResponse( 'not_found', 'Media not found', 404 );
        }

        $force = $input['force'] ?? true;
        $deleted = wp_delete_attachment( $input['id'], $force );

        if ( ! $deleted ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete media', 500 );
        }

        return [
            'success' => true,
            'message' => 'Media deleted successfully',
            'id'      => $input['id'],
        ];
    }

    /**
     * Format media item for output
     */
    private static function format_media( \WP_Post $attachment, bool $detailed = false ): array {
        $meta = wp_get_attachment_metadata( $attachment->ID );

        $data = [
            'id'        => $attachment->ID,
            'title'     => $attachment->post_title,
            'url'       => wp_get_attachment_url( $attachment->ID ),
            'mime_type' => $attachment->post_mime_type,
            'date'      => $attachment->post_date,
        ];

        if ( $detailed ) {
            $data['alt']         = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
            $data['caption']     = $attachment->post_excerpt;
            $data['description'] = $attachment->post_content;
            $data['filename']    = basename( get_attached_file( $attachment->ID ) );

            if ( is_array( $meta ) ) {
                $data['width']  = $meta['width'] ?? null;
                $data['height'] = $meta['height'] ?? null;
                $data['filesize'] = filesize( get_attached_file( $attachment->ID ) ) ?: null;
            }

            // Get available sizes for images
            if ( wp_attachment_is_image( $attachment->ID ) ) {
                $sizes = [];
                foreach ( get_intermediate_image_sizes() as $size ) {
                    $image = wp_get_attachment_image_src( $attachment->ID, $size );
                    if ( $image ) {
                        $sizes[ $size ] = [
                            'url'    => $image[0],
                            'width'  => $image[1],
                            'height' => $image[2],
                        ];
                    }
                }
                $data['sizes'] = $sizes;
            }
        }

        return $data;
    }
}
