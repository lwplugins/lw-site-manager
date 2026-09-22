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

    private const OURS = '/mcp/lw-site-manager';

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_filters();
        unset( $GLOBALS['wp_user_logged_in'] );
    }

    protected function tearDown(): void {
        reset_wp_caps();
        reset_wp_filters();
        unset( $GLOBALS['wp_user_logged_in'] );
        parent::tearDown();
    }

    private function request( string $path ): \WP_REST_Request {
        return new \WP_REST_Request( 'POST', $path );
    }

    /**
     * Run the guard the way WordPress does: with the path the client sent and
     * the registered route pattern it was matched to.
     */
    private function dispatch( string $matched_route, ?string $path = null ): mixed {
        return RouteGuard::guard( null, $this->request( $path ?? $matched_route ), $matched_route );
    }

    public function test_blocks_our_mcp_route_without_the_capability(): void {
        grant_wp_caps( [ 'read' ] ); // A subscriber: what the adapter default would allow.
        $GLOBALS['wp_user_logged_in'] = true;

        $result = $this->dispatch( self::OURS );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'rest_forbidden', $result->get_error_code() );
        $this->assertSame( 403, $result->get_error_data()['status'] );
    }

    public function test_an_unauthenticated_caller_gets_401(): void {
        $result = $this->dispatch( self::OURS );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 401, $result->get_error_data()['status'] );
    }

    public function test_allows_our_mcp_route_for_an_administrator(): void {
        grant_wp_caps( [ 'read', 'manage_options' ] );

        $this->assertNull( $this->dispatch( self::OURS ), 'an allowed request must pass the filter value through untouched' );
    }

    /**
     * WordPress matches routes with `@^{route}$@i`: case-insensitively, and with
     * a `$` that also accepts one trailing newline. These paths all reach the MCP
     * handler, so the guard must decide on the matched route, not the path.
     */
    public function test_blocks_every_path_spelling_wordpress_matches_to_our_route(): void {
        grant_wp_caps( [ 'read' ] );

        foreach ( [ '/MCP/lw-site-manager', '/mcp/LW-Site-Manager', "/mcp/lw-site-manager\n" ] as $path ) {
            $this->assertInstanceOf(
                \WP_Error::class,
                $this->dispatch( self::OURS, $path ),
                sprintf( 'path %s reached our route unguarded', wp_json_encode( $path ) )
            );
        }
    }

    /**
     * Sub-paths of our server must be covered too, not just the exact route.
     */
    public function test_blocks_sub_paths_of_our_mcp_route(): void {
        grant_wp_caps( [ 'read' ] );

        $this->assertInstanceOf( \WP_Error::class, $this->dispatch( self::OURS . '/anything' ) );
    }

    /**
     * Another plugin's MCP server is none of our business — guarding it would
     * break their endpoint.
     */
    public function test_ignores_another_plugins_mcp_route(): void {
        grant_wp_caps( [ 'read' ] );

        $this->assertNull( $this->dispatch( '/mcp/fluent-crm' ) );
        $this->assertNull( $this->dispatch( '/wp/v2/posts' ) );
        $this->assertNull( RouteGuard::guard( null, $this->request( self::OURS ), null ) );
    }

    /**
     * A route that merely starts with the same characters must not be caught.
     */
    public function test_ignores_a_similarly_named_route(): void {
        grant_wp_caps( [ 'read' ] );

        $this->assertNull( $this->dispatch( self::OURS . '-other' ) );
    }

    /**
     * An earlier filter that already produced a response or error must win —
     * this guard only ever adds a denial, never overrides one.
     */
    public function test_passes_an_existing_result_through(): void {
        grant_wp_caps( [ 'manage_options' ] );
        $existing = new \WP_Error( 'something_else', 'already handled' );

        $this->assertSame( $existing, RouteGuard::guard( $existing, $this->request( self::OURS ), self::OURS ) );
    }

    /**
     * Wired to the hook that carries the matched route, with enough arguments
     * to receive it.
     */
    public function test_register_hooks_the_guard_onto_rest_dispatch_request(): void {
        grant_wp_caps( [ 'read' ] );
        RouteGuard::register();

        $result = apply_filters( 'rest_dispatch_request', null, $this->request( '/MCP/LW-SITE-MANAGER' ), self::OURS, [] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }
}
