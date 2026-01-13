<?php
/**
 * Update Manager Service - Handles all WordPress updates
 */

declare(strict_types=1);

namespace WPSiteManager\Services;

use WPSiteManager\Handlers\ErrorHandler;

class UpdateManager extends AbstractService {

    /**
     * Check for available updates
     */
    public static function check_updates( array $input ): array {
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $type = $input['type'] ?? 'all';
        $force = $input['force_refresh'] ?? false;

        if ( $force ) {
            // Only delete update transients, not the entire plugin/theme cache
            delete_site_transient( 'update_plugins' );
            delete_site_transient( 'update_themes' );
            delete_site_transient( 'update_core' );
            wp_update_plugins();
            wp_update_themes();
            wp_version_check();
        }

        $result = [
            'core'          => [
                'current'    => get_bloginfo( 'version' ),
                'available'  => get_bloginfo( 'version' ),
                'has_update' => false,
            ],
            'plugins'       => [],
            'themes'        => [],
            'total_updates' => 0,
        ];

        // Core updates
        if ( in_array( $type, [ 'all', 'core' ], true ) ) {
            $core_updates = get_core_updates();
            $result['core'] = [
                'current'    => get_bloginfo( 'version' ),
                'available'  => ! empty( $core_updates[0]->version ) ? $core_updates[0]->version : get_bloginfo( 'version' ),
                'has_update' => ! empty( $core_updates[0] ) && $core_updates[0]->response === 'upgrade',
            ];
            if ( $result['core']['has_update'] ) {
                $result['total_updates']++;
            }
        }

        // Plugin updates
        if ( in_array( $type, [ 'all', 'plugins' ], true ) ) {
            $plugin_updates = get_site_transient( 'update_plugins' );
            if ( ! empty( $plugin_updates->response ) ) {
                $all_plugins = get_plugins();
                foreach ( $plugin_updates->response as $slug => $data ) {
                    $plugin_data = $all_plugins[ $slug ] ?? [];
                    $result['plugins'][] = [
                        'slug'      => $slug,
                        'name'      => $plugin_data['Name'] ?? $slug,
                        'current'   => $plugin_data['Version'] ?? 'unknown',
                        'available' => $data->new_version ?? 'unknown',
                    ];
                    $result['total_updates']++;
                }
            }
        }

        // Theme updates
        if ( in_array( $type, [ 'all', 'themes' ], true ) ) {
            $theme_updates = get_site_transient( 'update_themes' );
            if ( ! empty( $theme_updates->response ) ) {
                foreach ( $theme_updates->response as $slug => $data ) {
                    $theme = wp_get_theme( $slug );
                    $result['themes'][] = [
                        'slug'      => $slug,
                        'name'      => $theme->get( 'Name' ),
                        'current'   => $theme->get( 'Version' ),
                        'available' => $data['new_version'] ?? 'unknown',
                    ];
                    $result['total_updates']++;
                }
            }
        }

        return $result;
    }

