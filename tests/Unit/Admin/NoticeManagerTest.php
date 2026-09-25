<?php
/**
 * NoticeManager unit tests.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Admin;

use LightweightPlugins\SiteManager\Admin\NoticeManager;
use PHPUnit\Framework\TestCase;

final class NoticeManagerTest extends TestCase {

	/**
	 * The stub hook registry, restored after each test.
	 *
	 * @var mixed
	 */
	private $saved_filters;

	protected function setUp(): void {
		$this->saved_filters = $GLOBALS['wp_filter'] ?? [];
	}

	protected function tearDown(): void {
		$GLOBALS['wp_filter'] = $this->saved_filters;
		unset( $GLOBALS['plugin_page'], $GLOBALS['_lw_test_admin_page_parent'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: bool}>
	 */
	public static function callback_provider(): array {
		return [
			'LW static method string' => [ 'LightweightPlugins\\SiteManager\\Admin\\AdminNotice::render', true ],
			'LW class array'          => [ [ 'LightweightPlugins\\SEO\\Admin\\NoticeManager', 'open_wrap' ], true ],
			'LW object array'         => [ [ new NoticeManagerTestOwn(), 'render' ], true ],
			'LW closure'              => [ static function (): void {}, true ],
			'core function'           => [ 'wp_admin_notice', false ],
			'theme class array'       => [ [ 'TGM_Plugin_Activation', 'notices' ], false ],
			'theme object array'      => [ [ new \ArrayObject(), 'count' ], false ],
			'global closure'          => [ eval( 'return static function (): void {};' ), false ], // phpcs:ignore Squiz.PHP.Eval.Discouraged -- a closure declared outside any namespace.
			'look-alike namespace'    => [ 'LightweightPluginsFake\\Notice::render', false ],
		];
	}

	/**
	 * @dataProvider callback_provider
	 *
	 * @param mixed $callback Hook callback.
	 * @param bool  $expected Whether it is an LW callback.
	 */
	public function test_tells_lw_callbacks_from_the_rest( $callback, bool $expected ): void {
		$this->assertSame( $expected, NoticeManager::is_own( $callback ) );
	}

	public function test_removes_only_foreign_callbacks_on_lw_pages(): void {
		$this->on_lw_page();
		$own                  = [ 'function' => 'LightweightPlugins\\SiteManager\\Admin\\AdminNotice::render' ];
		$GLOBALS['wp_filter'] = [
			'admin_notices'     => (object) [
				'callbacks' => [
					10 => [
						'a' => [ 'function' => [ 'TGM_Plugin_Activation', 'notices' ] ],
						'b' => $own,
					],
				],
			],
			'all_admin_notices' => (object) [ 'callbacks' => [ 5 => [ 'c' => [ 'function' => 'brooklyn_purchase_notice' ] ] ] ],
		];

		NoticeManager::isolate();

		$this->assertSame(
			[ [ 10 => [ 'b' => $own ] ], [ 5 => [] ] ],
			[ $GLOBALS['wp_filter']['admin_notices']->callbacks, $GLOBALS['wp_filter']['all_admin_notices']->callbacks ]
		);
	}

	public function test_leaves_other_admin_pages_alone(): void {
		$GLOBALS['plugin_page']                = 'woocommerce';
		$GLOBALS['_lw_test_admin_page_parent'] = 'woocommerce';
		$callbacks                             = [ 10 => [ 'a' => [ 'function' => 'brooklyn_purchase_notice' ] ] ];
		$GLOBALS['wp_filter']                  = [ 'admin_notices' => (object) [ 'callbacks' => $callbacks ] ];

		NoticeManager::isolate();

		$this->assertSame( $callbacks, $GLOBALS['wp_filter']['admin_notices']->callbacks );
	}

	public function test_recognises_the_lw_plugins_overview_page(): void {
		$GLOBALS['plugin_page'] = 'lw-plugins';

		$this->assertTrue( NoticeManager::is_lw_page() );
	}

	public function test_adds_the_body_class_once(): void {
		$this->on_lw_page();

		$this->assertSame( 'a lw-plugins-admin-page', NoticeManager::body_class( NoticeManager::body_class( 'a' ) ) );
	}

	public function test_registers_its_hooks_once_however_often_it_is_called(): void {
		( new \ReflectionProperty( NoticeManager::class, 'registered' ) )->setValue( null, false );
		$GLOBALS['wp_filter'] = [];

		NoticeManager::register();
		NoticeManager::init();

		$this->assertSame(
			[ 1, 1, 1 ],
			[
				count( $GLOBALS['wp_filter']['in_admin_header'][ PHP_INT_MAX ] ),
				count( $GLOBALS['wp_filter']['admin_head'][10] ),
				count( $GLOBALS['wp_filter']['admin_body_class'][10] ),
			]
		);
	}

	private function on_lw_page(): void {
		$GLOBALS['plugin_page']                = 'lw-site-manager';
		$GLOBALS['_lw_test_admin_page_parent'] = 'lw-plugins';
	}
}

/**
 * An object whose class lives in the LW namespace.
 */
final class NoticeManagerTestOwn {

	public function render(): void {}
}
