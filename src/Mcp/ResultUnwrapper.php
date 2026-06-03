<?php
/**
 * MCP result unwrapper — surfaces hidden logical failures as real MCP errors.
 *
 * @package LightweightPlugins\SiteManager\Mcp
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unwraps `{ success:true, data:{ success:false, error } }` from the adapter's
 * execute-ability dispatcher so logical failures surface as real MCP errors.
 * Hooked on `mcp_adapter_tool_call_result`.
 */
final class ResultUnwrapper {

	/**
	 * Filter callback. Returns the inner error array when the wrapped result hides
	 * a logical failure; otherwise the original result unchanged.
	 *
	 * @param mixed               $result    The tool call result.
	 * @param array<string,mixed> $args      The tool call arguments (unused).
	 * @param string              $tool_name The MCP-sanitized tool name.
	 * @return mixed
	 */
	public static function filter( mixed $result, array $args, string $tool_name ): mixed {
		if ( 'mcp-adapter-execute-ability' !== $tool_name ) {
			return $result;
		}
		if ( ! is_array( $result ) || true !== ( $result['success'] ?? null ) ) {
			return $result;
		}
		$data = $result['data'] ?? null;
		if ( ! is_array( $data ) || false !== ( $data['success'] ?? null ) ) {
			return $result;
		}
		$error = $data['error'] ?? null;
		if ( ! is_string( $error ) || '' === trim( $error ) ) {
			return $result;
		}

		$detail = $data;
		unset( $detail['success'], $detail['error'] );
		if ( [] !== $detail ) {
			$encoded = wp_json_encode( $detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			if ( is_string( $encoded ) ) {
				$data['error'] = $error . "\n\nStructured detail (JSON):\n" . $encoded;
			}
		}
		return $data;
	}
}
