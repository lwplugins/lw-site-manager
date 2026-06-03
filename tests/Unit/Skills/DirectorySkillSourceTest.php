<?php
declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Skills;

use LightweightPlugins\SiteManager\Skills\DirectorySkillSource;
use PHPUnit\Framework\TestCase;

final class DirectorySkillSourceTest extends TestCase {

	private string $dir = '';

	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/lwsm-skills-' . uniqid( '', true );
		mkdir( $this->dir . '/my-skill', 0777, true );
		file_put_contents(
			$this->dir . '/my-skill/SKILL.md',
			"---\nname: My Skill\ndescription: Does a thing.\n---\n\n# Body of my skill"
		);
	}

	protected function tearDown(): void {
		@unlink( $this->dir . '/my-skill/SKILL.md' );
		@rmdir( $this->dir . '/my-skill' );
		@rmdir( $this->dir );
	}

	public function test_load_dir_reads_skill_md_files(): void {
		$skills = DirectorySkillSource::load_dir( $this->dir );
		$this->assertCount( 1, $skills );
		$this->assertSame( 'my-skill', $skills[0]['slug'] );
		$this->assertSame( 'My Skill', $skills[0]['name'] );
		$this->assertSame( 'Does a thing.', $skills[0]['description'] );
		$this->assertStringContainsString( '# Body of my skill', $skills[0]['content'] );
	}

	public function test_load_dir_returns_empty_for_missing_directory(): void {
		$this->assertSame( [], DirectorySkillSource::load_dir( '/no/such/dir-' . uniqid() ) );
	}

	public function test_entry_shape_and_loader(): void {
		$entry = DirectorySkillSource::entry( 'my-plugin', 'My Plugin', $this->dir, 15 );
		$this->assertSame( 'my-plugin', $entry['id'] );
		$this->assertSame( 'My Plugin', $entry['label'] );
		$this->assertSame( 15, $entry['priority'] );
		$this->assertIsCallable( $entry['loader'] );
		$loaded = ( $entry['loader'] )();
		$this->assertSame( 'my-skill', $loaded[0]['slug'] );
	}

	public function test_register_adds_source_to_filter(): void {
		DirectorySkillSource::register( 'my-plugin', 'My Plugin', $this->dir, 15 );
		$sources = apply_filters( 'lw_site_manager_skill_sources', [] );
		$this->assertArrayHasKey( 'my-plugin', $sources );
		$this->assertSame( 'My Plugin', $sources['my-plugin']['label'] );
		remove_all_filters( 'lw_site_manager_skill_sources' );
	}
}
