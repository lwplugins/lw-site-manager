<?php
/**
 * Sample integration test.
 *
 * @package LightweightPlugins\SiteManager\Tests\Integration
 * @group integration
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Integration;

use WP_UnitTestCase;

/**
 * Sample integration test demonstrating WP_UnitTestCase usage.
 *
 * These tests require a WordPress test environment.
 * Run: bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass>
 *
 * @group integration
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * Test that WordPress is loaded.
	 */
	public function test_wordpress_is_loaded(): void {
		$this->assertTrue( function_exists( 'add_action' ) );
		$this->assertTrue( defined( 'ABSPATH' ) );
	}

	/**
	 * Test that the plugin is loaded.
	 */
	public function test_plugin_is_loaded(): void {
		$this->assertTrue( defined( 'LW_SITE_MANAGER_VERSION' ) );
	}

	/**
	 * Test creating a post via factory.
	 */
	public function test_can_create_post(): void {
		$post_id = self::factory()->post->create( [
			'post_title'  => 'Test Post',
			'post_status' => 'publish',
		] );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$post = get_post( $post_id );
		$this->assertEquals( 'Test Post', $post->post_title );
	}

	/**
	 * Test PostManager with real WordPress.
	 */
	public function test_post_manager_list_posts(): void {
		// Create some test posts.
		self::factory()->post->create_many( 3, [
			'post_status' => 'publish',
		] );

		$result = \LightweightPlugins\SiteManager\Services\PostManager::list_posts( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertGreaterThanOrEqual( 3, count( $result['posts'] ) );
	}

	/**
	 * Test featured_image_id field in list_posts response.
	 */
	public function test_list_posts_includes_featured_image_id(): void {
		// Create a post.
		$post_id = self::factory()->post->create( [
			'post_title'  => 'Post with Featured Image',
			'post_status' => 'publish',
		] );

		// Create an attachment and set as featured image.
		$attachment_id = self::factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg',
			$post_id
		);
		set_post_thumbnail( $post_id, $attachment_id );

		// Get posts via PostManager.
		$result = \LightweightPlugins\SiteManager\Services\PostManager::list_posts( [
			'include' => [ $post_id ],
		] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertNotEmpty( $result['posts'] );

		$post_data = $result['posts'][0];
		$this->assertArrayHasKey( 'featured_image_id', $post_data );
		$this->assertEquals( $attachment_id, $post_data['featured_image_id'] );
	}
}
