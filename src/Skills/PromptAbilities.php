<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers one `site-manager/skill-prompt-{slug}` ability per skill that
 * opted in via `enable_prompt: true`, exposed natively as an MCP prompt.
 */
final class PromptAbilities {

	/**
	 * Register a prompt ability for each prompt-enabled skill.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( Sources::discoverable( 'prompt' ) as $skill ) {
			self::register_one( $skill );
		}
	}

	/**
	 * Register a single prompt ability.
	 *
	 * @param array<string,mixed> $skill Skill record.
	 */
	private static function register_one( array $skill ): void {
		$slug = (string) ( $skill['slug'] ?? '' );
		if ( '' === $slug ) {
			return;
		}
		$rendered = Parser::render_skill_md( $skill );
		/* translators: %s: skill name */
		$label = sprintf( __( 'Skill: %s', 'lw-site-manager' ), (string) ( $skill['name'] ?? $slug ) );
		wp_register_ability(
			'site-manager/skill-prompt-' . $slug,
			[
				'label'               => $label,
				'description'         => (string) ( $skill['description'] ?? '' ),
				'category'            => 'skill',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
				],
				'execute_callback'    => static fn(): array => [
					'messages' => [
						[
							'role'    => 'user',
							'content' => [
								'type' => 'text',
								'text' => $rendered,
							],
						],
					],
				],
				'permission_callback' => [ SkillGetAbility::class, 'can_run' ],
				'meta'                => [
					'annotations' => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'mcp'         => [
						'public' => true,
						'type'   => 'prompt',
					],
				],
			]
		);
	}
}
