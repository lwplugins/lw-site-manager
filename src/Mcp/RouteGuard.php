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
	 */
	public static function register(): void {
		add_filter( 'rest_pre_dispatch', [ self::class, 'guard' ], 10, 3 );
	}

	/**
	 * Deny requests to this plugin's MCP route from callers without the capability.
	 *
	 * @param mixed $result  Response to replace the request with, or null to continue.
	 * @param mixed $server  REST server instance (unused).
	 * @param mixed $request The request being dispatched.
	 * @return mixed
	 */
	public static function guard( mixed $result, mixed $server, mixed $request ): mixed {
		// Never override an answer another filter already produced.
		if ( null !== $result ) {
			return $result;
		}

		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return $result;
		}

		if ( ! self::isOwnRoute( (string) $request->get_route() ) ) {
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
	 * Whether a REST route belongs to this plugin's MCP server.
	 *
	 * Matches the server route and anything beneath it, but not a route that
	 * merely shares the same prefix (`…-other`), and not another plugin's MCP
	 * server, which we have no business gating.
	 *
	 * @param string $route Route path, e.g. /mcp/lw-site-manager.
	 */
	public static function isOwnRoute( string $route ): bool {
		$ours = '/' . Server::ROUTE_NS . '/' . Server::SERVER_ROUTE;
		$route = '/' . ltrim( $route, '/' );

		return $route === $ours || str_starts_with( $route, $ours . '/' );
	}
}
