<?php
/**
 * User Manager Service - User management operations
 */

declare(strict_types=1);

namespace WPSiteManager\Services;

class UserManager extends AbstractService {

    /**
     * List users
     */
    public static function list_users( array $input ): array {
        $args = [
            'orderby' => $input['orderby'] ?? 'registered',
            'order'   => $input['order'] ?? 'DESC',
        ];

        // Apply pagination
        self::applyPaginationToUserQuery( $args, $input );

        // Filter by role
        if ( ! empty( $input['role'] ) ) {
            $args['role'] = $input['role'];
        }

        // Search
        if ( ! empty( $input['search'] ) ) {
            $args['search'] = '*' . $input['search'] . '*';
            $args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
        }

        $user_query = new \WP_User_Query( $args );
        $users = [];

        foreach ( $user_query->get_results() as $user ) {
            $users[] = self::format_user( $user );
        }

        return self::listResponse(
            'users',
            $users,
            $user_query->get_total(),
            $args['number'],
            $args['offset']
        );
    }

    /**
     * Get single user
     */
    public static function get_user( array $input ): array|\WP_Error {
        $user = null;

        if ( ! empty( $input['id'] ) ) {
            $user = get_user_by( 'id', $input['id'] );
        } elseif ( ! empty( $input['email'] ) ) {
            $user = get_user_by( 'email', $input['email'] );
        } elseif ( ! empty( $input['login'] ) ) {
            $user = get_user_by( 'login', $input['login'] );
        }

        if ( ! $user ) {
            return self::errorResponse( 'user_not_found', 'User not found', 404 );
        }

        return self::entityResponse( 'user', self::format_user( $user, true ) );
    }

    /**
     * Create new user
     */
    public static function create_user( array $input ): array|\WP_Error {
        // Validate required fields
        $error = self::validateRequired( $input, [ 'username', 'email' ] );
        if ( $error ) {
            return $error;
        }

        // Check if user exists
        if ( username_exists( $input['username'] ) ) {
            return self::errorResponse( 'username_exists', 'Username already exists', 400 );
        }

        if ( email_exists( $input['email'] ) ) {
            return self::errorResponse( 'email_exists', 'Email already exists', 400 );
        }

        // Generate password if not provided
        $password = $input['password'] ?? wp_generate_password( 16, true, true );

        $userdata = [
            'user_login'    => sanitize_user( $input['username'] ),
            'user_email'    => sanitize_email( $input['email'] ),
            'user_pass'     => $password,
            'display_name'  => $input['display_name'] ?? $input['username'],
            'first_name'    => $input['first_name'] ?? '',
            'last_name'     => $input['last_name'] ?? '',
            'user_url'      => $input['website'] ?? '',
            'role'          => $input['role'] ?? 'subscriber',
        ];

        // Validate role
        $valid_roles = array_keys( wp_roles()->roles );
        $error = self::validateEnum( $userdata['role'], $valid_roles, 'role' );
        if ( $error ) {
            return $error;
        }

        $user_id = wp_insert_user( $userdata );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Send notification if requested
        $send_notification = $input['send_notification'] ?? false;
        if ( $send_notification ) {
            wp_new_user_notification( $user_id, null, 'user' );
        }

        $user = get_user_by( 'id', $user_id );

        $response = self::createdResponse( 'user', self::format_user( $user ), $user_id );

        // Only return password if it was generated
        if ( empty( $input['password'] ) ) {
            $response['password'] = $password;
        }

        return $response;
    }

    /**
     * Update user
     */
    public static function update_user( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $user = self::getUserOrError( $input['id'] );
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        $userdata = [ 'ID' => $input['id'] ];

        // Update email
        if ( ! empty( $input['email'] ) ) {
            $existing = email_exists( $input['email'] );
            if ( $existing && $existing !== $input['id'] ) {
                return self::errorResponse( 'email_exists', 'Email already used by another user', 400 );
            }
            $userdata['user_email'] = sanitize_email( $input['email'] );
        }

        // Update other fields
        if ( isset( $input['display_name'] ) ) {
            $userdata['display_name'] = sanitize_text_field( $input['display_name'] );
        }
        if ( isset( $input['first_name'] ) ) {
            $userdata['first_name'] = sanitize_text_field( $input['first_name'] );
        }
        if ( isset( $input['last_name'] ) ) {
            $userdata['last_name'] = sanitize_text_field( $input['last_name'] );
        }
        if ( isset( $input['website'] ) ) {
            $userdata['user_url'] = esc_url_raw( $input['website'] );
        }
        if ( ! empty( $input['password'] ) ) {
            $userdata['user_pass'] = $input['password'];
        }

        $result = wp_update_user( $userdata );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Update role separately
        if ( ! empty( $input['role'] ) ) {
            $valid_roles = array_keys( wp_roles()->roles );
            $error = self::validateEnum( $input['role'], $valid_roles, 'role' );
            if ( $error ) {
                return $error;
            }
            $user->set_role( $input['role'] );
        }

        $updated_user = get_user_by( 'id', $input['id'] );

        return self::updatedResponse( 'user', self::format_user( $updated_user ) );
    }

