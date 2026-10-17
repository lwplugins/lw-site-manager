<?php
/**
 * Which capability each bulk product action needs on each product.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\WooCommerce;

/**
 * Mirrors the product list screen: publishing needs publish_post, trashing,
 * restoring and deleting delete_post, any other status change edit_post —
 * each checked against the product, so one bulk call cannot touch products
 * the user could not change one by one.
 */
final class BulkProductAccess {

    private const CAPABILITIES = [
        'publish' => 'publish_post',
        'draft'   => 'edit_post',
        'trash'   => 'delete_post',
        'restore' => 'delete_post',
        'delete'  => 'delete_post',
    ];

    public static function capability( string $action ): string {
        return self::CAPABILITIES[ $action ] ?? 'do_not_allow';
    }
}
