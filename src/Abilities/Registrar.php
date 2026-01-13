<?php
/**
 * Abilities Registrar - Coordinates all site management ability registration
 *
 * This is the main entry point that orchestrates the registration of all abilities
 * through specialized registrar classes.
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities;

use WPSiteManager\Abilities\Registrars\UpdateAbilitiesRegistrar;
use WPSiteManager\Abilities\Registrars\MaintenanceAbilitiesRegistrar;
use WPSiteManager\Abilities\Registrars\UserAbilitiesRegistrar;
use WPSiteManager\Abilities\Registrars\ContentAbilitiesRegistrar;

class Registrar {

    private PermissionManager $permissions;

    public function __construct() {
        $this->permissions = new PermissionManager();
    }

    /**
     * Register all abilities
     *
     * Delegates to specialized registrar classes for different ability categories.
     */
    public function register_all(): void {
        $registrars = [
            new UpdateAbilitiesRegistrar( $this->permissions ),
            new MaintenanceAbilitiesRegistrar( $this->permissions ),
            new UserAbilitiesRegistrar( $this->permissions ),
            new ContentAbilitiesRegistrar( $this->permissions ),
        ];

        foreach ( $registrars as $registrar ) {
            $registrar->register();
        }
    }

    /**
     * Get the permission manager instance
     *
     * @return PermissionManager
     */
    public function get_permissions(): PermissionManager {
        return $this->permissions;
    }
}
