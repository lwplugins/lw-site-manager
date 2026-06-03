<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\BuiltInSource;
use PHPUnit\Framework\TestCase;

if ( ! defined( 'LW_SITE_MANAGER_DIR' ) ) {
	define( 'LW_SITE_MANAGER_DIR', dirname( __DIR__, 3 ) . '/' );
}

final class BuiltInSourceTest extends TestCase {

	public function test_loads_bundled_skill_directories(): void {
		$skills = BuiltInSource::load();
		$this->assertIsArray( $skills );
		foreach ( $skills as $s ) {
			$this->assertArrayHasKey( 'slug', $s );
			$this->assertArrayHasKey( 'content', $s );
			$this->assertNotSame( '', $s['content'] );
		}
		$slugs = array_column( $skills, 'slug' );
		$this->assertContains( 'site-health-triage', $slugs );
		$this->assertContains( 'woocommerce-order-ops', $slugs );
		$this->assertContains( 'safe-bulk-content', $slugs );
	}
}