    /**
     * Update a single plugin
     */
    public static function update_plugin( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'plugin', 'Plugin slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $plugin_slug = $input['plugin'];

        // Refresh update transient to ensure we have the latest info
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();

        // Get current version before update
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_slug );
        $old_version = $plugin_data['Version'] ?? 'unknown';

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Perform update with silent upgrader
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );
        $result = $upgrader->upgrade( $plugin_slug );

        // Get captured errors
        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return self::updateResultResponse( false, $result->get_error_message(), $old_version, $old_version, $php_errors );
        }

        if ( $result === false ) {
            return self::updateResultResponse(
                false,
                __( 'Plugin update failed or no update available', 'wp-site-manager' ),
                $old_version,
                $old_version,
                $php_errors
            );
        }

        // Get new version (clear file stat cache only)
        clearstatcache();
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_slug, false, false );
        $new_version = $plugin_data['Version'] ?? $old_version;

        // Check for fatal errors after update
        $has_fatal = self::hasFatalErrors( $php_errors );

        return self::updateResultResponse(
            ! $has_fatal,
            $has_fatal
                ? __( 'Plugin updated but PHP errors detected', 'wp-site-manager' )
                : sprintf( __( 'Plugin updated successfully: %s → %s', 'wp-site-manager' ), $old_version, $new_version ),
            $old_version,
            $new_version,
            $php_errors
        );
    }

    /**
     * Update a single theme
     */
    public static function update_theme( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'theme', 'Theme slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $theme_slug = $input['theme'];

        // Refresh update transient to ensure we have the latest info
        delete_site_transient( 'update_themes' );
        wp_update_themes();

        // Get current version
        $theme = wp_get_theme( $theme_slug );
        $old_version = $theme->get( 'Version' );

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Perform update
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Theme_Upgrader( $skin );
        $result = $upgrader->upgrade( $theme_slug );

        // Get captured errors
        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return self::updateResultResponse( false, $result->get_error_message(), $old_version, $old_version, $php_errors );
        }

        if ( $result === false ) {
            return self::updateResultResponse(
                false,
                __( 'Theme update failed or no update available', 'wp-site-manager' ),
                $old_version,
                $old_version,
                $php_errors
            );
        }

        // Get new version (clear file stat cache only)
        clearstatcache();
        $theme = wp_get_theme( $theme_slug );
        $new_version = $theme->get( 'Version' );

        $has_fatal = self::hasFatalErrors( $php_errors );

        return self::updateResultResponse(
            ! $has_fatal,
            $has_fatal
                ? __( 'Theme updated but PHP errors detected', 'wp-site-manager' )
                : sprintf( __( 'Theme updated successfully: %s → %s', 'wp-site-manager' ), $old_version, $new_version ),
            $old_version,
            $new_version,
            $php_errors
        );
    }

    /**
     * Update WordPress core
     */
    public static function update_core( array $input ): array {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';

        $old_version = get_bloginfo( 'version' );
        $minor_only = $input['minor_only'] ?? false;

        // Get available updates
        $updates = get_core_updates();
        if ( empty( $updates ) || $updates[0]->response !== 'upgrade' ) {
            return self::updateResultResponse(
                true,
                __( 'WordPress is already up to date', 'wp-site-manager' ),
                $old_version,
                $old_version,
                []
            );
        }

        $update = $updates[0];

        // Check minor only constraint
        if ( $minor_only ) {
            $current_parts = explode( '.', $old_version );
            $new_parts = explode( '.', $update->version );

            if ( $current_parts[0] !== $new_parts[0] || $current_parts[1] !== $new_parts[1] ) {
                return self::updateResultResponse(
                    false,
                    sprintf(
                        __( 'Major update available (%s) but minor_only is enabled', 'wp-site-manager' ),
                        $update->version
                    ),
                    $old_version,
                    $old_version,
                    []
                );
            }
        }

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Perform update
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Core_Upgrader( $skin );
        $result = $upgrader->upgrade( $update );

        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return self::updateResultResponse( false, $result->get_error_message(), $old_version, $old_version, $php_errors );
        }

        $new_version = get_bloginfo( 'version' );
        $has_fatal = self::hasFatalErrors( $php_errors );

        return self::updateResultResponse(
            ! $has_fatal,
            $has_fatal
                ? __( 'Core updated but PHP errors detected', 'wp-site-manager' )
                : sprintf( __( 'WordPress updated: %s → %s', 'wp-site-manager' ), $old_version, $new_version ),
            $old_version,
            $new_version,
            $php_errors
        );
    }

    /**
     * Update everything
     */
    public static function update_all( array $input ): array {
        $include_core = $input['include_core'] ?? false;
        $include_plugins = $input['include_plugins'] ?? true;
        $include_themes = $input['include_themes'] ?? true;
        $stop_on_error = $input['stop_on_error'] ?? true;

        // Save active plugins list before updates
        $active_plugins_before = get_option( 'active_plugins', [] );

        $result = [
            'success'       => true,
            'summary'       => '',
            'updated'       => [
                'core'    => false,
                'plugins' => [],
                'themes'  => [],
            ],
            'failed'        => [],
            'php_errors'    => [],
            'stopped_early' => false,
        ];

        $total_updated = 0;
        $total_failed = 0;

        // Update plugins
        if ( $include_plugins ) {
            $updates = self::check_updates( [ 'type' => 'plugins', 'force_refresh' => true ] );
            foreach ( $updates['plugins'] as $plugin ) {
                $update_result = self::update_plugin( [ 'plugin' => $plugin['slug'] ] );

                // Handle WP_Error return
                if ( is_wp_error( $update_result ) ) {
                    $result['failed'][] = [
                        'type'    => 'plugin',
                        'slug'    => $plugin['slug'],
                        'message' => $update_result->get_error_message(),
                    ];
                    $total_failed++;
                    continue;
                }

                if ( $update_result['success'] ) {
                    $result['updated']['plugins'][] = [
                        'slug'        => $plugin['slug'],
                        'name'        => $plugin['name'],
                        'old_version' => $update_result['old_version'],
                        'new_version' => $update_result['new_version'],
                    ];
                    $total_updated++;
                } else {
                    $result['failed'][] = [
                        'type'    => 'plugin',
                        'slug'    => $plugin['slug'],
                        'message' => $update_result['message'],
                    ];
                    $total_failed++;
                }

                // Collect PHP errors
                if ( ! empty( $update_result['php_errors'] ) ) {
                    $result['php_errors'] = array_merge( $result['php_errors'], $update_result['php_errors'] );

                    if ( $stop_on_error ) {
                        $result['stopped_early'] = true;
                        $result['success'] = false;
                        break;
                    }
                }
            }
        }

        // Update themes (if not stopped)
        if ( $include_themes && ! $result['stopped_early'] ) {
            $updates = self::check_updates( [ 'type' => 'themes', 'force_refresh' => true ] );
            foreach ( $updates['themes'] as $theme ) {
                $update_result = self::update_theme( [ 'theme' => $theme['slug'] ] );

                // Handle WP_Error return
                if ( is_wp_error( $update_result ) ) {
                    $result['failed'][] = [
                        'type'    => 'theme',
                        'slug'    => $theme['slug'],
                        'message' => $update_result->get_error_message(),
                    ];
                    $total_failed++;
                    continue;
                }

                if ( $update_result['success'] ) {
                    $result['updated']['themes'][] = [
                        'slug'        => $theme['slug'],
                        'name'        => $theme['name'],
                        'old_version' => $update_result['old_version'],
                        'new_version' => $update_result['new_version'],
                    ];
                    $total_updated++;
                } else {
                    $result['failed'][] = [
                        'type'    => 'theme',
                        'slug'    => $theme['slug'],
                        'message' => $update_result['message'],
                    ];
                    $total_failed++;
                }

                if ( ! empty( $update_result['php_errors'] ) ) {
                    $result['php_errors'] = array_merge( $result['php_errors'], $update_result['php_errors'] );

                    if ( $stop_on_error ) {
                        $result['stopped_early'] = true;
                        $result['success'] = false;
                        break;
                    }
                }
            }
        }

        // Update core (if not stopped)
        if ( $include_core && ! $result['stopped_early'] ) {
            $update_result = self::update_core( $input );

            if ( $update_result['success'] && $update_result['old_version'] !== $update_result['new_version'] ) {
                $result['updated']['core'] = true;
                $total_updated++;
            } elseif ( ! $update_result['success'] ) {
                $result['failed'][] = [
                    'type'    => 'core',
                    'slug'    => 'wordpress',
                    'message' => $update_result['message'],
                ];
                $total_failed++;
            }

            if ( ! empty( $update_result['php_errors'] ) ) {
                $result['php_errors'] = array_merge( $result['php_errors'], $update_result['php_errors'] );
            }
        }

        // Restore active plugins that may have been deactivated during updates
        $active_plugins_after = get_option( 'active_plugins', [] );
        if ( count( $active_plugins_after ) < count( $active_plugins_before ) ) {
            update_option( 'active_plugins', $active_plugins_before );
        }

        // Build summary
        $result['summary'] = sprintf(
            __( 'Updated: %d, Failed: %d, PHP Errors: %d', 'wp-site-manager' ),
            $total_updated,
            $total_failed,
            count( $result['php_errors'] )
        );

        if ( $result['stopped_early'] ) {
            $result['summary'] .= ' ' . __( '(Stopped early due to PHP errors)', 'wp-site-manager' );
        }

        $result['success'] = $total_failed === 0 && empty( $result['php_errors'] );

        return $result;
    }

    /**
     * List all plugins
     */
    public static function list_plugins( array $input ): array {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $status = $input['status'] ?? 'all';
        $all_plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );

        $plugins = [];
        foreach ( $all_plugins as $slug => $data ) {
            $is_active = in_array( $slug, $active_plugins, true );

            if ( $status === 'active' && ! $is_active ) {
                continue;
            }
            if ( $status === 'inactive' && $is_active ) {
                continue;
            }

            $plugins[] = [
                'slug'        => $slug,
                'name'        => $data['Name'],
                'version'     => $data['Version'],
                'author'      => $data['Author'],
                'description' => $data['Description'],
                'active'      => $is_active,
            ];
        }

        $active_count = count( array_filter( $plugins, fn( $p ) => $p['active'] ) );

        return [
            'plugins'        => $plugins,
            'total'          => count( $plugins ),
            'active_count'   => $active_count,
            'inactive_count' => count( $plugins ) - $active_count,
        ];
    }

    /**
     * Activate a plugin
     */
    public static function activate_plugin( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'plugin', 'Plugin slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin = $input['plugin'];

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        $result = activate_plugin( $plugin );

        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return [
                'success'    => false,
                'message'    => $result->get_error_message(),
                'php_errors' => $php_errors,
            ];
        }

        $has_fatal = self::hasFatalErrors( $php_errors );

        return [
            'success'    => ! $has_fatal,
            'message'    => $has_fatal
                ? __( 'Plugin activated but PHP errors detected', 'wp-site-manager' )
                : __( 'Plugin activated successfully', 'wp-site-manager' ),
            'php_errors' => $php_errors,
        ];
    }

    /**
     * Deactivate a plugin
     */
    public static function deactivate_plugin( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'plugin', 'Plugin slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugin = $input['plugin'];

        deactivate_plugins( $plugin );

        return self::successResponse( [], __( 'Plugin deactivated successfully', 'wp-site-manager' ) );
    }

    /**
     * Delete a plugin
     */
    public static function delete_plugin( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'plugin', 'Plugin file path is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $plugin = $input['plugin'];

        // Check if plugin exists
        $all_plugins = get_plugins();
        if ( ! isset( $all_plugins[ $plugin ] ) ) {
            return self::errorResponse( 'plugin_not_found', __( 'Plugin not found', 'wp-site-manager' ), 404 );
        }

        // Deactivate first if active
        if ( is_plugin_active( $plugin ) ) {
            deactivate_plugins( $plugin );
        }

        // Delete the plugin
        $result = delete_plugins( [ $plugin ] );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'delete_failed', $result->get_error_message(), 500 );
        }

        return self::successResponse( [], __( 'Plugin deleted successfully', 'wp-site-manager' ) );
    }

    /**
     * List all themes
     */
    public static function list_themes( array $input = [] ): array {
        $themes = wp_get_themes();
        $active_theme = get_stylesheet();

        $result = [];
        foreach ( $themes as $slug => $theme ) {
            $result[] = [
                'slug'        => $slug,
                'name'        => $theme->get( 'Name' ),
                'version'     => $theme->get( 'Version' ),
                'author'      => $theme->get( 'Author' ),
                'description' => $theme->get( 'Description' ),
                'active'      => $slug === $active_theme,
                'parent'      => $theme->parent() ? $theme->parent()->get_stylesheet() : null,
            ];
        }

        return [
            'themes'       => $result,
            'total'        => count( $result ),
            'active_theme' => $active_theme,
        ];
    }

    /**
     * Activate a theme
     */
    public static function activate_theme( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'theme', 'Theme slug is required' );
        if ( $error ) {
            return $error;
        }

        $theme_slug = $input['theme'];

        $theme = wp_get_theme( $theme_slug );
        if ( ! $theme->exists() ) {
            return self::errorResponse( 'theme_not_found', __( 'Theme not found', 'wp-site-manager' ), 404 );
        }

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        switch_theme( $theme_slug );

        $php_errors = $error_handler->stop_monitoring();
        $has_fatal = self::hasFatalErrors( $php_errors );

        return [
            'success'    => ! $has_fatal,
            'message'    => $has_fatal
                ? __( 'Theme activated but PHP errors detected', 'wp-site-manager' )
                : __( 'Theme activated successfully', 'wp-site-manager' ),
            'php_errors' => $php_errors,
        ];
    }

    /**
     * Delete a theme
     */
    public static function delete_theme( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'theme', 'Theme slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/theme.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $theme_slug = $input['theme'];

        // Check if theme exists
        $theme = wp_get_theme( $theme_slug );
        if ( ! $theme->exists() ) {
            return self::errorResponse( 'theme_not_found', __( 'Theme not found', 'wp-site-manager' ), 404 );
        }

        // Cannot delete active theme
        if ( get_stylesheet() === $theme_slug || get_template() === $theme_slug ) {
            return self::errorResponse( 'cannot_delete_active', __( 'Cannot delete the active theme', 'wp-site-manager' ), 400 );
        }

        // Delete the theme
        $result = delete_theme( $theme_slug );

        if ( is_wp_error( $result ) ) {
            return self::errorResponse( 'delete_failed', $result->get_error_message(), 500 );
        }

        return self::successResponse( [], __( 'Theme deleted successfully', 'wp-site-manager' ) );
    }

    /**
     * Install a plugin from WordPress.org repository
     */
    public static function install_plugin( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'slug', 'Plugin slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $slug = $input['slug'];
        $activate = $input['activate'] ?? false;

        // Check if plugin already exists
        $all_plugins = get_plugins();
        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            if ( strpos( $plugin_file, $slug . '/' ) === 0 || $plugin_file === $slug . '.php' ) {
                return self::errorResponse(
                    'plugin_exists',
                    sprintf( __( 'Plugin "%s" is already installed', 'wp-site-manager' ), $slug ),
                    400
                );
            }
        }

        // Get plugin info from WordPress.org
        $api = plugins_api( 'plugin_information', [
            'slug'   => $slug,
            'fields' => [
                'sections' => false,
            ],
        ] );

        if ( is_wp_error( $api ) ) {
            return $api;
        }

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Install the plugin
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );
        $result = $upgrader->install( $api->download_link );

        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return [
                'success'    => false,
                'message'    => $result->get_error_message(),
                'php_errors' => $php_errors,
            ];
        }

        if ( $result === false ) {
            return [
                'success'    => false,
                'message'    => __( 'Plugin installation failed', 'wp-site-manager' ),
                'php_errors' => $php_errors,
            ];
        }

        // Find the installed plugin file
        wp_clean_plugins_cache();
        $all_plugins = get_plugins();
        $plugin_file = null;

        foreach ( $all_plugins as $file => $data ) {
            if ( strpos( $file, $slug . '/' ) === 0 || $file === $slug . '.php' ) {
                $plugin_file = $file;
                break;
            }
        }

        $has_fatal = self::hasFatalErrors( $php_errors );

        // Activate if requested
        $activated = false;
        if ( $activate && $plugin_file && ! $has_fatal ) {
            $activate_result = activate_plugin( $plugin_file );
            $activated = ! is_wp_error( $activate_result );
        }

        return [
            'success'    => ! $has_fatal,
            'message'    => $has_fatal
                ? __( 'Plugin installed but PHP errors detected', 'wp-site-manager' )
                : sprintf( __( 'Plugin "%s" installed successfully (v%s)', 'wp-site-manager' ), $api->name, $api->version ),
            'plugin'     => $plugin_file,
            'name'       => $api->name,
            'version'    => $api->version,
            'activated'  => $activated,
            'php_errors' => $php_errors,
        ];
    }

    /**
     * Install a theme from WordPress.org repository
     */
    public static function install_theme( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'slug', 'Theme slug is required' );
        if ( $error ) {
            return $error;
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $slug = $input['slug'];
        $activate = $input['activate'] ?? false;

        // Check if theme already exists
        $theme = wp_get_theme( $slug );
        if ( $theme->exists() ) {
            return self::errorResponse(
                'theme_exists',
                sprintf( __( 'Theme "%s" is already installed', 'wp-site-manager' ), $slug ),
                400
            );
        }

        // Get theme info from WordPress.org
        $api = themes_api( 'theme_information', [
            'slug'   => $slug,
            'fields' => [
                'sections' => false,
            ],
        ] );

        if ( is_wp_error( $api ) ) {
            return $api;
        }

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Install the theme
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Theme_Upgrader( $skin );
        $result = $upgrader->install( $api->download_link );

        $php_errors = $error_handler->stop_monitoring();

        if ( is_wp_error( $result ) ) {
            return [
                'success'    => false,
                'message'    => $result->get_error_message(),
                'php_errors' => $php_errors,
            ];
        }

        if ( $result === false ) {
            return [
                'success'    => false,
                'message'    => __( 'Theme installation failed', 'wp-site-manager' ),
                'php_errors' => $php_errors,
            ];
        }

        $has_fatal = self::hasFatalErrors( $php_errors );

        // Activate if requested
        $activated = false;
        if ( $activate && ! $has_fatal ) {
            switch_theme( $slug );
            $activated = get_stylesheet() === $slug;
        }

        // Get installed theme info
        wp_clean_themes_cache();
        $theme = wp_get_theme( $slug );

        return [
            'success'    => ! $has_fatal,
            'message'    => $has_fatal
                ? __( 'Theme installed but PHP errors detected', 'wp-site-manager' )
                : sprintf( __( 'Theme "%s" installed successfully (v%s)', 'wp-site-manager' ), $theme->get( 'Name' ), $theme->get( 'Version' ) ),
            'theme'      => $slug,
            'name'       => $theme->get( 'Name' ),
            'version'    => $theme->get( 'Version' ),
            'activated'  => $activated,
            'php_errors' => $php_errors,
        ];
    }

    /**
     * Check if PHP errors array contains fatal errors
     */
    private static function hasFatalErrors( array $php_errors ): bool {
        return ! empty( array_filter( $php_errors, fn( $e ) => str_contains( $e, 'Fatal' ) || str_contains( $e, 'Error' ) ) );
    }
}
