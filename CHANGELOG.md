# Changelog

## [1.4.3] - 2026-08-25

### Changed
- The MCP Adapter bundled in the release ZIP moves from v0.5.0 to v0.6.1 (`wordpress/mcp-adapter` in `require-dev` and in the release workflow's bundle step). Verified against every adapter hook this plugin uses — `mcp_adapter_default_server_config`, `mcp_adapter_default_transport_permission_user_capability`, `mcp_adapter_tool_call_result`, `mcp_adapter_init` and the `mcp-adapter/discover-abilities` ability all still exist, and `McpNameSanitizer` still turns `mcp-adapter/execute-ability` into the `mcp-adapter-execute-ability` tool name `Mcp\ResultUnwrapper` matches on.
- `composer.json` allows the `automattic/jetpack-autoloader` Composer plugin. Adapter 0.6.0 made it a runtime dependency; without the entry `composer install` aborts, which is what broke CI on the dependency-update PR. The adapter uses it so the newest `WP\MCP` classes win when several plugins bundle their own copy — the same class of problem the outdated-adapter notice added in 1.4.2 reports.

### Notes
- **Upstream behaviour changes inherited from adapter 0.6.0.** Abilities registered by *other* plugins with `meta.public: true` are now exposed through the adapter's default MCP server unless they set `meta.mcp.public` to false. This plugin's own abilities are unaffected: `Mcp\AbilityExposer` has always set `meta.mcp.public` explicitly on every `site-manager/*` ability, and the transport still enforces the capability from `Mcp\TransportGuard`.
- On multisite only, active Streamable HTTP sessions must reconnect once after the update: adapter 0.6.0 moved session storage from a network-wide key to per-site keys. Single-site installs are unaffected.
- The adapter's own minimum is WordPress 6.9, which this plugin already required — no change to the supported floor.
- The release ZIP was rebuilt and checked: it ships only `automattic/`, `composer/` and `wordpress/` under `vendor/`, and all 345 classmap entries resolve inside the artifact. The 0.6.0 release-ZIP defect that mapped `WP_CLI` to an omitted test file is fixed in 0.6.1 and never applied to this build, which installs the adapter through Composer.

## [1.4.2] - 2026-08-24

### Fixed
- **Failed operations were reported as successes.** `activate-plugin`, `install-plugin` and `install-theme` returned `[ 'success' => false, ... ]` on a hard failure. Over REST that is an HTTP 200; over MCP the adapter wraps it as `{ success: true, data: { success: false } }`, so an agent was told a plugin had been installed when it had not. They now return `WP_Error`, which surfaces as a real error in both transports. The PHP errors captured during the attempt are preserved in the error data rather than dropped. Verified live: the REST call now answers `500 activation_failed` with the real message, and the MCP tool call reports `isError: true`.
- The WooCommerce report totals (`wc-orders-totals`, `wc-customers-totals`, `wc-products-totals`, `wc-coupons-totals`) returned `success: false` with an empty payload when WooCommerce was inactive, making "no store" look like "no data". They now return the same `woocommerce_not_active` error every other WooCommerce service already used.

### Added
- An admin notice when the MCP adapter that actually loaded is older than the one this plugin targets (`Mcp\AdapterVersion`). WooCommerce bundles `wordpress/mcp-adapter` v0.3.0 in its own vendor directory and requires it eagerly, which beats Composer's lazy PSR-4 loading — so on a WooCommerce store this plugin has always run against 0.3.0 even when its lockfile pins 0.5.0. That copy has no `mcp_adapter_tool_call_result` filter, so `Mcp\ResultUnwrapper` never ran. Until now this degraded in complete silence.

  Detection deliberately does **not** use `class_exists()`. Our PSR-4 autoloader still resolves classes the older copy lacks from our own vendor, so the runtime is a mixture — a 0.3.0 core with newer classes filled in behind it — and `class_exists( '\WP\MCP\Core\McpVersionNegotiator' )` returns `true` while 0.3.0 is running. Confirmed on a live store. The check instead resolves, by reflection, which installation the loaded `McpAdapter` came from and inspects that tree.

### Notes
- **Behaviour change.** A caller that previously received `200 { success: false }` from these three abilities now receives an error status. That is the point — but any integration branching on the body rather than the status needs to handle it.
- Batch abilities (`bulk-posts`, `update-all-plugin-dbs`, and friends) were deliberately left alone: a partial result carrying per-item detail is a real answer, not a failure. Verified live that they still return a normal result.
- `activate-plugin` also has a "activated, but PHP errors were emitted" outcome. That is deliberately still a success payload: the plugin *is* active, and turning it into an error would invite an agent to retry an action that already took effect.
- The `wordpress/mcp-adapter` Composer `suggest` text now warns that on a WooCommerce store the version you install may not be the version that runs.
## [1.4.1] - 2026-08-10

### Added
- WooCommerce product reviews now report `rating` (1-5, `null` when unset) and `verified` (verified purchaser). Both live in comment meta, so previously a review came back as plain text and an agent asked to moderate reviews could not see what it was moderating. The fields appear only on `type: "review"` rows.

### Changed
- **`list-comments` now defaults to `type: 'comment'`.** Whether product reviews appeared in the default listing was previously left to chance. WooCommerce hides reviews from comment queries via `comments_clauses`, but that filter bails out whenever any of a dozen query vars is set — `type__not_in` among them, which LearnDash populates with `ld_review`. The identical call therefore returned reviews on one site and not on another. WordPress maps `type => 'comment'` to `comment_type IN ( '', 'comment' )`, so legacy comments stored with an empty type are still returned. Pass `type=review` for reviews, or `type=all` to opt back into everything.
- **`comment-counts` counts comments and product reviews separately** instead of summing them, and no longer relies on `wp_count_comments()` — WooCommerce filters that through `get_comments()`, inheriting the same environment-dependence. Reviews now appear under a `reviews` key with the same status breakdown. Counting is in the new `Services\Comments\CommentCounter`.
- Tested up to WordPress 7.1.

### Fixed
- The comment abilities' declared output schema did not match what they return: it advertised `author_name` and `avatar_url` where the fields are `author` and `avatar`, and omitted `agent`, `replies_count`, `rating` and `verified` entirely. The `comment-counts` schema listed `pending` and `total_moderated`, which the ability never returned, while omitting `awaiting` and `post_trashed`, which it did. Schemas are how an agent learns which fields exist, so the drift actively misled callers.
- Local registry fallback pointed the overview "Settings" button at `admin.php?page=lw-site-manager`, which is not a registered page (403). It now links to `lw-site-manager-mcp` (same fix in `lwplugins/registry`).

### Notes
- WooCommerce **order notes** are unaffected: they are `comment_type = 'order_note'`, which WooCommerce excludes from every comment query unconditionally, and the plugin already handles them through the dedicated `wc-list-order-notes` / `wc-add-order-note` abilities.
- Verified end-to-end on a live WooCommerce 11.0 store: with a review present, the default `list-comments` no longer returns it, `type=review` returns it with `rating: 4` and `verified: true`, a plain comment carries no rating fields, and `comment-counts` reports the two groups separately.

## [1.4.0] - 2026-08-07

### Security

A security audit of the whole plugin found 9 HIGH and 1 MEDIUM issue, all from one architectural defect: authorization was expressed entirely as a registration-time **primitive** capability check, and `src/Services/` contained zero `current_user_can()` calls. A primitive capability means "may edit posts in general", never "may edit THIS post", so any ability gated one notch too low granted unrestricted access to every object of that type — and WordPress's and WooCommerce's own protections, which live in the meta-capability layer (`map_meta_cap`, `wc_modify_map_meta_cap`, `get_editable_roles()`), never ran. Verified against core: `wp_update_post()`, `wp_delete_post()`, `wp_delete_attachment()`, `update_post_meta()`, `wp_set_password()` and `WP_User::set_role()` perform no capability check of their own.

Object-level checks now run in the service layer, where the target ID is known. New focused helpers hold the policy: `Helpers\Capability`, `Helpers\ProtectedMeta` and `Services\Meta\MetaGuard`.

- **Posts** — `update-post`, `delete-post` and `bulk-posts` had no per-object check, so a Contributor could rewrite, publish and reassign any post of any type (pages and products included) and permanently delete arbitrary posts site-wide via `wp_delete_post( $id, true )`. Each bulk item is now authorized individually.
- **WooCommerce orders** — all 14 order-scoped abilities were gated on `can_edit_posts` (Contributor), exposing every customer's name, email, phone and address, and allowing order deletion and refund creation. They now use `can_manage_orders`, which already existed and was already used by the sibling order abilities. Product meta abilities move to a new `can_manage_products`.
- **`reset-password`** — reset any account including the administrator, silently when `send_notification:false`, and returned the new plaintext password. Now requires `edit_user` on the target and refuses super-admin targets.
- **`update-user`** — validated the role against every registered role rather than `get_editable_roles()`, letting a delegated user manager grant itself `administrator`. Role changes now require `promote_user` on the target, and the role is validated before anything is written so a rejected role cannot leave the account half-updated.
- **`wc-get-order-meta`** — leaked protected order meta two ways: `include_protected` was caller-supplied, and the single-key branch skipped the filter entirely, exposing `_order_key` (the guest order-access token), `_transaction_id` and `_customer_ip_address`.
- **`list-posts` / `get-post`** — `post_status` defaulted to `'any'`, which makes `WP_Query` build only negative status clauses and skip its private/protected capability mapping, returning every author's drafts and private posts. Expanded to an explicit status list plus `perm => 'editable'`; `get-post` now requires `read_post`.
- **Post and comment meta** — no per-object check, and the single-key read branch applied no protected-key filter at all.
- **Media** — `delete-media` / `update-media` acted on any attachment for anyone holding `upload_files`.
- **User meta** — `set-user-meta` could write `{prefix}capabilities`, which *is* the role assignment, bypassing both role validation and `promote_user`. Role, level and session-token keys are now refused for every caller at every capability, on the meta ability and on `update-user`'s inline `meta` map alike, and hidden from reads.
- **`wc-list-order-notes`** — exposed internal staff and payment-gateway notes to a Contributor.
- **`get-post` meta payload** — reading a *published* post is legitimate for anyone with `edit_posts`, but the response returned the post's entire meta map (only `_edit_lock`, `_edit_last` and `_thumbnail_id` were stripped), handing every protected key to any Contributor. Protected keys are now hidden from non-administrators.

### Changed
- `delete-media` no longer defaults to permanent deletion; items go to the trash unless `force: true` is passed. `wp_delete_attachment()` with force also unlinks the original and every generated size.
- `list-posts` no longer defaults to every post status. Callers that legitimately need other authors' drafts must hold the corresponding capability.

### Fixed
- `bulk-posts` (and `bulk-comments`) returned HTTP 500 `ability_invalid_output` whenever any item failed: the output schema declares `failed_ids` as an array of integers, but the loop pushed objects. Pre-existing — previously reachable only when a post did not exist — but per-item authorization makes a failed item routine for lower-privileged callers. `failed_ids` now carries integers as declared.
- `delete-theme` passed the caller's slug straight to core with no `validate_file()` and no allowlist. `WP_Theme::exists()` is not an allowlist — it fails only on `theme_not_found` — so `../plugins` resolved to an existing directory and core's `delete_theme()` recursively deleted it. Administrator-gated, so not a privilege boundary crossing, but the ability is AI-agent-facing: a hallucinated slug could destroy an unrelated directory. Now allowlisted against `wp_get_themes()`, mirroring `delete_plugin()`.

### Notes
- **Behaviour change.** Lower-privileged roles lose access they previously (incorrectly) had. Integrations running as Contributor, Author or WooCommerce shop manager may now receive `403 forbidden` where they previously succeeded — that is the fix working. Administrators are unaffected.
- Verified end-to-end against a live WooCommerce 10.8/11.0 store with HPOS, using real Contributor, Author and shop_manager accounts with application passwords: every documented attack now returns 403 (or is skipped per item), and an administrator's access is unchanged.
- Four new test suites (54 cases) cover every finding, red before the fix and green after. The `current_user_can` and `wp_register_ability` stubs are now controllable so both the allowed and denied branch are exercised.

## [1.3.3] - 2026-08-07

### Changed
- `wordpress/mcp-adapter` moved from `require` to `require-dev` + `suggest`, so `composer require lwplugins/lw-site-manager` no longer pulls it into consumer projects. Since mcp-adapter v0.5 the package is `type: wordpress-plugin`, so on Composer/Bedrock sites `composer/installers` was dropping it into `web/app/plugins/` as an unexpected standalone "MCP Adapter" plugin. The WordPress.org release ZIP still bundles the adapter — it is injected during the release workflow (`composer require … --no-dev` after the production install) — so the built-in MCP server keeps working out of the box for ZIP installs. Composer/Bedrock users who want the MCP server install the adapter (or the canonical MCP Adapter plugin) themselves. `Mcp\Bootstrap` already no-ops when the adapter class is absent, so the REST / Abilities API surface is unaffected.

## [1.3.2] - 2026-07-26

### Fixed
- `wc-list-orders` threw a fatal `TypeError` (HTTP 500, surfaced over MCP as a generic error) whenever a refund fell in the queried date range, making the ability unusable on any store that issues refunds. `list_orders()` built the `wc_get_orders()` query without a `type`, so under HPOS the result also contained `WC_Order_Refund` objects, which `format_order()` (typed `\WC_Order`) rejected on the first one. The query is now restricted to `'type' => 'shop_order'`, which excludes refunds and keeps `total` / `max_num_pages` honest (a per-row `instanceof` guard would not). Reproduced end-to-end on WooCommerce 10.8.1 with HPOS. (#21)

## [1.3.1] - 2026-07-24

### Fixed
- `upload-media` failed with `cURL error 60: SSL certificate problem` whenever the source URL was served with a self-signed or otherwise untrusted certificate, with no way to opt out — even when the URL host was the site's own, i.e. the site downloading a file from itself. `download_url()` exposes no `sslverify` parameter, so the setting is now injected through a narrowly scoped `http_request_args` filter, and verification is skipped for same-host URLs where no man-in-the-middle position exists. (#18)

### Added
- A same-host URL pointing inside the uploads directory is now read straight from disk instead of being fetched over HTTP. This removes the loopback round-trip entirely and makes such uploads immune to TLS, firewall and rate-limit interference. The file is copied (not moved) because `media_handle_sideload()` consumes the file it is given. Traversal is rejected: the URL path is decoded and lexically normalized, then required to stay inside the uploads directory, so `..` and `%2e%2e` cannot escape.
- Optional `verify_ssl` input on `upload-media` for explicit control. Defaults to `true` for external hosts and `false` for same-host URLs; documented as safe to disable for other hosts only in trusted environments such as staging.

### Changed
- Source resolution moved out of `MediaManager` into a focused `Services\Media\MediaFetcher`, split into a pure decision layer (`plan()`, unit-tested) and a thin I/O layer. The two upload paths (URL and base64) now share one `sideload_temp_file()` helper, removing the duplicated sideload/cleanup block and bringing `MediaManager` back under the 400-line limit.

## [1.3.0] - 2026-07-18

### Changed
- The built-in MCP server is now **enabled by default** (previously disabled). Connecting still requires an administrator Application Password (the `manage_options` transport gate is unchanged), and the server remains domain-locked. Disable it any time under **LW Plugins → AI / MCP**. The enable state is stored as `'1'` / `'0'` with an absent option meaning "on", so an explicit toggle-off always persists (a boolean `false` would have been a no-op over the new default) and only that `'0'` or a domain change turns the server off.
- Default-on sites lazily record their site host on the first `is_enabled()` check, so a cloned or migrated database on a different host still auto-disables the server (preserving the clone-protection that previously depended on an explicit enable).

### Fixed
- The `create-page` and `update-page` abilities honour the `template` input again: they now set `_wp_page_template` instead of silently dropping it (which forced a separate `set-page-template` call). (#17)

### Added
- PHPStan level 5 static analysis in CI (empty baseline), and the unit-test matrix widened to PHP 8.2–8.5.

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
