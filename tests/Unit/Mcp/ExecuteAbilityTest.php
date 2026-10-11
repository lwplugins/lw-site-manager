<?php
/**
 * Tests for ExecuteAbility.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Mcp
 */

declare(strict_types=1);
namespace LightweightPlugins\SiteManager\Tests\Unit\Mcp;

use LightweightPlugins\SiteManager\Mcp\ExecuteAbility;
use PHPUnit\Framework\TestCase;

final class ExecuteAbilityTest extends TestCase {

	/**
	 * Registration args of the adapter's execute-ability, reduced to the part
	 * ExecuteAbility touches.
	 *
	 * @param callable $execute The execute callback.
	 * @return array<string, mixed>
	 */
	private static function execute_ability_args( callable $execute ): array {
		return [
			'label'            => 'Execute Ability',
			'execute_callback' => $execute,
		];
	}

	public function test_hidden_inner_failure_becomes_an_error(): void {
		$out = ExecuteAbility::unwrap( [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'boom', 'code' => 'E1' ] ] );

		$this->assertInstanceOf( \WP_Error::class, $out );
		$this->assertStringContainsString( 'boom', $out->get_error_message() );
		$this->assertStringContainsString( 'E1', $out->get_error_message() );
	}

	public function test_reported_failure_becomes_an_error_with_its_message(): void {
		$out = ExecuteAbility::unwrap( [ 'success' => false, 'error' => 'Post not found' ] );

		$this->assertInstanceOf( \WP_Error::class, $out );
		$this->assertSame( 'Post not found', $out->get_error_message() );
	}

	/**
	 * @dataProvider provide_results_that_are_not_failures
	 */
	public function test_leaves_other_results_unchanged( mixed $result ): void {
		$this->assertSame( $result, ExecuteAbility::unwrap( $result ) );
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function provide_results_that_are_not_failures(): array {
		return [
			'inner success'                => [ [ 'success' => true, 'data' => [ 'success' => true, 'foo' => 1 ] ] ],
			'inner failure without text'   => [ [ 'success' => true, 'data' => [ 'success' => false ] ] ],
			'inner failure with blank text' => [ [ 'success' => true, 'data' => [ 'success' => false, 'error' => '  ' ] ] ],
			'outer failure without text'   => [ [ 'success' => false ] ],
			'scalar data'                  => [ [ 'success' => true, 'data' => 'ok' ] ],
			'not an array'                 => [ 'ok' ],
		];
	}

	public function test_wrapped_execute_callback_reports_a_hidden_failure_as_an_error(): void {
		$args = ExecuteAbility::filter_args(
			self::execute_ability_args( static fn( $input ) => [ 'success' => true, 'data' => [ 'success' => false, 'error' => 'boom' ] ] ),
			'mcp-adapter/execute-ability'
		);

		$this->assertInstanceOf( \WP_Error::class, ( $args['execute_callback'] )( [ 'ability_name' => 'x/y' ] ) );
	}

	public function test_wrapped_execute_callback_passes_input_and_success_through(): void {
		$args = ExecuteAbility::filter_args(
			self::execute_ability_args( static fn( $input ) => [ 'success' => true, 'data' => $input ] ),
			'mcp-adapter/execute-ability'
		);

		$this->assertSame(
			[ 'success' => true, 'data' => [ 'ability_name' => 'x/y' ] ],
			( $args['execute_callback'] )( [ 'ability_name' => 'x/y' ] )
		);
	}

	public function test_other_abilities_are_not_wrapped(): void {
		$args = self::execute_ability_args( static fn() => [ 'success' => false, 'error' => 'x' ] );

		$this->assertSame( $args, ExecuteAbility::filter_args( $args, 'mcp-adapter/discover-abilities' ) );
	}

	/**
	 * The copy WooCommerce bundles declares `data` without a type, so core's
	 * output validation raises PHP warnings on every successful call.
	 */
	public function test_untyped_result_data_accepts_every_json_type(): void {
		$args                  = self::execute_ability_args( static fn() => [] );
		$args['output_schema'] = [
			'type'       => 'object',
			'properties' => [ 'data' => [ 'description' => 'The result data' ] ],
		];

		$out = ExecuteAbility::filter_args( $args, 'mcp-adapter/execute-ability' );

		$this->assertSame(
			[
				'description' => 'The result data',
				'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
			],
			$out['output_schema']['properties']['data']
		);
	}

	public function test_typed_result_data_is_kept(): void {
		$args                  = self::execute_ability_args( static fn() => [] );
		$args['output_schema'] = [ 'properties' => [ 'data' => [ 'type' => 'object' ] ] ];

		$out = ExecuteAbility::filter_args( $args, 'mcp-adapter/execute-ability' );

		$this->assertSame( [ 'type' => 'object' ], $out['output_schema']['properties']['data'] );
	}

	public function test_args_without_a_callable_are_left_alone(): void {
		$args = [ 'execute_callback' => 'no_such_function_lw_test' ];

		$this->assertSame( $args, ExecuteAbility::filter_args( $args, 'mcp-adapter/execute-ability' ) );
	}
}
