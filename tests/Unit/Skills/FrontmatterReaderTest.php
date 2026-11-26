<?php
/**
 * YAML scalar semantics of the SKILL.md frontmatter reader.
 *
 * Expected values follow YAML 1.2 (chapters 5.7, 7.3, 8.1), cross-checked
 * against a reference YAML parser.
 */
declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\FrontmatterReader;
use PHPUnit\Framework\TestCase;

final class FrontmatterReaderTest extends TestCase {

	/**
	 * @dataProvider provide_scalars
	 */
	public function test_scalar_value( string $front, string $expected ): void {
		$this->assertSame( $expected, FrontmatterReader::read( $front )['d'] ?? null );
	}

	public function test_keep_chomping_through_the_parser(): void {
		$p = \LightweightPlugins\SiteManager\Skills\Parser::parse( "---\nname: |+\n  a\n\n---\nbody" );
		$this->assertSame( 'a', $p['name'] ); // Parser trims; the block itself keeps "a\n\n".
		$this->assertSame( 'body', $p['body'] );
	}

	/**
	 * Frontmatter text is the lines between the --- markers joined by "\n",
	 * without a final line break (as Parser hands it over).
	 */
	public static function provide_scalars(): array {
		return [
			'folded text lines'                  => [ "d: >\n  Line one\n  line two\n\n  Second\n", "Line one line two\nSecond\n" ],
			'folded blank lines around spaced'   => [ "d: >\n  a\n    b\n\n    c\n\n  e", "a\n  b\n\n  c\n\ne\n" ],
			'folded two blank lines'             => [ "d: >\n  a\n\n\n  b", "a\n\nb\n" ],
			'folded leading blank line'          => [ "d: >\n\n  a\n  b", "\na b\n" ],
			'literal explicit indent'            => [ "d: |1\n   x\n  y", "  x\n y\n" ],
			'folded explicit indent'             => [ "d: >2\n     x\n   y", "   x\n y\n" ],
			'explicit indent then chomp'         => [ "d: |2-\n    x\n  y\n", "  x\ny" ],
			'chomp before indent'                => [ "d: |-2\n    x\n", '  x' ],
			'literal clip'                       => [ "d: |\n  a\n\n", "a\n" ],
			'literal strip'                      => [ "d: |-\n  a\n\n", 'a' ],
			'literal keep, one blank line'       => [ "d: |+\n  a\n", "a\n\n" ],
			'empty block'                        => [ "d: |\nx: 1", '' ],
			'double-quoted escapes'              => [ "d: \"caf\\u00e9 \\x41 \\\"q\\\" tab\\tend \\U0001F600\"", "café A \"q\" tab\tend \u{1F600}" ],
			'double-quoted escaped line break'   => [ "d: \"abc\\\n  def\"", 'abcdef' ],
			'escaped backslash before line end'  => [ "d: \"abc\\\\\n  def\"", 'abc\\ def' ],
			'double-quoted folds lines'          => [ "d: \"a\n  b\n\n  c\"", "a b\nc" ],
			'single-quoted keeps backslashes'    => [ "d: 'a\\nb'", 'a\\nb' ],
		];
	}
}
