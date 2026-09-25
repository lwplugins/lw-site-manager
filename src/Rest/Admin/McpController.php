<?php
/**
 * MCP server REST controller.
 *
 * @package LightweightPlugins\SiteManager
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Rest\Admin;

use LightweightPlugins\SiteManager\Mcp\AdapterVersion;
use LightweightPlugins\SiteManager\Mcp\Diagnostics;
use LightweightPlugins\SiteManager\Mcp\Server;
use LightweightPlugins\SiteManager\Mcp\Toggle;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET/POST lw-site-manager/v1/admin/mcp: the server state, how to connect a
 * client, and what is wrong with it. POST { enabled } goes through Toggle,
 * so enabling (re)binds the server to the current domain.
 */
final class McpController {

	/**
	 * Largest accepted request body, in bytes.
	 */
	private const MAX_BYTES = 8192;

	/**
	 * Placeholder the user replaces with their own credentials.
	 */
	private const AUTH_PLACEHOLDER = 'Basic BASE64(user:application_password)';

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		Routes::add(
			'/admin/mcp',
			[
				WP_REST_Server::READABLE  => 'get_status',
				WP_REST_Server::CREATABLE => 'save_status',
			],
			$this
		);
	}

	/**
	 * Current state.
	 */
	public function get_status(): WP_REST_Response {
		return new WP_REST_Response( self::shape() );
	}

	/**
	 * Turn the server on or off.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_status( WP_REST_Request $request ) {
		if ( strlen( (string) $request->get_body() ) > self::MAX_BYTES ) {
			return Routes::error( 'lw_site_manager_too_large', __( 'The request is too large.', 'lw-site-manager' ), 413 );
		}

		$body = $request->get_json_params();

		if ( empty( $body ) ) {
			$body = $request->get_body_params();
		}

		$enabled = self::to_bool( ( (array) $body )['enabled'] ?? null );

		if ( null === $enabled ) {
			return Routes::error(
				'lw_site_manager_invalid',
				__( 'Send "enabled" as true or false.', 'lw-site-manager' ),
				400,
				[ 'fields' => [ 'enabled' => [ __( 'Must be true or false.', 'lw-site-manager' ) ] ] ]
			);
		}

		if ( $enabled ) {
			Toggle::enable();
		} else {
			Toggle::disable();
		}

		return new WP_REST_Response( self::shape() );
	}

	/**
	 * Response shape shared by GET and POST.
	 *
	 * @return array<string, mixed>
	 */
	public static function shape(): array {
		// First: is_enabled() may bind the domain or switch the server off.
		$enabled = Toggle::is_enabled();
		$locked  = Toggle::locked_domain();
		$current = Toggle::current_domain();
		$adapter = $enabled ? Diagnostics::adapter_state() : Diagnostics::ADAPTER_OK;

		return [
			'enabled'                         => $enabled,
			'endpoint'                        => Server::endpoint(),
			'server_id'                       => Server::SERVER_ID,
			'domain'                          => [
				'locked'  => $locked,
				'current' => $current,
				'matches' => '' === $locked || $locked === $current,
			],
			'snippet'                         => self::snippet(),
			'warnings'                        => Diagnostics::warnings(
				$enabled,
				$adapter,
				Diagnostics::ADAPTER_OUTDATED === $adapter ? AdapterVersion::loadedRoot() : null,
				$locked,
				$current
			),
			'application_passwords_available' => wp_is_application_passwords_available(),
			'profile_url'                     => admin_url( 'profile.php#application-passwords-section' ),
		];
	}

	/**
	 * The .mcp.json object for an MCP client.
	 *
	 * @return array<string, mixed>
	 */
	private static function snippet(): array {
		return [
			'mcpServers' => [
				Server::SERVER_ID => [
					'type'    => 'http',
					'url'     => Server::endpoint(),
					'headers' => [ 'Authorization' => self::AUTH_PLACEHOLDER ],
				],
			],
		];
	}

	/**
	 * A boolean from JSON (true/false, 1/0, "1"/"0", "true"/"false"), or null.
	 *
	 * @param mixed $value Submitted value.
	 */
	private static function to_bool( $value ): ?bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( ! is_int( $value ) && ! is_string( $value ) ) {
			return null;
		}

		$map = [
			'1'     => true,
			'true'  => true,
			'0'     => false,
			'false' => false,
		];

		return $map[ strtolower( (string) $value ) ] ?? null;
	}
}
