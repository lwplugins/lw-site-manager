<?php
/**
 * Self-updater client for updater.gobird.io
 */

declare(strict_types=1);

namespace LightweightPlugins\SiteManager\Updater;

class SelfUpdater {

    private const UPDATER_URL = "https://updater.gobird.io";

    private string $plugin_slug;
    private string $plugin_basename;
    private string $current_version;
    private ?object $update_info = null;

    public function __construct() {
        $this->plugin_slug = "wp-site-manager";
        $this->plugin_basename = "wp-site-manager/wp-site-manager.php";
        $this->current_version = LW_SITE_MANAGER_VERSION;
    }

    public function init(): void {
        add_filter("pre_set_site_transient_update_plugins", [$this, "check_for_update"]);
        add_filter("plugins_api", [$this, "plugin_info"], 20, 3);
        add_filter("upgrader_source_selection", [$this, "fix_directory_name"], 10, 4);

        // Clear our cache when WordPress clears its update cache (force-check)
        add_action("delete_site_transient_update_plugins", [$this, "clear_update_cache"]);
    }

    public function clear_update_cache(): void {
        delete_transient("wpsm_update_info");
        $this->update_info = null;
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $update = $this->get_update_info();

        if ($update && !empty($update->has_update) && $update->has_update === true) {
            $response = (object) [
                "slug"        => $this->plugin_slug,
                "plugin"      => $this->plugin_basename,
                "new_version" => $update->version,
                "url"         => $update->homepage ?? "",
                "package"     => $update->download_url,
                "icons"       => [],
                "banners"     => [],
                "tested"      => $update->tested ?? "",
                "requires"    => $update->requires ?? "6.9",
                "requires_php"=> $update->requires_php ?? "8.0",
            ];

            $transient->response[$this->plugin_basename] = $response;
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ("plugin_information" !== $action || $this->plugin_slug !== $args->slug) {
            return $result;
        }

        $update = $this->get_update_info();
        if (!$update) {
            return $result;
        }

        return (object) [
            "name"           => $update->name ?? "WP Site Manager",
            "slug"           => $this->plugin_slug,
            "version"        => $update->version,
            "author"         => $update->author ?? "trueqap",
            "homepage"       => $update->homepage ?? "",
            "short_description" => "WordPress Site Manager using Abilities API",
            "sections"       => [
                "description"  => "WordPress Site Manager using Abilities API - Full site maintenance via AI/REST",
                "changelog"    => nl2br($update->changelog ?? "No changelog available."),
            ],
            "download_link"  => $update->download_url,
            "requires"       => $update->requires ?? "6.9",
            "requires_php"   => $update->requires_php ?? "8.0",
            "tested"         => $update->tested ?? "",
        ];
    }

    public function fix_directory_name($source, $remote_source, $upgrader, $hook_extra = []) {
        if (!isset($hook_extra["plugin"]) || $hook_extra["plugin"] !== $this->plugin_basename) {
            return $source;
        }

        global $wp_filesystem;

        $corrected_source = trailingslashit($remote_source) . $this->plugin_slug . "/";

        if ($source !== $corrected_source && $wp_filesystem->move($source, $corrected_source)) {
            return $corrected_source;
        }

        return $source;
    }

    private function get_update_info(): ?object {
        if ($this->update_info) {
            return $this->update_info;
        }

        $cache_key = "wpsm_update_info";
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            $this->update_info = $cached;
            return $cached;
        }

        $url = self::UPDATER_URL . "/update/" . $this->plugin_slug . "?version=" . $this->current_version;

        $response = wp_remote_get($url, [
            "timeout" => 10,
            "headers" => ["Accept" => "application/json"],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (!$body || isset($body->error)) {
            return null;
        }

        set_transient($cache_key, $body, 6 * HOUR_IN_SECONDS);

        $this->update_info = $body;
        return $body;
    }

    public function force_check(): ?object {
        delete_transient("wpsm_update_info");
        $this->update_info = null;
        return $this->get_update_info();
    }
}
