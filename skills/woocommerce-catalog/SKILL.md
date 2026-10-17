---
name: woocommerce-catalog
description: Build and maintain a WooCommerce catalog and store setup via LW Site Manager — simple and variable products, variations and the default variation, global attributes, product categories, tags, brands and shipping classes, plus store settings (address, currency, units, tax, selling locations), shipping zones and payment methods. Use when the user asks to add, edit, price, restock, categorize or clean up products, set up sizes/colors, configure where and how the shop sells, or check whether the store is ready.
enable_prompt: false
enable_agentic: true
---

# WooCommerce catalog and store setup

Every ability below is `site-manager/<name>`. The catalog is products (post type `product`), their variations (`product_variation`) and the taxonomies that group them. Orders, refunds and sales reports belong to the `woocommerce-order-ops` skill.

## First look at the shop

- `wc-check-setup` — WooCommerce and database version, currency, base country, store pages, enabled payment methods, shipping zones, HPOS, detected extensions (subscriptions, bookings, Stripe …) and an `issues` list of what blocks selling. Start here.
- `wc-get-store-settings` — address, selling/shipping locations, currency and price format, weight/dimension units, tax options. `include_fields: true` adds each key's type, allowed options and whether it is risky. Read the currency and units before writing prices, weights or dimensions; never guess them.
- `wc-products-totals`, `wc-list-products` (`status`, `type`, `category`, `stock_status`, `featured`, `on_sale`, `search`, `limit` ≤ 100, `offset`), `wc-get-product` (`id`, `sku` or `slug`), `wc-low-stock-products`, `wc-top-sellers`.
- Treat product types you don't recognise (from extensions) as opaque: read them, don't restructure them.

## Simple products

`wc-create-product` needs only `name`; pass `status: "draft"` unless the user wants it live. Other fields: `type`, `slug`, `description`, `short_description`, `regular_price`, `sale_price`, `sku`, `manage_stock`, `stock_quantity`, `stock_status`, `backorders`, `weight`, `length`, `width`, `height`, `tax_status`, `tax_class`, `virtual`, `downloadable`, `featured`, `catalog_visibility`, `categories` / `tags` (term ID arrays), `image_id`, `gallery_image_ids`, `menu_order`, `meta`. `wc-update-product` takes `id` plus only what changes.

- Prices are strings (`"19.90"`); an empty `sale_price` means no sale. Match the store's decimals.
- SKUs are unique across products and variations; look one up with `wc-get-product` `sku` first.
- Images are media-library IDs: `upload-media` first, then pass the ID.
- Quick stock changes: `wc-update-stock` (`quantity`, `adjust` or `stock_status`); report old and new values.
- The product type can't change in place: create the new type, move the data, retire the old one with the user's agreement.
- Another plugin may rewrite title, slug or status on save — compare what you sent with what came back.

## Categories, tags, brands, shipping classes

One set of abilities covers all four product taxonomies; pick with `taxonomy`: `product_cat`, `product_tag`, `product_brand`, `product_shipping_class`.

- `wc-list-product-terms` (`taxonomy`, `search`, `parent`, `hide_empty`, `orderby`: name/slug/count/id/menu_order, `order`, `limit` ≤ 200, `offset`) and `wc-get-product-term` (`taxonomy`, `id`).
- `wc-create-product-term` (`taxonomy`, `name`, `slug`, `description`) and `wc-update-product-term` (`taxonomy`, `id`, only the fields that change). Extras, stored where WooCommerce keeps them:
  - `parent` — categories and brands (hierarchical). A term can't move under its own descendant.
  - `image_id` — categories and brands; a media-library image, `0` clears it.
  - `display_type` — categories: `""` (shop default), `products`, `subcategories`, `both`.
  - `menu_order` — categories: the drag-and-drop sort position.
  Sending an extra a taxonomy doesn't support is an error, not a silent no-op.
