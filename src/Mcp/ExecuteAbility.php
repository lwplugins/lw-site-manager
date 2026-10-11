<?php
/**
 * The adapter's execute-ability tool, made to behave the same on every adapter copy.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Patches the registration of the adapter's `mcp-adapter/execute-ability`,
 * the tool every Site Manager ability runs through, so it behaves the same
 * whichever copy of the adapter library registered it.
 *
 * That tool reports a failure inside its result instead of as an error:
 * `{ success:false, error }` when the target ability returned a WP_Error, and
 * `{ success:true, data:{ success:false, error } }` when the target reported
 * its failure in an array. What the agent then sees depends on the adapter
 * version. The copy WooCommerce bundles (0.3.0, which a WooCommerce store
 * loads instead of ours) returns the nested shape as a success and replaces
 * the error text of the flat one with a generic message. A WP_Error, on the
 * other hand, becomes a tool error carrying its own message in every adapter
 * version. That copy also declares the result `data` without a type, so core's
 * output validation raises PHP warnings on every successful call.
 *
 * Both are fixed at registration time, through the core
 * `wp_register_ability_args` filter (WordPress 6.9+), which fires no matter
 * which plugin's adapter copy registers the ability — unlike the adapter's own
 * `mcp_adapter_tool_call_result` filter, which the WooCommerce copy lacks.
 */
final class ExecuteAbility {

	public const NAME = 'mcp-adapter/execute-ability';

	public const ERROR_CODE = 'lw_site_manager_ability_failed';

	/**
	 * What the result `data` may be: any JSON value, as newer adapter copies declare.
	 */
	private const ANY_JSON_TYPE = [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ];

	/**
	 * Filter callback for `wp_register_ability_args`. Every other ability is untouched.
	 *
	 * @param mixed  $args The ability registration args.
	 * @param string $name The ability name.
	 * @return mixed
	 */
	public static function filter_args( mixed $args, string $name ): mixed {
		if ( self::NAME !== $name || ! is_array( $args ) ) {
			return $args;
		}

		if ( is_callable( $args['execute_callback'] ?? null ) ) {
			$execute                  = $args['execute_callback'];
			$args['execute_callback'] = static fn( mixed ...$input ): mixed => self::unwrap( $execute( ...$input ) );
		}

		if ( is_array( $args['output_schema']['properties']['data'] ?? null ) && ! isset( $args['output_schema']['properties']['data']['type'] ) ) {
			$args['output_schema']['properties']['data']['type'] = self::ANY_JSON_TYPE;
		}

		return $args;
	}

	/**
	 * Turn an execute-ability result that reports a failure into a WP_Error;
	 * return any other result unchanged.
	 *
	 * @param mixed $result The execute-ability result.
	 * @return mixed
	 */
	public static function unwrap( mixed $result ): mixed {
		if ( ! is_array( $result ) ) {
			return $result;
		}

		if ( false === ( $result['success'] ?? null ) ) {
			return self::is_message( $result['error'] ?? null )
				? new \WP_Error( self::ERROR_CODE, $result['error'] )
				: $result;
		}

		$data = $result['data'] ?? null;
		if ( true !== ( $result['success'] ?? null ) || ! is_array( $data ) || false !== ( $data['success'] ?? null ) ) {
			return $result;
		}
		if ( ! self::is_message( $data['error'] ?? null ) ) {
			return $result;
		}

		$detail = $data;
		unset( $detail['success'], $detail['error'] );

		return new \WP_Error( self::ERROR_CODE, self::with_detail( $data['error'], $detail ) );
	}

	/**
	 * Whether a value is a usable, non-blank error message.
	 *
	 * @param mixed $value Candidate message.
	 */
	private static function is_message( mixed $value ): bool {
		return is_string( $value ) && '' !== trim( $value );
	}

	/**
	 * Append the rest of the failed result (codes, per-item results) to the
	 * message, so the agent keeps the structured detail it would otherwise lose.
	 *
	 * @param string              $message The error message.
	 * @param array<mixed, mixed> $detail  The remaining fields of the failed result.
	 */
	private static function with_detail( string $message, array $detail ): string {
		if ( [] === $detail ) {
			return $message;
		}

		$encoded = wp_json_encode( $detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return is_string( $encoded ) ? $message . "\n\nStructured detail (JSON):\n" . $encoded : $message;
	}
}
