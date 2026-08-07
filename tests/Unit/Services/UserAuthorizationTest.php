<?php
/**
 * Object-level authorization for the user abilities.
 *
 * Security regression guard. These services gated on the PRIMITIVE edit_users
 * capability and never checked the target, so any caller holding it — including
 * every WooCommerce shop_manager, which WooCommerce grants edit_users
 * unconditionally via a user_has_cap filter — could reset the administrator's
 * password or promote itself to administrator. The primitive check also bypasses
 * WooCommerce's own guard (wc_modify_map_meta_cap), which only runs for the meta
 * capabilities edit_user / promote_user.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services;

use LightweightPlugins\SiteManager\Services\UserManager;
use PHPUnit\Framework\TestCase;

final class UserAuthorizationTest extends TestCase {

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
    // reset-password
    // =========================================================================

    public function test_reset_password_denied_without_capability_for_that_user(): void {
        grant_wp_caps( [ 'edit_users' ] ); // shop_manager-equivalent.

        $result = UserManager::reset_password( [ 'id' => 1, 'send_notification' => false ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_reset_password_allowed_with_capability_for_that_user(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:5' ] );

        $result = UserManager::reset_password(
            [ 'id' => 5, 'new_password' => 'sufficiently-long-pw', 'send_notification' => false ]
        );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    public function test_reset_password_refuses_super_admin_target(): void {
        global $wp_super_admins;
        $wp_super_admins = [ 1 ];
        grant_wp_caps( [ 'edit_users', 'edit_user:1' ] );

        $result = UserManager::reset_password( [ 'id' => 1 ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    // =========================================================================
    // update-user
    // =========================================================================

    public function test_update_user_denied_without_capability_for_that_user(): void {
        grant_wp_caps( [ 'edit_users' ] );

        $result = UserManager::update_user( [ 'id' => 1, 'password' => 'takeover-password' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    /**
     * Assigning a role requires promote_user for the target, and the role must
     * be one the caller may actually grant (get_editable_roles), not merely a
     * registered role.
     */
    public function test_update_user_role_change_requires_promote_capability(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2' ] ); // Can edit, cannot promote.

        $result = UserManager::update_user( [ 'id' => 2, 'role' => 'administrator' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden', $result->get_error_code() );
    }

    public function test_update_user_rejects_role_outside_editable_roles(): void {
        global $wp_editable_roles;
        // The caller may only hand out the subscriber role.
        $wp_editable_roles = [ 'subscriber' => [ 'name' => 'Subscriber' ] ];
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'promote_user:2' ] );

        $result = UserManager::update_user( [ 'id' => 2, 'role' => 'administrator' ] );

        $this->assertInstanceOf( \WP_Error::class, $result );
    }

    public function test_update_user_allows_editable_role(): void {
        global $wp_editable_roles;
        $wp_editable_roles = [ 'subscriber' => [ 'name' => 'Subscriber' ] ];
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'promote_user:2' ] );

        $result = UserManager::update_user( [ 'id' => 2, 'role' => 'subscriber' ] );

        $this->assertNotInstanceOf( \WP_Error::class, $result );
    }

    /**
     * The inline meta map on update-user is a second route to the same
     * capability-key write that set-user-meta guards.
     */
    public function test_update_user_inline_meta_refuses_capability_key(): void {
        grant_wp_caps( [ 'edit_users', 'edit_user:2', 'manage_options' ] );

        $result = UserManager::update_user(
            [ 'id' => 2, 'meta' => [ 'wp_capabilities' => [ 'administrator' => true ] ] ]
        );

        $this->assertInstanceOf( \WP_Error::class, $result );
        $this->assertSame( 'forbidden_meta_key', $result->get_error_code() );
    }
}
