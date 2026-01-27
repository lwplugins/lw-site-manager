<?php
/**
 * Unit tests for PermissionManager.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Abilities;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;

/**
 * Tests for PermissionManager.
 */
final class PermissionManagerTest extends TestCase {

    private PermissionManager $manager;

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        reset_wp_filters();
        reset_wp_options();
        $this->manager = new PermissionManager();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        reset_wp_filters();
        reset_wp_options();
        parent::tearDown();
    }

    // =========================================================================
    // Update & Installation Permission Tests
    // =========================================================================

    /**
     * Test that can_manage_updates returns boolean.
     */
    public function test_can_manage_updates_returns_boolean(): void {
        $result = $this->manager->can_manage_updates();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_install_plugins returns boolean.
     */
    public function test_can_install_plugins_returns_boolean(): void {
        $result = $this->manager->can_install_plugins();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_install_themes returns boolean.
     */
    public function test_can_install_themes_returns_boolean(): void {
        $result = $this->manager->can_install_themes();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Plugin & Theme Management Permission Tests
    // =========================================================================

    /**
     * Test that can_manage_plugins returns boolean.
     */
    public function test_can_manage_plugins_returns_boolean(): void {
        $result = $this->manager->can_manage_plugins();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_manage_themes returns boolean.
     */
    public function test_can_manage_themes_returns_boolean(): void {
        $result = $this->manager->can_manage_themes();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Site Management Permission Tests
    // =========================================================================

    /**
     * Test that can_manage_backups returns boolean.
     */
    public function test_can_manage_backups_returns_boolean(): void {
        $result = $this->manager->can_manage_backups();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_view_health returns boolean.
     */
    public function test_can_view_health_returns_boolean(): void {
        $result = $this->manager->can_view_health();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_manage_database returns boolean.
     */
    public function test_can_manage_database_returns_boolean(): void {
        $result = $this->manager->can_manage_database();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_manage_cache returns boolean.
     */
    public function test_can_manage_cache_returns_boolean(): void {
        $result = $this->manager->can_manage_cache();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_manage_options returns boolean.
     */
    public function test_can_manage_options_returns_boolean(): void {
        $result = $this->manager->can_manage_options();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // User Management Permission Tests
    // =========================================================================

    /**
     * Test that can_manage_users returns boolean.
     */
    public function test_can_manage_users_returns_boolean(): void {
        $result = $this->manager->can_manage_users();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_create_users returns boolean.
     */
    public function test_can_create_users_returns_boolean(): void {
        $result = $this->manager->can_create_users();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_edit_users returns boolean.
     */
    public function test_can_edit_users_returns_boolean(): void {
        $result = $this->manager->can_edit_users();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_delete_users returns boolean.
     */
    public function test_can_delete_users_returns_boolean(): void {
        $result = $this->manager->can_delete_users();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Post Permission Tests
    // =========================================================================

    /**
     * Test that can_edit_posts returns boolean.
     */
    public function test_can_edit_posts_returns_boolean(): void {
        $result = $this->manager->can_edit_posts();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_publish_posts returns boolean.
     */
    public function test_can_publish_posts_returns_boolean(): void {
        $result = $this->manager->can_publish_posts();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_delete_posts returns boolean.
     */
    public function test_can_delete_posts_returns_boolean(): void {
        $result = $this->manager->can_delete_posts();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_edit_others_posts returns boolean.
     */
    public function test_can_edit_others_posts_returns_boolean(): void {
        $result = $this->manager->can_edit_others_posts();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Page Permission Tests
    // =========================================================================

    /**
     * Test that can_edit_pages returns boolean.
     */
    public function test_can_edit_pages_returns_boolean(): void {
        $result = $this->manager->can_edit_pages();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_publish_pages returns boolean.
     */
    public function test_can_publish_pages_returns_boolean(): void {
        $result = $this->manager->can_publish_pages();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_delete_pages returns boolean.
     */
    public function test_can_delete_pages_returns_boolean(): void {
        $result = $this->manager->can_delete_pages();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_edit_others_pages returns boolean.
     */
    public function test_can_edit_others_pages_returns_boolean(): void {
        $result = $this->manager->can_edit_others_pages();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Comment Permission Tests
    // =========================================================================

    /**
     * Test that can_moderate_comments returns boolean.
     */
    public function test_can_moderate_comments_returns_boolean(): void {
        $result = $this->manager->can_moderate_comments();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_edit_comments returns boolean.
     */
    public function test_can_edit_comments_returns_boolean(): void {
        $result = $this->manager->can_edit_comments();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Media Permission Tests
    // =========================================================================

    /**
     * Test that can_upload_files returns boolean.
     */
    public function test_can_upload_files_returns_boolean(): void {
        $result = $this->manager->can_upload_files();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Taxonomy Permission Tests
    // =========================================================================

    /**
     * Test that can_manage_categories returns boolean.
     */
    public function test_can_manage_categories_returns_boolean(): void {
        $result = $this->manager->can_manage_categories();
        $this->assertIsBool( $result );
    }

    /**
     * Test that can_manage_tags returns boolean.
     */
    public function test_can_manage_tags_returns_boolean(): void {
        $result = $this->manager->can_manage_tags();
        $this->assertIsBool( $result );
    }

    // =========================================================================
    // Helper Method Tests
    // =========================================================================

    /**
     * Test that has_any_capability returns boolean.
     */
    public function test_has_any_capability_returns_boolean(): void {
        $result = $this->manager->has_any_capability( [ 'edit_posts', 'manage_options' ] );
        $this->assertIsBool( $result );
    }

    /**
     * Test that has_any_capability with empty array returns false.
     */
    public function test_has_any_capability_empty_array_returns_false(): void {
        $result = $this->manager->has_any_capability( [] );
        $this->assertFalse( $result );
    }

    /**
     * Test that has_all_capabilities returns boolean.
     */
    public function test_has_all_capabilities_returns_boolean(): void {
        $result = $this->manager->has_all_capabilities( [ 'edit_posts', 'manage_options' ] );
        $this->assertIsBool( $result );
    }

    /**
     * Test that has_all_capabilities with empty array returns true.
     */
    public function test_has_all_capabilities_empty_array_returns_true(): void {
        $result = $this->manager->has_all_capabilities( [] );
        $this->assertTrue( $result );
    }

    /**
     * Test that callback returns array.
     */
    public function test_callback_returns_array(): void {
        $result = $this->manager->callback( 'can_edit_posts' );
        $this->assertIsArray( $result );
        $this->assertCount( 2, $result );
        $this->assertSame( $this->manager, $result[0] );
        $this->assertSame( 'can_edit_posts', $result[1] );
    }

    /**
     * Test that callback is callable.
     */
    public function test_callback_is_callable(): void {
        $callback = $this->manager->callback( 'can_edit_posts' );
        $this->assertIsCallable( $callback );
    }
}
