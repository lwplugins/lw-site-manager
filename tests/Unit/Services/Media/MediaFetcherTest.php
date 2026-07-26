<?php
/**
 * Unit tests for the MediaFetcher source-resolution policy.
 *
 * @package LightweightPlugins\SiteManager\Tests\Unit\Services\Media
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Tests\Unit\Services\Media;

use LightweightPlugins\SiteManager\Services\Media\MediaFetcher;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MediaFetcher::plan() — the pure decision layer.
 *
 * Covers https://github.com/lwplugins/lw-site-manager/issues/18: URL uploads
 * failed on self-signed certs with no opt-out, even when the site was
 * downloading a file from itself.
 */
final class MediaFetcherTest extends TestCase {

    /**
     * Site context used by every test.
     *
     * @return array{home_url: string, uploads_baseurl: string, uploads_basedir: string}
     */
    private function site(): array {
        return [
            'home_url'        => 'https://staging.example.com',
            'uploads_baseurl' => 'https://staging.example.com/wp-content/uploads',
            'uploads_basedir' => '/var/www/html/wp-content/uploads',
        ];
    }

    // =========================================================================
    // Local short-circuit
    // =========================================================================

    public function test_same_host_uploads_url_resolves_to_local_path(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/uploads/tmp/x.jpeg',
            $this->site()
        );

        $this->assertSame( '/var/www/html/wp-content/uploads/tmp/x.jpeg', $plan['local_path'] );
    }

    public function test_local_path_ignores_query_string(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/uploads/tmp/x.jpeg?ver=123',
            $this->site()
        );

        $this->assertSame( '/var/www/html/wp-content/uploads/tmp/x.jpeg', $plan['local_path'] );
    }

    public function test_local_path_resolves_despite_scheme_mismatch(): void {
        $site                    = $this->site();
        $site['uploads_baseurl'] = 'http://staging.example.com/wp-content/uploads';

        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/uploads/a.png',
            $site
        );

        $this->assertSame( '/var/www/html/wp-content/uploads/a.png', $plan['local_path'] );
    }

    public function test_traversal_outside_uploads_dir_is_rejected(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/uploads/../../wp-config.php',
            $this->site()
        );

        $this->assertNull( $plan['local_path'] );
    }

    public function test_url_encoded_traversal_is_rejected(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/uploads/%2e%2e/%2e%2e/wp-config.php',
            $this->site()
        );

        $this->assertNull( $plan['local_path'] );
    }

    public function test_same_host_url_outside_uploads_has_no_local_path(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/themes/x/screenshot.png',
            $this->site()
        );

        $this->assertNull( $plan['local_path'] );
    }

    public function test_foreign_host_has_no_local_path(): void {
        $plan = MediaFetcher::plan(
            'https://cdn.other.com/wp-content/uploads/tmp/x.jpeg',
            $this->site()
        );

        $this->assertNull( $plan['local_path'] );
    }

    // =========================================================================
    // SSL verification policy
    // =========================================================================

    public function test_same_host_download_skips_ssl_verification(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/themes/x/screenshot.png',
            $this->site()
        );

        $this->assertFalse( $plan['sslverify'] );
    }

    public function test_foreign_host_verifies_ssl_by_default(): void {
        $plan = MediaFetcher::plan(
            'https://cdn.other.com/image.jpeg',
            $this->site()
        );

        $this->assertTrue( $plan['sslverify'] );
    }

    public function test_explicit_verify_ssl_true_overrides_same_host_default(): void {
        $plan = MediaFetcher::plan(
            'https://staging.example.com/wp-content/themes/x/screenshot.png',
            $this->site(),
            true
        );

        $this->assertTrue( $plan['sslverify'] );
    }

    public function test_explicit_verify_ssl_false_overrides_foreign_host_default(): void {
        $plan = MediaFetcher::plan(
            'https://cdn.other.com/image.jpeg',
            $this->site(),
            false
        );

        $this->assertFalse( $plan['sslverify'] );
    }

    public function test_host_comparison_is_case_insensitive(): void {
        $plan = MediaFetcher::plan(
            'https://STAGING.Example.COM/wp-content/uploads/a.png',
            $this->site()
        );

        $this->assertSame( '/var/www/html/wp-content/uploads/a.png', $plan['local_path'] );
        $this->assertFalse( $plan['sslverify'] );
    }
}
