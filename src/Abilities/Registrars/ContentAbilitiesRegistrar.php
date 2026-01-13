<?php
/**
 * Content Abilities Registrar - Coordinates content management ability registration
 *
 * This registrar delegates to the modular definition classes for posts, pages, comments,
 * media, taxonomies, and meta.
 */

declare(strict_types=1);

namespace WPSiteManager\Abilities\Registrars;

use WPSiteManager\Abilities\Definitions\PostAbilities;
use WPSiteManager\Abilities\Definitions\PageAbilities;
use WPSiteManager\Abilities\Definitions\CommentAbilities;
use WPSiteManager\Abilities\Definitions\MediaAbilities;
use WPSiteManager\Abilities\Definitions\TaxonomyAbilities;
use WPSiteManager\Abilities\Definitions\MetaAbilities;
use WPSiteManager\Abilities\Definitions\SettingsAbilities;
use WPSiteManager\Abilities\Definitions\WooCommerceAbilities;

class ContentAbilitiesRegistrar extends AbstractAbilitiesRegistrar {

    public function register(): void {
        // Delegate to modular definition classes
        // These classes handle their own ability registration
        PostAbilities::register( $this->permissions );
        PageAbilities::register( $this->permissions );
        CommentAbilities::register( $this->permissions );
        MediaAbilities::register( $this->permissions );
        TaxonomyAbilities::register( $this->permissions );
        MetaAbilities::register( $this->permissions );
        SettingsAbilities::register( $this->permissions );

        // WooCommerce abilities (only if WooCommerce is active)
        WooCommerceAbilities::register( $this->permissions );
    }
}
