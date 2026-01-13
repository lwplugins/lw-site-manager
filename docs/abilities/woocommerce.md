# WooCommerce Abilities

A WooCommerce ability-k csak akkor elérhetők, ha a WooCommerce plugin aktív.

## Tartalomjegyzék

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

Termékek listázása szűrési és lapozási lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-products/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `status` | string | nem | `any` | Állapot: `publish`, `draft`, `pending`, `private`, `trash`, `any` |
| `type` | string | nem | - | Típus: `simple`, `variable`, `grouped`, `external` |
| `category` | string | nem | - | Kategória slug vagy ID |
| `stock_status` | string | nem | - | Készlet: `instock`, `outofstock`, `onbackorder` |
| `featured` | boolean | nem | - | Kiemelt termékek |
| `on_sale` | boolean | nem | - | Akciós termékek |
| `search` | string | nem | - | Keresés névben |
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `orderby` | string | nem | `date` | Rendezés mező |
| `order` | string | nem | `DESC` | Irány: `ASC`, `DESC` |

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

### Példa

```bash
curl -s -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-products/run'
```

---

## wc-get-product

Egyetlen termék részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-get-product/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | - | - | Product ID |
| `sku` | string | - | - | Product SKU |
| `slug` | string | - | - | Product slug |

> **Megjegyzés:** `id`, `sku` vagy `slug` valamelyikének megadása kötelező.

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

Új termék létrehozása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-product/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `name` | string | **igen** | - | Termék neve |
| `type` | string | nem | `simple` | Típus: `simple`, `variable`, `grouped`, `external` |
| `status` | string | nem | `publish` | Állapot |
| `description` | string | nem | - | Leírás |
| `short_description` | string | nem | - | Rövid leírás |
| `regular_price` | string | nem | - | Normál ár |
| `sale_price` | string | nem | - | Akciós ár |
| `sku` | string | nem | - | SKU |
| `manage_stock` | boolean | nem | - | Készletkezelés |
| `stock_quantity` | integer | nem | - | Készlet mennyiség |
| `stock_status` | string | nem | - | Készlet státusz |
| `featured` | boolean | nem | - | Kiemelt |
| `virtual` | boolean | nem | - | Virtuális termék |
| `downloadable` | boolean | nem | - | Letölthető |
| `categories` | array | nem | - | Kategória ID-k tömbje |
| `tags` | array | nem | - | Tag ID-k tömbje |

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

### Példa

```bash
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-product/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"name": "New Product", "regular_price": "19.99", "status": "publish"}}'
```

---

## wc-update-product

Meglévő termék módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-product/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Product ID |
| `name` | string | nem | - | Új név |
| `status` | string | nem | - | Új állapot |
| `regular_price` | string | nem | - | Új ár |
| `sale_price` | string | nem | - | Új akciós ár |
| ... | | | | (minden mező mint create-nél) |

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

Termék törlése.

**Method:** `DELETE`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-delete-product/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Product ID |
| `force` | boolean | nem | `false` | Végleges törlés (kukába helyezés helyett) |

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

Termék másolat készítése.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-duplicate-product/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Eredeti Product ID |
| `new_name` | string | nem | - | Új termék neve (ha üres: "Copy of...") |
| `status` | string | nem | `draft` | Új termék állapota |

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

Készlet gyors frissítése.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Product ID |
| `quantity` | integer | nem | - | Új készlet mennyiség (abszolút) |
| `adjust` | integer | nem | - | Készlet módosítás (+/- érték) |
| `stock_status` | string | nem | - | Készlet státusz: `instock`, `outofstock`, `onbackorder` |

> **Megjegyzés:** `quantity` vagy `adjust` vagy `stock_status` valamelyikét meg kell adni.

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

### Példa

```bash
# Készlet beállítása 100-ra
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 101, "quantity": 100}}'

# Készlet csökkentése 5-tel
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-stock/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 101, "adjust": -5}}'
```

---

## wc-list-product-categories

Termék kategóriák listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-product-categories/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `hide_empty` | boolean | nem | `false` | Üres kategóriák elrejtése |
| `parent` | integer | nem | - | Szülő kategória ID |
| `search` | string | nem | - | Keresés névben |
| `limit` | integer | nem | `100` | Visszaadott elemek |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `orderby` | string | nem | `name` | Rendezés |
| `order` | string | nem | `ASC` | Irány |

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

Variábilis termék variációinak listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-variations/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `product_id` | integer | **igen** | - | Variábilis termék ID |

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

Tömeges művelet több terméken.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-bulk-products/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `ids` | array | **igen** | - | Product ID-k tömbje |
| `action` | string | **igen** | - | Művelet: `publish`, `draft`, `trash`, `delete`, `restore` |

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

