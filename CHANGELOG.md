# Changelog

## [1.3.0] - 2026-07-09

### Changed
- The built-in MCP server is now **enabled by default** (previously disabled). Connecting still requires an administrator Application Password (the `manage_options` transport gate is unchanged), and the server remains domain-locked. Disable it any time under **LW Plugins → AI / MCP**. The enable state is stored as `'1'` / `'0'` with an absent option meaning "on", so an explicit toggle-off always persists (a boolean `false` would have been a no-op over the new default) and only that `'0'` or a domain change turns the server off.
- Default-on sites lazily record their site host on the first `is_enabled()` check, so a cloned or migrated database on a different host still auto-disables the server (preserving the clone-protection that previously depended on an explicit enable).

## [1.2.1] - 2026-07-02

### Fixed
- WooCommerce abilities returned a raw `false` from `wp_get_attachment_url()` for items without a (valid) featured image, which violated the `string|null` `image` output schema and failed the entire ability with `ability_invalid_output` (surfaced over MCP as a generic "An error occurred"). Reproduced with `wc-top-sellers` when a top product had no featured image. Added `AbstractService::attachmentUrlOrNull()` and applied it to every WooCommerce `image` output (top-sellers, low-stock, product-categories, order line-items, and product/variation listings) so a missing or dangling image normalizes to `null`.

## [1.2.0] - 2026-06-03

### Added
- Built-in MCP server (`/wp-json/mcp/lw-site-manager`) via the WordPress MCP Adapter; disabled by default, admin-gated, domain-locked.
- Bundled static Skills library and `site-manager/skill-get` ability; skill catalog injected into MCP discovery; optional native MCP prompt mode.
- "AI / MCP" admin settings page (toggle, `.mcp.json` snippet, bundled-skill list).

### Fixed
- `Admin\ParentPage` / `Admin\NoticeManager` were never autoloaded (namespaced outside `src/`); relocated to `src/Admin/` and wired to `admin_menu`, so Site Manager now appears in the shared "LW Plugins" menu.

## [1.1.28] - 2026-05-27

### Fixed
- `site-manager/get-user` and `site-manager/list-users` output schema (`getUserSchema()`) now matches the actual `format_user()` response. Removed `role` (singular — never emitted; only the `roles` array is returned) and `avatar_url` (the emitted key is `avatar`); added the missing detailed-response fields `bio`, `posts_count`, `last_login` (`string|null`), and `capabilities`.
- `posts_count` in the detailed user response is now cast to an integer. `count_user_posts()` returns a numeric string, which contradicted the `integer` type declared in the schema.

## [1.1.27] - 2026-05-13

### Added
- Detailed post responses (`site-manager/get-post`, etc.) now include a `meta` map of custom post fields. WordPress internals (`_edit_lock`, `_edit_last`, `_thumbnail_id`) are filtered out; single-value keys are unwrapped and serialized values are auto-unserialized. Lets MCP / AI / REST clients read CPT-meta-heavy content (form submissions, ACF entries, custom directory CPTs) instead of receiving only title + status. Issue #16.
- `lw_site_manager/post_data` filter — shape the full post payload before it leaves the server (add fields, redact meta, per-CPT customization). 3 args: `$data`, `$post`, `$detailed`.
- `lw_site_manager/post_meta` filter — narrow the meta map (allowlist, redact secrets) for a given post. 2 args: `$meta`, `$post_id`.

## [1.1.26] - 2026-05-08

### Added

- **Global attribute taxonomies** — full CRUD for the `pa_*` attribute taxonomies (`wc-list-attributes`, `wc-get-attribute`, `wc-create-attribute`, `wc-update-attribute`, `wc-delete-attribute`). Create/update/delete invalidate the WC attribute cache (`wc_attribute_taxonomies` transient + `woocommerce-attributes` cache group). Resolution accepts either id or slug (with or without `pa_` prefix).
- **Attribute terms** — manage the values inside each taxonomy (`wc-list-attribute-terms`, `wc-create-attribute-term`, `wc-update-attribute-term`, `wc-delete-attribute-term`). List supports search, pagination (`limit`/`offset`) and `hide_empty`.
- **Product-attribute bindings** — bind attributes to a single product (`wc-set-product-attributes` for full replace, `wc-add-product-attribute` for incremental, `wc-remove-product-attribute` to detach). Supports both **global** (`pa_*`, term-based) and **custom** (free-form string) attributes; for global, the `options` field accepts term IDs, slugs, or names interchangeably and resolves them to IDs.
- **Product variations** — full CRUD (`wc-create-variation`, `wc-update-variation`, `wc-delete-variation`) plus combinatorial auto-generation (`wc-generate-variations`) that builds the cartesian product of all variation-flagged attributes, skips already-existing combinations (md5 combo-key dedup), and syncs the parent via `WC_Product_Variable::sync()`. The pre-existing `wc-list-variations` ability now has its full sibling set.
- New service classes: `AttributeManager`, `ProductAttributeManager`, `ProductVariationManager`.
- New ability category `wc-attributes` registered in `lw-site-manager.php` alongside the existing WooCommerce categories.

#### WooCommerce meta data

- **Standalone meta abilities** for products and orders, all HPOS-aware via `WC_Data` API: `wc-get-product-meta`, `wc-set-product-meta`, `wc-delete-product-meta`, `wc-get-order-meta`, `wc-set-order-meta`, `wc-delete-order-meta`. Get supports a single `key` lookup or full listing with `include_protected` flag (defaults to false to hide leading-underscore meta).
- **Inline `meta` field** added to `wc-create-order` and `wc-update-order` schemas + service handlers — a `{ key: value }` map applied via `WC_Order::update_meta_data()` before save.
- **Inline `meta` field** added to `wc-create-variation` and `wc-update-variation` schemas + service handlers, processed via `WC_Product_Variation::update_meta_data()` to stay HPOS-future-proof.
- New service class `WcMetaManager` providing the shared get/set/delete logic for any `WC_Data` entity.

#### Core meta coverage filled in

- **Comment meta** — three new abilities (`site-manager/get-comment-meta`, `site-manager/set-comment-meta`, `site-manager/delete-comment-meta`) and matching `MetaManager` methods. Comments now have the same standalone meta surface as posts, users, and terms.
- **Inline `meta` on user create/update** — `site-manager/create-user` and `site-manager/update-user` schemas + `UserManager` services now accept a `meta` map and apply it via `update_user_meta()`.
- **Inline `meta` on taxonomy term create/update** — applies to `site-manager/create-category`, `site-manager/update-category`, `site-manager/create-tag`, `site-manager/update-tag`; `TaxonomyManager::create_term` / `update_term` use `update_term_meta()`.
- **Inline `meta` on comment create/update** — `site-manager/create-comment` and `site-manager/update-comment` schemas + `CommentManager` services apply the map via `update_comment_meta()`.
- **Inline `meta` on `wc-update-order-item`** — symmetry with `wc-add-order-item` (`WC_Order_Item_Product::update_meta_data()`).

### Changed

- `WooCommerceAbilities` coordinator now wires up the new ability registrars (`AttributeAbilities`, `AttributeTermAbilities`, `ProductAttributeAbilities`, `VariationAbilities`, `WcMetaAbilities`).
- `ProductManager::create_product` and `update_product` now use `WC_Product::update_meta_data() + save_meta_data()` instead of raw `update_post_meta()`, so the same `meta` payload works the same way across product types and remains correct under future HPOS-style storage.

## [1.1.25] - 2026-05-06

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
