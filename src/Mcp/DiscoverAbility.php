<?php
/**
 * MCP discover-abilities override — adds instructions and skill catalog to the response.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces the adapter's `mcp-adapter/discover-abilities` tool so its result
 * carries an `instructions` field (run through a filter the Skills Catalog
 * hooks). Registered at wp_abilities_api_init priority 999 — after the
 * adapter registered its default abilities.
 */
final class DiscoverAbility {

	/**
	 * Override the adapter's discover-abilities ability with ours.
	 */
	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		if ( null !== wp_get_ability( 'mcp-adapter/discover-abilities' ) ) {
			wp_unregister_ability( 'mcp-adapter/discover-abilities' );
		}
		wp_register_ability(
			'mcp-adapter/discover-abilities',
			[
				'label'               => __( 'Discover Abilities', 'lw-site-manager' ),
				'description'         => __( 'List public WordPress abilities plus Site Manager usage instructions and skill catalog.', 'lw-site-manager' ),
				'category'            => 'mcp-adapter',
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'instructions' => [ 'type' => 'string' ],
						'abilities'    => [ 'type' => 'array' ],
					],
					'required'   => [ 'instructions', 'abilities' ],
				],
				'execute_callback'    => [ self::class, 'execute' ],
				'permission_callback' => [ TransportGuard::class, 'allow_read' ],
				'meta'                => [
					'annotations' => [
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					],
					'mcp'         => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);
	}

	/**
	 * Build the discover result: public tool abilities + admin-gated instructions.
	 *
	 * @return array{instructions:string,abilities:list<array<string,string>>}
	 */
	public static function execute(): array {
		$list = [];
		foreach ( wp_get_abilities() as $ability ) {
			$meta = $ability->get_meta();
			if ( ! ( $meta['mcp']['public'] ?? false ) ) {
				continue;
			}
			if ( 'tool' !== ( $meta['mcp']['type'] ?? 'tool' ) ) {
				continue;
			}
			$list[] = [
				'name'        => $ability->get_name(),
				'label'       => $ability->get_label(),
				'description' => $ability->get_description(),
			];
		}

		$instructions = '';
		if ( current_user_can( 'manage_options' ) ) {
			$instructions = (string) apply_filters( 'lw_site_manager_discover_instructions', self::base_instructions() );
		}

		return [
			'instructions' => $instructions,
			'abilities'    => $list,
		];
	}

	private static function base_instructions(): string {
		return __( 'You are connected to a WordPress site via LW Site Manager. Use the listed abilities to manage it. Prefer read abilities first; confirm destructive actions with the user.', 'lw-site-manager' );
	}
}