- `wc-delete-product-term` (`taxonomy`, `id`, `confirm_delete: true`) deletes **permanently** (terms have no trash). Without `confirm_delete` it answers 409 with the product count — show that to the user first.
- Assign categories and tags with `categories` / `tags` on `wc-update-product` (they replace the set; an empty array still leaves the default category; omit to keep).
- Assign brands with `wc-set-product-brands` (`product_id`, `brand_ids`, `append`; an empty list removes all). Brands belong to the parent product, not variations.
- Assign a shipping class with `wc-set-product-shipping-class` (`product_id` — a product or a variation —, `shipping_class_id`, `0` clears; a variation without one uses its parent's).

Warn before deleting:

- The default category (`is_default: true` in the term output; it may have been renamed) can't be deleted.
- Deleting a category moves its direct children up a level; products left without a category fall back to the default one.
- Deleting a shipping class removes it from every product that used it — shipping costs tied to it stop applying.
- A duplicate name under the same parent is refused (409). A clashing slug is silently suffixed (`-2`); read back the slug you got.
- Term `count` covers published products only.

## Global attributes and terms

A global attribute (Color) is a store-wide taxonomy `pa_<slug>`; its terms are the values (Red, Blue).

- `wc-list-attributes`, `wc-get-attribute` (`id` or `slug` without `pa_`), `wc-create-attribute` (`name`, `slug`, `type` select/text, `order_by`, `has_archives`), `wc-update-attribute`.
- `wc-list-attribute-terms`, `wc-create-attribute-term`, `wc-update-attribute-term`, `wc-delete-attribute-term` (attribute `id` or `slug`, plus the term's `term_id`).
- `wc-delete-attribute` permanently deletes the attribute **and all its terms**; products keep broken attribute entries. Deleting a term that variations use leaves those variations unselectable. Check usage and confirm first.

## Attaching attributes to a product

- `wc-add-product-attribute` (`product_id`, `name` — `pa_color` for global, any label for product-only —, `options`, `position`, `visible`, `variation`).
- `wc-set-product-attributes` replaces the whole set; `wc-remove-product-attribute` removes one.
- Global `options` may be term IDs, slugs or names; values matching no term are dropped, so create terms first and check the returned `attributes`.

## Variable products and variations

1. `wc-create-product` with `type: "variable"`, no price or stock on the parent.
2. Attach at least one attribute with `variation: true`.
3. `wc-generate-variations` (`product_id`, `default_price`) creates every missing combination — count first and confirm large numbers — or `wc-create-variation` (`product_id`, `attributes`, plus price/stock/SKU/dimension fields).
4. Price and stock each with `wc-update-variation`; set `regular_price` and `sale_price` together.

- The variation `attributes` map holds one value per axis, e.g. `{"pa_color": "red", "pa_size": "m"}`: global values are term slugs, product-only ones the option text. On update it replaces the whole combination. Don't set the variation name.
- `wc-list-variations` (`product_id`); `wc-delete-variation` (`id`, `force` + `confirm_delete: true` for a permanent delete) trashes by default.
- **Default (preselected) variation:** `wc-set-default-variation` with `product_id` and one of: `attributes` (`{"pa_color": "red", "Size": "M"}` — an axis left out means "any"), `variation_id` (copy that variation's combination), or `clear: true`. Values must be options of the product's variation attributes. There is only one default; setting it replaces the old one.

## Store settings

`wc-update-store-settings` takes `settings` with the keys `wc-get-store-settings` returns: `store_address`, `store_address_2`, `store_city`, `store_postcode`, `default_country` (`"HU"` or `"US:CA"`), `allowed_countries` (all/all_except/specific), `all_except_countries`, `specific_allowed_countries`, `ship_to_countries`, `specific_ship_to_countries`, `default_customer_address`, `calc_taxes`, `enable_coupons`, `calc_discounts_sequentially`, `currency`, `currency_pos`, `price_thousand_sep`, `price_decimal_sep`, `price_num_decimals`, `weight_unit`, `dimension_unit`, `prices_include_tax`, `tax_based_on`, `shipping_tax_class`, `tax_round_at_subtotal`, `tax_display_shop`, `tax_display_cart`, `price_display_suffix`, `tax_total_display`.

- Checkboxes take `true`/`false`; country lists take arrays of ISO codes; selects must be one of the options — an invalid value is refused, never replaced by a default.
- Currency, decimals, tax calculation, units, base country and selling/shipping locations are **risky**: existing prices, weights and dimensions are not converted. Without `confirm_change: true` the call saves nothing and returns 409 with a `changes` preview (`from` → `to`). Show it to the user, then resend with `confirm_change: true`.
- Only the keys that actually change are saved and returned.

## Payment methods

- `wc-list-payment-gateways` — id, titles, `enabled`, `needs_setup`. Credentials are never returned; configure gateways (API keys, account details) in WooCommerce → Settings → Payments.
- `wc-set-payment-gateway-enabled` (`id`, `enabled`). A gateway with `needs_setup: true` can't be enabled. Turning off the last enabled method needs `confirm_change: true` — customers could no longer pay.

## Shipping zones and methods

- `wc-list-shipping-zones`, `wc-get-shipping-zone` (`id`; `0` is "rest of the world"). Zones are matched by `order`, lowest first.
- `wc-create-shipping-zone` / `wc-update-shipping-zone` (`name`, `order`, `locations`: `[{ "code": "HU", "type": "country" }]`, types country/state (`"US:CA"`)/continent (`"EU"`)/postcode (`"1*"`, `"1000...1999"`)). `locations` replaces all of the zone's locations. Zone 0 has no name or locations of its own.
- `wc-delete-shipping-zone` (`id`, `confirm_delete: true`) deletes the zone and its methods permanently.
- `wc-list-shipping-method-types` lists what can be added (`flat_rate`, `free_shipping`, `local_pickup`, extension methods) with their setting fields.
- `wc-add-shipping-zone-method` (`zone_id`, `method_id`, optional `settings` such as `{"title": "Courier", "cost": "1990"}`, `enabled`).
- `wc-update-shipping-zone-method` (`zone_id`, `instance_id`, `settings` — only the fields sent change —, `enabled`, `order`). WooCommerce validates each setting. Credential-like values come back masked as `********`; sending the mask back leaves the stored value untouched.
- `wc-delete-shipping-zone-method` (`zone_id`, `instance_id`, `confirm_delete: true`).

## Bulk, duplicates, deletion

- `wc-duplicate-product` (`id`, `new_name`, `status` default `draft`).
- `wc-bulk-products` (`ids`, `action`: publish/draft/trash/delete/restore). `delete` (and `trash` on a site without trash) needs `confirm_delete: true`; IDs you may not change, and variations, come back in `failed_ids`.
- `wc-delete-product` (`id`, `force`, `confirm_delete`): trash by default; a permanent delete needs `force` plus `confirm_delete: true` and can't be undone. WooCommerce deletes a variable product's variations with it. Variation IDs are refused here — use `wc-delete-variation`.
- Product meta: `wc-get-product-meta`, `wc-set-product-meta`, `wc-delete-product-meta` (also on variations). Prefer the typed fields over underscore-prefixed WooCommerce internals.

## Working rules

- Confirm any delete, bulk action, price change across many products, risky setting change, or generation of more than a handful of variations. Echo the IDs and the exact change.
- When an ability answers 409 `confirm_delete_required` / `confirm_change_required`, show the user what it reported and resend with the flag only after they agree.
- Create products as `draft` unless the user wants them live.
- After writing, read back (`wc-get-product`, `wc-list-variations`, `wc-get-product-term`, `wc-get-store-settings`, `wc-get-shipping-zone`) and report what is stored.
