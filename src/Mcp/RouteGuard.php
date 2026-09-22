<?php
/**
 * Adapter-independent capability gate on the plugin's MCP route.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Second, independent capability check on this plugin's MCP endpoint.
 *
 * The adapter applies its own gate as
 * `apply_filters( 'mcp_adapter_default_transport_permission_user_capability', 'read', ... )`
 * — note the default value. TransportGuard raises that to `manage_options`, but
 * only for as long as that one filter is actually applied. If it stops being
 * applied for any reason — an upstream refactor, a hook rename, a different
 * bundled copy of the library winning the autoload race, which is exactly what
 * already happens on WooCommerce stores — the endpoint quietly falls back to
 * `read`, a capability every logged-in subscriber holds.
 *
 * That is a single point of failure for an administrator-level surface, and the
 * fallback is present in every adapter version, not just the older one. This
 * layer runs on WordPress's own REST dispatch instead, so it holds regardless of
 * which adapter is loaded or whether its filters fire.
 */
final class RouteGuard {

	/**
	 * Hook the guard onto REST dispatch.
	 *
	 * `rest_dispatch_request` is the first filter that receives the route
	 * WordPress actually matched, and it runs before the route callback on every
	 * path that can reach it (single requests and batch sub-requests alike).
	 */
	public static function register(): void {
		add_filter( 'rest_dispatch_request', [ self::class, 'guard' ], 10, 3 );
	}

	/**
	 * Deny dispatch of this plugin's MCP route to callers without the capability.
	 *
	 * Keys on the matched route pattern, never on the path the client sent.
	 * WordPress matches routes case-insensitively, and the `$` anchor of its
	 * route regex also accepts a trailing newline, so `/MCP/LW-Site-Manager` or
	 * `?rest_route=/mcp/lw-site-manager%0A` reach the same handler under a
	 * spelling a string comparison on the path would not recognise.
	 *
	 * @param mixed $result  Dispatch result from an earlier filter, or null to continue.
	 * @param mixed $request The request being dispatched (unused).
	 * @param mixed $route   The registered route pattern WordPress matched.
	 * @return mixed
	 */
	public static function guard( mixed $result, mixed $request, mixed $route ): mixed {
		// Never override an answer another filter already produced.
		if ( null !== $result ) {
			return $result;
		}

		if ( ! is_string( $route ) || ! self::isOwnRoute( $route ) ) {
			return $result;
		}

		if ( current_user_can( TransportGuard::capability( null ) ) ) {
			return $result;
		}

		return new \WP_Error(
			'rest_forbidden',
			__( 'You are not allowed to access the MCP endpoint.', 'lw-site-manager' ),
			[ 'status' => is_user_logged_in() ? 403 : 401 ]
		);
	}

	/**
	 * Whether a registered REST route belongs to this plugin's MCP server.
	 *
	 * Matches the server route and anything beneath it, but not a route that
	 * merely shares the same prefix (`…-other`), and not another plugin's MCP
	 * server, which we have no business gating. Case-insensitive, like
	 * WordPress's own route matching.
	 *
	 * @param string $route Route pattern, e.g. /mcp/lw-site-manager.
	 */
	public static function isOwnRoute( string $route ): bool {
		$ours  = strtolower( '/' . Server::ROUTE_NS . '/' . Server::SERVER_ROUTE );
		$route = strtolower( '/' . ltrim( $route, '/' ) );

		return $route === $ours || str_starts_with( $route, $ours . '/' );
	}
}
