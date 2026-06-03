<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Sources;
use PHPUnit\Framework\TestCase;

final class SourcesTest extends TestCase {

	public function test_find_returns_null_when_no_source_has_slug(): void {
		add_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
		$this->assertNull( Sources::find( 'nope' ) );
		remove_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
	}

	public function test_sources_are_priority_sorted_and_annotated(): void {
		$loader = static fn(): array => [ [
			'slug' => 'demo', 'name' => 'Demo', 'description' => 'd',
			'content' => 'body', 'enable_prompt' => false, 'enable_agentic' => true,
		] ];
		$cb = static function ( array $s ) use ( $loader ): array {
			$s['t'] = [ 'id' => 't', 'priority' => 5, 'label' => 'Test', 'loader' => $loader ];
			return $s;
		};
		add_filter( 'lw_site_manager_skill_sources', $cb );
		$found = Sources::find( 'demo' );
		$this->assertSame( 't', $found['source'] );
		$this->assertSame( 'Test', $found['source_label'] );
		$this->assertContains( 'demo', array_column( Sources::discoverable( 'agentic' ), 'slug' ) );
		remove_filter( 'lw_site_manager_skill_sources', $cb );
	}
}