Rendelések listázása szűrési és lapozási lehetőségekkel.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-orders/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `status` | string | nem | - | Rendelés státusz |
| `customer` | integer | nem | - | Vásárló ID |
| `product` | integer | nem | - | Szűrés termékre |
| `date_after` | string | nem | - | Dátum után (Y-m-d) |
| `date_before` | string | nem | - | Dátum előtt (Y-m-d) |
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `offset` | integer | nem | `0` | Kihagyott elemek |
| `orderby` | string | nem | `date` | Rendezés |
| `order` | string | nem | `DESC` | Irány |

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

Egyetlen rendelés részletes lekérése.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-get-order/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Order ID |

### Output

A részletes nézet tartalmazza:
- Teljes számlázási és szállítási cím
- Line items (tételek)
- Shipping lines (szállítási módok)
- Coupon lines (kuponok)
- Customer note
- Refunds total

---

## wc-update-order-status

Rendelés státuszának módosítása.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-order-status/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `id` | integer | **igen** | - | Order ID |
| `status` | string | **igen** | - | Új státusz (pl. `processing`, `completed`, `on-hold`) |
| `note` | string | nem | - | Megjegyzés a státuszváltáshoz |

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

### Példa

```bash
curl -s -X POST -u "user:pass" \
  'https://example.com/wp-json/wp-abilities/v1/abilities/site-manager/wc-update-order-status/run' \
  -H "Content-Type: application/json" \
  -d '{"input": {"id": 102, "status": "processing", "note": "Payment received"}}'
```

---

## wc-list-order-statuses

Elérhető rendelési státuszok listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-order-statuses/run`

### Input

Nincs szükséges input.

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

Visszatérítés létrehozása rendeléshez.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-create-refund/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `order_id` | integer | **igen** | - | Order ID |
| `amount` | number | nem | - | Visszatérítendő összeg (alapértelmezetten teljes összeg) |
| `reason` | string | nem | - | Visszatérítés indoka |
| `restock_items` | boolean | nem | `true` | Készlet visszaállítása |
| `line_items` | array | nem | - | Részleges visszatérítés tételenként |

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

Rendeléshez tartozó megjegyzések listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-list-order-notes/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `order_id` | integer | **igen** | - | Order ID |
| `type` | string | nem | `any` | Típus: `any`, `customer`, `internal` |

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

Megjegyzés hozzáadása rendeléshez.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-add-order-note/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `order_id` | integer | **igen** | - | Order ID |
| `note` | string | **igen** | - | Megjegyzés szövege |
| `customer_note` | boolean | nem | `false` | Vásárlónak küldés |

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

Tömeges státusz módosítás több rendelésen.

**Method:** `POST`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-bulk-orders/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `ids` | array | **igen** | - | Order ID-k tömbje |
| `status` | string | **igen** | - | Új státusz |
| `note` | string | nem | - | Megjegyzés mindegyikhez |

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

Értékesítési jelentés lekérése időszakra.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-sales-report/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `period` | string | nem | `month` | Előre definiált időszak: `day`, `week`, `month`, `year`, `last_7_days`, `last_30_days` |
| `date_min` | string | nem | - | Egyedi kezdő dátum (Y-m-d) |
| `date_max` | string | nem | - | Egyedi záró dátum (Y-m-d) |

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

Legnépszerűbb termékek listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-top-sellers/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `limit` | integer | nem | `10` | Visszaadott elemek (1-100) |
| `period` | string | nem | `month` | Időszak: `week`, `month`, `year` |

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

Rendelések összesítése státuszonként.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-orders-totals/run`

### Input

Nincs szükséges input.

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

Bevételi statisztikák időszak összehasonlítással.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-revenue-stats/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `period` | string | nem | `last_7_days` | Időszak: `today`, `last_7_days`, `last_30_days`, `this_month`, `this_year` |
| `compare` | boolean | nem | `false` | Összehasonlítás az előző időszakkal |

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

Alacsony készletű és elfogyott termékek listázása.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-low-stock-products/run`

### Input

| Mező | Típus | Kötelező | Alapértelmezett | Leírás |
|------|-------|----------|-----------------|--------|
| `threshold` | integer | nem | - | Egyedi küszöbérték (alapértelmezetten WooCommerce beállítás) |
| `limit` | integer | nem | `20` | Visszaadott elemek (1-100) |
| `include_out_of_stock` | boolean | nem | `true` | Elfogyott termékek is |

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

Termékek összesítése státusz és készlet szerint.

**Method:** `GET`  
**Endpoint:** `/wp-json/wp-abilities/v1/abilities/site-manager/wc-products-totals/run`

### Input

Nincs szükséges input.

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
