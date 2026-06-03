<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\AbilityExposer;
use PHPUnit\Framework\TestCase;

final class AbilityExposerTest extends TestCase {

	public function test_flags_own_ability_public_tool_by_default(): void {
		$out = AbilityExposer::flag_args( [ 'meta' => [ 'annotations' => [] ] ], 'site-manager/wc-list-orders' );
		$this->assertTrue( $out['meta']['mcp']['public'] );
		$this->assertSame( 'tool', $out['meta']['mcp']['type'] );
	}

	public function test_leaves_foreign_ability_untouched(): void {
		$args = [ 'meta' => [] ];
		$this->assertSame( $args, AbilityExposer::flag_args( $args, 'other-plugin/thing' ) );
	}

	public function test_preserves_explicit_prompt_type(): void {
		$out = AbilityExposer::flag_args( [ 'meta' => [ 'mcp' => [ 'type' => 'prompt' ] ] ], 'site-manager/skill-prompt-x' );
		$this->assertSame( 'prompt', $out['meta']['mcp']['type'] );
		$this->assertTrue( $out['meta']['mcp']['public'] );
	}

	public function test_adds_meta_when_absent(): void {
		$out = AbilityExposer::flag_args( [], 'site-manager/health-check' );
		$this->assertTrue( $out['meta']['mcp']['public'] );
	}
}
