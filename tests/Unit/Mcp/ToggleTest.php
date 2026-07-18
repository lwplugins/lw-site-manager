<?php
/**
 * Tests for Toggle.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\Toggle;
use PHPUnit\Framework\TestCase;

final class ToggleTest extends TestCase {

	protected function setUp(): void {
		reset_wp_options();
	}

	public function test_enabled_by_default(): void {
		$this->assertTrue( Toggle::is_enabled() );
	}

	public function test_explicit_disable_persists(): void {
		Toggle::disable();
		$this->assertFalse( Toggle::is_enabled() );
	}

	public function test_default_on_records_domain_for_clone_protection(): void {
		// Default-on: the first check records the current domain...
		$this->assertTrue( Toggle::is_enabled() );
		$this->assertSame( 'example.com', get_option( Toggle::OPTION_DOMAIN ) );

		// ...so a later clone on a different host auto-disables.
		update_option( Toggle::OPTION_DOMAIN, 'clone.example' );
		$this->assertFalse( Toggle::is_enabled() );
	}

	public function test_enable_then_enabled_on_same_domain(): void {
		Toggle::enable();
		$this->assertTrue( Toggle::is_enabled() );
	}

	public function test_auto_disables_on_domain_change(): void {
		Toggle::enable();
		// Simulate a domain change by overwriting the stored domain with a different host.
		update_option( Toggle::OPTION_DOMAIN, 'some-old-host.example' );
		$this->assertFalse( Toggle::is_enabled() );
		$this->assertFalse( (bool) get_option( Toggle::OPTION_ENABLED ) );
	}
}
