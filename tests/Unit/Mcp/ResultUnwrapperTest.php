<?php
/**
 * Tests for ResultUnwrapper.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\ResultUnwrapper;
use PHPUnit\Framework\TestCase;

final class ResultUnwrapperTest extends TestCase {

	public function test_passes_through_non_execute_tool(): void {
		$r = [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'x' ] ];
		$this->assertSame( $r, ResultUnwrapper::filter( $r, [], 'mcp-adapter-discover-abilities' ) );
	}

	public function test_unwraps_inner_error(): void {
		$r   = [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'boom', 'code' => 'E1' ] ];
		$out = ResultUnwrapper::filter( $r, [], 'mcp-adapter-execute-ability' );
		$this->assertFalse( $out['success'] );
		$this->assertStringContainsString( 'boom', $out['error'] );
		$this->assertStringContainsString( 'E1', $out['error'] );
	}

	public function test_passes_through_inner_success(): void {
		$r = [ 'success' => true, 'data' => [ 'success' => true, 'foo' => 1 ] ];
		$this->assertSame( $r, ResultUnwrapper::filter( $r, [], 'mcp-adapter-execute-ability' ) );
	}

	public function test_passes_through_when_no_inner_error_string(): void {
		$r = [ 'success' => true, 'data' => [ 'success' => false ] ];
		$this->assertSame( $r, ResultUnwrapper::filter( $r, [], 'mcp-adapter-execute-ability' ) );
	}
}
