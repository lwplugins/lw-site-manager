<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lenient SKILL.md frontmatter parser.
 */
final class Parser {

	public const MAX_BODY_BYTES = 1048576; // 1 MB sanity cap.

	/**
	 * Parse a raw SKILL.md string into a structured array.
	 *
	 * @return array{name:string,description:string,enable_prompt:bool,enable_agentic:bool,body:string,parse_error:?string}
	 */
	public static function parse( string $raw ): array {
		$result = [
			'name'           => '',
			'description'    => '',
			'enable_prompt'  => false,
			'enable_agentic' => true,
			'body'           => $raw,
			'parse_error'    => null,
		];

		$normalized = (string) preg_replace( '/\r\n?/', "\n", $raw );
		if ( ! str_starts_with( $normalized, "---\n" ) ) {
			return $result;
		}

		$closing = strpos( $normalized, "\n---\n", 4 );
		if ( false === $closing && str_ends_with( $normalized, "\n---" ) ) {
			$closing = strlen( $normalized ) - 4;
		}
		if ( false === $closing ) {
			$result['parse_error'] = 'Frontmatter opened with --- but never closed.';
			return $result;
		}

		$front          = substr( $normalized, 4, $closing - 4 );
		$result['body'] = ltrim( substr( $normalized, $closing + 5 ), "\n" );
		self::apply_frontmatter( $front, $result );
		return $result;
	}

	/**
	 * Apply parsed frontmatter key/value pairs into the result array.
	 *
	 * @param array{name:string,description:string,enable_prompt:bool,enable_agentic:bool,body:string,parse_error:?string} $result
	 */
	private static function apply_frontmatter( string $front, array &$result ): void {
		foreach ( explode( "\n", $front ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			$colon = strpos( $line, ':' );
			if ( false === $colon ) {
				continue;
			}
			$key   = strtolower( trim( substr( $line, 0, $colon ) ) );
			$value = trim( substr( $line, $colon + 1 ), " \t\"'" );
			match ( $key ) {
				'name'           => $result['name'] = $value,
				'description'    => $result['description'] = $value,
				'enable_prompt'  => $result['enable_prompt'] = self::to_bool( $value, false ),
				'enable_agentic' => $result['enable_agentic'] = self::to_bool( $value, true ),
				default          => null,
			};
		}
	}

	private static function to_bool( string $value, bool $fallback ): bool {
		$parsed = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return null === $parsed ? $fallback : $parsed;
	}

	public static function normalize_slug( string $raw ): string {
		$slug = sanitize_title( $raw );
		if ( strlen( $slug ) > 60 ) {
			$slug = rtrim( substr( $slug, 0, 60 ), '-' );
		}
		return $slug;
	}

	/**
	 * Render a skill array back into SKILL.md format.
	 *
	 * @param array{name?:string,slug?:string,description?:string,enable_prompt?:bool,enable_agentic?:bool,content?:string} $skill
	 */
	public static function render_skill_md( array $skill ): string {
		return sprintf(
			"---\nname: %s\ndescription: %s\nenable_prompt: %s\nenable_agentic: %s\n---\n\n%s",
			$skill['name'] ?? $skill['slug'] ?? '',
			str_replace( "\n", ' ', $skill['description'] ?? '' ),
			( $skill['enable_prompt'] ?? false ) ? 'true' : 'false',
			( $skill['enable_agentic'] ?? true ) ? 'true' : 'false',
			$skill['content'] ?? ''
		);
	}
}
