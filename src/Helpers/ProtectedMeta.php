<?php
/**
 * Protected meta-key policy.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Decides which meta keys an ability caller may read or write.
 *
 * Two separate concerns live here:
 *
 * 1. *Protected* keys (leading underscore) hold plugin-internal state — API
 *    keys, licence keys, form payloads, WooCommerce internals. They are hidden
 *    from listings unless the caller asks for them, and never writable by a
 *    caller who is not an administrator.
 * 2. *Capability-bearing* user meta ( {prefix}capabilities, {prefix}user_level )
 *    IS the role assignment. Writing it is a direct privilege escalation that
 *    bypasses both role validation and the promote_user capability, so it is
 *    denied outright rather than gated.
 */
final class ProtectedMeta {

    /**
     * Meta keys that are never writable through an ability, at any capability.
     *
     * The table prefix varies per install and per site on multisite
     * ( wp_capabilities, wp_2_capabilities, ... ), so the role and level keys
     * are matched by suffix rather than by an exact name.
     */
    private const BLOCKED_USER_PATTERN = '/(?:^|_)(capabilities|user_level)$/';

    /**
     * Whether a key is protected (plugin-internal) meta.
     *
     * @param string $key  Meta key.
     * @param string $type Object type: post, user, term or comment.
     */
    public static function isProtected( string $key, string $type = 'post' ): bool {
        if ( function_exists( 'is_protected_meta' ) ) {
            return (bool) is_protected_meta( $key, $type );
        }

        return str_starts_with( $key, '_' );
    }

    /**
     * Whether a user meta key carries role/capability state or a login session.
     *
     * Writing any of these grants privileges directly, so they are refused for
     * every caller regardless of capability.
     */
    public static function isUserPrivilegeKey( string $key ): bool {
        $key = strtolower( trim( $key ) );

        if ( 'session_tokens' === $key ) {
            return true;
        }

        return 1 === preg_match( self::BLOCKED_USER_PATTERN, $key );
    }

    /**
     * Guard a user meta write.
     */
    public static function guardUserWrite( string $key ): ?\WP_Error {
        if ( self::isUserPrivilegeKey( $key ) ) {
            return ResponseFormatter::error(
                'forbidden_meta_key',
                sprintf(
                    'The meta key "%s" carries role or session state and cannot be set through this ability. Use the role parameter of update-user instead.',
                    $key
                ),
                403
            );
        }

        return null;
    }

    /**
     * Guard a write to a protected key on any object type.
     *
     * Administrators keep full access; everyone else is refused, because a
     * protected key is by definition state another plugin trusts.
     */
    public static function guardProtectedWrite( string $key, string $type = 'post' ): ?\WP_Error {
        if ( ! self::isProtected( $key, $type ) || current_user_can( 'manage_options' ) ) {
            return null;
        }

        return ResponseFormatter::error(
            'forbidden_meta_key',
            sprintf( 'The meta key "%s" is protected and can only be modified by an administrator.', $key ),
            403
        );
    }

    /**
     * Whether a protected key may be disclosed to the current caller.
     *
     * `include_private` is a caller-supplied convenience flag, not an
     * authorization decision, so it only takes effect for administrators.
     */
    public static function mayReadProtected( bool $requested ): bool {
        return $requested && current_user_can( 'manage_options' );
    }
}
