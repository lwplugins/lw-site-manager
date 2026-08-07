<?php
/**
 * Authorization guard for the meta abilities.
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Services\Meta;

use LightweightPlugins\SiteManager\Helpers\Capability;
use LightweightPlugins\SiteManager\Helpers\ProtectedMeta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single entry point for "may this caller touch this meta key on this object?".
 *
 * Keeps the two rules that must hold for every meta operation in one place:
 * an object-level capability check, and the protected-key policy. MetaManager
 * only delegates here, so the answer cannot drift between the twelve
 * get/set/delete methods.
 */
final class MetaGuard {

    /**
     * Meta capability used per object type, as [ read, write ].
     *
     * Terms are readable by anyone who can reach the ability (taxonomy data is
     * public), so only the write side is gated for them.
     *
     * @var array<string, array{0: ?string, 1: string}>
     */
    private const CAPS = [
        'post'    => [ 'read_post', 'edit_post' ],
        'user'    => [ 'edit_user', 'edit_user' ],
        'comment' => [ 'edit_comment', 'edit_comment' ],
        'term'    => [ null, 'edit_term' ],
    ];

    /**
     * Guard reading one meta key from one object.
     *
     * @param string $type     Object type: post, user, comment or term.
     * @param int    $objectId Target object ID.
     * @param string $key      Meta key being read.
     */
    public static function read( string $type, int $objectId, string $key ): ?\WP_Error {
        $error = self::checkCapability( $type, $objectId, 0 );
        if ( $error ) {
            return $error;
        }

        // Role, level and session-token keys are never disclosed, at any
        // capability: they are privilege and login state, not content.
        if ( 'user' === $type && ProtectedMeta::isUserPrivilegeKey( $key ) ) {
            return self::protectedKeyError( $key );
        }

        // A single-key read must not become a way around the listing filter.
        if ( ProtectedMeta::isProtected( $key, $type ) && ! ProtectedMeta::mayReadProtected( true ) ) {
            return self::protectedKeyError( $key );
        }

        return null;
    }

    /**
     * Guard writing (or deleting) one meta key on one object.
     *
     * @param string $type     Object type: post, user, comment or term.
     * @param int    $objectId Target object ID.
     * @param string $key      Meta key being written.
     */
    public static function write( string $type, int $objectId, string $key ): ?\WP_Error {
        // Role/session keys are refused for everyone, before any capability
        // check — no capability makes writing a role assignment acceptable here.
        if ( 'user' === $type ) {
            $error = ProtectedMeta::guardUserWrite( $key );
            if ( $error ) {
                return $error;
            }
        }

        $error = self::checkCapability( $type, $objectId, 1 );
        if ( $error ) {
            return $error;
        }

        return ProtectedMeta::guardProtectedWrite( $key, $type );
    }

    /**
     * Guard access to a whole meta listing for one object.
     *
     * @param string $type     Object type.
     * @param int    $objectId Target object ID.
     */
    public static function readAll( string $type, int $objectId ): ?\WP_Error {
        return self::checkCapability( $type, $objectId, 0 );
    }

    /**
     * Whether protected keys may appear in a listing for this caller.
     *
     * @param bool $requested Value of the caller's include_private flag.
     */
    public static function mayListProtected( bool $requested ): bool {
        return ProtectedMeta::mayReadProtected( $requested );
    }

    /**
     * Resolve and run the capability for an object type.
     *
     * @param string $type     Object type.
     * @param int    $objectId Target object ID.
     * @param int    $slot     0 for the read capability, 1 for the write one.
     */
    private static function checkCapability( string $type, int $objectId, int $slot ): ?\WP_Error {
        $capability = self::CAPS[ $type ][ $slot ] ?? null;

        if ( null === $capability ) {
            return null;
        }

        return Capability::check(
            $capability,
            $objectId,
            sprintf( 'You are not allowed to access meta for this %s.', $type )
        );
    }

    /**
     * Denial response for a protected key.
     */
    private static function protectedKeyError( string $key ): \WP_Error {
        return new \WP_Error(
            'forbidden_meta_key',
            sprintf( 'The meta key "%s" is protected and cannot be read through this ability.', $key ),
            [ 'status' => 403 ]
        );
    }
}
