<?php
/**
 * Checks the synced LW Plugins hub copy (lwplugins/admin-hub).
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Admin;

use LightweightPlugins\SiteManager\Admin\Hub\Assets;
use LightweightPlugins\SiteManager\Admin\Hub\Hub;
use LightweightPlugins\SiteManager\Admin\Hub\RegistryFallback;
use LightweightPlugins\SiteManager\Admin\ParentPage;
use PHPUnit\Framework\TestCase;

/**
 * The hub logic itself is tested in the admin-hub repo; these tests prove the
 * copy landed in this plugin's namespace, text domain and asset layout.
 */
final class HubIntegrationTest extends TestCase {

	/**
	 * Plugin root.
	 *
	 * @return string
	 */
	private function root(): string {
		return dirname( __DIR__, 3 ) . '/';
	}

	protected function tearDown(): void {
		Hub::reset();
		unset( $GLOBALS['wp_transients'] );
	}

	public function test_candidate_is_this_plugins_copy(): void {
		Hub::init( $this->root() . 'lw-site-manager.php' );

		$candidate = Hub::add_candidate( [] )[0];

		$this->assertSame( 'LightweightPlugins\\SiteManager\\Admin\\Hub\\Hub', $candidate['class'] );
		$this->assertSame( Hub::VERSION, $candidate['version'] );
	}

	public function test_legacy_parent_page_api_is_kept(): void {
		$GLOBALS['wp_transients'] = [ 'lw_plugins_registry' => [ 'lw-site-manager' => [ 'name' => 'LW Site Manager' ] ] ];

		$this->assertSame( 'lw-plugins', ParentPage::SLUG );
		$this->assertSame( [ 'lw-site-manager' ], array_keys( ParentPage::get_plugins_registry() ) );
	}

	public function test_bundled_assets_use_this_text_domain(): void {
		$dir = $this->root() . Assets::DIR . '/';
		$js  = (string) file_get_contents( $dir . 'index.js' );

		$this->assertFileExists( $dir . 'index.css' );
		$this->assertFileExists( $dir . 'index.asset.json' );
		$this->assertStringContainsString( '"lw-site-manager"', $js );
		$this->assertStringNotContainsString( 'lw-admin-hub', $js );
		$this->assertFileExists( $this->root() . 'languages/lw-site-manager-hu_HU-' . md5( Assets::DIR . '/index.js' ) . '.json' );
	}

	public function test_every_bundled_registry_plugin_has_an_icon(): void {
		foreach ( array_keys( RegistryFallback::get() ) as $slug ) {
			$this->assertFileExists( $this->root() . Assets::DIR . '/icons/' . $slug . '.svg' );
		}
	}
}
