<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Catalog;
use PHPUnit\Framework\TestCase;

final class CatalogTest extends TestCase {

	public function test_render_lists_slug_badge_and_description(): void {
		$md = Catalog::render( [
			[ 'slug' => 'demo', 'description' => 'Do a thing', 'source_label' => 'Built-in' ],
		] );
		$this->assertStringContainsString( '## Available Skills', $md );
		$this->assertStringContainsString( '`demo`', $md );
		$this->assertStringContainsString( '(Built-in)', $md );
		$this->assertStringContainsString( 'Do a thing', $md );
		$this->assertStringContainsString( 'site-manager/skill-get', $md );
	}

	public function test_inject_returns_original_when_no_skills(): void {
		$this->assertSame( 'orig', Catalog::inject( 'orig', [] ) );
	}

	public function test_inject_prepends_catalog_before_original(): void {
		$out = Catalog::inject( 'orig', [
			[ 'slug' => 'demo', 'description' => 'd', 'source_label' => 'Built-in' ],
		] );
		$this->assertStringEndsWith( 'orig', $out );
		$this->assertStringContainsString( '## Available Skills', $out );
	}

	public function test_inject_filter_passes_through_non_string(): void {
		$this->assertSame( 42, Catalog::inject_filter( 42 ) );
	}
}
