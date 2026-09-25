<?php
/**
 * REST / admin-screen stubs for the admin REST controller unit tests.
 *
 * @package LightweightPlugins\SiteManager\Tests
 */

declare(strict_types=1);

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Method constants of WP_REST_Server.
	 */
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST, PUT, PATCH';
		const DELETABLE = 'DELETE';
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Minimal WP_REST_Response stub.
	 */
	class WP_REST_Response {
		/** @var mixed */
		public $data;
		public int $status;

		/**
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status.
		 */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * Records registered routes in $GLOBALS['wp_rest_routes'].
	 *
	 * @param string $route_namespace Namespace.
	 * @param string $route           Route.
	 * @param array  $args            Endpoints.
	 */
	function register_rest_route( string $route_namespace, string $route, array $args = [], bool $override = false ): bool {
		$GLOBALS['wp_rest_routes'][ $route_namespace . $route ] = $args;
		return true;
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( string $path = '' ): string {
		return 'http://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_is_application_passwords_available' ) ) {
	/**
	 * Toggle with $GLOBALS['wp_app_passwords_available'] (default true).
	 */
	function wp_is_application_passwords_available(): bool {
		return (bool) ( $GLOBALS['wp_app_passwords_available'] ?? true );
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	/**
	 * Returns the hook suffix WordPress would build (or the value of
	 * $GLOBALS['wp_submenu_hook'] when a test sets it).
	 */
	function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '' ) {
		return $GLOBALS['wp_submenu_hook'] ?? 'lw-plugins_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', string $icon_url = '', $position = null ): string {
		$GLOBALS['admin_page_hooks'][ $menu_slug ] = $menu_slug;
		return 'toplevel_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	/**
	 * Screen whose id is $GLOBALS['wp_current_screen_id'], or null.
	 */
	function get_current_screen(): ?object {
		$id = $GLOBALS['wp_current_screen_id'] ?? null;
		return null === $id ? null : (object) [ 'id' => $id ];
	}
}
