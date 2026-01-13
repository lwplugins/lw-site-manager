<?php
/**
 * Maintenance Abilities Registrar - Registers backup, health, database, and cache abilities
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Registrars;

use WPSiteManager\Services\BackupManager;
use WPSiteManager\Services\HealthCheck;
use WPSiteManager\Services\DatabaseManager;
use WPSiteManager\Services\CacheManager;

class MaintenanceAbilitiesRegistrar extends AbstractAbilitiesRegistrar {

    public function register(): void {
        $this->register_backup_abilities();
        $this->register_health_abilities();
        $this->register_database_abilities();
        $this->register_cache_abilities();
    }

    // =========================================================================
    // Backup Abilities
    // =========================================================================

    private function register_backup_abilities(): void {
        // Create backup (starts background job)
        wp_register_ability(
            'site-manager/create-backup',
            [
                'label'       => __( 'Create Backup', 'wp-site-manager' ),
                'description' => __( 'Start a backup job. Returns immediately with job ID. Use backup-status to monitor progress.', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'include_database' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Include database in backup',
                        ],
                        'include_files' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Include WordPress files in backup',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'          => [ 'type' => 'boolean' ],
                        'message'          => [ 'type' => 'string' ],
                        'backup_id'        => [ 'type' => 'string' ],
                        'status'           => [ 'type' => 'string' ],
                        'total_files'      => [ 'type' => 'integer' ],
                        'total_size'       => [ 'type' => 'integer' ],
                        'total_size_human' => [ 'type' => 'string' ],
                        'chunks_total'     => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ BackupManager::class, 'create_backup' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->writeMeta( idempotent: false ),
            ]
        );

        // Get backup status
        wp_register_ability(
            'site-manager/backup-status',
            [
                'label'       => __( 'Get Backup Status', 'wp-site-manager' ),
                'description' => __( 'Get the status and progress of a backup job', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backup_id' => [
                            'type'        => 'string',
                            'description' => 'Backup job ID',
                        ],
                    ],
                    'required' => [ 'backup_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backup_id'       => [ 'type' => 'string' ],
                        'status'          => [ 'type' => 'string' ],
                        'progress'        => [ 'type' => 'number' ],
                        'total_files'     => [ 'type' => 'integer' ],
                        'processed_files' => [ 'type' => 'integer' ],
                        'current_chunk'   => [ 'type' => 'integer' ],
                        'chunks_total'    => [ 'type' => 'integer' ],
                        'created_at'      => [ 'type' => [ 'string', 'null' ] ],
                        'started_at'      => [ 'type' => [ 'string', 'null' ] ],
                        'completed_at'    => [ 'type' => [ 'string', 'null' ] ],
                        'file_path'       => [ 'type' => [ 'string', 'null' ] ],
                        'file_size'       => [ 'type' => [ 'integer', 'null' ] ],
                        'file_size_human' => [ 'type' => [ 'string', 'null' ] ],
                        'errors'          => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                    ],
                ],
                'execute_callback'    => [ BackupManager::class, 'get_backup_status' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Cancel backup
        wp_register_ability(
            'site-manager/cancel-backup',
            [
                'label'       => __( 'Cancel Backup', 'wp-site-manager' ),
                'description' => __( 'Cancel a running backup job', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backup_id' => [
                            'type'        => 'string',
                            'description' => 'Backup job ID to cancel',
                        ],
                    ],
                    'required' => [ 'backup_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'   => [ 'type' => 'boolean' ],
                        'message'   => [ 'type' => 'string' ],
                        'backup_id' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ BackupManager::class, 'cancel_backup' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );

        // List backups
        wp_register_ability(
            'site-manager/list-backups',
            [
                'label'       => __( 'List Backups', 'wp-site-manager' ),
                'description' => __( 'List all available backups', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        $this->paginationSchema( 20 ),
                        []
                    ),
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backups' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'id'        => [ 'type' => 'string' ],
                                    'file_path' => [ 'type' => 'string' ],
                                    'file_size' => [ 'type' => 'integer' ],
                                    'timestamp' => [ 'type' => 'string' ],
                                    'includes'  => [
                                        'type'       => 'object',
                    'default'    => [],
                                        'properties' => [
                                            'database' => [ 'type' => 'boolean' ],
                                            'uploads'  => [ 'type' => 'boolean' ],
                                            'plugins'  => [ 'type' => 'boolean' ],
                                            'themes'   => [ 'type' => 'boolean' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'total'        => [ 'type' => 'integer' ],
                        'storage_used' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ BackupManager::class, 'list_backups' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Restore backup
        wp_register_ability(
            'site-manager/restore-backup',
            [
                'label'       => __( 'Restore Backup', 'wp-site-manager' ),
                'description' => __( 'Restore site from a backup', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backup_id' => [
                            'type'        => 'string',
                            'description' => 'Backup ID to restore',
                        ],
                        'restore_database' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Restore database from backup',
                        ],
                        'restore_files' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Restore files from backup',
                        ],
                    ],
                    'required' => [ 'backup_id' ],
                ],
                'output_schema' => $this->successOutputSchema( [
                    'backup_id' => [ 'type' => 'string' ],
                    'restored'  => [
                        'type'       => 'object',
                    'default'    => [],
                        'properties' => [
                            'database' => [ 'type' => 'boolean' ],
                            'files'    => [ 'type' => 'boolean' ],
                        ],
                    ],
                ] ),
                'execute_callback'    => [ BackupManager::class, 'restore_backup' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->destructiveMeta( idempotent: false ),
            ]
        );

        // Delete backup
        wp_register_ability(
            'site-manager/delete-backup',
            [
                'label'       => __( 'Delete Backup', 'wp-site-manager' ),
                'description' => __( 'Delete a backup file', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'backup_id' => [
                            'type'        => 'string',
                            'description' => 'Backup ID to delete',
                        ],
                    ],
                    'required' => [ 'backup_id' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'    => [ 'type' => 'boolean' ],
                        'message'    => [ 'type' => 'string' ],
                        'deleted_id' => [ 'type' => 'string' ],
                    ],
                ],
                'execute_callback'    => [ BackupManager::class, 'delete_backup' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_backups' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );
    }

    // =========================================================================
    // Health & Diagnostics Abilities
    // =========================================================================

    private function register_health_abilities(): void {
        // Health check
        wp_register_ability(
            'site-manager/health-check',
            [
                'label'       => __( 'Health Check', 'wp-site-manager' ),
                'description' => __( 'Run comprehensive site health check', 'wp-site-manager' ),
                'category'    => 'diagnostics',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'include_debug' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Include debug information',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'status'      => [ 'type' => 'string' ],
                        'score'       => [ 'type' => 'integer' ],
                        'issues'      => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'type'     => [ 'type' => 'string' ],
                                    'message'  => [ 'type' => 'string' ],
                                    'category' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                        'php_version' => [ 'type' => 'string' ],
                        'wp_version'  => [ 'type' => 'string' ],
                        'disk_usage'  => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'total'        => [ 'type' => 'number' ],
                                'total_human'  => [ 'type' => 'string' ],
                                'free'         => [ 'type' => 'number' ],
                                'free_human'   => [ 'type' => 'string' ],
                                'used'         => [ 'type' => 'number' ],
                                'used_human'   => [ 'type' => 'string' ],
                                'percent_used' => [ 'type' => 'number' ],
                                'wordpress'    => [
                                    'type'       => 'object',
                    'default'    => [],
                                    'properties' => [
                                        'total'   => [ 'type' => 'string' ],
                                        'uploads' => [ 'type' => 'string' ],
                                        'plugins' => [ 'type' => 'string' ],
                                        'themes'  => [ 'type' => 'string' ],
                                    ],
                                ],
                            ],
                        ],
                        'memory' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'limit'   => [ 'type' => 'string' ],
                                'usage'   => [ 'type' => 'string' ],
                                'percent' => [ 'type' => 'number' ],
                            ],
                        ],
                        'server' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'software' => [ 'type' => 'string' ],
                                'hostname' => [ 'type' => 'string' ],
                            ],
                        ],
                        'paths' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'wordpress'  => [ 'type' => 'string' ],
                                'wp_content' => [ 'type' => 'string' ],
                                'uploads'    => [ 'type' => 'string' ],
                                'plugins'    => [ 'type' => 'string' ],
                                'themes'     => [ 'type' => 'string' ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ HealthCheck::class, 'run_check' ],
                'permission_callback' => $this->permissions->callback( 'can_view_health' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Error log
        wp_register_ability(
            'site-manager/error-log',
            [
                'label'       => __( 'Get Error Log', 'wp-site-manager' ),
                'description' => __( 'Retrieve recent PHP errors from log', 'wp-site-manager' ),
                'category'    => 'diagnostics',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'lines' => [
                            'type'        => 'integer',
                            'default'     => 100,
                            'minimum'     => 1,
                            'maximum'     => 1000,
                            'description' => 'Number of lines to retrieve',
                        ],
                        'filter' => [
                            'type'        => 'string',
                            'description' => 'Filter errors by keyword',
                        ],
                        'level' => [
                            'type'        => 'string',
                            'enum'        => [ 'all', 'error', 'warning', 'notice' ],
                            'default'     => 'all',
                            'description' => 'Filter by error level',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'errors' => [
                            'type'  => 'array',
                            'items' => [ 'type' => 'string' ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ HealthCheck::class, 'get_error_log' ],
                'permission_callback' => $this->permissions->callback( 'can_view_health' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // Database Abilities
    // =========================================================================

    private function register_database_abilities(): void {
        // Optimize database
        wp_register_ability(
            'site-manager/optimize-database',
            [
                'label'       => __( 'Optimize Database', 'wp-site-manager' ),
                'description' => __( 'Optimize database tables', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'tables' => [
                            'type'        => 'array',
                            'items'       => [ 'type' => 'string' ],
                            'description' => 'Specific tables to optimize (all if empty)',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'tables'  => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'name'       => [ 'type' => 'string' ],
                                    'status'     => [ 'type' => 'string' ],
                                    'size_before' => [ 'type' => 'integer' ],
                                    'size_after'  => [ 'type' => 'integer' ],
                                ],
                            ],
                        ],
                        'total_saved' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ DatabaseManager::class, 'optimize' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_database' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );

        // Cleanup database
        wp_register_ability(
            'site-manager/cleanup-database',
            [
                'label'       => __( 'Cleanup Database', 'wp-site-manager' ),
                'description' => __( 'Remove revisions, transients, spam, trash', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'revisions' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete post revisions',
                        ],
                        'auto_drafts' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete auto-draft posts',
                        ],
                        'trash_posts' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete trashed posts',
                        ],
                        'spam_comments' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete spam comments',
                        ],
                        'trash_comments' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete trashed comments',
                        ],
                        'expired_transients' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Delete expired transients',
                        ],
                        'all_transients' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Delete all transients (not just expired)',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'deleted' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'revisions'          => [ 'type' => 'integer' ],
                                'auto_drafts'        => [ 'type' => 'integer' ],
                                'trash_posts'        => [ 'type' => 'integer' ],
                                'spam_comments'      => [ 'type' => 'integer' ],
                                'trash_comments'     => [ 'type' => 'integer' ],
                                'expired_transients' => [ 'type' => 'integer' ],
                                'all_transients'     => [ 'type' => 'integer' ],
                            ],
                        ],
                        'total_deleted' => [ 'type' => 'integer' ],
                        'space_freed'   => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ DatabaseManager::class, 'cleanup' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_database' ),
                'meta'                => $this->destructiveMeta( idempotent: false ),
            ]
        );
    }

    // =========================================================================
    // Cache Abilities
    // =========================================================================

    private function register_cache_abilities(): void {
        wp_register_ability(
            'site-manager/flush-cache',
            [
                'label'       => __( 'Flush Cache', 'wp-site-manager' ),
                'description' => __( 'Clear all caches (object cache, page cache, etc.)', 'wp-site-manager' ),
                'category'    => 'maintenance',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'object_cache' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Flush object cache (Redis, Memcached)',
                        ],
                        'page_cache' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Flush page cache',
                        ],
                        'opcache' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Flush PHP OPcache',
                        ],
                        'plugin_caches' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Flush caches from popular plugins',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success' => [ 'type' => 'boolean' ],
                        'message' => [ 'type' => 'string' ],
                        'flushed' => [
                            'type'       => 'object',
                    'default'    => [],
                            'properties' => [
                                'object_cache'  => [ 'type' => 'boolean' ],
                                'page_cache'    => [ 'type' => 'boolean' ],
                                'opcache'       => [ 'type' => 'boolean' ],
                                'plugin_caches' => [
                                    'type'  => 'array',
                                    'items' => [ 'type' => 'string' ],
                                ],
                            ],
                        ],
                    ],
                ],
                'execute_callback'    => [ CacheManager::class, 'flush' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_cache' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );
    }
}
