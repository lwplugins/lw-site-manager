<?php
/**
 * Settings Manager Service - Handles WordPress settings operations
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

class SettingsManager extends AbstractService {

    /**
     * General settings option keys
     */
    private const GENERAL_SETTINGS = [
        'blogname',
        'blogdescription',
        'siteurl',
        'home',
        'admin_email',
        'users_can_register',
        'default_role',
        'timezone_string',
        'date_format',
        'time_format',
        'start_of_week',
        'WPLANG',
    ];

    /**
     * Reading settings option keys
     */
    private const READING_SETTINGS = [
        'posts_per_page',
        'posts_per_rss',
        'rss_use_excerpt',
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'blog_public',
    ];

    /**
     * Discussion settings option keys
     */
    private const DISCUSSION_SETTINGS = [
        'default_pingback_flag',
        'default_ping_status',
        'default_comment_status',
        'require_name_email',
        'comment_registration',
        'close_comments_for_old_posts',
        'close_comments_days_old',
        'thread_comments',
        'thread_comments_depth',
        'page_comments',
        'comments_per_page',
        'default_comments_page',
        'comment_order',
        'comments_notify',
        'moderation_notify',
        'comment_moderation',
        'comment_previously_approved',
        'moderation_keys',
        'disallowed_keys',
        'show_avatars',
        'avatar_rating',
        'avatar_default',
    ];

    /**
     * Permalink settings option keys
     */
    private const PERMALINK_SETTINGS = [
        'permalink_structure',
        'category_base',
        'tag_base',
    ];

    /**
     * Get general settings
     */
    public static function get_general_settings( array $input ): array {
        $settings = [];

        foreach ( self::GENERAL_SETTINGS as $key ) {
            $value = get_option( $key, '' );
            $settings[ $key ] = $value;
        }

        // Add site language info
        $settings['site_language'] = get_locale();

        // Get available roles for reference
        $wp_roles = wp_roles();
        $settings['available_roles'] = array_keys( $wp_roles->roles );

        return [
            'success'  => true,
            'settings' => $settings,
        ];
    }

    /**
     * Update general settings
     */
    public static function update_general_settings( array $input ): array|\WP_Error {
        $updated = [];
        $failed = [];

        // Allowed fields to update (security - don't allow siteurl/home changes via API)
        $allowed_fields = [
            'blogname',
            'blogdescription',
            'admin_email',
            'users_can_register',
            'default_role',
            'timezone_string',
            'date_format',
            'time_format',
            'start_of_week',
            'WPLANG',
        ];

        foreach ( $allowed_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $value = $input[ $key ];

                // Validate specific fields
                if ( $key === 'admin_email' && ! is_email( $value ) ) {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Invalid email address',
                    ];
                    continue;
                }

                if ( $key === 'users_can_register' ) {
                    $value = (int) (bool) $value;
                }

                if ( $key === 'start_of_week' ) {
                    $value = max( 0, min( 6, (int) $value ) );
                }

                if ( $key === 'default_role' ) {
                    $wp_roles = wp_roles();
                    if ( ! isset( $wp_roles->roles[ $value ] ) ) {
                        $failed[] = [
                            'key'     => $key,
                            'message' => 'Invalid role',
                        ];
                        continue;
                    }
                }

                $result = update_option( $key, $value );
                if ( $result || get_option( $key ) === $value ) {
                    $updated[] = $key;
                } else {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Failed to update',
                    ];
                }
            }
        }

        if ( empty( $updated ) && ! empty( $failed ) ) {
            return self::errorResponse(
                'update_failed',
                'Failed to update settings: ' . implode( ', ', array_column( $failed, 'key' ) ),
                400
            );
        }

        return [
            'success' => true,
            'message' => sprintf( 'Updated %d setting(s)', count( $updated ) ),
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }

    /**
     * Get reading settings
     */
    public static function get_reading_settings( array $input ): array {
        $settings = [];

        foreach ( self::READING_SETTINGS as $key ) {
            $value = get_option( $key, '' );
            $settings[ $key ] = $value;
        }

        // Add human-readable info
        if ( $settings['show_on_front'] === 'page' ) {
            if ( $settings['page_on_front'] ) {
                $page = get_post( $settings['page_on_front'] );
                $settings['homepage_title'] = $page ? $page->post_title : '';
            }
            if ( $settings['page_for_posts'] ) {
                $page = get_post( $settings['page_for_posts'] );
                $settings['posts_page_title'] = $page ? $page->post_title : '';
            }
        }

        return [
            'success'  => true,
            'settings' => $settings,
        ];
    }

    /**
     * Update reading settings
     */
    public static function update_reading_settings( array $input ): array|\WP_Error {
        $updated = [];
        $failed = [];

        $allowed_fields = [
            'posts_per_page',
            'posts_per_rss',
            'rss_use_excerpt',
            'show_on_front',
            'page_on_front',
            'page_for_posts',
            'blog_public',
        ];

        foreach ( $allowed_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $value = $input[ $key ];

                // Validate specific fields
                if ( in_array( $key, [ 'posts_per_page', 'posts_per_rss' ], true ) ) {
                    $value = max( 1, min( 100, (int) $value ) );
                }

                if ( $key === 'show_on_front' && ! in_array( $value, [ 'posts', 'page' ], true ) ) {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Invalid value, must be "posts" or "page"',
                    ];
                    continue;
                }

                if ( in_array( $key, [ 'page_on_front', 'page_for_posts' ], true ) && $value ) {
                    $page = get_post( (int) $value );
                    if ( ! $page || $page->post_type !== 'page' ) {
                        $failed[] = [
                            'key'     => $key,
                            'message' => 'Invalid page ID',
                        ];
                        continue;
                    }
                }

                if ( in_array( $key, [ 'rss_use_excerpt', 'blog_public' ], true ) ) {
                    $value = (int) (bool) $value;
                }

                $result = update_option( $key, $value );
                if ( $result || get_option( $key ) == $value ) {
                    $updated[] = $key;
                } else {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Failed to update',
                    ];
                }
            }
        }

        if ( empty( $updated ) && ! empty( $failed ) ) {
            return self::errorResponse(
                'update_failed',
                'Failed to update settings: ' . implode( ', ', array_column( $failed, 'key' ) ),
                400
            );
        }

        return [
            'success' => true,
            'message' => sprintf( 'Updated %d setting(s)', count( $updated ) ),
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }

    /**
     * Get discussion settings
     */
    public static function get_discussion_settings( array $input ): array {
        $settings = [];

        foreach ( self::DISCUSSION_SETTINGS as $key ) {
            $value = get_option( $key, '' );
            $settings[ $key ] = $value;
        }

        return [
            'success'  => true,
            'settings' => $settings,
        ];
    }

    /**
     * Update discussion settings
     */
    public static function update_discussion_settings( array $input ): array|\WP_Error {
        $updated = [];
        $failed = [];

        $boolean_fields = [
            'default_pingback_flag',
            'default_ping_status',
            'default_comment_status',
            'require_name_email',
            'comment_registration',
            'close_comments_for_old_posts',
            'thread_comments',
            'page_comments',
            'comments_notify',
            'moderation_notify',
            'comment_moderation',
            'comment_previously_approved',
            'show_avatars',
        ];

        $integer_fields = [
            'close_comments_days_old',
            'thread_comments_depth',
            'comments_per_page',
        ];

        $string_fields = [
            'default_comments_page',
            'comment_order',
            'moderation_keys',
            'disallowed_keys',
            'avatar_rating',
            'avatar_default',
        ];

        $allowed_fields = array_merge( $boolean_fields, $integer_fields, $string_fields );

        foreach ( $allowed_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $value = $input[ $key ];

                if ( in_array( $key, $boolean_fields, true ) ) {
                    // Handle 'open'/'closed' for status fields
                    if ( in_array( $key, [ 'default_ping_status', 'default_comment_status' ], true ) ) {
                        $value = in_array( $value, [ 'open', '1', 1, true ], true ) ? 'open' : 'closed';
                    } else {
                        $value = (int) (bool) $value;
                    }
                }

                if ( in_array( $key, $integer_fields, true ) ) {
                    $value = (int) $value;
                }

                if ( $key === 'comment_order' && ! in_array( $value, [ 'asc', 'desc' ], true ) ) {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Invalid value, must be "asc" or "desc"',
                    ];
                    continue;
                }

                if ( $key === 'default_comments_page' && ! in_array( $value, [ 'newest', 'oldest' ], true ) ) {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Invalid value, must be "newest" or "oldest"',
                    ];
                    continue;
                }

                $result = update_option( $key, $value );
                if ( $result || get_option( $key ) == $value ) {
                    $updated[] = $key;
                } else {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Failed to update',
                    ];
                }
            }
        }

        if ( empty( $updated ) && ! empty( $failed ) ) {
            return self::errorResponse(
                'update_failed',
                'Failed to update settings: ' . implode( ', ', array_column( $failed, 'key' ) ),
                400
            );
        }

        return [
            'success' => true,
            'message' => sprintf( 'Updated %d setting(s)', count( $updated ) ),
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }

    /**
     * Get permalink settings
     */
    public static function get_permalink_settings( array $input ): array {
        $settings = [];

        foreach ( self::PERMALINK_SETTINGS as $key ) {
            $value = get_option( $key, '' );
            $settings[ $key ] = $value;
        }

        // Add common permalink structures for reference
        $settings['common_structures'] = [
            'plain'      => '',
            'day_name'   => '/%year%/%monthnum%/%day%/%postname%/',
            'month_name' => '/%year%/%monthnum%/%postname%/',
            'numeric'    => '/archives/%post_id%',
            'post_name'  => '/%postname%/',
        ];

        return [
            'success'  => true,
            'settings' => $settings,
        ];
    }

    /**
     * Update permalink settings
     */
    public static function update_permalink_settings( array $input ): array|\WP_Error {
        $updated = [];
        $failed = [];

        $allowed_fields = [
            'permalink_structure',
            'category_base',
            'tag_base',
        ];

        foreach ( $allowed_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $value = sanitize_text_field( $input[ $key ] );

                $result = update_option( $key, $value );
                if ( $result || get_option( $key ) === $value ) {
                    $updated[] = $key;
                } else {
                    $failed[] = [
                        'key'     => $key,
                        'message' => 'Failed to update',
                    ];
                }
            }
        }

        // Flush rewrite rules if permalink structure changed
        if ( in_array( 'permalink_structure', $updated, true ) ) {
            flush_rewrite_rules();
        }

        if ( empty( $updated ) && ! empty( $failed ) ) {
            return self::errorResponse(
                'update_failed',
                'Failed to update settings: ' . implode( ', ', array_column( $failed, 'key' ) ),
                400
            );
        }

        return [
            'success' => true,
            'message' => sprintf( 'Updated %d setting(s)', count( $updated ) ),
            'updated' => $updated,
            'failed'  => $failed,
        ];
    }
}
