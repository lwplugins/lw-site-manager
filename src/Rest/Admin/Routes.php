<?php
/**
 * Admin REST routes bootstrap.
 *
 * @package LightweightPlugins\SiteManager
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Rest\Admin;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the lw-site-manager/v1/admin/* routes used by the React admin.
 * Every route needs manage_options; REST cookie auth supplies the nonce.
 */
final class Routes {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'lw-site-manager/v1';

	/**
	 * Hook the route registration.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Register every admin route.
	 */
	public static function register_routes(): void {
		( new McpController() )->register_routes();
		( new SkillsController() )->register_routes();
	}

	/**
	 * Permission callback of every admin route.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Register one route with a handler per HTTP method.
	 *
	 * @param string                $path     Route path under the namespace.
	 * @param array<string, string> $handlers Method constant => public method name on $owner.
	 * @param object                $owner    Controller instance.
	 */
	public static function add( string $path, array $handlers, object $owner ): void {
		$endpoints = [];

		foreach ( $handlers as $methods => $callback ) {
			$endpoints[] = [
				'methods'             => $methods,
				'callback'            => [ $owner, $callback ],
				'permission_callback' => [ self::class, 'can_manage' ],
			];
		}

		register_rest_route( self::NAMESPACE, $path, $endpoints );
	}

	/**
	 * A translated REST error.
	 *
	 * @param string               $code    Error code.
	 * @param string               $message Translated message.
	 * @param int                  $status  HTTP status.
	 * @param array<string, mixed> $data    Extra error data.
	 */
	public static function error( string $code, string $message, int $status, array $data = [] ): WP_Error {
		return new WP_Error( $code, $message, array_merge( [ 'status' => $status ], $data ) );
	}
}
