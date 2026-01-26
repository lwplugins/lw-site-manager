<?php
/**
 * Content Abilities Registrar - Coordinates content management ability registration
 *
 * This registrar delegates to the modular definition classes for posts, pages, comments,
 * media, taxonomies, and meta.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Abilities\Registrars;

use LightweightPlugins\SiteManager\Abilities\Definitions\PostAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\PageAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\CommentAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\MediaAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\TaxonomyAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\MetaAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\SettingsAbilities;
use LightweightPlugins\SiteManager\Abilities\Definitions\WooCommerceAbilities;

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
