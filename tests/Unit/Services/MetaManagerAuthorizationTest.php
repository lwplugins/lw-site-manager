<?php
/**
 * Object-level authorization for the meta abilities.
 *
 * Security regression guard. The meta services previously checked only that the
 * target object existed: the registration-time primitive capability
 * (edit_posts / edit_users) was the sole gate, so any caller holding it could
 * read and write meta on EVERY object, including protected keys and the
 * capability-bearing user meta that IS the role assignment.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\MetaManager;
use PHPUnit\Framework\TestCase;

final class MetaManagerAuthorizationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        reset_wp_caps();
        reset_wp_options();
    }

    protected function tearDown(): void {
        reset_wp_caps();
        parent::tearDown();
    }

    // =========================================================================
    // Post meta — per-object capability
    // =========================================================================

    public function test_get_post_meta_denied_without_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] ); // Primitive only — no rights on post 42.

        $result = MetaManager::get_post_meta( [ 'post_id' => 42, 'key' => '_secret_api_key' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_get_post_meta_allowed_with_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts', 'read_post:42' ] );

        $result = MetaManager::get_post_meta( [ 'post_id' => 42, 'key' => 'public_key' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_set_post_meta_denied_without_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $result = MetaManager::set_post_meta(
            [ 'post_id' => 42, 'key' => 'colour', 'value' => 'red' ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_delete_post_meta_denied_without_capability_for_that_post(): void {
        grant_wp_caps( [ 'edit_posts' ] );

        $result = MetaManager::delete_post_meta( [ 'post_id' => 42, 'key' => 'colour' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    // =========================================================================
    // Post meta — protected keys
    // =========================================================================

    public function test_single_key_read_hides_protected_key_from_non_admin(): void {
        // Allowed to read the post, but not an administrator.
        grant_wp_caps( [ 'edit_posts', 'read_post:42' ] );

        $result = MetaManager::get_post_meta( [ 'post_id' => 42, 'key' => '_private_licence' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_protected_key_write_denied_for_non_admin(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42' ] );

        $result = MetaManager::set_post_meta(
            [ 'post_id' => 42, 'key' => '_wp_page_template', 'value' => 'evil.php' ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    public function test_administrator_may_write_protected_key(): void {
        grant_wp_caps( [ 'edit_posts', 'edit_post:42', 'manage_options' ] );

        $result = MetaManager::set_post_meta(
            [ 'post_id' => 42, 'key' => '_wp_page_template', 'value' => 'full-width.php' ]
        );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    // =========================================================================
    // User meta — privilege escalation
    // =========================================================================

    /**
     * @dataProvider provide_privilege_keys
     */
    public function test_set_user_meta_refuses_capability_keys( string $key ): void {
        // Even a full administrator must not set roles through the meta ability.
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = MetaManager::set_user_meta(
            [ 'user_id' => 2, 'key' => $key, 'value' => [ 'administrator' => true ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provide_privilege_keys(): array {
        return [
            'default prefix'   => [ 'wp_capabilities' ],
            'multisite prefix' => [ 'wp_2_capabilities' ],
            'custom prefix'    => [ 'xyz_capabilities' ],
            'user level'       => [ 'wp_user_level' ],
            'session tokens'   => [ 'session_tokens' ],
        ];
    }

    public function test_set_user_meta_denied_without_capability_for_that_user(): void {
        grant_wp_caps( [ 'edit_users' ] ); // Primitive only — no rights on user 2.

        $result = MetaManager::set_user_meta(
            [ 'user_id' => 2, 'key' => 'nickname', 'value' => 'x' ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }
}
