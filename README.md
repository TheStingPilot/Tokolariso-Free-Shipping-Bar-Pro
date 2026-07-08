# Free Shipping and Progressbar PRO

Technical documentation for the WooCommerce plugin **Free Shipping and Progressbar PRO**.

Current plugin version: `2.0.0`

## Purpose

This plugin adds a sticky free shipping and upsell bar to a WooCommerce storefront.

The plugin has two related frontend responsibilities:

- Show a free shipping progress message only when the current shipping zone has a free shipping threshold.
- Show an upsell product carousel whenever there are products in the cart, independent of the free shipping threshold.

The plugin also adds a WooCommerce admin page where upsell rules can be managed without editing code.

## Main Features

- Free shipping progress bar based on the detected WooCommerce shipping zone.
- Support for native WooCommerce free shipping methods.
- Support for Weight Based Shipping / WBSNG-style rule configuration.
- Smart upsells based on source categories, target categories, source products, and target products.
- Product-based upsell rules.
- Category-to-category upsell rules.
- Rule priorities.
- Fallback upsells when no rule-based upsell products are found.
- Product and variation filtering for stock, price, and WCPOS POS-only visibility.
- WooCommerce Cart and Checkout Blocks support.
- Classic WooCommerce cart and checkout fallback support.
- Sydney theme cart counter synchronization.
- WPML-aware product and category ID translation.
- Admin-managed import/export for upsell rules.
- Admin product search with token matching and search aliases.
- Admin autocomplete fields that automatically add the selected product's categories to the same rule panel.
- Desktop carousel autoplay with an admin-managed interval.
- Shortened variation names in the frontend carousel.

## Requirements

- WordPress with WooCommerce active.
- PHP 8.3 compatible codebase.
- WooCommerce Cart and Checkout Blocks are supported.
- Classic WooCommerce cart and checkout are still supported.
- WPML is supported when present, but the plugin also works without WPML.

## Installation

Upload the plugin folder or zip so WordPress sees this structure:

```text
wp-content/plugins/free-shipping-and-progressbar-pro/
    free-shipping-and-progressbar-pro.php
    includes/
        free-shipping-bar.php
        admin/
            menu.php
            settings.php
    README.md
```

Activate **Free Shipping and Progressbar PRO** in WordPress Admin > Plugins.

## File Structure

```text
free-shipping-and-progressbar-pro.php
```

Main plugin bootstrap. It checks for WooCommerce and loads the admin and frontend modules.

```text
includes/free-shipping-bar.php
```

Main frontend and WooCommerce integration file. It contains shipping zone detection, upsell selection, frontend CSS/JS output, AJAX endpoints, add-to-cart handling, Blocks compatibility, and Store API shipping adjustments.

```text
includes/admin/menu.php
```

Registers the WooCommerce submenu page for the plugin settings.

```text
includes/admin/settings.php
```

Renders and saves the admin settings page. It handles upsell rules, product/category selectors, product search AJAX, import/export, debug mode, WPML source language setting, and carousel autoplay interval.

```text
assets/js/
```

Historical asset files from the external JavaScript refactor. The stable frontend implementation currently lives inline in `includes/free-shipping-bar.php`.

## WordPress Hooks

The plugin uses these important WordPress and WooCommerce hooks:

