<?php
/**
 * Memory figures of health-check, including an unlimited memory_limit.
 *
 * Regression guard: memory_limit = -1 gave wp_convert_hr_to_bytes() = -1, so
 * size_format() returned false (rejected by the output schema) and the
 * percentage came out negative.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Health
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Health;

use LightweightPlugins\SiteManager\Services\Health\MemoryUsage;
use PHPUnit\Framework\TestCase;

final class MemoryUsageTest extends TestCase {

    public function test_unlimited_memory_limit_is_reported_explicitly(): void {
        $memory = MemoryUsage::report( '-1', 64 * 1024 * 1024 );

        $this->assertSame( 'unlimited', $memory['limit'] );
        $this->assertTrue( $memory['unlimited'] );
        $this->assertNull( $memory['percent'] );
        $this->assertSame( '64 MB', $memory['usage'] );
    }

    public function test_limited_memory_reports_size_and_percent(): void {
        $memory = MemoryUsage::report( '256M', 64 * 1024 * 1024 );

        $this->assertSame( '256 MB', $memory['limit'] );
        $this->assertFalse( $memory['unlimited'] );
        $this->assertSame( 25.0, $memory['percent'] );
    }

    public function test_every_field_is_schema_valid_for_unlimited(): void {
        $memory = MemoryUsage::report( '-1', 1024 );

        $this->assertIsString( $memory['limit'] );
        $this->assertIsString( $memory['usage'] );
    }
}
