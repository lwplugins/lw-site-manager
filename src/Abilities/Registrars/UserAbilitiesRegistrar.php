<?php
/**
 * User Abilities Registrar - Registers user management abilities
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Registrars;

use LightweightPlugins\SiteManager\Services\UserManager;

class UserAbilitiesRegistrar extends AbstractAbilitiesRegistrar {

    public function register(): void {
        $this->register_list_abilities();
        $this->register_crud_abilities();
        $this->register_utility_abilities();
    }

    // =========================================================================
    // List & Get Abilities
    // =========================================================================

    private function register_list_abilities(): void {
        // List users
        wp_register_ability(
            'site-manager/list-users',
            [
                'label'       => __( 'List Users', 'lw-site-manager' ),
                'description' => __( 'List all users with optional filtering', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => array_merge(
                        $this->paginationSchema( 50 ),
                        $this->orderingSchema( 'registered', 'DESC', [ 'registered', 'display_name', 'email', 'login' ] ),
                        [
                            'role' => [
                                'type'        => 'string',
                                'description' => 'Filter by role (e.g., administrator, editor)',
                            ],
                            'search' => [
                                'type'        => 'string',
                                'description' => 'Search in username, email, display name',
                            ],
                        ]
                    ),
                ],
                'output_schema' => $this->listOutputSchema( 'users', $this->getUserSchema() ),
                'execute_callback'    => [ UserManager::class, 'list_users' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_users' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );

        // Get single user
        wp_register_ability(
            'site-manager/get-user',
            [
                'label'       => __( 'Get User', 'lw-site-manager' ),
                'description' => __( 'Get detailed information about a user', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'User ID',
                        ],
                        'email' => [
                            'type'        => 'string',
                            'description' => 'User email',
                        ],
                        'login' => [
                            'type'        => 'string',
                            'description' => 'Username',
                        ],
                    ],
                ],
                'output_schema' => $this->entityOutputSchema( 'user', $this->getUserSchema() ),
                'execute_callback'    => [ UserManager::class, 'get_user' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_users' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // CRUD Abilities
    // =========================================================================

    private function register_crud_abilities(): void {
        // Create user
        wp_register_ability(
            'site-manager/create-user',
            [
                'label'       => __( 'Create User', 'lw-site-manager' ),
                'description' => __( 'Create a new user account', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'username' => [
                            'type'        => 'string',
                            'description' => 'Username (required)',
                        ],
                        'email' => [
                            'type'        => 'string',
                            'description' => 'Email address (required)',
                        ],
                        'password' => [
                            'type'        => 'string',
                            'description' => 'Password (auto-generated if not provided)',
                        ],
                        'display_name' => [
                            'type'        => 'string',
                            'description' => 'Display name',
                        ],
                        'first_name' => [
                            'type'        => 'string',
                            'description' => 'First name',
                        ],
                        'last_name' => [
                            'type'        => 'string',
                            'description' => 'Last name',
                        ],
                        'website' => [
                            'type'        => 'string',
                            'description' => 'Website URL',
                        ],
                        'role' => [
                            'type'        => 'string',
                            'default'     => 'subscriber',
                            'description' => 'User role',
                        ],
                        'send_notification' => [
                            'type'        => 'boolean',
                            'default'     => false,
                            'description' => 'Send welcome email to user',
                        ],
                    ],
                    'required' => [ 'username', 'email' ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'  => [ 'type' => 'boolean' ],
                        'message'  => [ 'type' => 'string' ],
                        'id'       => [ 'type' => 'integer' ],
                        'user'     => $this->getUserSchema(),
                        'password' => [
                            'type'        => 'string',
                            'description' => 'Generated password (only if auto-generated)',
                        ],
                    ],
                ],
                'execute_callback'    => [ UserManager::class, 'create_user' ],
                'permission_callback' => $this->permissions->callback( 'can_create_users' ),
                'meta'                => $this->writeMeta( idempotent: false ),
            ]
        );

        // Update user
        wp_register_ability(
            'site-manager/update-user',
            [
                'label'       => __( 'Update User', 'lw-site-manager' ),
                'description' => __( 'Update an existing user', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'User ID (required)',
                        ],
                        'email' => [
                            'type'        => 'string',
                            'description' => 'New email address',
                        ],
                        'display_name' => [
                            'type'        => 'string',
                            'description' => 'Display name',
                        ],
                        'first_name' => [
                            'type'        => 'string',
                            'description' => 'First name',
                        ],
                        'last_name' => [
                            'type'        => 'string',
                            'description' => 'Last name',
                        ],
                        'website' => [
                            'type'        => 'string',
                            'description' => 'Website URL',
                        ],
                        'password' => [
                            'type'        => 'string',
                            'description' => 'New password',
                        ],
                        'role' => [
                            'type'        => 'string',
                            'description' => 'New role',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => $this->entityOutputSchema( 'user', $this->getUserSchema() ),
                'execute_callback'    => [ UserManager::class, 'update_user' ],
                'permission_callback' => $this->permissions->callback( 'can_edit_users' ),
                'meta'                => $this->writeMeta( idempotent: true ),
            ]
        );

        // Delete user
        wp_register_ability(
            'site-manager/delete-user',
            [
                'label'       => __( 'Delete User', 'lw-site-manager' ),
                'description' => __( 'Delete a user account', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'User ID to delete (required)',
                        ],
                        'reassign_to' => [
                            'type'        => 'integer',
                            'description' => 'Reassign posts to this user ID',
                        ],
                    ],
                    'required' => [ 'id' ],
                ],
                'output_schema' => $this->successOutputSchema( [
                    'id'            => [ 'type' => 'integer' ],
                    'reassigned_to' => [ 'type' => 'integer' ],
                    'posts_count'   => [ 'type' => 'integer' ],
                ] ),
                'execute_callback'    => [ UserManager::class, 'delete_user' ],
                'permission_callback' => $this->permissions->callback( 'can_delete_users' ),
                'meta'                => $this->destructiveMeta( idempotent: true ),
            ]
        );
    }

    // =========================================================================
    // Utility Abilities
    // =========================================================================

    private function register_utility_abilities(): void {
        // Reset password
        wp_register_ability(
            'site-manager/reset-password',
            [
                'label'       => __( 'Reset Password', 'lw-site-manager' ),
                'description' => __( 'Reset a user password', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'id' => [
                            'type'        => 'integer',
                            'description' => 'User ID',
                        ],
                        'email' => [
                            'type'        => 'string',
                            'description' => 'User email',
                        ],
                        'login' => [
                            'type'        => 'string',
                            'description' => 'Username',
                        ],
                        'new_password' => [
                            'type'        => 'string',
                            'description' => 'New password (auto-generated if not provided)',
                        ],
                        'send_notification' => [
                            'type'        => 'boolean',
                            'default'     => true,
                            'description' => 'Send email with new password',
                        ],
                    ],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'success'           => [ 'type' => 'boolean' ],
                        'message'           => [ 'type' => 'string' ],
                        'user_id'           => [ 'type' => 'integer' ],
                        'password'          => [
                            'type'        => 'string',
                            'description' => 'New password (only if auto-generated)',
                        ],
                        'notification_sent' => [ 'type' => 'boolean' ],
                    ],
                ],
                'execute_callback'    => [ UserManager::class, 'reset_password' ],
                'permission_callback' => $this->permissions->callback( 'can_edit_users' ),
                'meta'                => $this->writeMeta( idempotent: false ),
            ]
        );

        // Get roles
        wp_register_ability(
            'site-manager/get-roles',
            [
                'label'       => __( 'Get Roles', 'lw-site-manager' ),
                'description' => __( 'List all available user roles', 'lw-site-manager' ),
                'category'    => 'users',
                'input_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [],
                ],
                'output_schema' => [
                    'type'       => 'object',
                    'default'    => [],
                    'properties' => [
                        'roles' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                    'default'    => [],
                                'properties' => [
                                    'slug'         => [ 'type' => 'string' ],
                                    'name'         => [ 'type' => 'string' ],
                                    'capabilities' => [
                                        'type'  => 'array',
                                        'items' => [ 'type' => 'string' ],
                                    ],
                                    'user_count' => [ 'type' => 'integer' ],
                                ],
                            ],
                        ],
                        'total' => [ 'type' => 'integer' ],
                    ],
                ],
                'execute_callback'    => [ UserManager::class, 'get_roles' ],
                'permission_callback' => $this->permissions->callback( 'can_manage_users' ),
                'meta'                => $this->readOnlyMeta(),
            ]
        );
    }

    // =========================================================================
    // Schema Helpers
    // =========================================================================

    /**
     * Get user entity schema
     *
     * @return array JSON Schema for user entity
     */
    private function getUserSchema(): array {
        return [
            'type'       => 'object',
                    'default'    => [],
            'properties' => [
                'id'           => [ 'type' => 'integer' ],
                'username'     => [ 'type' => 'string' ],
                'email'        => [ 'type' => 'string' ],
                'display_name' => [ 'type' => 'string' ],
                'first_name'   => [ 'type' => 'string' ],
                'last_name'    => [ 'type' => 'string' ],
                'website'      => [ 'type' => 'string' ],
                'role'         => [ 'type' => 'string' ],
                'roles'        => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
                'registered'   => [ 'type' => 'string' ],
                'avatar_url'   => [ 'type' => 'string' ],
            ],
        ];
    }
}
