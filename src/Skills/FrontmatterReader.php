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
 * spaces, a blank line kept as a line break), or be a block scalar (`|` / `>`,
 * see BlockScalar). Double-quoted values resolve the YAML escapes, including
 * \x, \u, \U and the escaped line break. Lines indented under a key belong to
 * that key, so a nested map never overrides a top-level field.
 *
 * Not supported (kept as before): a trailing `# comment` after a plain or a
 * quoted value stays part of the value.
 */
final class FrontmatterReader {

	/**
	 * Top-level key => scalar value, keys lowercased.
	 *
	 * @param string $front Frontmatter lines between the --- markers, joined by "\n" (no final line break).
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

			$colon = strpos( $text, ':' );
			if ( false === $colon ) {
				continue;
			}

			$key            = strtolower( trim( substr( $text, 0, $colon ) ) );
			$values[ $key ] = self::scalar( trim( substr( $text, $colon + 1 ) ), $nested, $indent );
		}

		return $values;
	}

	/**
	 * The value of one key from its first line and its continuation lines.
	 *
	 * @param string       $head   Text after the colon.
	 * @param list<string> $lines  Continuation lines, untrimmed.
	 * @param int          $indent Indent of the key line.
	 */
	private static function scalar( string $head, array $lines, int $indent ): string {
		$header = BlockScalar::header( $head );
		if ( null !== $header ) {
			return BlockScalar::value( $header, $lines, $indent );
		}

		$value = $head;
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				$value .= "\n";
			} elseif ( str_starts_with( $head, '"' ) && 1 === preg_match( '/(?<!\\\\)(?:\\\\\\\\)*\\\\$/', $value ) ) {
				$value = substr( $value, 0, -1 ) . $line; // Escaped line break: join without a space.
			} else {
				$value .= ( '' === $value || str_ends_with( $value, "\n" ) ? '' : ' ' ) . $line;
			}
		}

		return self::unquote( $value );
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
			return self::unescape( substr( $value, 1, -1 ) );
		}
		if ( $len >= 2 && "'" === $value[0] && "'" === $value[ $len - 1 ] ) {
			return str_replace( "''", "'", substr( $value, 1, -1 ) );
		}

		return trim( $value, " \t\"'" );
	}

	/**
	 * Resolve the escapes of a double-quoted scalar (YAML 1.2 section 5.7).
	 *
	 * @param string $value Text between the quotes.
	 */
	private static function unescape( string $value ): string {
		$simple = [
			'0'  => "\0",
			'a'  => "\x07",
			'b'  => "\x08",
			't'  => "\t",
			"\t" => "\t",
			'n'  => "\n",
			'v'  => "\v",
			'f'  => "\f",
			'r'  => "\r",
			'e'  => "\e",
			' '  => ' ',
			'"'  => '"',
			'/'  => '/',
			'\\' => '\\',
			'N'  => "\u{85}",
			'_'  => "\u{A0}",
			'L'  => "\u{2028}",
			'P'  => "\u{2029}",
		];

		return (string) preg_replace_callback(
			'/\\\\(x[0-9a-fA-F]{2}|u[0-9a-fA-F]{4}|U[0-9a-fA-F]{8}|.)/s',
			static function ( array $match ) use ( $simple ): string {
				$code = $match[1];
				if ( strlen( $code ) > 1 ) {
					return self::utf8( (int) hexdec( substr( $code, 1 ) ) );
				}
				return $simple[ $code ] ?? $match[0];
			},
			$value
		);
	}

	/**
	 * UTF-8 bytes of a code point (no mbstring dependency).
	 *
	 * @param int $cp Code point.
	 */
	private static function utf8( int $cp ): string {
		if ( $cp < 0x80 ) {
			return chr( $cp );
		}
		if ( $cp < 0x800 ) {
			return chr( 0xC0 | ( $cp >> 6 ) ) . chr( 0x80 | ( $cp & 0x3F ) );
		}
		if ( $cp < 0x10000 ) {
			return chr( 0xE0 | ( $cp >> 12 ) ) . chr( 0x80 | ( ( $cp >> 6 ) & 0x3F ) ) . chr( 0x80 | ( $cp & 0x3F ) );
		}
		if ( $cp <= 0x10FFFF ) {
			return chr( 0xF0 | ( $cp >> 18 ) ) . chr( 0x80 | ( ( $cp >> 12 ) & 0x3F ) ) . chr( 0x80 | ( ( $cp >> 6 ) & 0x3F ) ) . chr( 0x80 | ( $cp & 0x3F ) );
		}

		return "\u{FFFD}";
	}
}
