<?php
/**
 * Plugin Database Updater Service
 *
 * Handles database updates for plugins like WooCommerce, Elementor, etc.
 * These plugins have their own DB version that needs updating after plugin updates.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services;

use LightweightPlugins\SiteManager\Handlers\ErrorHandler;

class PluginDatabaseUpdater extends AbstractService {

    /**
     * Supported plugins for DB updates
     */
    public const SUPPORTED_PLUGINS = [
        'woocommerce/woocommerce.php' => [
            'name'              => 'WooCommerce',
            'db_version_option' => 'woocommerce_db_version',
            'update_callback'   => 'update_woocommerce_db',
            'check_callback'    => 'check_woocommerce_db',
        ],
        'elementor/elementor.php' => [
            'name'              => 'Elementor',
            'db_version_option' => 'elementor_version',
            'update_callback'   => 'update_elementor_db',
            'check_callback'    => 'check_elementor_db',
        ],
        'elementor-pro/elementor-pro.php' => [
            'name'              => 'Elementor Pro',
            'db_version_option' => 'elementor_pro_version',
            'update_callback'   => 'update_elementor_pro_db',
            'check_callback'    => 'check_elementor_pro_db',
        ],
    ];

    /**
     * Check for pending plugin database updates
     */
    public static function check_updates( array $input ): array {
        $updates = [];

        foreach ( self::SUPPORTED_PLUGINS as $slug => $config ) {
            $check_method = $config['check_callback'];
            $result = self::$check_method();

            if ( $result['needs_update'] ) {
                $updates[ $slug ] = [
                    'name'           => $config['name'],
                    'slug'           => $slug,
                    'db_version'     => $result['current_version'],
                    'new_db_version' => $result['new_version'],
                    'needs_update'   => true,
                ];
            }
        }

        return [
            'updates'       => $updates,
            'total_updates' => count( $updates ),
            'supported'     => array_keys( self::SUPPORTED_PLUGINS ),
        ];
    }

    /**
     * Update a specific plugin's database
     */
    public static function update_plugin_db( array $input ): array|\WP_Error {
        $error = self::validateRequiredField( $input, 'plugin', 'Plugin slug is required' );
        if ( $error ) {
            return $error;
        }

        $plugin_slug = $input['plugin'];

        if ( ! isset( self::SUPPORTED_PLUGINS[ $plugin_slug ] ) ) {
            return self::errorResponse(
                'unsupported_plugin',
                sprintf( __( 'Plugin %s is not supported for DB updates', 'lw-site-manager' ), $plugin_slug ),
                400
            );
        }

        $config = self::SUPPORTED_PLUGINS[ $plugin_slug ];

        // Check if plugin is active
        if ( ! is_plugin_active( $plugin_slug ) ) {
            return self::errorResponse(
                'plugin_inactive',
                sprintf( __( 'Plugin %s is not active', 'lw-site-manager' ), $config['name'] ),
                400
            );
        }

        // Start error monitoring
        $error_handler = ErrorHandler::instance();
        $error_handler->start_monitoring();

        // Get current version before update
        $check_method = $config['check_callback'];
        $before = self::$check_method();

        // Perform the update
        $update_method = $config['update_callback'];
        $result = self::$update_method();

        // Get version after update
        $after = self::$check_method();

        // Get captured errors
        $php_errors = $error_handler->stop_monitoring();
        $has_errors = ! empty( array_filter( $php_errors, fn( $e ) => str_contains( $e, 'Fatal' ) || str_contains( $e, 'Error' ) ) );

        return self::updateResultResponse(
            $result['success'] && ! $has_errors,
            $result['message'],
            $before['current_version'],
            $after['current_version'],
            $php_errors
        );
    }

    /**
     * Update all pending plugin databases
     */
    public static function update_all( array $input ): array {
        $stop_on_error = $input['stop_on_error'] ?? true;

        $results = [
            'success'       => true,
            'updated'       => [],
            'failed'        => [],
            'php_errors'    => [],
            'stopped_early' => false,
        ];

        $pending = self::check_updates( [] );

        foreach ( $pending['updates'] as $slug => $update_info ) {
            $result = self::update_plugin_db( [ 'plugin' => $slug ] );

            // Handle WP_Error
            if ( is_wp_error( $result ) ) {
                $results['failed'][] = [
                    'plugin'  => $update_info['name'],
                    'slug'    => $slug,
                    'message' => $result->get_error_message(),
                ];
                $results['success'] = false;
                continue;
            }

            if ( $result['success'] ) {
                $results['updated'][] = [
                    'plugin'      => $update_info['name'],
                    'slug'        => $slug,
                    'old_version' => $result['old_version'],
                    'new_version' => $result['new_version'],
                ];
            } else {
                $results['failed'][] = [
                    'plugin'  => $update_info['name'],
                    'slug'    => $slug,
                    'message' => $result['message'],
                ];
                $results['success'] = false;
            }

            if ( ! empty( $result['php_errors'] ) ) {
                $results['php_errors'] = array_merge( $results['php_errors'], $result['php_errors'] );

                if ( $stop_on_error ) {
                    $results['stopped_early'] = true;
                    break;
                }
            }
        }

        $results['summary'] = sprintf(
            __( 'Updated: %d, Failed: %d', 'lw-site-manager' ),
            count( $results['updated'] ),
            count( $results['failed'] )
        );

        return $results;
    }

    // =========================================================================
    // WooCommerce DB Update Methods
    // =========================================================================

    /**
     * Check WooCommerce database version
     */
    private static function check_woocommerce_db(): array {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return [
                'needs_update'    => false,
                'current_version' => null,
                'new_version'     => null,
                'message'         => 'WooCommerce not installed',
            ];
        }

        $current_db_version = get_option( 'woocommerce_db_version', null );
        $plugin_version = defined( 'WC_VERSION' ) ? WC_VERSION : null;

        // WooCommerce stores pending updates in options
        $pending_updates = \WC_Install::get_db_update_callbacks();
        $needs_update = ! empty( $pending_updates ) || version_compare( $current_db_version, $plugin_version, '<' );

        return [
            'needs_update'    => $needs_update,
            'current_version' => $current_db_version,
            'new_version'     => $plugin_version,
            'pending_updates' => count( $pending_updates ),
        ];
    }

    /**
     * Update WooCommerce database
     */
    private static function update_woocommerce_db(): array {
        if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Install' ) ) {
            return [
                'success' => false,
                'message' => 'WooCommerce not available',
            ];
        }

        try {
            // Trigger WooCommerce DB update
            if ( method_exists( 'WC_Install', 'update' ) ) {
                \WC_Install::update();
            }

            // Run background updates if available
            if ( class_exists( 'WC_Background_Updater' ) ) {
                $updater = new \WC_Background_Updater();

                $callbacks = \WC_Install::get_db_update_callbacks();
                foreach ( $callbacks as $version => $update_callbacks ) {
                    foreach ( $update_callbacks as $callback ) {
                        if ( is_callable( $callback ) ) {
                            call_user_func( $callback );
                        }
                    }
                }
            }

            // Update DB version option
            if ( defined( 'WC_VERSION' ) ) {
                update_option( 'woocommerce_db_version', WC_VERSION );
            }

            return [
                'success' => true,
                'message' => __( 'WooCommerce database updated successfully', 'lw-site-manager' ),
            ];

        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // Elementor DB Update Methods
    // =========================================================================

    /**
     * Check Elementor database version
     */
    private static function check_elementor_db(): array {
        if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
            return [
                'needs_update'    => false,
                'current_version' => null,
                'new_version'     => null,
                'message'         => 'Elementor not installed',
            ];
        }

        $current_db_version = get_option( 'elementor_version', null );
        $plugin_version = ELEMENTOR_VERSION;

        // Check if upgrade is needed
        $needs_update = false;
        if ( class_exists( '\Elementor\Core\Upgrade\Manager' ) ) {
            $upgrade_manager = \Elementor\Plugin::$instance->upgrade;
            if ( $upgrade_manager && method_exists( $upgrade_manager, 'should_upgrade' ) ) {
                $needs_update = $upgrade_manager->should_upgrade();
            }
        }

        // Fallback check
        if ( ! $needs_update && $current_db_version ) {
            $needs_update = version_compare( $current_db_version, $plugin_version, '<' );
        }

        return [
            'needs_update'    => $needs_update,
            'current_version' => $current_db_version,
            'new_version'     => $plugin_version,
        ];
    }

    /**
     * Update Elementor database
     */
    private static function update_elementor_db(): array {
        if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
            return [
                'success' => false,
                'message' => 'Elementor not available',
            ];
        }

        try {
            // Trigger Elementor upgrade
            if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->upgrade ) ) {
                $upgrade_manager = \Elementor\Plugin::$instance->upgrade;

                if ( method_exists( $upgrade_manager, 'do_upgrade' ) ) {
                    $upgrade_manager->do_upgrade();
                }
            }

            // Update version option
            update_option( 'elementor_version', ELEMENTOR_VERSION );

            // Clear Elementor cache
            if ( class_exists( '\Elementor\Plugin' ) ) {
                \Elementor\Plugin::$instance->files_manager->clear_cache();
            }

            return [
                'success' => true,
                'message' => __( 'Elementor database updated successfully', 'lw-site-manager' ),
            ];

        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // Elementor Pro DB Update Methods
    // =========================================================================

    /**
     * Check Elementor Pro database version
     */
    private static function check_elementor_pro_db(): array {
        if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
            return [
                'needs_update'    => false,
                'current_version' => null,
                'new_version'     => null,
                'message'         => 'Elementor Pro not installed',
            ];
        }

        $current_db_version = get_option( 'elementor_pro_version', null );
        $plugin_version = ELEMENTOR_PRO_VERSION;

        $needs_update = $current_db_version && version_compare( $current_db_version, $plugin_version, '<' );

        return [
            'needs_update'    => $needs_update,
            'current_version' => $current_db_version,
            'new_version'     => $plugin_version,
        ];
    }

    /**
     * Update Elementor Pro database
     */
    private static function update_elementor_pro_db(): array {
        if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
            return [
                'success' => false,
                'message' => 'Elementor Pro not available',
            ];
        }

        try {
            // Trigger Elementor Pro upgrade if available
            if ( class_exists( '\ElementorPro\Plugin' ) ) {
                $plugin = \ElementorPro\Plugin::instance();

                if ( isset( $plugin->upgrade ) && method_exists( $plugin->upgrade, 'do_upgrade' ) ) {
                    $plugin->upgrade->do_upgrade();
                }
            }

            // Update version option
            update_option( 'elementor_pro_version', ELEMENTOR_PRO_VERSION );

            return [
                'success' => true,
                'message' => __( 'Elementor Pro database updated successfully', 'lw-site-manager' ),
            ];

        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of supported plugins
     */
    public static function get_supported_plugins(): array {
        $plugins = [];

        foreach ( self::SUPPORTED_PLUGINS as $slug => $config ) {
            $plugins[] = [
                'slug'   => $slug,
                'name'   => $config['name'],
                'active' => is_plugin_active( $slug ),
            ];
        }

        return $plugins;
    }
}
