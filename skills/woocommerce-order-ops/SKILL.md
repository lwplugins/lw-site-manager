---
name: woocommerce-order-ops
description: Safely inspect and modify WooCommerce orders via LW Site Manager. Use when the user asks to look up an order, refund, change an order status, or report on sales.
enable_prompt: false
enable_agentic: true
---

# WooCommerce order operations

Operate on WooCommerce orders through LW Site Manager abilities. Treat customer data as sensitive.

## Reading

- `site-manager/wc-list-orders` to find orders (filter by status/customer/date). Do not page through the entire customer base to fish for data.
- `site-manager/wc-get-order` for one order's detail.

## Writing (confirm with the user first)

- `site-manager/wc-update-order-status` to move an order between statuses.
- `site-manager/wc-create-refund` records a refund (no money is sent through the gateway). The amount defaults to what is still refundable and cannot exceed it. The first call without `confirm_refund: true` returns 409 with the amount — show it to the user, then resend with `confirm_refund: true`.
- `site-manager/wc-delete-order` trashes by default; a permanent delete needs `force` plus `confirm_delete: true`.
- `site-manager/wc-mark-order-paid` only when the user confirms payment was received out-of-band.

## Rules

- Echo back the order id + amount and get explicit confirmation before any refund or paid-marking.
- Never expose more customer PII than needed to answer the question.
