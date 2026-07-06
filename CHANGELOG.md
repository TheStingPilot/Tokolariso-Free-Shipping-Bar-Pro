# Changelog

## 1.2.0.20 - 2026-07-05

- Added product category metadata to admin product search results.
- Automatically selects a product's categories in the same source or target panel when a product is chosen.
- Improved selected item remove buttons in the admin upsell rule editor.

## 1.2.0.19 - 2026-07-05

- Restored the plugin's own admin product search for upsell rule product fields.
- Improved product search so every typed word must match the product title, slug, or SKU.
- Added direct database candidate matching to find products beyond WooCommerce's broad default search result set.
- Fixed searches such as "cap lang" returning broad "cap" matches without the intended Cap Lang products.

## 1.2.0.18 - 2026-07-05

- Added SelectWoo stylesheet loading when available in WooCommerce admin.
- Added fallback overlay CSS for SelectWoo, Select2 v4, and older Select2 v3 dropdown classes.
- Prevented admin search results from flowing through upsell rule cards when Select2 library CSS is missing or overridden.

## 1.2.0.17 - 2026-07-05

- Switched admin product selectors to WooCommerce's native `wc-product-search` enhanced select.
- Product search now uses WooCommerce's standard product and variation search endpoint.
- Removed custom product Select2 initialization from the admin rule editor to fix misplaced search results.
- Kept category selectors on the plugin's local SelectWoo/Select2 initialization.

## 1.2.0.16 - 2026-07-05

- Fixed Select2/SelectWoo dropdown positioning in the admin upsell rule editor.
- Restored body-level dropdown rendering to prevent product search results from appearing at the bottom of the page.
- Inserted newly created upsell rules at the top of the rules list, directly below the toolbar.
- Automatically scrolls to a newly created rule so product entry starts near the "Nieuwe regel" button.

## 1.2.0.15 - 2026-07-04

- Improved the admin upsell rule editor with larger multi-select fields.
- Kept product search dropdowns attached to the correct rule panel.
- Returned plain product labels in AJAX search results to prevent raw HTML from appearing in the dropdown.
- Added stronger Select2/SelectWoo styling for selected products, selected categories, and search input fields.

## 1.2.0.14 - 2026-06-08

- Replaced `sprintf()` in the free shipping progress messages with a safe formatter.
- Prevented PHP 8 `ValueError` fatals when translated strings contain stray percent signs.
- Applied the safe formatter to both desktop and mobile progress messages.

## 1.2.0.13 - 2026-05-21

- Removed the plugin's Sydney mobile header size overrides.
- Restored the mobile masthead height to the theme's original behavior.
- Kept only the sticky offset needed for the free shipping / upsell bar.

## 1.2.0.12 - 2026-05-20

- Stabilized upsell card height when product titles wrap to two lines.
- Reserved two-line title space in each upsell card to prevent the bar height from changing between slider pages.
- Re-measured the bar height after desktop slider navigation.

## 1.2.0.11 - 2026-05-20

- Stabilized the sticky header offset when the WordPress admin bar is visible.
- Re-measured the bar height after text fitting and upsell rendering to prevent a white gap between the plugin bar and the menu.
- Rounded the measured bar height up to avoid sub-pixel layout gaps.

## 1.2.0.10 - 2026-05-20

- Added fallback upsells when no rule-based products are found.
- Added a shorter mobile progress message: "Nog X tot gratis verzending. Bespaar Y".
- Added automatic font-size fitting for the mobile progress message.
- Tightened the Sydney mobile header logo limits for narrow screens.

## 1.2.0.9 - 2026-05-20

- Added dynamic shipping savings to the progress message: "Add X for free shipping and save Y".
- Shipping savings are calculated from WooCommerce shipping rates for the detected zone and include shipping tax.
- No shipping amounts are hardcoded.
- Kept the mobile progress message on a single line with overflow ellipsis.
- Translated the changelog to English.

## 1.2.0.8 - 2026-05-19

- Adjusted the Sydney mobile header from the plugin CSS.
- Limited the mobile logo width and height so the menu bar is no longer stretched.
- Prevented mobile header icons from shrinking unnecessarily.
- Scoped the change to `#masthead-mobile` and screens up to 1024px wide.

## 1.2.0.7 - 2026-05-19

- Made the progress AJAX endpoint read-only tolerant so expired or cached nonces no longer cause `400 Bad Request`.
- Kept the add-to-cart nonce validation for write actions.
- Moved `fsb_ajax` to the footer directly before the frontend bar code.
- Added a dependency correction for the known Mollie/Inpsyde handle `inpsyde-blocks`.
- Kept the stable `1.2.0.3` base and did not reintroduce the broken external JS refactor from `1.2.0.4/1.2.0.5`.

## 1.2.0.6 - 2026-05-19

- Restored the working `1.2.0.3` base from the zip archive.
- Excluded the `1.2.0.4` script refactor because it caused AJAX 400 errors.
- Added a WordPress admin bar offset for logged-in administrators.
- Kept the free shipping / upsell bar below `#wpadminbar`.
- Made the Sydney desktop and mobile headers move below the admin bar and plugin bar.
- Added this changelog to the build.

## 1.2.0.5 - 2026-05-19

- Built on `1.2.0.4`; superseded by `1.2.0.6` because of a frontend bar regression.

## 1.2.0.4 - 2026-05-18

- Moved frontend JavaScript to separate asset files.
- Declared WooCommerce Blocks dependencies explicitly.
- Loaded scripts through the WordPress enqueue system in the footer.
- Added defensive checks around WooCommerce Blocks data.
- Preserved backward compatibility with the classic WooCommerce checkout.

## 1.2.0.3 - 2026-05-18

- Last known stable base for the free shipping / upsell bar.
