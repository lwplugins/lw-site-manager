# Changelog

## [1.1.25] - Unreleased

### Added
- **Order line item management** — `wc-add-order-item`, `wc-update-order-item`, `wc-remove-order-item` for adding products, changing quantities, and removing items on existing orders. Each takes a `recalculate` flag (default `true`) so chained operations can defer total recalculation.
- **Order coupons & fees** — `wc-apply-order-coupon`, `wc-remove-order-coupon`, `wc-add-order-fee`, `wc-remove-order-fee` for adjusting promotions and ad-hoc charges on existing orders.
- **Order shipping** — `wc-set-order-shipping` (with `replace_existing` flag), `wc-remove-order-shipping`.
- **Order recalculation** — `wc-recalculate-order` to force a `calculate_totals()` pass without other modifications.
- **Order payment workflow** — `wc-mark-order-paid` (drives `payment_complete()`, with `silent: true` flag to suppress customer emails), `wc-send-order-email` (whitelisted: `new_order`, `customer_invoice`, `customer_processing_order`, `customer_completed_order`, `customer_on_hold_order`, `customer_refunded_order`), `wc-get-payment-url` (returns the customer-facing pay-for-order URL).
- New `PermissionManager::can_manage_orders()` helper backed by the `edit_shop_orders` capability — applied to all newly added order abilities.
- Order modification abilities reject `cancelled` / `refunded` / `failed` orders with HTTP 409 (`order_locked`).
- Minimum WooCommerce version guard (7.0+) on the new abilities; below that they short-circuit with a clear error.

### Changed
- Split the monolithic `WooCommerceAbilities.php` (1523 lines) into focused classes under `src/Abilities/Definitions/WooCommerce/` — product CRUD/extras, order CRUD/extras, reports, and dedicated schema helpers (`ProductSchema`, `OrderSchema`, `ResponseSchema`, `AbilityMeta`). The public registration entry point (`WooCommerceAbilities::register()`) is unchanged.
- New service classes under `src/Services/WooCommerce/`: `OrderItemManager`, `OrderModificationManager`, `OrderPaymentManager`. The original `OrderManager` keeps the CRUD/notes/refund/bulk surface unchanged.

## [1.1.24] - 2026-04-30

### Changed
- Cleaned up redundant `'default' => []` entries in ability schemas — kept them only at top-level `input_schema` blocks where the Abilities API normalizer actually uses them; removed from `output_schema` blocks and nested object properties where they were semantically meaningless

## [1.1.23] - 2026-04-30

### Added
- `wc-create-order` ability — create WooCommerce orders on behalf of any customer via the Abilities API (`customer_id` + `line_items` with `product_id` / `quantity`, billing/shipping addresses, payment method, status, customer + internal notes)
- `wc-update-order` ability — update billing/shipping address and customer note on existing orders
- `wc-delete-order` ability — trash an order, or permanently delete with `force=true`
- Internal `addressSchema()` helper in `WooCommerceAbilities` to keep billing/shipping input schemas DRY

## [1.1.22] - 2026-04-23

### Fixed
- `upload-media` ability no longer returns stale attachment IDs and URLs on rapid consecutive uploads ([#11](https://github.com/lwplugins/lw-site-manager/issues/11))
- Post + `post_meta` object caches are now flushed before `get_post()` / `wp_get_attachment_url()` reads the newly-uploaded attachment back, so filename-collision retries don't leak a phantom ID
- Added a post-upload sanity check: if the attachment record or the file on disk is missing, the ability returns a 500 with a clear error code (`attachment_missing` / `attachment_file_missing`) instead of serialising stale data

## [1.1.19] - 2026-03-22

### Fixed
- Graceful error when autoloader is missing (admin notice instead of fatal error)

## [1.1.18] - 2026-03-17

### Fixed
- wc-list-products: status=any now includes private products by using explicit status array instead of WP_Query 'any' (#10)

## [1.1.17] - 2026-02-17

### Fixed
- wc-list-products: null price/regular_price/sale_price breaks output validation — cast to string (#9)

## [1.1.16] - 2026-02-17

### Fixed
- wc-products-totals: Replaced per-product iteration with SQL aggregation for stock status counts (#7)
- wc-low-stock-products: Push filtering into WP_Query meta_query instead of loading all products and filtering in PHP (#8)

## [1.1.15] - 2026-02-17

### Fixed
- wc-sales-report: PHP timeout on stores with large order count — replaced per-order iteration with SQL aggregation (#2)
- wc-revenue-stats: PHP timeout on stores with large order count — replaced per-order iteration with SQL aggregation (#3)
- wc-top-sellers: Returns 0 results when HPOS is enabled — added HPOS-compatible SQL query (#4)
- wc-low-stock-products: Output validation fails when product price is null — cast to string (#5)
- wc-list-orders: date_after and date_before filters can now be used together — WooCommerce range syntax (#6)

### Added
- ReportAggregator service class for SQL-based report aggregation with HPOS + legacy support

## [1.1.5] - 2026-01-26

### Fixed
- SVG upload support via Abilities API when SVG plugins (Allow SVG, Safe SVG) are active
- Media upload now properly passes MIME type to WordPress sideload filters

### Added
- `get_mime_type_for_file()` helper method that respects third-party plugin MIME type filters

## [1.1.14]

### Fixed
- Minor fix

## [1.1.13]

### Changed
- Updated ParentPage with SVG icon support from registry

## [1.1.12]

### Fixed
- Minor fix

## [1.1.11]

### Fixed
- Minor fix

## [1.1.10]

### Fixed
- Admin notice isolation for notices relocated by WordPress core JS

## [1.1.9]

### Changed
- Isolate third-party admin notices on LW plugin pages

## [1.1.8]

### Added
- Fresh POT file and Hungarian (hu_HU) translation

## [1.1.7]

### Added
- Central plugin registry from GitHub JSON

## [1.1.6]

### Added
- `featured_image_id` field to `list-posts` response (fixes #1)
- PHPUnit testing infrastructure with unit and integration tests
- Docker devcontainer for testing environment

### Fixed
- Deprecated `finfo_close()` call for PHP 8.1+ compatibility

### Removed
- Self-updater functionality (unnecessary for GitHub releases)

## [1.1.4] - 2026-01-26

### Added
- Custom taxonomy support in category and tag abilities via `taxonomy` parameter
- list-categories, get-category, create-category, update-category, delete-category now support any taxonomy
- list-tags, get-tag, create-tag, update-tag, delete-tag now support any taxonomy

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
