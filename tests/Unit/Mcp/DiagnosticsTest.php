<?php
/**
 * Tests for the MCP diagnostics shown on the AI / MCP screen.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\Diagnostics;
use PHPUnit\Framework\TestCase;

final class DiagnosticsTest extends TestCase {

	/**
	 * @param list<array{code: string, message: string, detail: string}> $warnings
	 * @return list<string>
	 */
	private static function codes( array $warnings ): array {
		return array_column( $warnings, 'code' );
	}

	public function test_healthy_enabled_server_has_no_warnings(): void {
		$this->assertSame( [], Diagnostics::warnings( true, Diagnostics::ADAPTER_OK, null, 'example.com', 'example.com' ) );
	}

	public function test_missing_adapter_is_reported_while_enabled(): void {
		$warnings = Diagnostics::warnings( true, Diagnostics::ADAPTER_MISSING, null, 'example.com', 'example.com' );

		$this->assertSame( [ 'adapter_missing' ], self::codes( $warnings ) );
		$this->assertSame( Diagnostics::missing_message(), $warnings[0]['message'] );
		$this->assertSame( '', $warnings[0]['detail'] );
	}

	public function test_outdated_adapter_names_the_copy_that_loaded(): void {
		$warnings = Diagnostics::warnings( true, Diagnostics::ADAPTER_OUTDATED, '/wp-content/plugins/woocommerce/vendor/mcp-adapter/includes', 'example.com', 'example.com' );

		$this->assertSame( [ 'adapter_outdated' ], self::codes( $warnings ) );
		$this->assertSame( Diagnostics::outdated_message(), $warnings[0]['message'] );
		$this->assertSame( '/wp-content/plugins/woocommerce/vendor/mcp-adapter/includes', $warnings[0]['detail'] );
	}

	/**
	 * Mirrors Bootstrap: the adapter is only looked at while the server is on.
	 */
	public function test_adapter_problems_are_not_reported_while_disabled(): void {
		$this->assertSame( [], Diagnostics::warnings( false, Diagnostics::ADAPTER_MISSING, null, 'example.com', 'example.com' ) );
		$this->assertSame( [], Diagnostics::warnings( false, Diagnostics::ADAPTER_OUTDATED, '/x', 'example.com', 'example.com' ) );
	}

	public function test_domain_change_explains_why_the_server_is_off(): void {
		$warnings = Diagnostics::warnings( false, Diagnostics::ADAPTER_OK, null, 'old.example', 'example.com' );

		$this->assertSame( [ 'domain_changed' ], self::codes( $warnings ) );
		$this->assertStringContainsString( 'old.example', $warnings[0]['message'] );
		$this->assertStringContainsString( 'example.com', $warnings[0]['message'] );
	}

	public function test_manual_disable_on_the_same_domain_is_not_a_warning(): void {
		$this->assertSame( [], Diagnostics::warnings( false, Diagnostics::ADAPTER_OK, null, 'example.com', 'example.com' ) );
	}

	public function test_never_locked_site_has_no_domain_warning(): void {
		$this->assertSame( [], Diagnostics::warnings( false, Diagnostics::ADAPTER_OK, null, '', 'example.com' ) );
	}

	/**
	 * The dev dependency ships a current adapter, so under the unit tests the
	 * live check sees a healthy install.
	 */
	public function test_adapter_state_reads_the_loaded_install(): void {
		$this->assertSame( Diagnostics::ADAPTER_OK, Diagnostics::adapter_state() );
	}
}
