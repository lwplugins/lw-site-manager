<?php
/**
 * Detection of an outdated bundled MCP adapter.
 *
 * On any WooCommerce site the adapter that actually runs is the copy
 * WooCommerce eagerly requires from its own vendor directory (v0.3.0), not the
 * one this plugin ships. That copy has no `mcp_adapter_tool_call_result` hook,
 * so ResultUnwrapper never runs and failed tool calls are reported to the agent
 * as successes.
 *
 * class_exists() cannot detect this: our PSR-4 autoloader still resolves
 * classes the older copy lacks from our own vendor, so the runtime is a mix and
 * the check reports "current" while a 0.3.0 core is running. Detection must
 * therefore look inside the installation the loaded adapter actually came from.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\AdapterVersion;
use PHPUnit\Framework\TestCase;

final class AdapterVersionTest extends TestCase {

    private string $tmp = '';

    protected function tearDown(): void {
        if ( '' !== $this->tmp && is_dir( $this->tmp ) ) {
            exec( 'rm -rf ' . escapeshellarg( $this->tmp ) );
        }
        parent::tearDown();
    }

    private function makeTree( bool $current ): string {
        $this->tmp = sys_get_temp_dir() . '/lwsm-adapter-' . uniqid( '', true ) . '/includes';
        mkdir( $this->tmp . '/Core', 0777, true );
        touch( $this->tmp . '/Core/McpAdapter.php' );
        if ( $current ) {
            touch( $this->tmp . '/Core/McpVersionNegotiator.php' );
        }
        return $this->tmp;
    }

    public function test_tree_shipping_the_newer_core_is_current(): void {
        $this->assertTrue( AdapterVersion::treeIsCurrent( $this->makeTree( true ) ) );
    }

    /**
     * The shape of WooCommerce's bundled v0.3.0: an adapter core without the
     * classes 0.5 added, and without the tool-result filter.
     */
    public function test_tree_without_the_newer_core_is_outdated(): void {
        $this->assertFalse( AdapterVersion::treeIsCurrent( $this->makeTree( false ) ) );
    }

    public function test_unreadable_tree_is_not_reported_as_current(): void {
        $this->assertFalse( AdapterVersion::treeIsCurrent( '/nonexistent/mcp-adapter/includes' ) );
    }

    /**
     * End-to-end against the adapter this plugin actually develops against
     * (v0.5.0, a dev dependency), which is the case that must report current.
     */
    public function test_our_own_bundled_adapter_is_current(): void {
        AdapterVersion::reset();

        $this->assertTrue( AdapterVersion::isCurrent() );
        $this->assertStringContainsString( 'mcp-adapter', (string) AdapterVersion::loadedRoot() );
    }
}
