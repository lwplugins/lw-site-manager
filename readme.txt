=== LW Site Manager ===
Contributors: lwplugins
Tags: site-manager, maintenance, ai, rest-api, abilities
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress Site Manager using the Abilities API - Full site maintenance via AI/REST.

== Description ==

LW Site Manager is a comprehensive WordPress site management plugin built on the WordPress Abilities API. It provides a native, AI-ready alternative to MainWP for managing your WordPress site.

= Features =

* **Updates Management** - Check and apply updates for core, plugins, and themes
* **Plugin Management** - Install, activate, deactivate, and delete plugins
* **Theme Management** - Install, activate, and delete themes
* **Content Management** - Full CRUD for posts, pages, and custom post types
* **Taxonomy Management** - Manage categories, tags, and custom taxonomies
* **User Management** - Create, update, and manage users
* **Media Management** - Upload and manage media files
* **Comments Management** - Moderate and manage comments
* **Backup & Restore** - Create and restore site backups
* **Health & Diagnostics** - Monitor site health and PHP errors
* **Database Maintenance** - Optimize, cleanup, and repair database
* **Cache Management** - Flush object cache, page cache, and OPcache
* **Settings Management** - Read and update WordPress options
* **WooCommerce Integration** - Manage products, orders, and reports (if WooCommerce is active)

= AI Integration =

This plugin is designed for AI agent integration via:

* **REST API** - Any AI can call abilities via HTTP
* **MCP Adapter** - Claude, GPT can use abilities as tools
* **Agentic Loops** - AI decides which abilities to call

= Part of LW Plugins =

LW Site Manager is part of the [LW Plugins](https://lwplugins.com) family - lightweight plugins for WordPress with no bloat, no upsells, and no tracking.

== Installation ==

= Via Composer =

`composer require lwplugins/lw-site-manager`

= Manual Installation =

1. Download the plugin from GitHub
2. Upload to the `/wp-content/plugins/lw-site-manager` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

= Requirements =

* PHP 8.1 or higher
* WordPress 6.9 or higher (requires Abilities API)

== Frequently Asked Questions ==

= What is the WordPress Abilities API? =

The WordPress Abilities API is a new feature in WordPress 6.9 that allows plugins to register standardized capabilities that can be executed via REST API, PHP, or JavaScript.

= How do I authenticate API requests? =

Use WordPress Application Passwords. Go to Users → Your Profile → Application Passwords to create one. Then use Basic Auth with your username and app password.

= Does this work with WooCommerce? =

Yes! When WooCommerce is active, additional abilities for managing products, orders, customers, and reports become available.

= Is this a MainWP alternative? =

Yes, LW Site Manager provides similar functionality to MainWP but uses the native WordPress Abilities API instead of custom endpoints.

== Screenshots ==

1. Site health check results
2. Update management interface
3. Backup creation options

== Changelog ==

= 1.1.5 =
* Fixed: SVG upload support via Abilities API when SVG plugins (Allow SVG, Safe SVG) are active
* Added: MIME type detection in media upload that respects third-party plugin filters

= 1.1.4 =
* Added: Custom taxonomy support in category and tag abilities via taxonomy parameter
* Added: All category/tag abilities now support any taxonomy (not just category/post_tag)

= 1.1.3 =
* Added: Custom taxonomy support in create-post and update-post
* Added: New set-post-terms ability for setting taxonomy terms
* Added: New get-post-terms ability for retrieving taxonomy terms
* Added: All taxonomies now returned in post responses
* Added: PHPCS configuration for code quality
* Fixed: Duplicate array key in TaxonomyAbilities
* Changed: Improved .gitignore configuration

= 1.1.2 =
* Changed: Documentation translated to English

= 1.1.1 =
* Changed: Version bump for documentation updates

= 1.1.0 =
* Changed: Renamed plugin from WP Site Manager to LW Site Manager
* Changed: Moved to LW Plugins organization
* Changed: Updated namespace to LightweightPlugins\SiteManager
* Changed: Requires PHP 8.1+ (was 8.0+)
* Added: LW Plugins unified admin menu integration

= 1.0.6 =
* Added: meta_key and meta_value parameters for list-posts filtering

= 1.0.5 =
* Fixed: Tags input now accepts both integer IDs and string names/slugs

= 1.0.4 =
* Added: Plugin Database Updates abilities
* Added: Documentation for all abilities
* Fixed: Various documentation corrections

= 1.0.3 =
* Added: Self-update functionality from private repository

= 1.0.2 =
* Fixed: Plugin update mechanism improvements

= 1.0.1 =
* Fixed: Initial bug fixes

= 1.0.0 =
* Initial release
* WordPress Abilities API integration
* Full site management capabilities

== Upgrade Notice ==

= 1.1.3 =
Adds custom taxonomy support for posts and custom post types.

= 1.1.0 =
Plugin renamed from WP Site Manager to LW Site Manager. Update your references if needed.
