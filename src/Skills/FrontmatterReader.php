<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the top-level scalars of a SKILL.md frontmatter block.
 *
 * Not a full YAML parser: it covers what skill frontmatter uses. A value may
 * be plain or quoted on one line, continue on more-indented lines (folded with
 * spaces), or be a block scalar: `|` keeps the line breaks, `>` folds them.
 * Chomping (`-`, `+`) and indentation indicators are accepted; the caller
 * trims the values, so the trailing line break they control does not matter.
 * Lines indented under a key belong to that key, so a nested map never
 * overrides a top-level field.
 */
final class FrontmatterReader {

	private const BLOCK_HEADER = '/^([|>])(?:[1-9]?[+-]?|[+-]?[1-9]?)\s*(?:#.*)?$/';

	/**
	 * Top-level key => scalar value, keys lowercased.
	 *
	 * @param string $front Frontmatter text between the --- lines.
	 * @return array<string, string>
	 */
	public static function read( string $front ): array {
		$lines  = explode( "\n", $front );
		$count  = count( $lines );
		$values = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$line = $lines[ $i ];
			$text = trim( $line );
			if ( '' === $text || str_starts_with( $text, '#' ) ) {
				continue;
			}

			$indent = strspn( $line, " \t" );
			$nested = [];
			while ( $i + 1 < $count && ( '' === trim( $lines[ $i + 1 ] ) || strspn( $lines[ $i + 1 ], " \t" ) > $indent ) ) {
				$nested[] = $lines[ ++$i ];
			}
			while ( [] !== $nested && '' === trim( (string) end( $nested ) ) ) {
				array_pop( $nested );
			}

			$colon = strpos( $text, ':' );
			if ( false === $colon ) {
				continue;
			}

			$key            = strtolower( trim( substr( $text, 0, $colon ) ) );
			$values[ $key ] = self::scalar( trim( substr( $text, $colon + 1 ) ), $nested );
		}

		return $values;
	}

	/**
	 * The value of one key from its first line and its continuation lines.
	 *
	 * @param string       $head  Text after the colon.
	 * @param list<string> $lines Continuation lines, untrimmed.
	 */
	private static function scalar( string $head, array $lines ): string {
		if ( 1 === preg_match( self::BLOCK_HEADER, $head, $match ) ) {
			return self::block( $match[1], $lines );
		}

		$value = $head;
		foreach ( $lines as $line ) {
			$line  = trim( $line );
			$value = '' === $line ? $value . "\n" : $value . ( str_ends_with( $value, "\n" ) || '' === $value ? '' : ' ' ) . $line;
		}

		return self::unquote( $value );
	}

	/**
	 * A `|` (literal) or `>` (folded) block scalar.
	 *
	 * @param string       $style `|` or `>`.
	 * @param list<string> $lines Block lines, untrimmed.
	 */
	private static function block( string $style, array $lines ): string {
		$indent = PHP_INT_MAX;
		foreach ( $lines as $line ) {
			if ( '' !== trim( $line ) ) {
				$indent = min( $indent, strspn( $line, " \t" ) );
			}
		}

		$lines = array_map( static fn( string $line ): string => '' === trim( $line ) ? '' : rtrim( substr( $line, PHP_INT_MAX === $indent ? 0 : $indent ) ), $lines );
		if ( '|' === $style ) {
			return implode( "\n", $lines );
		}

		$out  = '';
		$prev = null; // Previous line: null after a blank line, else whether it was more indented.
		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				$out .= "\n";
				$prev = null;
				continue;
			}
			// More-indented lines keep their breaks; only text-to-text breaks fold.
			$literal = str_starts_with( $line, ' ' ) || str_starts_with( $line, "\t" );
			if ( null !== $prev ) {
				$out .= ( $literal || $prev ) ? "\n" : ' ';
			}
			$out .= $line;
			$prev = $literal;
		}

		return $out;
	}

	/**
	 * Strip a matching pair of quotes, or (as before) stray quote characters.
	 *
	 * @param string $value Raw value.
	 */
	private static function unquote( string $value ): string {
		$value = trim( $value );
		$len   = strlen( $value );

		if ( $len >= 2 && '"' === $value[0] && '"' === $value[ $len - 1 ] ) {
			return strtr( substr( $value, 1, -1 ), [ '\\"' => '"', '\\\\' => '\\', '\\n' => "\n", '\\t' => "\t" ] );
		}
		if ( $len >= 2 && "'" === $value[0] && "'" === $value[ $len - 1 ] ) {
			return str_replace( "''", "'", substr( $value, 1, -1 ) );
		}

		return trim( $value, " \t\"'" );
	}
}
