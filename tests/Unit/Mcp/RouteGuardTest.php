<?php
/**
 * Independent capability gate on the MCP route.
 *
 * The adapter's own gate is applied as
 * `apply_filters( 'mcp_adapter_default_transport_permission_user_capability', 'read', ... )`
 * — note the default. If our filter is ever not applied (a refactor upstream, a
 * different bundled copy winning the autoload race, a hook rename), the endpoint
 * silently falls back to `read`, which every logged-in subscriber has. The whole
 * admin gate rests on one hook firing, in every adapter version.
 *
 * This layer does not depend on the adapter at all.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\RouteGuard;
use PHPUnit\Framework\TestCase;

final class RouteGuardTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        parent::tearDown();
    }

    private function request( string $route ): \WP_REST_Request {
        return new \WP_REST_Request( 'POST', $route );
    }

    public function test_blocks_our_mcp_route_without_the_capability(): void {
        grant_wp_caps( [ 'read' ] ); // A subscriber: what the adapter default would allow.

        $result = RouteGuard::guard( null, null, $this->request( '/mcp/lw-site-manager' ) );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'rest_forbidden', $result->get_error_code() );
    }

    public function test_allows_our_mcp_route_for_an_administrator(): void {
        grant_wp_caps( [ 'read', 'manage_options' ] );

        $result = RouteGuard::guard( null, null, $this->request( '/mcp/lw-site-manager' ) );

        $this->assertNull( $result, 'an allowed request must pass the filter value through untouched' );
    }

    /**
     * Sub-paths of our server must be covered too, not just the exact route.
     */
    public function test_blocks_sub_paths_of_our_mcp_route(): void {
        grant_wp_caps( [ 'read' ] );

        $result = RouteGuard::guard( null, null, $this->request( '/mcp/lw-site-manager/anything' ) );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    /**
     * Another plugin's MCP server is none of our business — guarding it would
     * break their endpoint.
     */
    public function test_ignores_another_plugins_mcp_route(): void {
        grant_wp_caps( [ 'read' ] );

        $this->assertNull( RouteGuard::guard( null, null, $this->request( '/mcp/fluent-crm' ) ) );
        $this->assertNull( RouteGuard::guard( null, null, $this->request( '/wp/v2/posts' ) ) );
    }

    /**
     * A route that merely starts with the same characters must not be caught.
     */
    public function test_ignores_a_similarly_named_route(): void {
        grant_wp_caps( [ 'read' ] );

        $this->assertNull( RouteGuard::guard( null, null, $this->request( '/mcp/lw-site-manager-other' ) ) );
    }

    /**
     * An earlier filter that already produced a response or error must win —
     * this guard only ever adds a denial, never overrides one.
     */
    public function test_passes_an_existing_result_through(): void {
        grant_wp_caps( [ 'manage_options' ] );
        $existing = new \WP_Error( 'something_else', 'already handled' );

        $this->assertSame(
            $existing,
            RouteGuard::guard( $existing, null, $this->request( '/mcp/lw-site-manager' ) )
        );
    }
}