- `plugins_loaded`: initializes the plugin after WooCommerce is available.
- `admin_menu`: adds the settings page below WooCommerce.
- `admin_enqueue_scripts`: loads SelectWoo/Select2 assets for the admin rule editor.
- `wp_footer`: outputs frontend configuration, CSS, and JavaScript.
- `wp_enqueue_scripts`: patches a known WooCommerce Blocks dependency warning for the Mollie/Inpsyde handle when possible.
- `wp_ajax_get_free_shipping_progress`: authenticated free shipping and upsell AJAX endpoint.
- `wp_ajax_nopriv_get_free_shipping_progress`: guest free shipping and upsell AJAX endpoint.
- `wp_ajax_free_shipment_progressbar_add_upsell_to_cart`: authenticated add-to-cart endpoint for carousel products.
- `wp_ajax_nopriv_free_shipment_progressbar_add_upsell_to_cart`: guest add-to-cart endpoint for carousel products.
- `wp_ajax_free_shipment_progressbar_search_products`: admin product search endpoint.
- `woocommerce_package_rates`: forces WBSNG shipping rates to free when the free shipping threshold is reached.
- `woocommerce_store_api_cart_shipping_rates`: adjusts Store API shipping rates for WooCommerce Blocks.

## Stored Options

The plugin stores settings in the WordPress options table:

```text
free_shipment_progressbar_debug
```

`yes` or `no`. Enables debug logging in browser console and selected PHP logging.

```text
free_shipment_progressbar_wpml_source_language
```

Currently forced to `en` from the settings page so WPML treats English as the source language for rules.

```text
free_shipment_progressbar_upsell_rules
```

Array of admin-managed upsell rules.

```text
free_shipment_progressbar_carousel_interval
```

Autoplay interval for the desktop upsell carousel, in seconds. Default is `30`. Set to `0` to disable autoplay. Values above `300` are capped.

## Upsell Rule Data Model

Each upsell rule has this structure:

```php
[
    'enabled' => true,
    'priority' => 100,
    'source_categories' => [59],
    'target_categories' => [1880, 35],
    'source_products' => [],
    'target_products' => [],
]
```

Rule behavior:

- A rule matches when at least one source category or source product is in the cart.
- Matching rules contribute target categories and target products.
- Higher priority rules are shown first.
- Product IDs and category IDs are normalized to integers.
- WPML object IDs are translated to the current frontend language where possible.
- If no matching products are found, the plugin falls back to random sellable online products.

## Admin Rule Editor

The admin page is located under WooCommerce.

The editor supports:

- Adding new rules.
- Removing rules.
- Enabling/disabling rules.
- Setting priority.
- Selecting source categories.
- Selecting target categories.
- Searching and selecting source products.
- Searching and selecting target products.
- Importing/exporting rule JSON.
- Resetting default rules.
- Setting carousel autoplay interval.

Product selection behavior:

- Product search uses a custom AJAX endpoint.
- Every typed token must match product title, slug, or SKU.
- Additional aliases help searches such as `cap lang` find products like `Cap Lang Kayu Putih`.
- When a product is selected, its categories are automatically selected in the same source or target panel.
- Selected products and categories can be removed from the selector.

## Frontend Flow

On page load, the plugin:

1. Outputs the `fsb_ajax` configuration object in the footer.
2. Detects whether the current cart has products.
3. Reads the best available shipping address:
   - WooCommerce Blocks cart/checkout address.
   - Classic WooCommerce address fields.
   - Logged-in customer account address on account pages.
   - Customer city fallback where appropriate.
4. Calls `get_free_shipping_progress` through `admin-ajax.php`.
5. Receives progress data, shipping zone data, cart count, and upsells.
6. Renders the sticky bar when upsells or free shipping progress are available.
7. Keeps the Sydney cart counter synchronized with WooCommerce cart updates.

The free shipping progress section is shown only when a free shipping threshold exists for the detected zone.

The upsell carousel is shown when the cart contains products and upsell products are available.

## Shipping Zone Detection

The central function is:

```php
free_shipment_progressbar_get_shipping_data($package = [])
```

It:

- Loads WooCommerce cart if needed.
- Builds a package destination.
- Uses `wc_get_shipping_zone()` to detect the zone.
- Reads enabled native `free_shipping` methods.
- Reads enabled WBSNG methods and their rule configuration.
- Returns the lowest free shipping threshold found.

## Shipping Savings Calculation

The function:

```php
free_shipment_progressbar_get_shipping_saving_amount($package = [])
```

