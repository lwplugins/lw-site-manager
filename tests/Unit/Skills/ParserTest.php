<?php
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {

	public function test_parses_frontmatter_fields(): void {
		$raw = "---\nname: my-skill\ndescription: Do a thing\nenable_prompt: true\n---\n\nBody here.";
		$p = Parser::parse( $raw );
		$this->assertSame( 'my-skill', $p['name'] );
		$this->assertSame( 'Do a thing', $p['description'] );
		$this->assertTrue( $p['enable_prompt'] );
		$this->assertTrue( $p['enable_agentic'] ); // default true
		$this->assertSame( 'Body here.', $p['body'] );
		$this->assertNull( $p['parse_error'] );
	}

	public function test_no_frontmatter_keeps_whole_body(): void {
		$p = Parser::parse( "Just a body." );
		$this->assertSame( '', $p['name'] );
		$this->assertSame( 'Just a body.', $p['body'] );
	}

	public function test_unterminated_frontmatter_reports_error(): void {
		$p = Parser::parse( "---\nname: x\nno closing" );
		$this->assertNotNull( $p['parse_error'] );
	}

	public function test_default_enable_prompt_is_false(): void {
		$p = Parser::parse( "---\nname: x\ndescription: d\n---\n\nbody" );
		$this->assertFalse( $p['enable_prompt'] );
	}

	public function test_normalize_slug(): void {
		$this->assertSame( 'my-skill', Parser::normalize_slug( 'My Skill' ) );
		$this->assertSame( 'hello-world', Parser::normalize_slug( 'hello world' ) );
	}

	public function test_render_skill_md_uses_name_not_slug(): void {
		$md = Parser::render_skill_md( [
			'slug' => 'wc-order-ops', 'name' => 'WooCommerce Order Operations',
			'description' => 'd', 'content' => 'body',
		] );
		$this->assertStringContainsString( 'name: WooCommerce Order Operations', $md );
	}

	public function test_folded_block_description(): void {
		$p = Parser::parse( "---\nname: x\ndescription: >\n  Line one\n  line two\n\n  Second paragraph\nenable_prompt: true\n---\n\nbody" );
		$this->assertSame( "Line one line two\nSecond paragraph", $p['description'] );
		$this->assertTrue( $p['enable_prompt'] );
		$this->assertSame( 'body', $p['body'] );
	}

	public function test_folded_block_keeps_more_indented_lines(): void {
		$p = Parser::parse( "---\ndescription: >-\n  Intro\n    code line\n  outro\n---\nb" );
		$this->assertSame( "Intro\n  code line\noutro", $p['description'] );
	}

	/**
	 * @dataProvider provide_literal_headers
	 */
	public function test_literal_block_description( string $header ): void {
		$p = Parser::parse( "---\nname: x\ndescription: {$header}\n  first: line\n  second line\n\nenable_agentic: false\n---\nb" );
		$this->assertSame( "first: line\nsecond line", $p['description'] );
		$this->assertFalse( $p['enable_agentic'] );
		$this->assertSame( 'x', $p['name'] );
	}

	public static function provide_literal_headers(): array {
		return [
			'clip'                 => [ '|' ],
			'strip'                => [ '|-' ],
			'keep'                 => [ '|+' ],
			'indentation'          => [ '|2' ],
			'indentation and keep' => [ '|2+' ],
			'with comment'         => [ '| # note' ],
		];
	}

	public function test_block_with_crlf_line_endings(): void {
		$p = Parser::parse( "---\r\ndescription: >\r\n  a\r\n  b\r\nname: n\r\n---\r\nbody" );
		$this->assertSame( 'a b', $p['description'] );
		$this->assertSame( 'n', $p['name'] );
	}

	public function test_plain_value_continues_on_indented_lines(): void {
		$p = Parser::parse( "---\ndescription: A long text\n  that continues here\nname: n\n---\nb" );
		$this->assertSame( 'A long text that continues here', $p['description'] );
	}

	public function test_quoted_values(): void {
		$p = Parser::parse( "---\nname: 'It''s mine'\ndescription: \"Says \\\"hi\\\": ok\"\n---\nb" );
		$this->assertSame( "It's mine", $p['name'] );
		$this->assertSame( 'Says "hi": ok', $p['description'] );
	}

	public function test_nested_map_does_not_override_top_level_fields(): void {
		$p = Parser::parse( "---\nname: real\nmetadata:\n  name: fake\n  description: fake\ndescription: d\n---\nb" );
		$this->assertSame( 'real', $p['name'] );
		$this->assertSame( 'd', $p['description'] );
	}

	public function test_empty_block_is_empty(): void {
		$p = Parser::parse( "---\nname: x\ndescription: >\n---\nb" );
		$this->assertSame( '', $p['description'] );
	}
}
