# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.3] - 2026-01-26

### Added
- Custom taxonomy support in `create-post` and `update-post` via `taxonomies` parameter
- New `set-post-terms` ability for setting taxonomy terms on any post type
- New `get-post-terms` ability for retrieving taxonomy terms (single or all)
- All taxonomies now returned in post responses with backwards compatibility for `categories` and `tags`
- PHPCS configuration with WordPress coding standards (PSR-4 compatible)

### Fixed
- Duplicate array key in TaxonomyAbilities
- Auto-fixed placeholder ordering in i18n strings

### Changed
- Improved `.gitignore` configuration
- Removed `composer.lock` from version control

## [1.1.2] - 2026-01-26

### Changed
- Documentation translated to English (all 14 ability documentation files)

## [1.1.1] - 2026-01-26

### Changed
- Version bump for documentation updates

## [1.1.0] - 2026-01-26

### Changed
- Renamed plugin from WP Site Manager to LW Site Manager
- Moved to LW Plugins organization (lwplugins)
- Updated namespace from `WPSiteManager` to `LightweightPlugins\SiteManager`
- Updated constants prefix from `WPSM_` to `LW_SITE_MANAGER_`
- Updated text-domain from `wp-site-manager` to `lw-site-manager`
- Requires PHP 8.1+ (was 8.0+)
- Added LW Plugins unified admin menu integration

## [1.0.6] - 2025-01-19

### Added
- `list-posts` ability now supports `meta_key` and `meta_value` parameters for filtering posts by custom meta fields
- Useful for finding posts by unique identifiers like `helloblog_id`

## [1.0.5] - 2025-01-15

### Fixed
- Tags input now accepts both integer IDs and string names/slugs in `create-post` and `update-post`
- Integer tags are resolved to existing tag IDs instead of creating new tags with numeric names
- Mixed arrays supported (e.g., `[28, "php", "backend"]`)

## [1.0.4] - 2025-01-15

### Added
- Plugin Database Updates abilities (`check-plugin-db-updates`, `update-plugin-db`, `update-all-plugin-dbs`, `get-supported-db-plugins`)
- Documentation for all abilities in `docs/abilities/`
- README.md explaining WordPress Abilities API

### Fixed
- Pages documentation: added missing abilities (restore-page, duplicate-page, reorder-pages, set-page-template)
- Pages documentation: fixed set-homepage and set-posts-page methods (DELETE → POST)
- Comments documentation: fixed limit default (20 → 50), orderby (date → comment_date), added type param
- Media documentation: removed non-existent author param, added data param for base64, added force param

## [1.0.3] - 2025-01-14

### Added
- Self-update functionality from private Gitea repository
- SelfUpdater.php class for WordPress update integration
- Gitea Actions workflow for automated releases

## [1.0.2] - 2025-01-14

### Fixed
- Plugin update mechanism improvements

## [1.0.1] - 2025-01-14

### Fixed
- Initial bug fixes

## [1.0.0] - 2025-01-14

### Added
- Initial release
- WordPress Abilities API integration
- Maintenance abilities (updates, backups, cache, database)
- Diagnostics abilities (health check, error log)
- Plugin management abilities
- Theme management abilities
- User management abilities
- Content abilities (posts, pages, comments, media)
- Settings abilities
- Taxonomy abilities
- Meta abilities
- WooCommerce abilities
