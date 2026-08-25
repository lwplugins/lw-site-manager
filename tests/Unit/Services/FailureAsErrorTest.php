<?php
/**
 * Hard failures must be returned as WP_Error, not as a 200 with success:false.
 *
 * A `[ 'success' => false ]` array is indistinguishable from success to any
 * caller that does not inspect the body: over REST it is an HTTP 200, and over
 * MCP the adapter wraps it as { success:true, data:{ success:false } }. The
 * unwrapping filter that fixed the MCP side exists only in adapter v0.5.0, and
 * on a WooCommerce store the copy that actually runs is WooCommerce's bundled
 * v0.3.0 — so a failed plugin install or activation reached the agent looking
 * like it had worked.
 *
 * Returning WP_Error fixes that at the source: it holds for every adapter
 * version and for plain REST too. Batch abilities are deliberately excluded —
 * a partial result carrying per-item detail is a real answer, not a failure.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\UpdateManager;
use PHPUnit\Framework\TestCase;

final class FailureAsErrorTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        unset( $GLOBALS['wp_activate_plugin_result'] );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_activate_plugin_result'] );
        parent::tearDown();
    }

    public function test_failed_activation_returns_an_error(): void {
        $GLOBALS['wp_activate_plugin_result'] = new \WP_Error(
            'plugin_not_found',
            'Plugin file does not exist.'
        );

        $result = UpdateManager::activate_plugin( [ 'plugin' => 'ghost/ghost.php' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'activation_failed', $result->get_error_code() );
        $this->assertStringContainsString( 'Plugin file does not exist.', $result->get_error_message() );
    }

    /**
     * The PHP errors captured while the operation ran are the most useful part
     * of the response, so they must survive the move to WP_Error.
     */
    public function test_failed_activation_keeps_the_captured_php_errors(): void {
        $GLOBALS['wp_activate_plugin_result'] = new \WP_Error( 'x', 'boom' );

        $result = UpdateManager::activate_plugin( [ 'plugin' => 'ghost/ghost.php' ] );

        $data = $result->get_error_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( 'php_errors', $data );
        $this->assertSame( 500, $data['status'] ?? null, 'status must still be carried for REST' );
    }

    /**
     * A successful activation keeps returning a plain array, so the change does
     * not turn ordinary results into errors.
     */
    public function test_successful_activation_still_returns_an_array(): void {
        $result = UpdateManager::activate_plugin( [ 'plugin' => 'hello/hello.php' ] );

        $this->assertIsArray( $result );
        $this->assertTrue( $result['success'] );
    }
}
