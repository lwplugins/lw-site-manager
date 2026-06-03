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
}