    /**
     * Delete user
     */
    public static function delete_user( array $input ): array|\WP_Error {
        $error = self::validateId( $input );
        if ( $error ) {
            return $error;
        }

        $input['id'] = (int) $input['id'];
        $user = self::getUserOrError( $input['id'] );
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        // Prevent deleting current user
        if ( $input['id'] === get_current_user_id() ) {
            return self::errorResponse( 'cannot_delete_self', 'Cannot delete your own account', 400 );
        }

        // Prevent deleting super admin in multisite
        if ( is_multisite() && is_super_admin( $input['id'] ) ) {
            return self::errorResponse( 'cannot_delete_super_admin', 'Cannot delete super admin', 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';

        // Reassign posts to another user if specified
        $reassign = $input['reassign_to'] ?? null;

        $deleted = wp_delete_user( $input['id'], $reassign );

        if ( ! $deleted ) {
            return self::errorResponse( 'delete_failed', 'Failed to delete user', 500 );
        }

        return [
            'success'     => true,
            'message'     => 'User deleted successfully',
            'deleted_id'  => $input['id'],
            'reassigned'  => $reassign,
        ];
    }

    /**
     * Reset user password
     */
    public static function reset_password( array $input ): array|\WP_Error {
        $user = null;

        if ( ! empty( $input['id'] ) ) {
            $user = get_user_by( 'id', $input['id'] );
        } elseif ( ! empty( $input['email'] ) ) {
            $user = get_user_by( 'email', $input['email'] );
        } elseif ( ! empty( $input['login'] ) ) {
            $user = get_user_by( 'login', $input['login'] );
        }

        if ( ! $user ) {
            return self::errorResponse( 'user_not_found', 'User not found', 404 );
        }

        // Generate new password or use provided one
        $new_password = $input['new_password'] ?? wp_generate_password( 16, true, true );

        wp_set_password( $new_password, $user->ID );

        // Send notification if requested
        $send_notification = $input['send_notification'] ?? true;
        if ( $send_notification ) {
            // Send password reset email
            $reset_key = get_password_reset_key( $user );
            if ( ! is_wp_error( $reset_key ) ) {
                $message = sprintf(
                    "Your password has been reset.\n\nUsername: %s\nNew Password: %s\n\nLogin: %s",
                    $user->user_login,
                    $new_password,
                    wp_login_url()
                );
                wp_mail( $user->user_email, 'Password Reset', $message );
            }
        }

        $response = [
            'success'  => true,
            'message'  => 'Password reset successfully',
            'user_id'  => $user->ID,
            'email'    => $user->user_email,
            'notified' => $send_notification,
        ];

        // Only return password if it was generated
        if ( empty( $input['new_password'] ) ) {
            $response['password'] = $new_password;
        }

        return $response;
    }

    /**
     * Get available roles
     */
    public static function get_roles( array $input = [] ): array {
        $roles = wp_roles()->roles;
        $formatted = [];

        // Get user counts per role
        $user_counts = count_users();
        $role_counts = $user_counts['avail_roles'] ?? [];

        foreach ( $roles as $slug => $role ) {
            $formatted[] = [
                'slug'         => $slug,
                'name'         => translate_user_role( $role['name'] ),
                'capabilities' => array_keys( array_filter( $role['capabilities'] ) ),
                'user_count'   => $role_counts[ $slug ] ?? 0,
            ];
        }

        return [
            'roles' => $formatted,
            'total' => count( $formatted ),
        ];
    }

    /**
     * Format user data for output
     */
    private static function format_user( \WP_User $user, bool $detailed = false ): array {
        $data = [
            'id'           => $user->ID,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'roles'        => $user->roles,
            'registered'   => $user->user_registered,
        ];

        if ( $detailed ) {
            $data['first_name'] = $user->first_name;
            $data['last_name'] = $user->last_name;
            $data['website'] = $user->user_url;
            $data['bio'] = $user->description;
            $data['avatar'] = get_avatar_url( $user->ID, [ 'size' => 96 ] );
            $data['posts_count'] = count_user_posts( $user->ID );
            $data['last_login'] = get_user_meta( $user->ID, 'last_login', true ) ?: null;
            $data['capabilities'] = array_keys( array_filter( $user->allcaps ) );
        }

        return $data;
    }
}
