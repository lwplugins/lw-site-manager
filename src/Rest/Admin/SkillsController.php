<?php
/**
 * Skills REST controller.
 *
 * @package LightweightPlugins\SiteManager
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Rest\Admin;

use LightweightPlugins\SiteManager\Skills\Sources;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET lw-site-manager/v1/admin/skills: every registered skill, without its
 * body (the agent loads that through the skill-get ability).
 */
final class SkillsController {

	/**
	 * Register the routes.
	 */
	public function register_routes(): void {
		Routes::add( '/admin/skills', [ WP_REST_Server::READABLE => 'get_skills' ], $this );
	}

	/**
	 * The skill list.
	 */
	public function get_skills(): WP_REST_Response {
		return new WP_REST_Response( array_map( [ self::class, 'item' ], Sources::all() ) );
	}

	/**
	 * One list entry.
	 *
	 * @param array<string, mixed> $skill Skill from Sources::all().
	 * @return array{slug: string, name: string, description: string, source: string, source_label: string, agentic: bool, prompt: bool}
	 */
	private static function item( array $skill ): array {
		return [
			'slug'         => (string) ( $skill['slug'] ?? '' ),
			'name'         => (string) ( $skill['name'] ?? '' ),
			'description'  => (string) ( $skill['description'] ?? '' ),
			'source'       => (string) ( $skill['source'] ?? '' ),
			'source_label' => (string) ( $skill['source_label'] ?? '' ),
			'agentic'      => (bool) ( $skill['enable_agentic'] ?? true ),
			'prompt'       => (bool) ( $skill['enable_prompt'] ?? false ),
		];
	}
}
