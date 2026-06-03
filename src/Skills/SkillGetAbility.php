<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `site-manager/skill-get` ability: load a bundled skill by slug.
 */
final class SkillGetAbility {

	public const NAME = 'site-manager/skill-get';

	/**
	 * Register the ability with the WordPress Abilities API.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		wp_register_ability(
			self::NAME,
			[
				'label'               => __( 'Get Skill', 'lw-site-manager' ),
				'description'         => __( 'Load a Site Manager skill by slug. Returns the full SKILL.md content plus metadata.', 'lw-site-manager' ),
				'category'            => 'skill',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'slug' => [
							'type'        => 'string',
							'description' => 'The slug of the skill to load.',
						],
					],
					'required'   => [ 'slug' ],
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'found'       => [ 'type' => 'boolean' ],
						'slug'        => [ 'type' => 'string' ],
						'name'        => [ 'type' => 'string' ],
						'description' => [ 'type' => 'string' ],
						'content'     => [ 'type' => 'string' ],
						'source'      => [ 'type' => 'string' ],
					],
					'required'   => [ 'found' ],
				],
				'execute_callback'    => [ self::class, 'execute' ],
				'permission_callback' => [ self::class, 'can_run' ],
				'meta'                => self::build_meta(),
			]
		);
	}

	/**
	 * Build the meta array, conditionally including the mcp key.
	 *
	 * @return array<string,mixed>
	 */
	private static function build_meta(): array {
		$meta = [
			'annotations' => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
		if ( \LightweightPlugins\SiteManager\Mcp\Toggle::is_enabled() ) {
			$meta['mcp'] = [
				'public' => true,
				'type'   => 'tool',
			];
		}
		return $meta;
	}

	public static function can_run(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Execute the ability: load a skill by slug and return its metadata and content.
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public static function execute( array $input ): array {
		$slug = self::normalize_slug( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return [ 'found' => false ];
		}
		$skill = Sources::find( $slug );
		if ( null === $skill ) {
			return [ 'found' => false ];
		}
		return [
			'found'       => true,
			'slug'        => (string) $skill['slug'],
			'name'        => (string) ( $skill['name'] ?? $skill['slug'] ),
			'description' => (string) ( $skill['description'] ?? '' ),
			'content'     => Parser::render_skill_md( $skill ),
			'source'      => (string) ( $skill['source'] ?? 'built-in' ),
		];
	}

	private static function normalize_slug( string $slug ): string {
		$slug = trim( $slug );
		if ( str_starts_with( $slug, 'site-manager/' ) ) {
			$slug = substr( $slug, strlen( 'site-manager/' ) );
		}
		return Parser::normalize_slug( $slug );
	}
}
