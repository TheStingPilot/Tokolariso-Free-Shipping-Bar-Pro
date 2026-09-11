# Technical Notes

Current plugin version: `2.1.3`

## Architecture

`free-shipping-and-progressbar-pro.php` bootstraps the plugin after WooCommerce is available and loads the admin and frontend modules.

`includes/free-shipping-bar.php` contains the frontend bar, AJAX progress endpoint, upsell selection, WooCommerce shipping-rate filters, Store API shipping-rate filter, and giftcard exclusion helpers.

`includes/admin/settings.php` contains the WooCommerce admin settings page, upsell rule editor, admin product search endpoint, WPML-aware product lookups, and debug settings.

The stable frontend implementation currently lives inline in `includes/free-shipping-bar.php`. The files in `assets/js/` are retained as historical/refactor assets.

## Free Shipping Basis

The free-shipping progress amount is intentionally not the raw WooCommerce cart total. It is the cart total for shipping-eligible merchandise only:

- regular product line subtotal plus line subtotal tax;
- excluding giftcard product purchases;
- excluding cart items that are only store-credit/voucher value.

This means a giftcard purchase cannot push a shopper over the free-delivery threshold.

The same rule is applied to shipping rates. If WooCommerce exposes a native `free_shipping` rate only because the raw cart total includes excluded giftcard value, the plugin removes that native free-shipping rate from both classic package rates and Store API shipping-rate output.

Example with a EUR 50 free-shipping threshold:

- Regular goods: EUR 25.00 including VAT
- Toko Lariso giftcard purchase: EUR 50.00
- Cart display total: EUR 75.00
- Free-shipping progress total: EUR 25.00
- Remaining for free delivery: EUR 25.00

## Giftcard Detection

The central product helper is:

```php
free_shipment_progressbar_get_giftcard_excluded_reason($product)
```

It detects known giftcard products using product type, technical metadata, and known giftcard callbacks.

Version `2.1.3` adds explicit support for Toko Lariso Giftcards:

- product type `tokolarisogiftcard`;
- cart item data key `tokolariso_giftcard`;
- related `_tokolariso_*` metadata where present.

Cart-level calculations use:

```php
free_shipment_progressbar_get_giftcard_cart_item_excluded_reason($cart_item)
free_shipment_progressbar_is_giftcard_cart_item($cart_item)
```

These cart helpers are used for:

- free-shipping progress totals;
- excluded giftcard debug data;
- source product/category collection for upsell rules.

Product-level filtering still uses `free_shipment_progressbar_is_giftcard_product()` for admin search results and upsell candidates, where no cart item data is available.

## Shipping Hooks

The plugin reads configured free-shipping thresholds through:

```php
free_shipment_progressbar_get_shipping_data($package = [])
```

The threshold logic supports native WooCommerce `free_shipping` methods and WBSNG-style rules.

The paid shipping saving shown in the bar is calculated by:

```php
free_shipment_progressbar_get_shipping_saving_amount($package = [])
```

This returns the lowest paid shipping amount including shipping tax.

When the shipping-eligible merchandise total reaches the threshold, the plugin can force matching WBSNG rates to zero through:

- `woocommerce_package_rates`
- `woocommerce_store_api_cart_shipping_rates`

Because those filters use `free_shipment_progressbar_get_shipping_eligible_cart_totals()`, giftcard purchase value is not counted.

When the shipping-eligible merchandise total is still below the threshold, `free_shipment_progressbar_should_remove_native_free_shipping()` detects the specific case where excluded giftcard value would otherwise make the raw cart total appear eligible. In that case, native WooCommerce `free_shipping` rates are removed from classic shipping packages and Store API shipping packages.

## Blocks and AJAX Flow

The frontend calls `wp_ajax_get_free_shipping_progress` / `wp_ajax_nopriv_get_free_shipping_progress`.

The response includes:

- `total`: shipping-eligible total including VAT;
- `minimum`: detected free-shipping threshold;
- `remaining`: threshold minus shipping-eligible total;
- `eligible_cart_count`;
- `excluded_giftcard_count`;
- `excluded_giftcard_total`;
- `has_only_giftcards`;
- `debug_excluded_cart_items` when debug mode is enabled.

WooCommerce Cart and Checkout Blocks are refreshed by listening to Store API/cart data changes. Classic WooCommerce cart and checkout pages use the existing jQuery event fallbacks.

## WPML

The plugin text domain is `free_shipment_progressbar`.

The plugin forces its WPML string source language to English (`en`) and translates product/category IDs to the current frontend language where possible.

Giftcard exclusion does not depend on translated product names. It uses technical product type and cart item markers, so it remains stable across languages.

## Debugging

Enable debug mode in the plugin admin page.

Useful fields in the AJAX response:

- `eligible_cart_count`: number of non-giftcard cart items counted toward free shipping.
- `excluded_giftcard_count`: number of giftcard cart items excluded.
- `excluded_giftcard_total`: excluded giftcard value including tax.
- `debug_excluded_cart_items`: excluded item list with `excluded_reason`, such as `tokolariso_giftcard_cart_item` or `tokolariso_giftcard_product_type`.

## Manual Test Scenarios

1. Add only a Toko Lariso giftcard of EUR 50 to the cart. The free-shipping progress bar should not show progress toward free delivery and `has_only_giftcards` should be true in debug mode.
2. Add regular goods of EUR 25 and a Toko Lariso giftcard of EUR 50 with a EUR 50 free-shipping threshold. The bar should show EUR 25 remaining, not free delivery.
3. Add regular goods of EUR 50 and a Toko Lariso giftcard of EUR 50. Free delivery may be shown because the regular goods alone reached the threshold.
4. Add a WPC Gift Cards product and confirm it is still excluded from progress totals.
5. Open Cart Block and Checkout Block and confirm the same progress values appear after address/shipping changes.
6. Enable debug mode and confirm `debug_excluded_cart_items` contains the giftcard line with a Toko Lariso exclusion reason.

## Packaging

The uploadable zip must contain the plugin folder:

```text
free-shipping-and-progressbar-pro/
    assets/
    includes/
    free-shipping-and-progressbar-pro.php
    LICENSE.md
    README.md
    technical.md
```

Do not include old release zips inside the package.
