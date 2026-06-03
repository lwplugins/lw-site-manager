<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Skills;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the Skills subsystem hooks. Safe to call unconditionally.
 */
final class Bootstrap {

	/**
	 * Register skill sources, the discover-instructions filter, and abilities.
	 */
	public static function init(): void {
		add_filter( 'lw_site_manager_skill_sources', [ BuiltInSource::class, 'register' ] );
		add_filter( 'lw_site_manager_discover_instructions', [ Catalog::class, 'inject_filter' ], 10 );

		add_action( 'wp_abilities_api_categories_init', [ self::class, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ SkillGetAbility::class, 'register' ], 999 );
		add_action( 'wp_abilities_api_init', [ PromptAbilities::class, 'register' ], 500 );
	}

	/**
	 * Register the `skill` ability category.
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			'skill',
			[
				'label'       => __( 'Skills', 'lw-site-manager' ),
				'description' => __( 'Load Site Manager skill playbooks.', 'lw-site-manager' ),
			]
		);
	}
}
