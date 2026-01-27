<?php
/**
 * Unit tests for Registrar.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Abilities;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\SiteManager\Abilities\Registrar;
use LightweightPlugins\SiteManager\Abilities\PermissionManager;

/**
 * Tests for Registrar.
 */
final class RegistrarTest extends TestCase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        reset_wp_filters();
        reset_wp_options();
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
    // Constructor Tests
    // =========================================================================

    /**
     * Test that Registrar can be instantiated.
     */
    public function test_can_instantiate(): void {
        $registrar = new Registrar();
        $this->assertInstanceOf( Registrar::class, $registrar );
    }

    // =========================================================================
    // Get Permissions Tests
    // =========================================================================

    /**
     * Test that get_permissions returns PermissionManager instance.
     */
    public function test_get_permissions_returns_permission_manager(): void {
        $registrar = new Registrar();
        $permissions = $registrar->get_permissions();

        $this->assertInstanceOf( PermissionManager::class, $permissions );
    }

    /**
     * Test that get_permissions returns same instance.
     */
    public function test_get_permissions_returns_same_instance(): void {
        $registrar = new Registrar();
        $permissions1 = $registrar->get_permissions();
        $permissions2 = $registrar->get_permissions();

        $this->assertSame( $permissions1, $permissions2 );
    }

    // =========================================================================
    // Register All Tests (Integration - requires WordPress Abilities API)
    // =========================================================================

    /**
     * Test that register_all can be called without errors.
     *
     * @group integration
     */
    public function test_register_all_executes(): void {
        // This test requires the WordPress Abilities API which is
        // not yet available in stubs. Skip for unit tests.
        $this->markTestSkipped( 'Requires WordPress Abilities API (integration test)' );
    }
}
