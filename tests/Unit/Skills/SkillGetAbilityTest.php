<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\SkillGetAbility;
use PHPUnit\Framework\TestCase;

final class SkillGetAbilityTest extends TestCase {

	public function test_unknown_slug_returns_not_found(): void {
		add_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
		$result = SkillGetAbility::execute( [ 'slug' => 'does-not-exist' ] );
		$this->assertFalse( $result['found'] );
		remove_filter( 'lw_site_manager_skill_sources', '__return_empty_array' );
	}

	public function test_empty_slug_returns_not_found(): void {
		$this->assertFalse( SkillGetAbility::execute( [] )['found'] );
	}

	public function test_known_slug_returns_rendered_skill(): void {
		$loader = static fn(): array => [ [
			'slug' => 'demo', 'name' => 'Demo', 'description' => 'd',
			'content' => '# Body', 'enable_prompt' => false, 'enable_agentic' => true,
		], ];
		$cb = static function ( array $s ) use ( $loader ): array {
			$s['t'] = [ 'id' => 't', 'priority' => 5, 'label' => 'Test', 'loader' => $loader ];
			return $s;
		};
		add_filter( 'lw_site_manager_skill_sources', $cb );
		$result = SkillGetAbility::execute( [ 'slug' => 'demo' ] );
		$this->assertTrue( $result['found'] );
		$this->assertStringContainsString( '# Body', $result['content'] );
		$this->assertSame( 't', $result['source'] );
		remove_filter( 'lw_site_manager_skill_sources', $cb );
	}

	public function test_strips_namespace_prefix_from_slug(): void {
		$loader = static fn(): array => [ [
			'slug' => 'demo', 'name' => 'Demo', 'description' => 'd',
			'content' => '# Body', 'enable_prompt' => false, 'enable_agentic' => true,
		], ];
		$cb = static function ( array $s ) use ( $loader ): array {
			$s['t'] = [ 'id' => 't', 'priority' => 5, 'label' => 'Test', 'loader' => $loader ];
			return $s;
		};
		add_filter( 'lw_site_manager_skill_sources', $cb );
		$result = SkillGetAbility::execute( [ 'slug' => 'site-manager/demo' ] );
		$this->assertTrue( $result['found'] );
		remove_filter( 'lw_site_manager_skill_sources', $cb );
	}
}
