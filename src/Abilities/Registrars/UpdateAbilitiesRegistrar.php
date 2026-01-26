<?php
/**
 * Update Abilities Registrar - Registers update, plugin, and theme management abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Registrars;

use LightweightPlugins\SiteManager\Services\UpdateManager;
use LightweightPlugins\SiteManager\Services\PluginDatabaseUpdater;

class UpdateAbilitiesRegistrar extends AbstractAbilitiesRegistrar {

    public function register(): void {
        $this->register_update_check_abilities();
        $this->register_update_action_abilities();
        $this->register_plugin_management_abilities();
        $this->register_theme_management_abilities();
        $this->register_plugin_database_abilities();
    }

    // =========================================================================
    // Update Check Abilities
    // =========================================================================

    private function register_update_check_abilities(): void {
        wp_register_ability(
            'site-manager/check-updates',
            [
                'label'       => __( 'Check Updates', 'lw-site-manager' ),
                'description' => __( 'Check for available WordPress core, plugin, and theme updates', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'type' => [
                            'type'        => 'string',
                            'enum'        => [ 'all', 'core', 'plugins', 'themes' ],
                            'default'     => 'all',
                            'description' => 'Type of updates to check',
                        ],
                        'force_refresh' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Force refresh update cache',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'core' => [
                            'type'       => 'object',
                            'default'    => [],
                            'properties' => [
                                'current'    => [ 'type' => 'string' ],
                                'available'  => [ 'type' => 'string' ],
                                'has_update' => [ 'type' => 'boolean' ],
                            ],
                        ],
                        'plugins' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'      => [ 'type' => 'string' ],
                                    'name'      => [ 'type' => 'string' ],
                                    'current'   => [ 'type' => 'string' ],
                                    'available' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'themes' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'      => [ 'type' => 'string' ],
                                    'name'      => [ 'type' => 'string' ],
                                    'current'   => [ 'type' => 'string' ],
                                    'available' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'total_updates' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'check_updates' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->readOnlyMeta( idempotent: true ),
            ]
        );
    }

    // =========================================================================
    // Update Action Abilities
    // =========================================================================

    private function register_update_action_abilities(): void {
        // Update single plugin
        wp_register_ability(
            'site-manager/update-plugin',
            [
                'label'       => __( 'Update Plugin', 'lw-site-manager' ),
                'description' => __( 'Update a specific plugin to latest version', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugin' => [
                            'type'        => 'string',
                            'description' => 'Plugin slug (e.g., woocommerce/woocommerce.php)',
                        ],
                    ],
                    'required' => [ 'plugin' ],
                ],
                'output_schema'       => $this->updateResultSchema(),
                'execute_callback'    => [ UpdateManager::class, 'update_plugin' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );

        // Update single theme
        wp_register_ability(
            'site-manager/update-theme',
            [
                'label'       => __( 'Update Theme', 'lw-site-manager' ),
                'description' => __( 'Update a specific theme to latest version', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'theme' => [
                            'type'        => 'string',
                            'description' => 'Theme slug',
                        ],
                    ],
                    'required' => [ 'theme' ],
                ],
                'output_schema'       => $this->updateResultSchema(),
                'execute_callback'    => [ UpdateManager::class, 'update_theme' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );

        // Update WordPress core
        wp_register_ability(
            'site-manager/update-core',
            [
                'label'       => __( 'Update WordPress Core', 'lw-site-manager' ),
                'description' => __( 'Update WordPress core to latest version', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'version' => [
                            'type'        => 'string',
                            'description' => 'Specific version to update to (optional, defaults to latest)',
                        ],
                        'minor_only' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Only update to minor versions (safer)',
                        ],
                    ],
                ],
                'output_schema'       => $this->updateResultSchema(),
                'execute_callback'    => [ UpdateManager::class, 'update_core' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );

        // Update all
        wp_register_ability(
            'site-manager/update-all',
            [
                'label'       => __( 'Update Everything', 'lw-site-manager' ),
                'description' => __( 'Update all plugins, themes, and optionally WordPress core', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'include_core' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Include WordPress core update',
                        ],
                        'include_plugins' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Include plugin updates',
                        ],
                        'include_themes' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Include theme updates',
                        ],
                        'stop_on_error' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Stop updating if PHP error detected',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'summary' => [ 'type' => 'string' ],
                        'updated' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'core'    => [ 'type' => 'boolean' ],
                                'plugins' => [ 'type' => 'array' ],
                                'themes'  => [ 'type' => 'array' ],
                            ],
                        ],
                        'failed'        => [ 'type' => 'array' ],
                        'php_errors'    => [ 'type' => 'array' ],
                        'stopped_early' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'update_all' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->writeMeta( idempotent: false ),
            ]
        );
    }

    // =========================================================================
    // Plugin Management Abilities
    // =========================================================================

    private function register_plugin_management_abilities(): void {
        // List plugins
        wp_register_ability(
            'site-manager/list-plugins',
            [
                'label'       => __( 'List Plugins', 'lw-site-manager' ),
                'description' => __( 'List all installed plugins with status', 'lw-site-manager' ),
                'category'    => 'plugins',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status' => [
                            'type'    => 'string',
                            'enum'    => [ 'all', 'active', 'inactive' ],
                            'default' => 'all',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugins' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'             => [ 'type' => 'string' ],
                                    'name'             => [ 'type' => 'string' ],
                                    'version'          => [ 'type' => 'string' ],
                                    'author'           => [ 'type' => 'string' ],
                                    'active'           => [ 'type' => 'boolean' ],
                                    'update_available' => [ 'type' => 'boolean' ],
                                ],
                            ],
                        ],
                        'total'          => [ 'type' => 'integer' ],
                        'active_count'   => [ 'type' => 'integer' ],
                        'inactive_count' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'list_plugins' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_plugins' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Activate plugin
        wp_register_ability(
            'site-manager/activate-plugin',
            [
                'label'       => __( 'Activate Plugin', 'lw-site-manager' ),
                'description' => __( 'Activate a specific plugin', 'lw-site-manager' ),
                'category'    => 'plugins',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugin' => [
                            'type'        => 'string',
                            'description' => 'Plugin slug (e.g., woocommerce/woocommerce.php)',
                        ],
                    ],
                    'required' => [ 'plugin' ],
                ],
                'output_schema' => $this->successOutputSchema( [
                    'plugin'          => [ 'type' => 'string' ],
                    'name'            => [ 'type' => 'string' ],
                    'previous_status' => [ 'type' => 'string' ],
                    'php_errors'      => [
                        'type'  => 'array',
                        'items' => [ 'type' => 'string' ],
                    ],
                ] ),
                'execute_callback'    => [ UpdateManager::class, 'activate_plugin' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_plugins' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );

        // Deactivate plugin
        wp_register_ability(
            'site-manager/deactivate-plugin',
            [
                'label'       => __( 'Deactivate Plugin', 'lw-site-manager' ),
                'description' => __( 'Deactivate a specific plugin', 'lw-site-manager' ),
                'category'    => 'plugins',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugin' => [
                            'type'        => 'string',
                            'description' => 'Plugin slug (e.g., woocommerce/woocommerce.php)',
                        ],
                    ],
                    'required' => [ 'plugin' ],
                ],
                'output_schema' => $this->successOutputSchema( [
                    'plugin'          => [ 'type' => 'string' ],
                    'name'            => [ 'type' => 'string' ],
                    'previous_status' => [ 'type' => 'string' ],
                ] ),
                'execute_callback'    => [ UpdateManager::class, 'deactivate_plugin' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_plugins' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );

        // Install plugin
        wp_register_ability(
            'site-manager/install-plugin',
            [
                'label'       => __( 'Install Plugin', 'lw-site-manager' ),
                'description' => __( 'Install a plugin from WordPress.org repository', 'lw-site-manager' ),
                'category'    => 'plugins',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Plugin slug from WordPress.org (e.g., woocommerce, contact-form-7)',
                        ],
                        'activate' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Activate plugin after installation',
                        ],
                    ],
                    'required' => [ 'slug' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'plugin'     => [ 'type' => 'string' ],
                        'name'       => [ 'type' => 'string' ],
                        'version'    => [ 'type' => 'string' ],
                        'activated'  => [ 'type' => 'boolean' ],
                        'php_errors' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'install_plugin' ],
                'permission_callback' => $this->permissions->callback( 'can_install_plugins' ),
                'meta'                => $this->destructiveMeta( idempotent: false ),
            ]
        );

        // Delete plugin
        wp_register_ability(
            'site-manager/delete-plugin',
            [
                'label'       => __( 'Delete Plugin', 'lw-site-manager' ),
                'description' => __( 'Delete a plugin from the site', 'lw-site-manager' ),
                'category'    => 'plugins',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugin' => [
                            'type'        => 'string',
                            'description' => 'Plugin file path (e.g., hello.php or akismet/akismet.php)',
                        ],
                    ],
                    'required' => [ 'plugin' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'delete_plugin' ],
                'permission_callback' => $this->permissions->callback( 'can_install_plugins' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );
    }

    // =========================================================================
    // Theme Management Abilities
    // =========================================================================

    private function register_theme_management_abilities(): void {
        // List themes
        wp_register_ability(
            'site-manager/list-themes',
            [
                'label'       => __( 'List Themes', 'lw-site-manager' ),
                'description' => __( 'List all installed themes', 'lw-site-manager' ),
                'category'    => 'themes',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'themes' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'             => [ 'type' => 'string' ],
                                    'name'             => [ 'type' => 'string' ],
                                    'version'          => [ 'type' => 'string' ],
                                    'author'           => [ 'type' => 'string' ],
                                    'active'           => [ 'type' => 'boolean' ],
                                    'update_available' => [ 'type' => 'boolean' ],
                                ],
                            ],
                        ],
                        'total'        => [ 'type' => 'integer' ],
                        'active_theme' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'list_themes' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_themes' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Activate theme
        wp_register_ability(
            'site-manager/activate-theme',
            [
                'label'       => __( 'Activate Theme', 'lw-site-manager' ),
                'description' => __( 'Switch to a different theme', 'lw-site-manager' ),
                'category'    => 'themes',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'theme' => [
                            'type'        => 'string',
                            'description' => 'Theme slug',
                        ],
                    ],
                    'required' => [ 'theme' ],
                ],
                'output_schema' => $this->successOutputSchema( [
                    'theme'          => [ 'type' => 'string' ],
                    'name'           => [ 'type' => 'string' ],
                    'previous_theme' => [ 'type' => 'string' ],
                ] ),
                'execute_callback'    => [ UpdateManager::class, 'activate_theme' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_themes' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );

        // Install theme
        wp_register_ability(
            'site-manager/install-theme',
            [
                'label'       => __( 'Install Theme', 'lw-site-manager' ),
                'description' => __( 'Install a theme from WordPress.org repository', 'lw-site-manager' ),
                'category'    => 'themes',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'slug' => [
                            'type'        => 'string',
                            'description' => 'Theme slug from WordPress.org',
                        ],
                        'activate' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Activate theme after installation',
                        ],
                    ],
                    'required' => [ 'slug' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'theme'      => [ 'type' => 'string' ],
                        'name'       => [ 'type' => 'string' ],
                        'version'    => [ 'type' => 'string' ],
                        'activated'  => [ 'type' => 'boolean' ],
                        'php_errors' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'install_theme' ],
                'permission_callback' => $this->permissions->callback( 'can_install_themes' ),
                'meta'                => $this->destructiveMeta( idempotent: false ),
            ]
        );

        // Delete theme
        wp_register_ability(
            'site-manager/delete-theme',
            [
                'label'       => __( 'Delete Theme', 'lw-site-manager' ),
                'description' => __( 'Delete a theme from the site', 'lw-site-manager' ),
                'category'    => 'themes',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'theme' => [
                            'type'        => 'string',
                            'description' => 'Theme slug (e.g., astra, flavor)',
                        ],
                    ],
                    'required' => [ 'theme' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ UpdateManager::class, 'delete_theme' ],
                'permission_callback' => $this->permissions->callback( 'can_install_themes' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );
    }

    // =========================================================================
    // Plugin Database Update Abilities
    // =========================================================================

    private function register_plugin_database_abilities(): void {
        // Check plugin DB updates
        wp_register_ability(
            'site-manager/check-plugin-db-updates',
            [
                'label'       => __( 'Check Plugin Database Updates', 'lw-site-manager' ),
                'description' => __( 'Check for pending database updates for WooCommerce, Elementor, etc.', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'updates' => [
                            'type'        => 'object',
                    'default'    => [],
                            'description' => 'Pending DB updates by plugin slug',
                        ],
                        'total_updates' => [ 'type' => 'integer' ],
                        'supported'     => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'execute_callback'    => [ PluginDatabaseUpdater::class, 'check_updates' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->readOnlyMeta( idempotent: true ),
            ]
        );

        // Update single plugin DB
        wp_register_ability(
            'site-manager/update-plugin-db',
            [
                'label'       => __( 'Update Plugin Database', 'lw-site-manager' ),
                'description' => __( 'Run database update for a specific plugin (WooCommerce, Elementor)', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugin' => [
                            'type'        => 'string',
                            'description' => 'Plugin slug (e.g., woocommerce/woocommerce.php)',
                            'enum'        => [
                                'woocommerce/woocommerce.php',
                                'elementor/elementor.php',
                                'elementor-pro/elementor-pro.php',
                            ],
                        ],
                    ],
                    'required' => [ 'plugin' ],
                ],
                'output_schema'       => $this->updateResultSchema(),
                'execute_callback'    => [ PluginDatabaseUpdater::class, 'update_plugin_db' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );

        // Update all plugin DBs
        wp_register_ability(
            'site-manager/update-all-plugin-dbs',
            [
                'label'       => __( 'Update All Plugin Databases', 'lw-site-manager' ),
                'description' => __( 'Run all pending database updates for supported plugins', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'stop_on_error' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Stop if PHP error detected',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'       => [ 'type' => 'boolean' ],
                        'summary'       => [ 'type' => 'string' ],
                        'updated'       => [ 'type' => 'array' ],
                        'failed'        => [ 'type' => 'array' ],
                        'php_errors'    => [ 'type' => 'array' ],
                        'stopped_early' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ PluginDatabaseUpdater::class, 'update_all' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->destructiveMeta( idempotent: false ),
            ]
        );

        // Get supported plugins
        wp_register_ability(
            'site-manager/get-supported-db-plugins',
            [
                'label'       => __( 'Get Supported DB Update Plugins', 'lw-site-manager' ),
                'description' => __( 'List plugins that support database updates', 'lw-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'plugins' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'      => [ 'type' => 'string' ],
                                    'name'      => [ 'type' => 'string' ],
                                    'installed' => [ 'type' => 'boolean' ],
                                    'active'    => [ 'type' => 'boolean' ],
                                ],
                            ],
                        ],
                        'total'           => [ 'type' => 'integer' ],
                        'installed_count' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ PluginDatabaseUpdater::class, 'get_supported_plugins' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_updates' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );
    }
}