calculates the lowest paid shipping amount for the detected package using WooCommerce calculated shipping rates. The returned amount includes shipping tax, because the configured shipping method prices are usually stored excluding tax.

The progress message can therefore show:

```text
Add € X for free shipping and save € Y
```

On mobile, the shorter message is:

```text
€ X left for free shipping. Save € Y
```

## Product Carousel

The carousel receives products from `free_shipment_progressbar_get_upsells()`.

Products are filtered before display:

- Must exist.
- Must be in stock.
- Must have a price above `0.01`.
- Must not be WCPOS POS-only.
- Must not already be in the cart.

Variable products:

- Variable products are expanded to their variations.
- The add-to-cart button uses the variation product ID.
- The frontend name is shortened for readability.

Variation title normalization:

```text
ABC zoete sojasaus (275 ml / 600 ml) - 600 ml
```

is displayed as:

```text
ABC zoete sojasaus (600 ml)
```

This only changes the displayed title in the carousel. It does not change product IDs, cart behavior, or WooCommerce product data.

Autoplay:

- Desktop carousel advances automatically.
- Default interval is 30 seconds.
- The interval is configurable in the admin page.
- The previous and next buttons still work manually.
- Mobile remains swipe/scroll based.

## WooCommerce Blocks Support

The plugin supports WooCommerce Cart and Checkout Blocks by:

- Reading cart data through `wp.data.select('wc/store/cart')` when available.
- Invalidating the Store API cart resolution after add-to-cart actions.
- Listening for Store API cart changes.
- Supporting Blocks shipping method changes.
- Adjusting Store API shipping rate output through `woocommerce_store_api_cart_shipping_rates`.

Defensive checks are used before accessing Blocks data so the plugin does not crash when Blocks are unavailable.

## Classic WooCommerce Support

The plugin keeps compatibility with classic WooCommerce pages by:

- Listening to classic WooCommerce jQuery events such as `added_to_cart`, `removed_from_cart`, `updated_cart_totals`, `updated_wc_div`, and `updated_checkout`.
- Refreshing WooCommerce fragments after carousel add-to-cart.
- Falling back to classic address fields such as billing and shipping postcode/city/country fields.

## Sydney Theme Integration

The plugin includes specific cart counter synchronization for the Sydney/Sydney Pro header:

- Updates `.cart-count .count`.
- Updates `.cart-count .count-number`.
- Creates a badge when the wrapper exists but the badge is missing.
- Forces zero when the cart is empty.
- Avoids unstable repeated updates where possible.

## WPML Behavior

The admin settings force the source language for rules to English:

```text
en
```

Rules are stored against source-language IDs. On the frontend, category and product IDs are translated to the current language with WPML filters when WPML is available.

From version 1.2.0.24, existing WPML String Translation records for the `free_shipment_progressbar` text domain are synchronized to English (`en`) as source language in the WordPress admin. This prevents WPML from continuing to treat the plugin strings as Dutch source strings after scanning.

## WCPOS Visibility

The plugin checks POS-only visibility before showing upsell products.

It reads:

- Legacy `_pos_visibility` post meta.
- `woocommerce_pos_settings_visibility` from the options table.

Products or variations marked POS-only are excluded from frontend upsells.

## Security Notes

The plugin uses these protections:

- Capability check `manage_woocommerce` for the admin settings page.
- Nonce check for settings saves.
- Nonce check for write actions such as add-to-cart.
- Product and category IDs are normalized with `absint`.
- Admin product search is restricted to WooCommerce managers.
- Frontend output is escaped in JavaScript before insertion.
- Product prices are generated through WooCommerce formatting.
- The free shipping progress endpoint is read-only tolerant to avoid stale cached nonce failures.

## Debugging

Debug mode can be enabled from the plugin admin page.

When enabled, the browser console can show:

