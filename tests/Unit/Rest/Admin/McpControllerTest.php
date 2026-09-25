<?php
/**
 * Tests for GET/POST lw-site-manager/v1/admin/mcp.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Rest\Admin
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Rest\Admin;

use LightweightPlugins\SiteManager\Mcp\Toggle;
use LightweightPlugins\SiteManager\Rest\Admin\McpController;
use LightweightPlugins\SiteManager\Rest\Admin\Routes;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class McpControllerTest extends TestCase {

	private McpController $controller;

	protected function setUp(): void {
		reset_wp_options();
		reset_wp_caps();
		$GLOBALS['wp_rest_routes'] = [];
		unset( $GLOBALS['wp_app_passwords_available'] );
		$this->controller = new McpController();
	}

	protected function tearDown(): void {
		reset_wp_caps();
		unset( $GLOBALS['wp_app_passwords_available'] );
	}

	private static function post( string $body ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/lw-site-manager/v1/admin/mcp' );
		$request->set_body( $body );
		return $request;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function data( $response ): array {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		return $response->get_data();
	}

	public function test_registers_get_and_post_behind_manage_options(): void {
		Routes::register_routes();

		$endpoints = $GLOBALS['wp_rest_routes']['lw-site-manager/v1/admin/mcp'] ?? null;
		$this->assertIsArray( $endpoints );
		$this->assertSame( [ 'GET', 'POST' ], array_column( $endpoints, 'methods' ) );

		foreach ( $endpoints as $endpoint ) {
			$this->assertSame( [ Routes::class, 'can_manage' ], $endpoint['permission_callback'] );
		}

		$this->assertFalse( Routes::can_manage() );
		grant_wp_caps( [ 'manage_options' ] );
		$this->assertTrue( Routes::can_manage() );
	}

	public function test_get_returns_the_full_screen_state(): void {
		$data = $this->data( $this->controller->get_status() );

		$this->assertSame(
			[ 'enabled', 'endpoint', 'server_id', 'domain', 'snippet', 'warnings', 'application_passwords_available', 'profile_url' ],
			array_keys( $data )
		);
		$this->assertTrue( $data['enabled'] );
		$this->assertSame( 'http://example.com/wp-json/mcp/lw-site-manager', $data['endpoint'] );
		$this->assertSame( 'lw-site-manager', $data['server_id'] );
		$this->assertSame( [ 'locked' => 'example.com', 'current' => 'example.com', 'matches' => true ], $data['domain'] );
		$this->assertSame( [], $data['warnings'] );
		$this->assertTrue( $data['application_passwords_available'] );
		$this->assertSame( 'http://example.com/wp-admin/profile.php#application-passwords-section', $data['profile_url'] );
	}

	public function test_snippet_is_the_mcp_json_object(): void {
		$snippet = $this->data( $this->controller->get_status() )['snippet'];

		$this->assertSame(
			[
				'mcpServers' => [
					'lw-site-manager' => [
						'type'    => 'http',
						'url'     => 'http://example.com/wp-json/mcp/lw-site-manager',
						'headers' => [ 'Authorization' => 'Basic BASE64(user:application_password)' ],
					],
				],
			],
			$snippet
		);
	}

	public function test_get_reports_unavailable_application_passwords(): void {
		$GLOBALS['wp_app_passwords_available'] = false;

		$this->assertFalse( $this->data( $this->controller->get_status() )['application_passwords_available'] );
	}

	public function test_get_after_a_domain_change_reports_the_auto_disable(): void {
		Toggle::enable();
		update_option( Toggle::OPTION_DOMAIN, 'old.example' );

		$data = $this->data( $this->controller->get_status() );

		$this->assertFalse( $data['enabled'] );
		$this->assertSame( [ 'locked' => 'old.example', 'current' => 'example.com', 'matches' => false ], $data['domain'] );
		$this->assertSame( [ 'domain_changed' ], array_column( $data['warnings'], 'code' ) );
	}

	public function test_post_false_disables_the_server(): void {
		$data = $this->data( $this->controller->save_status( self::post( '{"enabled":false}' ) ) );

		$this->assertFalse( $data['enabled'] );
		$this->assertFalse( Toggle::is_enabled() );
		$this->assertSame( '0', get_option( Toggle::OPTION_ENABLED ) );
	}

	public function test_post_true_re_enables_and_relocks_to_the_current_domain(): void {
		Toggle::enable();
		update_option( Toggle::OPTION_DOMAIN, 'old.example' );
		Toggle::is_enabled();

		$data = $this->data( $this->controller->save_status( self::post( '{"enabled":true}' ) ) );

		$this->assertTrue( $data['enabled'] );
		$this->assertSame( 'example.com', $data['domain']['locked'] );
		$this->assertTrue( $data['domain']['matches'] );
		$this->assertSame( [], $data['warnings'] );
	}

	public function test_post_without_enabled_is_rejected_and_changes_nothing(): void {
		$result = $this->controller->save_status( self::post( '{}' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'lw_site_manager_invalid', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertArrayHasKey( 'enabled', $result->get_error_data()['fields'] );
		$this->assertTrue( Toggle::is_enabled() );
	}

	public function test_post_with_a_non_boolean_is_rejected(): void {
		foreach ( [ '{"enabled":"maybe"}', '{"enabled":[true]}', '{"enabled":null}', '{"enabled":2}' ] as $body ) {
			$result = $this->controller->save_status( self::post( $body ) );
			$this->assertInstanceOf( WP_Error::class, $result, $body );
		}
		$this->assertTrue( Toggle::is_enabled() );
	}

	public function test_post_accepts_boolean_like_values(): void {
		$this->assertFalse( $this->data( $this->controller->save_status( self::post( '{"enabled":0}' ) ) )['enabled'] );
		$this->assertTrue( $this->data( $this->controller->save_status( self::post( '{"enabled":"1"}' ) ) )['enabled'] );
		$this->assertFalse( $this->data( $this->controller->save_status( self::post( '{"enabled":"false"}' ) ) )['enabled'] );
	}

	public function test_post_rejects_an_oversized_body(): void {
		$result = $this->controller->save_status( self::post( '{"enabled":false,"pad":"' . str_repeat( 'x', 9000 ) . '"}' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 413, $result->get_error_data()['status'] );
		$this->assertTrue( Toggle::is_enabled() );
	}
}
