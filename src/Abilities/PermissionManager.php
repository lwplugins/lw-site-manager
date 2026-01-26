<?php
/**
 * Permission Manager - Centralized permission callbacks for all abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities;

class PermissionManager {

    // =========================================================================
    // Update & Installation Permissions
    // =========================================================================

    /**
     * Check if user can manage updates (plugins and themes)
     */
    public function can_manage_updates(): bool {
        return current_user_can( 'update_plugins' ) && current_user_can( 'update_themes' );
    }

    /**
     * Check if user can install plugins
     */
    public function can_install_plugins(): bool {
        return current_user_can( 'install_plugins' );
    }

    /**
     * Check if user can install themes
     */
    public function can_install_themes(): bool {
        return current_user_can( 'install_themes' );
    }

    // =========================================================================
    // Plugin & Theme Management Permissions
    // =========================================================================

    /**
     * Check if user can manage plugins (activate/deactivate)
     */
    public function can_manage_plugins(): bool {
        return current_user_can( 'activate_plugins' );
    }

    /**
     * Check if user can manage themes (switch themes)
     */
    public function can_manage_themes(): bool {
        return current_user_can( 'switch_themes' );
    }

    // =========================================================================
    // Site Management Permissions
    // =========================================================================

    /**
     * Check if user can manage backups
     */
    public function can_manage_backups(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if user can view site health information
     */
    public function can_view_health(): bool {
        return current_user_can( 'view_site_health_checks' );
    }

    /**
     * Check if user can manage database operations
     */
    public function can_manage_database(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if user can manage cache operations
     */
    public function can_manage_cache(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if user can manage site options
     */
    public function can_manage_options(): bool {
        return current_user_can( 'manage_options' );
    }

    // =========================================================================
    // User Management Permissions
    // =========================================================================

    /**
     * Check if user can manage users (list users)
     */
    public function can_manage_users(): bool {
        return current_user_can( 'list_users' );
    }

    /**
     * Check if user can create new users
     */
    public function can_create_users(): bool {
        return current_user_can( 'create_users' );
    }

    /**
     * Check if user can edit users
     */
    public function can_edit_users(): bool {
        return current_user_can( 'edit_users' );
    }

    /**
     * Check if user can delete users
     */
    public function can_delete_users(): bool {
        return current_user_can( 'delete_users' );
    }

    // =========================================================================
    // Post Permissions
    // =========================================================================

    /**
     * Check if user can edit posts
     */
    public function can_edit_posts(): bool {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Check if user can publish posts
     */
    public function can_publish_posts(): bool {
        return current_user_can( 'publish_posts' );
    }

    /**
     * Check if user can delete posts
     */
    public function can_delete_posts(): bool {
        return current_user_can( 'delete_posts' );
    }

    /**
     * Check if user can edit others' posts
     */
    public function can_edit_others_posts(): bool {
        return current_user_can( 'edit_others_posts' );
    }

    // =========================================================================
    // Page Permissions
    // =========================================================================

    /**
     * Check if user can edit pages
     */
    public function can_edit_pages(): bool {
        return current_user_can( 'edit_pages' );
    }

    /**
     * Check if user can publish pages
     */
    public function can_publish_pages(): bool {
        return current_user_can( 'publish_pages' );
    }

    /**
     * Check if user can delete pages
     */
    public function can_delete_pages(): bool {
        return current_user_can( 'delete_pages' );
    }

    /**
     * Check if user can edit others' pages
     */
    public function can_edit_others_pages(): bool {
        return current_user_can( 'edit_others_pages' );
    }

    // =========================================================================
    // Comment Permissions
    // =========================================================================

    /**
     * Check if user can moderate comments
     */
    public function can_moderate_comments(): bool {
        return current_user_can( 'moderate_comments' );
    }

    /**
     * Check if user can edit comments
     */
    public function can_edit_comments(): bool {
        return current_user_can( 'edit_posts' ); // WordPress uses edit_posts for comment editing
    }

    // =========================================================================
    // Media Permissions
    // =========================================================================

    /**
     * Check if user can upload files
     */
    public function can_upload_files(): bool {
        return current_user_can( 'upload_files' );
    }

    // =========================================================================
    // Taxonomy Permissions
    // =========================================================================

    /**
     * Check if user can manage categories
     */
    public function can_manage_categories(): bool {
        return current_user_can( 'manage_categories' );
    }

    /**
     * Check if user can manage tags
     */
    public function can_manage_tags(): bool {
        return current_user_can( 'manage_categories' ); // Same cap as categories in WP
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if current user has any of the given capabilities
     *
     * @param array $capabilities Array of capabilities to check
     * @return bool True if user has any of the capabilities
     */
    public function has_any_capability( array $capabilities ): bool {
        foreach ( $capabilities as $cap ) {
            if ( current_user_can( $cap ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if current user has all of the given capabilities
     *
     * @param array $capabilities Array of capabilities to check
     * @return bool True if user has all capabilities
     */
    public function has_all_capabilities( array $capabilities ): bool {
        foreach ( $capabilities as $cap ) {
            if ( ! current_user_can( $cap ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get a callback array for the specified permission method
     *
     * @param string $method Permission method name
     * @return array Callback array for use in ability registration
     */
    public function callback( string $method ): array {
        return [ $this, $method ];
    }
}