- AJAX responses.
- Detected totals.
- Detected free shipping threshold.
- Remaining amount.
- Shipping zone/debug data.
- Address source.
- Cart counter updates.

Debug mode should normally be disabled on production.

## Packaging

The installable zip should contain only:

```text
free-shipping-and-progressbar-pro/
    free-shipping-and-progressbar-pro.php
    includes/
        free-shipping-bar.php
        admin/
            menu.php
            settings.php
    README.md
```

Do not include historical zip files inside the installable zip.

## Changelog

### 2.0.0 - 2026-07-08

- Rebranded the plugin to **Free Shipping and Progressbar PRO**.
- Replaced plugin function prefixes, AJAX actions, nonces, admin slugs, CSS classes, JS globals, and WPML context with `free_shipment_progressbar` naming.
- Renamed the main plugin file and package folder to remove the old project-specific plugin slug.

### 1.2.0.25 - 2026-07-08

- Removed the old default category `38` upsell seed rule.
- Filters the matching legacy empty category `38` default rules out of existing stored upsell settings.

### 1.2.0.24 - 2026-07-08

- Changed the plugin source language to English for WPML.
- Synchronized existing WPML String Translation records for the `free_shipment_progressbar` text domain to English (`en`) in the WordPress admin.
- Translated frontend and admin plugin strings from Dutch to English.

### 1.2.0.23 - 2026-07-08

- Fixed WooCommerce Blocks checkout address detection when the visible checkout country changes before a postcode or city is entered.
- Added fallback reads for the Blocks checkout store, cart customer data, and hyphenated Blocks field IDs such as `shipping-country`.
- Prevented stale saved checkout addresses from overriding a fresh country selection.

### 1.2.0.22 - 2026-07-08

- Synchronized existing WPML String Translation source-language records for the `free_shipment_progressbar` text domain to Dutch in the WordPress admin.

### 1.2.0.21 - 2026-07-06

- Shortened variation names in the free shipping upsell carousel, for example `ABC zoete sojasaus (275 ml / 600 ml) - 600 ml` now displays as `ABC zoete sojasaus (600 ml)`.
- Added an admin setting for the upsell carousel autoplay interval.
- Added desktop carousel autoplay so the visible upsell products advance automatically.

### 1.2.0.20 - 2026-07-05

- Added product category metadata to admin product search results.
- Automatically selects a product's categories in the same source or target panel when a product is chosen.
- Improved selected item remove buttons in the admin upsell rule editor.

### 1.2.0.19 - 2026-07-05

- Restored the plugin's own admin product search for upsell rule product fields.
- Improved product search so every typed word must match the product title, slug, or SKU.
- Added direct database candidate matching to find products beyond WooCommerce's broad default search result set.
- Fixed searches such as `cap lang` returning broad `cap` matches without the intended Cap Lang products.

### 1.2.0.18 - 2026-07-05

- Added SelectWoo stylesheet loading when available in WooCommerce admin.
- Added fallback overlay CSS for SelectWoo, Select2 v4, and older Select2 v3 dropdown classes.
- Prevented admin search results from flowing through upsell rule cards when Select2 library CSS is missing or overridden.

### 1.2.0.17 - 2026-07-05

- Switched admin product selectors to WooCommerce's native `wc-product-search` enhanced select.
- Product search now uses WooCommerce's standard product and variation search endpoint.
- Removed custom product Select2 initialization from the admin rule editor to fix misplaced search results.
- Kept category selectors on the plugin's local SelectWoo/Select2 initialization.

### 1.2.0.16 - 2026-07-05

- Fixed Select2/SelectWoo dropdown positioning in the admin upsell rule editor.
- Restored body-level dropdown rendering to prevent product search results from appearing at the bottom of the page.
- Inserted newly created upsell rules at the top of the rules list, directly below the toolbar.
- Automatically scrolls to a newly created rule so product entry starts near the "New rule" button.

### 1.2.0.15 - 2026-07-04

