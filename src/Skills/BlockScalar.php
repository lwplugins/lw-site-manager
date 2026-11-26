<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A YAML block scalar (`|` literal, `>` folded), per YAML 1.2 chapter 8.1.
 *
 * Honours the indentation indicator (content indent = parent indent + digit;
 * without one, the first non-blank line sets it) and the chomping indicator
 * (clip: one final line break, `-` strip: none, `+` keep: all trailing ones).
 * Folding joins text lines with a space, keeps a line break for every blank
 * line, and keeps the breaks around more-indented ("spaced") lines.
 */
final class BlockScalar {

	/**
	 * Header after the colon: style, then an optional indentation digit and
	 * chomping sign in either order, then an optional comment.
	 */
	private const HEADER = '/^([|>])([1-9][+-]?|[+-][1-9]?)?(?:\s+#.*)?\s*$/';

	/**
	 * Parse a header; null when the text after the colon is not one.
	 *
	 * @param string $head Text after the colon, trimmed.
	 * @return array{style: string, indent: int, chomp: string}|null
	 */
	public static function header( string $head ): ?array {
		if ( 1 !== preg_match( self::HEADER, $head, $match ) ) {
			return null;
		}

		$flags = $match[2] ?? '';

		return [
			'style'  => $match[1],
			'indent' => (int) preg_replace( '/\D/', '', $flags ),
			'chomp'  => (string) preg_replace( '/\d/', '', $flags ),
		];
	}

	/**
	 * The value of a block scalar.
	 *
	 * @param array{style: string, indent: int, chomp: string} $header       Parsed header.
	 * @param list<string>                                     $lines        Block lines, untrimmed.
	 * @param int                                              $parentIndent Indent of the key line.
	 */
	public static function value( array $header, array $lines, int $parentIndent ): string {
		$indent = $header['indent'] > 0 ? $parentIndent + $header['indent'] : self::detectIndent( $lines );
		$lines  = array_map(
			static function ( string $line ) use ( $indent ): string {
				if ( '' === trim( $line ) ) {
					return '';
				}
				$cut = min( $indent, strspn( $line, " \t" ) );
				return rtrim( substr( $line, $cut ), "\r" );
			},
			$lines
		);

		$trailing = 0;
		while ( [] !== $lines && '' === end( $lines ) ) {
			array_pop( $lines );
			++$trailing;
		}

		$body = '|' === $header['style'] ? implode( "\n", $lines ) : self::fold( $lines );

		return self::chomp( $body, $header['chomp'], $trailing );
	}

	/**
	 * Content indent from the first non-blank line.
	 *
	 * @param list<string> $lines Block lines.
	 */
	private static function detectIndent( array $lines ): int {
		foreach ( $lines as $line ) {
			if ( '' !== trim( $line ) ) {
				return strspn( $line, " \t" );
			}
		}

		return 0;
	}

	/**
	 * Fold the content lines of a `>` block (trailing blank lines already removed).
	 *
	 * @param list<string> $lines Content lines with the indent removed.
	 */
	private static function fold( array $lines ): string {
		$out    = '';
		$blank  = 0;
		$prev   = null; // Whether the previous non-blank line was spaced; null before the first.
		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				++$blank;
				continue;
			}

			$spaced = str_starts_with( $line, ' ' ) || str_starts_with( $line, "\t" );
			if ( null === $prev ) {
				$out .= str_repeat( "\n", $blank );
			} elseif ( ! $prev && ! $spaced ) {
				$out .= 0 === $blank ? ' ' : str_repeat( "\n", $blank );
			} else {
				$out .= str_repeat( "\n", $blank + 1 );
			}

			$out  .= $line;
			$prev  = $spaced;
			$blank = 0;
		}

		return $out;
	}

	/**
	 * Apply the chomping indicator.
	 *
	 * @param string $body     Content without a final line break.
	 * @param string $chomp    '', '-' or '+'.
	 * @param int    $trailing Number of trailing blank lines.
	 */
	private static function chomp( string $body, string $chomp, int $trailing ): string {
		if ( '-' === $chomp ) {
			return $body;
		}
		if ( '+' === $chomp ) {
			return ( '' === $body ? '' : $body . "\n" ) . str_repeat( "\n", $trailing );
		}

		return '' === $body ? '' : $body . "\n";
	}
}
