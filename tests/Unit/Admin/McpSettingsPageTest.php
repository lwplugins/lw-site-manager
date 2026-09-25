<?php
/**
 * Tests for the AI / MCP screen mount point.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Admin;

use LightweightPlugins\SiteManager\Admin\McpSettingsPage;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'LW_SITE_MANAGER_DIR' ) ) {
	define( 'LW_SITE_MANAGER_DIR', dirname( __DIR__, 3 ) . '/' );
}

final class McpSettingsPageTest extends TestCase {

	/** @var mixed */
	private $saved_filters;

	protected function setUp(): void {
		$this->saved_filters = $GLOBALS['wp_filter'] ?? [];
		$GLOBALS['admin_page_hooks'] = [ 'lw-plugins' => 'lw-plugins' ];
		reset_wp_caps();
	}

	protected function tearDown(): void {
		$GLOBALS['wp_filter'] = $this->saved_filters;
		unset( $GLOBALS['wp_submenu_hook'], $GLOBALS['wp_current_screen_id'], $GLOBALS['admin_page_hooks'] );
		reset_wp_caps();
	}

	public function test_render_prints_the_mount_point_outside_wrap(): void {
		grant_wp_caps( [ 'manage_options' ] );

		ob_start();
		( new McpSettingsPage() )->render();
		$html = (string) ob_get_clean();

		$this->assertSame( '<div id="lw-site-manager-root" class="lw-site-manager-root"></div>', $html );
	}

	public function test_render_prints_nothing_without_manage_options(): void {
		ob_start();
		( new McpSettingsPage() )->render();

		$this->assertSame( '', (string) ob_get_clean() );
	}

	/**
	 * The body class follows the hook suffix WordPress returned, which is
	 * built from the (translatable) parent menu title.
	 */
	public function test_body_class_uses_the_returned_hook_suffix(): void {
		$GLOBALS['wp_submenu_hook'] = 'lw-bovitmenyek_page_lw-site-manager-mcp';
		$page                       = new McpSettingsPage();
		$page->register_menu();

		$GLOBALS['wp_current_screen_id'] = 'lw-plugins_page_lw-site-manager-mcp';
		$this->assertSame( 'a', $page->body_class( 'a' ) );

		$GLOBALS['wp_current_screen_id'] = 'lw-bovitmenyek_page_lw-site-manager-mcp';
		$this->assertSame( 'a lw-site-manager-screen', $page->body_class( 'a' ) );
	}

	public function test_body_class_is_untouched_before_the_menu_is_registered(): void {
		$GLOBALS['wp_current_screen_id'] = 'lw-plugins_page_lw-site-manager-mcp';

		$this->assertSame( 'a', ( new McpSettingsPage() )->body_class( 'a' ) );
	}
}