- Improved the admin upsell rule editor with larger multi-select fields.
- Kept product search dropdowns attached to the correct rule panel.
- Returned plain product labels in AJAX search results to prevent raw HTML from appearing in the dropdown.
- Added stronger Select2/SelectWoo styling for selected products, selected categories, and search input fields.

### 1.2.0.14 - 2026-06-08

- Replaced `sprintf()` in the free shipping progress messages with a safe formatter.
- Prevented PHP 8 `ValueError` fatals when translated strings contain stray percent signs.
- Applied the safe formatter to both desktop and mobile progress messages.

### 1.2.0.13 - 2026-05-21

- Removed the plugin's Sydney mobile header size overrides.
- Restored the mobile masthead height to the theme's original behavior.
- Kept only the sticky offset needed for the free shipping / upsell bar.

### 1.2.0.12 - 2026-05-20

- Stabilized upsell card height when product titles wrap to two lines.
- Reserved two-line title space in each upsell card to prevent the bar height from changing between slider pages.
- Re-measured the bar height after desktop slider navigation.

### 1.2.0.11 - 2026-05-20

- Stabilized the sticky header offset when the WordPress admin bar is visible.
- Re-measured the bar height after text fitting and upsell rendering to prevent a white gap between the plugin bar and the menu.
- Rounded the measured bar height up to avoid sub-pixel layout gaps.

### 1.2.0.10 - 2026-05-20

- Added fallback upsells when no rule-based products are found.
- Added a shorter mobile progress message: `X left for free shipping. Save Y`.
- Added automatic font-size fitting for the mobile progress message.
- Tightened the Sydney mobile header logo limits for narrow screens.

### 1.2.0.9 - 2026-05-20

- Added dynamic shipping savings to the progress message: `Add X for free shipping and save Y`.
- Shipping savings are calculated from WooCommerce shipping rates for the detected zone and include shipping tax.
- No shipping amounts are hardcoded.
- Kept the mobile progress message on a single line with overflow ellipsis.
- Translated the changelog to English.

### 1.2.0.8 - 2026-05-19

- Adjusted the Sydney mobile header from the plugin CSS.
- Limited the mobile logo width and height so the menu bar is no longer stretched.
- Prevented mobile header icons from shrinking unnecessarily.
- Scoped the change to `#masthead-mobile` and screens up to 1024px wide.

### 1.2.0.7 - 2026-05-19

- Made the progress AJAX endpoint read-only tolerant so expired or cached nonces no longer cause `400 Bad Request`.
- Kept the add-to-cart nonce validation for write actions.
- Moved `fsb_ajax` to the footer directly before the frontend bar code.
- Added a dependency correction for the known Mollie/Inpsyde handle `inpsyde-blocks`.
- Kept the stable `1.2.0.3` base and did not reintroduce the broken external JS refactor from `1.2.0.4/1.2.0.5`.

### 1.2.0.6 - 2026-05-19

- Restored the working `1.2.0.3` base from the zip archive.
- Excluded the `1.2.0.4` script refactor because it caused AJAX 400 errors.
- Added a WordPress admin bar offset for logged-in administrators.
- Kept the free shipping / upsell bar below `#wpadminbar`.
- Made the Sydney desktop and mobile headers move below the admin bar and plugin bar.
- Added this changelog to the build.

### 1.2.0.5 - 2026-05-19

- Built on `1.2.0.4`; superseded by `1.2.0.6` because of a frontend bar regression.

### 1.2.0.4 - 2026-05-18

- Moved frontend JavaScript to separate asset files.
- Declared WooCommerce Blocks dependencies explicitly.
- Loaded scripts through the WordPress enqueue system in the footer.
- Added defensive checks around WooCommerce Blocks data.
- Preserved backward compatibility with the classic WooCommerce checkout.

### 1.2.0.3 - 2026-05-18

- Last known stable base for the free shipping / upsell bar.
