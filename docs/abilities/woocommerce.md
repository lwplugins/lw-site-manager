# WooCommerce Abilities

WooCommerce abilities are only available when the WooCommerce plugin is active.

## Table of Contents

- [Products](#products)
  - [wc-list-products](#wc-list-products)
  - [wc-get-product](#wc-get-product)
  - [wc-create-product](#wc-create-product)
  - [wc-update-product](#wc-update-product)
  - [wc-delete-product](#wc-delete-product)
  - [wc-duplicate-product](#wc-duplicate-product)
  - [wc-update-stock](#wc-update-stock)
  - [wc-list-product-categories](#wc-list-product-categories)
  - [wc-list-variations](#wc-list-variations)
  - [wc-bulk-products](#wc-bulk-products)
- [Orders](#orders)
  - [wc-list-orders](#wc-list-orders)
  - [wc-get-order](#wc-get-order)
  - [wc-update-order-status](#wc-update-order-status)
  - [wc-list-order-statuses](#wc-list-order-statuses)
  - [wc-create-refund](#wc-create-refund)
  - [wc-list-order-notes](#wc-list-order-notes)
  - [wc-add-order-note](#wc-add-order-note)
  - [wc-bulk-orders](#wc-bulk-orders)
- [Reports](#reports)
  - [wc-sales-report](#wc-sales-report)
  - [wc-top-sellers](#wc-top-sellers)
  - [wc-orders-totals](#wc-orders-totals)
  - [wc-revenue-stats](#wc-revenue-stats)
  - [wc-low-stock-products](#wc-low-stock-products)
  - [wc-products-totals](#wc-products-totals)

---

# Products

## wc-list-products

List products with filtering and pagination options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-products/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `status` | string | no | `any` | Status: `publish`, `draft`, `pending`, `private`, `trash`, `any` |
| `type` | string | no | - | Type: `simple`, `variable`, `grouped`, `external` |
| `category` | string | no | - | Category slug or ID |
| `stock_status` | string | no | - | Stock: `instock`, `outofstock`, `onbackorder` |
| `featured` | boolean | no | - | Featured products |
| `on_sale` | boolean | no | - | Products on sale |
| `search` | string | no | - | Search in name |
| `limit` | integer | no | `20` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `orderby` | string | no | `date` | Sort field |
| `order` | string | no | `DESC` | Direction: `ASC`, `DESC` |

### Output

```json
{
  "products": [
    {
      "id": 101,
      "name": "Test Product",
      "slug": "test-product",
      "type": "simple",
      "status": "publish",
      "sku": "",
      "price": "29.99",
      "regular_price": "29.99",
      "sale_price": "",
      "on_sale": false,
      "stock_quantity": null,
      "stock_status": "instock",
      "manage_stock": false,
      "featured": false,
      "virtual": false,
      "downloadable": false,
      "permalink": "https://example.com/product/test-product/",
      "date_created": "2026-01-13 09:35:35",
      "date_modified": "2026-01-13 09:35:35",
      "image": null,
      "categories": [
        {
          "id": 33,
          "name": "Uncategorized",
          "slug": "uncategorized"
        }
      ]
    }
  ],
  "total": 1,
  "total_pages": 1,
  "limit": 20,
  "offset": 0,
  "has_more": false
}
```

### Example

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-products/run'
```

---

## wc-get-product

Get detailed information about a single product.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-get-product/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | - | - | Product ID |
| `sku` | string | - | - | Product SKU |
| `slug` | string | - | - | Product slug |

> **Note:** Either `id`, `sku` or `slug` is required.

### Output

```json
{
  "success": true,
  "product": {
    "id": 101,
    "name": "Test Product",
    "slug": "test-product",
    "type": "simple",
    "status": "publish",
    "sku": "",
    "price": "29.99",
    "regular_price": "29.99",
    "sale_price": "",
    "on_sale": false,
    "stock_quantity": 50,
    "stock_status": "instock",
    "manage_stock": true,
    "featured": false,
    "virtual": false,
    "downloadable": false,
    "permalink": "https://example.com/product/test-product/",
    "date_created": "2026-01-13 09:35:35",
    "date_modified": "2026-01-13 09:35:35",
    "image": null,
    "categories": [...],
    "description": "Full description...",
    "short_description": "Short desc...",
    "weight": "",
    "length": "",
    "width": "",
    "height": "",
    "tax_status": "taxable",
    "tax_class": "",
    "backorders": "no",
    "catalog_visibility": "visible",
    "menu_order": 0,
    "total_sales": 0,
    "gallery": [],
    "tags": [],
    "attributes": [],
    "average_rating": "0",
    "review_count": 0
  }
}
```

---

## wc-create-product

Create a new product.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-product/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `name` | string | **yes** | - | Product name |
| `type` | string | no | `simple` | Type: `simple`, `variable`, `grouped`, `external` |
| `status` | string | no | `publish` | Status |
| `description` | string | no | - | Description |
| `short_description` | string | no | - | Short description |
| `regular_price` | string | no | - | Regular price |
| `sale_price` | string | no | - | Sale price |
| `sku` | string | no | - | SKU |
| `manage_stock` | boolean | no | - | Stock management |
| `stock_quantity` | integer | no | - | Stock quantity |
| `stock_status` | string | no | - | Stock status |
| `featured` | boolean | no | - | Featured |
| `virtual` | boolean | no | - | Virtual product |
| `downloadable` | boolean | no | - | Downloadable |
| `categories` | array | no | - | Array of category IDs |
| `tags` | array | no | - | Array of tag IDs |

### Output

```json
{
  "success": true,
  "message": "Product created successfully",
  "id": 103,
  "product": {
    "id": 103,
    "name": "API Test Product",
    "slug": "api-test-product",
    ...
  }
}
```

### Example

```bash
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-product/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"name": "New Product", "regular_price": "19.99", "status": "publish"}}'
```

---

## wc-update-product

Update an existing product.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-product/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Product ID |
| `name` | string | no | - | New name |
| `status` | string | no | - | New status |
| `regular_price` | string | no | - | New price |
| `sale_price` | string | no | - | New sale price |
| ... | | | | (all fields same as create) |

### Output

```json
{
  "success": true,
  "message": "Product updated successfully",
  "product": {...}
}
```

---

## wc-delete-product

Delete a product.

**Method:** `DELETE`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-delete-product/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Product ID |
| `force` | boolean | no | `false` | Permanent deletion (instead of trash) |

### Output

```json
{
  "success": true,
  "message": "Product moved to trash",
  "id": 103,
  "name": "API Test Product",
  "trashed": true
}
```

---

## wc-duplicate-product

Create a copy of a product.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-duplicate-product/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Original Product ID |
| `new_name` | string | no | - | New product name (if empty: "Copy of...") |
| `status` | string | no | `draft` | New product status |

### Output

```json
{
  "success": true,
  "message": "Product duplicated successfully",
  "id": 104,
  "product": {...}
}
```

---

## wc-update-stock

Quick stock update.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Product ID |
| `quantity` | integer | no | - | New stock quantity (absolute) |
| `adjust` | integer | no | - | Stock adjustment (+/- value) |
| `stock_status` | string | no | - | Stock status: `instock`, `outofstock`, `onbackorder` |

> **Note:** Either `quantity`, `adjust` or `stock_status` must be provided.

### Output

```json
{
  "success": true,
  "message": "Stock updated successfully",
  "id": 101,
  "name": "Test Product",
  "old_quantity": null,
  "new_quantity": 50,
  "old_status": "instock",
  "new_status": "instock"
}
```

### Example

```bash
# Set stock to 100
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 101, "quantity": 100}}'

# Decrease stock by 5
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 101, "adjust": -5}}'
```

---

## wc-list-product-categories

List product categories.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-product-categories/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `hide_empty` | boolean | no | `false` | Hide empty categories |
| `parent` | integer | no | - | Parent category ID |
| `search` | string | no | - | Search in name |
| `limit` | integer | no | `100` | Items to return |
| `offset` | integer | no | `0` | Items to skip |
| `orderby` | string | no | `name` | Sort field |
| `order` | string | no | `ASC` | Direction |

### Output

```json
{
  "categories": [
    {
      "id": 33,
      "name": "Uncategorized",
      "slug": "uncategorized",
      "description": "",
      "parent": 0,
      "count": 2,
      "image": null
    }
  ],
  "total": 1,
  "limit": 100,
  "offset": 0
}
```

---

## wc-list-variations

List variations of a variable product.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-variations/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `product_id` | integer | **yes** | - | Variable product ID |

### Output

```json
{
  "product_id": 50,
  "product_name": "Variable Product",
  "variations": [
    {
      "id": 51,
      "sku": "VAR-001",
      "price": "29.99",
      "regular_price": "29.99",
      "sale_price": "",
      "stock_quantity": 10,
      "stock_status": "instock",
      "attributes": {
        "pa_color": "Red",
        "pa_size": "Large"
      },
      "image": "https://example.com/...",
      "status": "publish"
    }
  ],
  "total": 3
}
```

---

## wc-bulk-products

Bulk operations on multiple products.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-bulk-products/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `ids` | array | **yes** | - | Array of product IDs |
| `action` | string | **yes** | - | Action: `publish`, `draft`, `trash`, `delete`, `restore` |

### Output

```json
{
  "success": true,
  "message": "Bulk action completed: 3 succeeded, 0 failed",
  "action": "trash",
  "succeeded": [101, 102, 103],
  "failed": []
}
```

---

# Orders

## wc-list-orders

List orders with filtering and pagination options.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-orders/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `status` | string | no | - | Order status |
| `customer` | integer | no | - | Customer ID |
| `product` | integer | no | - | Filter by product |
| `date_after` | string | no | - | After date (Y-m-d) |
| `date_before` | string | no | - | Before date (Y-m-d) |
| `limit` | integer | no | `20` | Items to return (1-100) |
| `offset` | integer | no | `0` | Items to skip |
| `orderby` | string | no | `date` | Sort field |
| `order` | string | no | `DESC` | Direction |

### Output

```json
{
  "orders": [
    {
      "id": 102,
      "order_number": "102",
      "status": "pending",
      "currency": "HUF",
      "total": "29.99",
      "subtotal": "29.99",
      "total_tax": "0",
      "shipping_total": "0",
      "discount_total": "0",
      "customer_id": 1,
      "date_created": "2026-01-13 09:38:35",
      "date_modified": "2026-01-13 09:38:35",
      "date_completed": null,
      "date_paid": null,
      "payment_method": "",
      "payment_method_title": "",
      "items_count": 1,
      "billing": {
        "first_name": "",
        "last_name": "",
        "email": "customer@example.com",
        "phone": ""
      }
    }
  ],
  "total": 1,
  "total_pages": 1,
  "limit": 20,
  "offset": 0,
  "has_more": false
}
```

---

## wc-get-order

Get detailed information about a single order.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-get-order/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Order ID |

### Output

The detailed view includes:
- Full billing and shipping address
- Line items
- Shipping lines
- Coupon lines
- Customer note
- Refunds total

---

## wc-update-order-status

Update order status.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-order-status/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `id` | integer | **yes** | - | Order ID |
| `status` | string | **yes** | - | New status (e.g., `processing`, `completed`, `on-hold`) |
| `note` | string | no | - | Note for status change |

### Output

```json
{
  "success": true,
  "message": "Order status changed from checkout-draft to pending",
  "id": 102,
  "old_status": "checkout-draft",
  "new_status": "pending"
}
```

### Example

```bash
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-order-status/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 102, "status": "processing", "note": "Payment received"}}'
```

---

## wc-list-order-statuses

List available order statuses.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-order-statuses/run`

### Input

No required input.

### Output

```json
{
  "statuses": [
    {"slug": "pending", "label": "Pending payment"},
    {"slug": "processing", "label": "Processing"},
    {"slug": "on-hold", "label": "On hold"},
    {"slug": "completed", "label": "Completed"},
    {"slug": "cancelled", "label": "Cancelled"},
    {"slug": "refunded", "label": "Refunded"},
    {"slug": "failed", "label": "Failed"},
    {"slug": "checkout-draft", "label": "Draft"}
  ],
  "total": 8
}
```

---

## wc-create-refund

Create a refund for an order.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-refund/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `order_id` | integer | **yes** | - | Order ID |
| `amount` | number | no | - | Amount to refund (defaults to full amount) |
| `reason` | string | no | - | Refund reason |
| `restock_items` | boolean | no | `true` | Restock items |
| `line_items` | array | no | - | Partial refund by line item |

### Output

```json
{
  "success": true,
  "message": "Refund created successfully",
  "refund_id": 105,
  "order_id": 102,
  "amount": 29.99,
  "reason": "Customer request"
}
```

---

## wc-list-order-notes

List notes for an order.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-order-notes/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `order_id` | integer | **yes** | - | Order ID |
| `type` | string | no | `any` | Type: `any`, `customer`, `internal` |

### Output

```json
{
  "order_id": 102,
  "notes": [
    {
      "id": 1,
      "content": "Order status changed to Processing",
      "date_created": "2026-01-13 10:00:00",
      "customer_note": false,
      "added_by": "system"
    }
  ],
  "total": 1
}
```

---

## wc-add-order-note

Add a note to an order.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-add-order-note/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `order_id` | integer | **yes** | - | Order ID |
| `note` | string | **yes** | - | Note content |
| `customer_note` | boolean | no | `false` | Send to customer |

### Output

```json
{
  "success": true,
  "message": "Order note added successfully",
  "note_id": 2,
  "order_id": 102,
  "customer_note": false
}
```

---

## wc-bulk-orders

Bulk status update for multiple orders.

**Method:** `POST`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-bulk-orders/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `ids` | array | **yes** | - | Array of order IDs |
| `status` | string | **yes** | - | New status |
| `note` | string | no | - | Note for all orders |

### Output

```json
{
  "success": true,
  "message": "Bulk action completed: 3 succeeded, 0 failed",
  "succeeded": [101, 102, 103],
  "failed": []
}
```

---

# Reports

## wc-sales-report

Get sales report for a period.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-sales-report/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `period` | string | no | `month` | Predefined period: `day`, `week`, `month`, `year`, `last_7_days`, `last_30_days` |
| `date_min` | string | no | - | Custom start date (Y-m-d) |
| `date_max` | string | no | - | Custom end date (Y-m-d) |

### Output

```json
{
  "success": true,
  "period": "month",
  "date_min": "2026-01-01",
  "date_max": "2026-01-31",
  "total_sales": 1250.50,
  "net_sales": 1100.00,
  "total_orders": 25,
  "total_items": 48,
  "total_shipping": 75.00,
  "total_tax": 150.50,
  "total_refunds": 0,
  "total_discounts": 25.00,
  "average_order": 50.02,
  "currency": "HUF"
}
```

---

## wc-top-sellers

List top-selling products.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-top-sellers/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `limit` | integer | no | `10` | Items to return (1-100) |
| `period` | string | no | `month` | Period: `week`, `month`, `year` |

### Output

```json
{
  "success": true,
  "period": "month",
  "date_from": "2026-01-01",
  "products": [
    {
      "id": 101,
      "name": "Test Product",
      "sku": "PROD-001",
      "quantity_sold": 15,
      "total_sales": 449.85,
      "price": "29.99",
      "stock_status": "instock",
      "stock_quantity": 35,
      "image": null
    }
  ],
  "total": 5
}
```

---

## wc-orders-totals

Get order counts by status.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-orders-totals/run`

### Input

No required input.

### Output

```json
{
  "success": true,
  "totals": [
    {"status": "pending", "label": "Pending payment", "count": 5},
    {"status": "processing", "label": "Processing", "count": 12},
    {"status": "completed", "label": "Completed", "count": 48},
    {"status": "cancelled", "label": "Cancelled", "count": 2},
    {"status": "refunded", "label": "Refunded", "count": 1}
  ],
  "grand_total": 68
}
```

---

## wc-revenue-stats

Get revenue statistics with period comparison.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-revenue-stats/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `period` | string | no | `last_7_days` | Period: `today`, `last_7_days`, `last_30_days`, `this_month`, `this_year` |
| `compare` | boolean | no | `false` | Compare with previous period |

### Output

```json
{
  "success": true,
  "period": "last_7_days",
  "current": {
    "date_start": "2026-01-06",
    "date_end": "2026-01-13",
    "revenue": 850.00,
    "orders": 15,
    "items_sold": 28
  },
  "previous": {
    "date_start": "2025-12-30",
    "date_end": "2026-01-05",
    "revenue": 720.00,
    "orders": 12,
    "items_sold": 22
  },
  "changes": {
    "revenue": {"value": 130.00, "percentage": 18.06, "trend": "up"},
    "orders": {"value": 3, "percentage": 25.00, "trend": "up"},
    "items_sold": {"value": 6, "percentage": 27.27, "trend": "up"}
  },
  "currency": "HUF"
}
```

---

## wc-low-stock-products

List low stock and out of stock products.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-low-stock-products/run`

### Input

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `threshold` | integer | no | - | Custom threshold (defaults to WooCommerce setting) |
| `limit` | integer | no | `20` | Items to return (1-100) |
| `include_out_of_stock` | boolean | no | `true` | Include out of stock products |

### Output

```json
{
  "success": true,
  "threshold": 5,
  "products": [
    {
      "id": 101,
      "name": "Test Product",
      "sku": "PROD-001",
      "stock_quantity": 3,
      "stock_status": "instock",
      "price": "29.99",
      "type": "simple",
      "permalink": "https://example.com/product/test-product/",
      "image": null
    },
    {
      "id": 102,
      "name": "Out of Stock Product",
      "sku": "PROD-002",
      "stock_quantity": 0,
      "stock_status": "outofstock",
      "price": "49.99",
      "type": "simple",
      "permalink": "https://example.com/product/out-of-stock-product/",
      "image": null
    }
  ],
  "total": 2
}
```

---

## wc-products-totals

Get product counts by status and stock.

**Method:** `GET`
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-products-totals/run`

### Input

No required input.

### Output

```json
{
  "success": true,
  "published": 25,
  "draft": 3,
  "pending": 1,
  "trash": 2,
  "in_stock": 20,
  "out_of_stock": 4,
  "on_backorder": 1,
  "low_stock": 3,
  "total": 31
}
```
