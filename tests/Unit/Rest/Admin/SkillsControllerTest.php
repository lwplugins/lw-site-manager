<?php
/**
 * Tests for GET lw-site-manager/v1/admin/skills.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Rest\Admin
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Rest\Admin;

use LightweightPlugins\SiteManager\Rest\Admin\Routes;
use LightweightPlugins\SiteManager\Rest\Admin\SkillsController;
use PHPUnit\Framework\TestCase;
use WP_REST_Response;

final class SkillsControllerTest extends TestCase {

	/** @var callable */
	private $source;

	protected function setUp(): void {
		$GLOBALS['wp_rest_routes'] = [];
		$loader       = static fn(): array => [
			[
				'slug'           => 'demo-skill',
				'name'           => 'Demo skill',
				'description'    => 'Does a demo.',
				'content'        => 'SECRET BODY',
				'enable_prompt'  => true,
				'enable_agentic' => false,
			],
			[ 'slug' => 'bare' ],
		];
		$this->source = static function ( array $sources ) use ( $loader ): array {
			$sources['test'] = [ 'id' => 'test', 'priority' => 5, 'label' => 'Test source', 'loader' => $loader ];
			return $sources;
		};
		add_filter( 'lw_site_manager_skill_sources', $this->source );
	}

	protected function tearDown(): void {
		remove_filter( 'lw_site_manager_skill_sources', $this->source );
	}

	public function test_registers_a_read_route_behind_manage_options(): void {
		Routes::register_routes();

		$endpoints = $GLOBALS['wp_rest_routes']['lw-site-manager/v1/admin/skills'] ?? null;
		$this->assertIsArray( $endpoints );
		$this->assertSame( [ 'GET' ], array_column( $endpoints, 'methods' ) );
		$this->assertSame( [ Routes::class, 'can_manage' ], $endpoints[0]['permission_callback'] );
	}

	public function test_lists_skills_without_their_body(): void {
		$response = ( new SkillsController() )->get_skills();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame(
			[
				[
					'slug'         => 'demo-skill',
					'name'         => 'Demo skill',
					'description'  => 'Does a demo.',
					'source'       => 'test',
					'source_label' => 'Test source',
					'agentic'      => false,
					'prompt'       => true,
				],
				[
					'slug'         => 'bare',
					'name'         => '',
					'description'  => '',
					'source'       => 'test',
					'source_label' => 'Test source',
					'agentic'      => true,
					'prompt'       => false,
				],
			],
			$response->get_data()
		);
	}
}
