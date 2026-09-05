<?php

/*
 * FREE SHIPPING BAR
 * FREE SHIPPING AND PROGRESSBAR
 * VERSIE: ZONDAG 10 MEI 2026
 * CSS AANGEPAST 11 MEI 2026
 * v
 * Versie 1.0.3:
 *  - LOCATIEBUG
 * 
 * =========================================================
 * VERSIE 1.0.4
 * =========================================================
 * 
 *  - Prijs alleen hoger dan 0,01
 *  - Aantal producten in de slider is nu 15
 *  - Met pijltjes.
 *  - CSS issues op mobiel.
 * 
 * =========================================================
 * VERSIE 1.0.5
 * =========================================================
 * 
 *  - Met smart upsell rules 
 *  - BUG opgelost: in de winkelwagen producten toevoegen 
 *        geen update van de winkelwagen is opgelost.
 * 
 * =========================================================
 * VERSIE 1.0.6
 * =========================================================
 *
 * - Sydney cart counter volledig gestabiliseerd
 * - WooCommerce Blocks sync verbeterd
 * - MutationObserver verbeterd
 * - Counter badge HTML veilig gemaakt
 * - Debug logging opgeschoond
 * - END comments toegevoegd
 * - Onderhoudbaarheid sterk verbeterd
 */

/* =========================================================
 * AJAX URL
========================================================= */

add_action('wp_footer', function () {
?>
<script>
var fsb_ajax = {

    url: '<?php echo admin_url('admin-ajax.php'); ?>',

    debug: <?php echo get_option('free_shipment_progressbar_debug', 'no') === 'yes' ? 'true' : 'false'; ?>,

    carousel_interval:
        <?php echo (int) free_shipment_progressbar_get_carousel_interval_seconds(); ?>,

    progress_nonce:
        '<?php echo esc_js(wp_create_nonce('free_shipment_progressbar_progress')); ?>',

    cart_nonce:
        '<?php echo esc_js(wp_create_nonce('free_shipment_progressbar_cart')); ?>',

    is_logged_in:
        <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,

    use_customer_address:
        <?php echo (
            is_user_logged_in()
            && function_exists('is_account_page')
            && is_account_page()
        ) ? 'true' : 'false'; ?>,
	
    fragments_url:
    '<?php echo WC_AJAX::get_endpoint( "get_refreshed_fragments" ); ?>',

    i18n: {

        add_to_cart:
            '<?php echo esc_js(
                __('+ Add', 'free_shipment_progressbar')
            ); ?>',

        added:
            '<?php echo esc_js(
                __('✓ Added', 'free_shipment_progressbar')
            ); ?>'
    }
};
</script>
<?php
}, 5);

add_action(
    'wp_enqueue_scripts',
    'free_shipment_progressbar_add_missing_blocks_dependencies',
    100
);

function free_shipment_progressbar_add_missing_blocks_dependencies(){

    /*
     * Mollie/Inpsyde gebruikt in sommige versies wc.wcBlocksData zonder
     * wc-blocks-data-store te declareren. WooCommerce 10 meldt dit als
     * console warning. We corrigeren alleen de bekende handle en alleen
     * wanneer WooCommerce Blocks de dependency beschikbaar heeft gemaakt.
     */

    if(
        !wp_script_is('wc-blocks-data-store', 'registered')
    ){
        return;
    } // END if

    global $wp_scripts;

    if(
        !($wp_scripts instanceof WP_Scripts)
    ){
        return;
    } // END if

    $handles = [
        'inpsyde-blocks',
    ];

    foreach(
        $handles as $handle
    ){

        if(
            empty($wp_scripts->registered[$handle])
        ){
            continue;
        } // END if

        if(
            in_array(
                'wc-blocks-data-store',
                $wp_scripts->registered[$handle]->deps,
                true
            )
        ){
            continue;
        } // END if

        $wp_scripts->registered[$handle]->deps[] =
            'wc-blocks-data-store';

    } // END foreach
} // END function free_shipment_progressbar_add_missing_blocks_dependencies()

if(
    !function_exists('str_contains')
){
    function str_contains(
        $haystack,
        $needle
    ){
        return $needle === ''
            || strpos($haystack, $needle) !== false;
    } // END function str_contains()
} // END if

function free_shipment_progressbar_debug_log(
    $message,
    $context = null
){

    if(
        get_option('free_shipment_progressbar_debug', 'no') !== 'yes'
    ){
        return;
    } // END if

    if(
        $context !== null
    ){
        $message .= ' ' . print_r($context, true);
    } // END if

    error_log($message);
} // END function free_shipment_progressbar_debug_log()

function free_shipment_progressbar_format_text(
    $template,
    $replacements = []
){

    $formatted =
        (string) $template;

    foreach(
        $replacements as $key => $value
    ){
        $formatted =
            str_replace(
                '{' . $key . '}',
                (string) $value,
                $formatted
            );
    } // END foreach

    return $formatted;
} // END function free_shipment_progressbar_format_text()

function free_shipment_progressbar_safe_sprintf(
    $template,
    ...$values
){

    $formatted =
        (string) $template;

    foreach(
        $values as $index => $value
    ){
        $formatted =
            str_replace(
                '%' . ($index + 1) . '$s',
                (string) $value,
                $formatted
            );
    } // END foreach

    foreach(
        $values as $value
    ){
        $position =
            strpos(
                $formatted,
                '%s'
            );

        if(
            $position === false
        ){
            continue;
        } // END if

        $formatted =
            substr_replace(
                $formatted,
                (string) $value,
                $position,
                2
            );
    } // END foreach

    return $formatted;
} // END function free_shipment_progressbar_safe_sprintf()

function free_shipment_progressbar_default_upsell_rules(){

    return [
        [
            'enabled' => true,
            'priority' => 100,
            'source_categories' => [59],
            'target_categories' => [1880,35,30,32,3480,3477],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 90,
            'source_categories' => [32],
            'target_categories' => [34,35,30],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 80,
            'source_categories' => [204],
            'target_categories' => [172],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 70,
            'source_categories' => [208],
            'target_categories' => [34],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 60,
            'source_categories' => [172],
            'target_categories' => [3480],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 50,
            'source_categories' => [157],
            'target_categories' => [21,224,2019,19,177,1857],
            'source_products' => [],
            'target_products' => [],
        ],
        [
            'enabled' => true,
            'priority' => 40,
            'source_categories' => [3478],
            'target_categories' => [3477,3479,3480],
            'source_products' => [],
            'target_products' => [],
        ],
    ];
} // END function free_shipment_progressbar_default_upsell_rules()

function free_shipment_progressbar_parse_ids(
    $value
){

    if(
        is_array($value)
    ){
        $parts = $value;
    }else{
        $parts = preg_split(
            '/[\s,;|]+/',
            (string) $value
        );
    } // END if

    $ids = [];

    foreach(
        $parts as $part
    ){
        $id = absint($part);

        if(
            $id > 0
        ){
            $ids[] = $id;
        } // END if
    } // END foreach

    return array_values(array_unique($ids));
} // END function free_shipment_progressbar_parse_ids()

function free_shipment_progressbar_normalize_upsell_rules(
    $rules
){

    if(
        !is_array($rules)
    ){
        return [];
    } // END if

    $normalized = [];

    foreach(
        $rules as $rule
    ){
        if(
            !is_array($rule)
        ){
            continue;
        } // END if

        $normalized_rule = [
            'enabled' => !isset($rule['enabled']) || (bool) $rule['enabled'],
            'priority' => isset($rule['priority']) ? (int) $rule['priority'] : 0,
            'source_categories' => free_shipment_progressbar_parse_ids($rule['source_categories'] ?? []),
            'target_categories' => free_shipment_progressbar_parse_ids($rule['target_categories'] ?? []),
            'source_products' => free_shipment_progressbar_parse_ids($rule['source_products'] ?? []),
            'target_products' => free_shipment_progressbar_parse_ids($rule['target_products'] ?? []),
        ];

        if(
            free_shipment_progressbar_is_removed_default_rule($normalized_rule)
        ){
            continue;
        } // END if

        $normalized[] =
            $normalized_rule;
    } // END foreach

    usort(
        $normalized,
        function($a, $b){
            return ($b['priority'] <=> $a['priority']);
        }
    );

    return $normalized;
} // END function free_shipment_progressbar_normalize_upsell_rules()

function free_shipment_progressbar_is_removed_default_rule(
    $rule
){

    if(
        !empty($rule['source_products'])
        ||
        !empty($rule['target_products'])
    ){
        return false;
    } // END if

    $source_categories =
        array_values(
            array_map(
                'absint',
                $rule['source_categories'] ?? []
            )
        );

    $target_categories =
        array_values(
            array_map(
                'absint',
                $rule['target_categories'] ?? []
            )
        );

    sort($source_categories);
    sort($target_categories);

    return (
        empty($source_categories)
        &&
        $target_categories === [38]
    )
    ||
    (
        $source_categories === [38]
        &&
        empty($target_categories)
    );
} // END function free_shipment_progressbar_is_removed_default_rule()

function free_shipment_progressbar_make_default_rules_bidirectional(
    $rules
){

    $extra_rules = [];

    foreach(
        $rules as $rule
    ){
        foreach(
            $rule['source_categories'] ?? [] as $source_category
        ){
            foreach(
                $rule['target_categories'] ?? [] as $target_category
            ){
                $extra_rules[] = [
                    'enabled' => true,
                    'priority' => max(1, ((int) ($rule['priority'] ?? 0)) - 1),
                    'source_categories' => [$target_category],
                    'target_categories' => [$source_category],
                    'source_products' => [],
                    'target_products' => [],
                ];
            } // END foreach
        } // END foreach
    } // END foreach

    return array_merge(
        $rules,
        $extra_rules
    );
} // END function free_shipment_progressbar_make_default_rules_bidirectional()

function free_shipment_progressbar_get_upsell_rules(){

    $rules =
        get_option(
            'free_shipment_progressbar_upsell_rules',
            null
        );

    if(
        empty($rules)
        || !is_array($rules)
    ){
        $rules =
            free_shipment_progressbar_make_default_rules_bidirectional(
                free_shipment_progressbar_default_upsell_rules()
            );
    } // END if

    return free_shipment_progressbar_normalize_upsell_rules($rules);
} // END function free_shipment_progressbar_get_upsell_rules()

function free_shipment_progressbar_get_current_language(){

    if(
        has_filter('wpml_current_language')
    ){
        $language =
            apply_filters(
                'wpml_current_language',
                null
            );

        if(
            $language
        ){
            return $language;
        } // END if
    } // END if

    return free_shipment_progressbar_get_source_language();
} // END function free_shipment_progressbar_get_current_language()

function free_shipment_progressbar_get_source_language(){

    $language =
        get_option(
            'free_shipment_progressbar_wpml_source_language',
            'en'
        );

    $language =
        apply_filters(
            'free_shipment_progressbar_wpml_source_language',
            $language
        );

    return $language ? $language : 'en';
} // END function free_shipment_progressbar_get_source_language()

function free_shipment_progressbar_get_carousel_interval_seconds(){

    $interval =
        absint(
            get_option(
                'free_shipment_progressbar_carousel_interval',
                30
            )
        );

    if(
        $interval <= 0
    ){
        return 0;
    } // END if

    return min(
        300,
        max(
            5,
            $interval
        )
    );
} // END function free_shipment_progressbar_get_carousel_interval_seconds()

function free_shipment_progressbar_translate_object_id(
    $id,
    $type,
    $language
){

    if(
        has_filter('wpml_object_id')
    ){
        $translated =
            apply_filters(
                'wpml_object_id',
                $id,
                $type,
                true,
                $language
            );

        if(
            $translated
        ){
            return (int) $translated;
        } // END if
    } // END if

    return (int) $id;
} // END function free_shipment_progressbar_translate_object_id()

function free_shipment_progressbar_id_in_wcpos_list(
    $value,
    $product_id
){

    if(
        is_array($value)
    ){
        foreach(
            $value as $item
        ){
            if(
                free_shipment_progressbar_id_in_wcpos_list(
                    $item,
                    $product_id
                )
            ){
                return true;
            } // END if
        } // END foreach

        return false;
    } // END if

    return absint($value) === absint($product_id);
} // END function free_shipment_progressbar_id_in_wcpos_list()

function free_shipment_progressbar_wcpos_visibility_has_pos_only(
    $value,
    $product_id,
    $key_hint = ''
){

    if(
        is_array($value)
    ){
        foreach(
            $value as $key => $item
        ){
            $key_string =
                strtolower(
                    (string) $key
                );

            $combined_key =
                trim($key_hint . ' ' . $key_string);

            if(
                (
                    $key_string === (string) absint($product_id)
                    || $key === absint($product_id)
                )
                && is_string($item)
                && $item === 'pos_only'
            ){
                return true;
            } // END if

            if(
                strpos($combined_key, 'pos_only') !== false
                &&
                free_shipment_progressbar_id_in_wcpos_list(
                    $item,
                    $product_id
                )
            ){
                return true;
            } // END if

            if(
                free_shipment_progressbar_wcpos_visibility_has_pos_only(
                    $item,
                    $product_id,
                    $combined_key
                )
            ){
                return true;
            } // END if
        } // END foreach
    } // END if

    return false;
} // END function free_shipment_progressbar_wcpos_visibility_has_pos_only()

function free_shipment_progressbar_is_wcpos_pos_only_product_id(
    $product_id
){

    $product_id =
        absint($product_id);

    if(
        !$product_id
    ){
        return false;
    } // END if

    $legacy_visibility =
        get_post_meta(
            $product_id,
            '_pos_visibility',
            true
        );

    if(
        $legacy_visibility === 'pos_only'
    ){
        return true;
    } // END if

    $visibility_options =
        get_option(
            'woocommerce_pos_settings_visibility',
            []
        );

    if(
        empty($visibility_options)
    ){
        return false;
    } // END if

    if(
        !empty($visibility_options['products']['pos_only']['ids'])
        &&
        is_array($visibility_options['products']['pos_only']['ids'])
        &&
        in_array(
            $product_id,
            array_map('absint', $visibility_options['products']['pos_only']['ids']),
            true
        )
    ){
        return true;
    } // END if

    if(
        !empty($visibility_options['variations']['pos_only']['ids'])
        &&
        is_array($visibility_options['variations']['pos_only']['ids'])
        &&
        in_array(
            $product_id,
            array_map('absint', $visibility_options['variations']['pos_only']['ids']),
            true
        )
    ){
        return true;
    } // END if

    return free_shipment_progressbar_wcpos_visibility_has_pos_only(
        $visibility_options,
        $product_id
    );
} // END function free_shipment_progressbar_is_wcpos_pos_only_product_id()

function free_shipment_progressbar_is_wcpos_pos_only_product(
    $product
){

    if(
        !$product
    ){
        return false;
    } // END if

    if(
        free_shipment_progressbar_is_wcpos_pos_only_product_id(
            $product->get_id()
        )
    ){
        return true;
    } // END if

    $parent_id =
        $product->get_parent_id();

    if(
        $parent_id
        &&
        free_shipment_progressbar_is_wcpos_pos_only_product_id($parent_id)
    ){
        return true;
    } // END if

    return false;
} // END function free_shipment_progressbar_is_wcpos_pos_only_product()

function free_shipment_progressbar_get_giftcard_excluded_reason(
    $product
){

    if(
        !$product
    ){
        return '';
    } // END if

    $product_type =
        method_exists($product, 'get_type')
        ? strtolower((string) $product->get_type())
        : '';

    $giftcard_types = [
        'giftcard',
        'gift_card',
        'wpc_gift_card',
        'wgm_gift_card',
        'pw-gift-card',
        'yith_gift_card',
    ];

    if(
        in_array($product_type, $giftcard_types, true)
    ){
        return 'giftcard_product_type';
    } // END if

    $product_id =
        $product->get_id();

    $parent_id =
        $product->get_parent_id();

    $product_ids =
        array_values(
            array_filter(
                array_unique(
                    array_map(
                        'absint',
                        [
                            $product_id,
                            $parent_id,
                        ]
                    )
                )
            )
        );

    $wpcgc_meta_keys = [
        '_wpcgc_amounts',
        '_wpcgc_header_images',
        '_wpcgc_allow_custom',
        '_wpcgc_individual_use',
        '_wpcgc_allow_upload',
        '_wpcgc_custom_min',
        '_wpcgc_custom_max',
        '_wpcgc_expires_days',
    ];

    foreach(
        $product_ids as $meta_product_id
    ){
        foreach(
            $wpcgc_meta_keys as $meta_key
        ){
            if(
                metadata_exists(
                    'post',
                    $meta_product_id,
                    $meta_key
                )
            ){
                return 'wpc_gift_card_meta';
            } // END if
        } // END foreach
    } // END foreach

    foreach(
        ['is_giftcard_product', 'is_gift_card_product'] as $callback
    ){
        if(
            !function_exists($callback)
        ){
            continue;
        } // END if

        try {

            if(
                $callback($product)
                ||
                $callback($product_id)
                ||
                (
                    $parent_id
                    &&
                    $callback($parent_id)
                )
            ){
                return 'giftcard_callback';
            } // END if
        } catch (Throwable $error) {

            continue;
        } // END try/catch
    } // END foreach

    $meta_keys = [
        '_giftcard',
        '_gift_card',
        '_wpc_gift_card',
        '_wgm_giftcard',
        '_wgm_gift_card',
        'wpc_gift_card',
        'wgm_giftcard',
        'wgm_gift_card',
    ];

    foreach(
        $product_ids as $meta_product_id
    ){
        foreach(
            $meta_keys as $meta_key
        ){
            $value =
                get_post_meta(
                    $meta_product_id,
                    $meta_key,
                    true
                );

            if(
                $value
                &&
                $value !== 'no'
            ){
                return 'giftcard_meta';
            } // END if
        } // END foreach
    } // END foreach

    return '';
} // END function free_shipment_progressbar_get_giftcard_excluded_reason()

function free_shipment_progressbar_is_giftcard_product(
    $product
){

    return
        free_shipment_progressbar_get_giftcard_excluded_reason($product)
        !== '';
} // END function free_shipment_progressbar_is_giftcard_product()

function free_shipment_progressbar_get_debug_excluded_cart_items(){

    if(
        get_option('free_shipment_progressbar_debug', 'no') !== 'yes'
        ||
        !WC()->cart
    ){
        return [];
    } // END if

    $items = [];

    foreach(
        WC()->cart->get_cart() as $cart_item_key => $cart_item
    ){
        $product =
            $cart_item['data'] ?? null;

        $excluded_reason =
            free_shipment_progressbar_get_giftcard_excluded_reason($product);

        if(
            $excluded_reason === ''
        ){
            continue;
        } // END if

        $items[] = [
            'cart_item_key' => $cart_item_key,
            'product_id' => absint($cart_item['product_id'] ?? 0),
            'variation_id' => absint($cart_item['variation_id'] ?? 0),
            'quantity' => absint($cart_item['quantity'] ?? 0),
            'name' => $product ? $product->get_name() : '',
            'excluded_reason' => $excluded_reason,
        ];
    } // END foreach

    return $items;
} // END function free_shipment_progressbar_get_debug_excluded_cart_items()

function free_shipment_progressbar_get_shipping_eligible_cart_totals(){

    $totals = [
        'eligible_total' => 0.0,
        'eligible_subtotal' => 0.0,
        'eligible_subtotal_tax' => 0.0,
        'excluded_giftcard_total' => 0.0,
        'excluded_giftcard_subtotal' => 0.0,
        'excluded_giftcard_subtotal_tax' => 0.0,
        'eligible_cart_count' => 0,
        'excluded_giftcard_count' => 0,
        'has_only_giftcards' => false,
    ];

    if(
        !WC()->cart
    ){
        return $totals;
    } // END if

    foreach(
        WC()->cart->get_cart() as $cart_item
    ){
        $product =
            $cart_item['data'] ?? null;

        $quantity =
            isset($cart_item['quantity'])
            ? (int) $cart_item['quantity']
            : 0;

        $line_subtotal =
            isset($cart_item['line_subtotal'])
            ? (float) $cart_item['line_subtotal']
            : 0.0;

        $line_subtotal_tax =
            isset($cart_item['line_subtotal_tax'])
            ? (float) $cart_item['line_subtotal_tax']
            : 0.0;

        $line_total =
            $line_subtotal
            +
            $line_subtotal_tax;

        if(
            free_shipment_progressbar_is_giftcard_product($product)
        ){
            $totals['excluded_giftcard_subtotal'] += $line_subtotal;
            $totals['excluded_giftcard_subtotal_tax'] += $line_subtotal_tax;
            $totals['excluded_giftcard_total'] += $line_total;
            $totals['excluded_giftcard_count'] += max(0, $quantity);

            continue;
        } // END if

        $totals['eligible_subtotal'] += $line_subtotal;
        $totals['eligible_subtotal_tax'] += $line_subtotal_tax;
        $totals['eligible_total'] += $line_total;
        $totals['eligible_cart_count'] += max(0, $quantity);
    } // END foreach

    $totals['has_only_giftcards'] =
        $totals['excluded_giftcard_count'] > 0
        &&
        $totals['eligible_cart_count'] <= 0;

    return $totals;
} // END function free_shipment_progressbar_get_shipping_eligible_cart_totals()

function free_shipment_progressbar_get_cart_context(){

    $category_ids = [];
    $product_ids = [];

    foreach(
        WC()->cart->get_cart() as $cart_item
    ){
        $product_id = absint($cart_item['product_id'] ?? 0);
        $variation_id = absint($cart_item['variation_id'] ?? 0);
        $product =
            $cart_item['data'] ?? null;

        if(
            free_shipment_progressbar_is_giftcard_product($product)
        ){
            continue;
        } // END if

        if(
            $product_id
        ){
            $product_ids[] = $product_id;
            $product_ids[] =
                free_shipment_progressbar_translate_object_id(
                    $product_id,
                    'product',
                    free_shipment_progressbar_get_source_language()
                );
        } // END if

        if(
            $variation_id
        ){
            $product_ids[] = $variation_id;
            $product_ids[] =
                free_shipment_progressbar_translate_object_id(
                    $variation_id,
                    'product_variation',
                    free_shipment_progressbar_get_source_language()
                );
        } // END if

        $terms =
            get_the_terms(
                $product_id,
                'product_cat'
            );

        if(
            empty($terms)
            || is_wp_error($terms)
        ){
            continue;
        } // END if

        foreach(
            $terms as $term
        ){
            $category_ids[] =
                free_shipment_progressbar_translate_object_id(
                    $term->term_id,
                    'product_cat',
                    free_shipment_progressbar_get_source_language()
                );
        } // END foreach
    } // END foreach

    return [
        'category_ids' => array_values(array_unique(array_map('absint', $category_ids))),
        'product_ids' => array_values(array_unique(array_map('absint', $product_ids))),
    ];
} // END function free_shipment_progressbar_get_cart_context()

function free_shipment_progressbar_prepare_upsell_product(
    $product
){

    if(
        !$product
        || free_shipment_progressbar_is_giftcard_product($product)
        || free_shipment_progressbar_is_wcpos_pos_only_product($product)
        || !$product->is_in_stock()
        || (float) $product->get_price() <= 0.01
    ){
        return null;
    } // END if

    $image_id =
        $product->get_image_id();

    if(
        !$image_id
        && $product->get_parent_id()
    ){
        $image_id =
            get_post_thumbnail_id(
                $product->get_parent_id()
            );
    } // END if

    return [
        'id' => $product->get_id(),
        'name' => free_shipment_progressbar_get_carousel_product_name($product),
        'price' => html_entity_decode(
            wp_strip_all_tags(
                wc_price(
                    $product->get_price()
                )
            )
        ),
        'image' => wp_get_attachment_image_url(
            $image_id,
            'thumbnail'
        ),
    ];
} // END function free_shipment_progressbar_prepare_upsell_product()

function free_shipment_progressbar_get_carousel_product_name(
    $product
){

    if(
        !$product
    ){
        return '';
    } // END if

    $name =
        wp_strip_all_tags(
            $product->get_name()
        );

    if(
        !$product->is_type('variation')
    ){
        return $name;
    } // END if

    if(
        !preg_match(
            '/^(.*?)\s*\((.*)\)\s*-\s*(.+)$/u',
            $name,
            $matches
        )
    ){
        return $name;
    } // END if

    $base_name =
        trim($matches[1]);

    $variation_label =
        trim($matches[3]);

    if(
        $base_name === ''
        || $variation_label === ''
    ){
        return $name;
    } // END if

    return $base_name . ' (' . $variation_label . ')';
} // END function free_shipment_progressbar_get_carousel_product_name()

function free_shipment_progressbar_get_upsells(
    $limit = 15
){

    if(
        is_null(WC()->cart)
        && function_exists('wc_load_cart')
    ){
        wc_load_cart();
    } // END if

    if(
        !WC()->cart
        || WC()->cart->is_empty()
    ){
        return [];
    } // END if

    $eligible_totals =
        free_shipment_progressbar_get_shipping_eligible_cart_totals();

    if(
        !empty($eligible_totals['has_only_giftcards'])
        ||
        (int) $eligible_totals['eligible_cart_count'] <= 0
    ){
        return [];
    } // END if

    $language =
        free_shipment_progressbar_get_current_language();

    $context =
        free_shipment_progressbar_get_cart_context();

    $exclude_ids =
        $context['product_ids'];

    $target_categories = [];
    $target_products = [];
    $candidate_priority = [];
    $category_priority = [];

    foreach(
        free_shipment_progressbar_get_upsell_rules() as $rule
    ){
        if(
            empty($rule['enabled'])
        ){
            continue;
        } // END if

        $category_match =
            !empty($rule['source_categories'])
            && array_intersect(
                $rule['source_categories'],
                $context['category_ids']
            );

        $product_match =
            !empty($rule['source_products'])
            && array_intersect(
                $rule['source_products'],
                $context['product_ids']
            );

        if(
            !$category_match
            && !$product_match
        ){
            continue;
        } // END if

        foreach(
            $rule['target_categories'] as $category_id
        ){
            $translated_id =
                free_shipment_progressbar_translate_object_id(
                    $category_id,
                    'product_cat',
                    $language
                );

            $target_categories[] = $translated_id;
            $category_priority[$translated_id] =
                max(
                    $category_priority[$translated_id] ?? PHP_INT_MIN,
                    (int) $rule['priority']
                );
        } // END foreach

        foreach(
            $rule['target_products'] as $product_id
        ){
            $translated_id =
                free_shipment_progressbar_translate_object_id(
                    $product_id,
                    'product',
                    $language
                );

            if(
                in_array($translated_id, $exclude_ids, true)
            ){
                continue;
            } // END if

            $target_products[] = $translated_id;
            $candidate_priority[$translated_id] =
                max(
                    $candidate_priority[$translated_id] ?? PHP_INT_MIN,
                    (int) $rule['priority']
                );
        } // END foreach
    } // END foreach

    $products_by_id = [];

    foreach(
        array_values(array_unique($target_products)) as $product_id
    ){
        $product =
            wc_get_product($product_id);

        if(
            !$product
        ){
            continue;
        } // END if

        if(
            $product->is_type('variable')
        ){
            foreach(
                $product->get_children() as $variation_id
            ){
                if(
                    in_array($variation_id, $exclude_ids, true)
                ){
                    continue;
                } // END if

                $variation =
                    wc_get_product($variation_id);

                if(
                    $variation
                ){
                    $products_by_id[$variation->get_id()] = $variation;
                    $candidate_priority[$variation->get_id()] =
                        $candidate_priority[$product->get_id()] ?? 0;
                } // END if
            } // END foreach
        }else{
            $products_by_id[$product->get_id()] = $product;
        } // END if
    } // END foreach

    $target_categories =
        array_values(
            array_unique(
                array_filter(
                    array_map('absint', $target_categories)
                )
            )
        );

    if(
        $target_categories
    ){
        $query = new WC_Product_Query([
            'status' => 'publish',
            'limit' => 40,
            'stock_status' => 'instock',
            'exclude' => $exclude_ids,
            'return' => 'objects',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $target_categories,
                    'operator' => 'IN',
                ],
            ],
        ]);

        foreach(
            $query->get_products() as $product
        ){
            if(
                $product->is_type('variable')
            ){
                foreach(
                    $product->get_children() as $variation_id
                ){
                    if(
                        in_array($variation_id, $exclude_ids, true)
                    ){
                        continue;
                    } // END if

                    $variation =
                        wc_get_product($variation_id);

                    if(
                        $variation
                    ){
                        $products_by_id[$variation->get_id()] = $variation;
                    } // END if
                } // END foreach
            }else{
                $products_by_id[$product->get_id()] = $product;
            } // END if

            $terms =
                get_the_terms(
                    $product->get_id(),
                    'product_cat'
                );

            if(
                empty($terms)
                || is_wp_error($terms)
            ){
                continue;
            } // END if

            foreach(
                $terms as $term
            ){
                $term_id =
                    free_shipment_progressbar_translate_object_id(
                        $term->term_id,
                        'product_cat',
                        $language
                    );

                if(
                    !isset($category_priority[$term_id])
                ){
                    continue;
                } // END if

                $matched_priority =
                    $category_priority[$term_id];

                $candidate_priority[$product->get_id()] =
                    max(
                        $candidate_priority[$product->get_id()] ?? PHP_INT_MIN,
                        $matched_priority
                    );

                if(
                    $product->is_type('variable')
                ){
                    foreach(
                        $product->get_children() as $variation_id
                    ){
                        $candidate_priority[$variation_id] =
                            max(
                                $candidate_priority[$variation_id] ?? PHP_INT_MIN,
                                $matched_priority
                            );
                    } // END foreach
                } // END if
            } // END foreach
        } // END foreach
    } // END if

    $products =
        array_values($products_by_id);

    usort(
        $products,
        function($a, $b) use ($candidate_priority){
            $a_priority =
                $candidate_priority[$a->get_id()] ?? 0;

            $b_priority =
                $candidate_priority[$b->get_id()] ?? 0;

            if(
                $a_priority === $b_priority
            ){
                return strcasecmp(
                    $a->get_name(),
                    $b->get_name()
                );
            } // END if

            return $b_priority <=> $a_priority;
        }
    );

    $upsells = [];

    foreach(
        $products as $product
    ){
        $prepared =
            free_shipment_progressbar_prepare_upsell_product($product);

        if(
            !$prepared
        ){
            continue;
        } // END if

        $upsells[] = $prepared;

        if(
            count($upsells) >= $limit
        ){
            break;
        } // END if
    } // END foreach

    if(
        empty($upsells)
    ){
        $upsells =
            free_shipment_progressbar_get_fallback_upsells(
                $limit,
                $exclude_ids
            );
    } // END if

    return $upsells;
} // END function free_shipment_progressbar_get_upsells()

function free_shipment_progressbar_get_fallback_upsells(
    $limit = 15,
    $exclude_ids = []
){

    /*
     * If no rule-based upsells match, keep the bar useful by showing
     * random sellable online products. Product preparation still filters
     * out POS-only products, out-of-stock products, and zero-price items.
     */

    $query = new WC_Product_Query([
        'status' => 'publish',
        'limit' => max(30, $limit * 3),
        'stock_status' => 'instock',
        'exclude' => array_values(array_unique(array_map('absint', $exclude_ids))),
        'orderby' => 'rand',
        'return' => 'objects',
    ]);

    $upsells = [];

    foreach(
        $query->get_products() as $product
    ){

        if(
            $product->is_type('variable')
        ){
            foreach(
                $product->get_children() as $variation_id
            ){

                if(
                    in_array($variation_id, $exclude_ids, true)
                ){
                    continue;
                } // END if

                $prepared =
                    free_shipment_progressbar_prepare_upsell_product(
                        wc_get_product($variation_id)
                    );

                if(
                    !$prepared
                ){
                    continue;
                } // END if

                $upsells[] = $prepared;

                if(
                    count($upsells) >= $limit
                ){
                    return $upsells;
                } // END if
            } // END foreach

            continue;
        } // END if

        $prepared =
            free_shipment_progressbar_prepare_upsell_product($product);

        if(
            !$prepared
        ){
            continue;
        } // END if

        $upsells[] = $prepared;

        if(
            count($upsells) >= $limit
        ){
            break;
        } // END if
    } // END foreach

    return $upsells;
} // END function free_shipment_progressbar_get_fallback_upsells()

function free_shipment_progressbar_get_customer_address(){

    $address = [
        'country' => '',
        'postcode' => '',
        'city' => '',
        'state' => '',
    ];

    $customer =
        function_exists('WC') && WC()->customer
        ? WC()->customer
        : null;

    if ($customer) {

        $address['country'] =
            $customer->get_shipping_country()
            ?: $customer->get_billing_country()
            ?: '';

        $address['postcode'] =
            $customer->get_shipping_postcode()
            ?: $customer->get_billing_postcode()
            ?: '';

        $address['city'] =
            $customer->get_shipping_city()
            ?: $customer->get_billing_city()
            ?: '';

        $address['state'] =
            $customer->get_shipping_state()
            ?: $customer->get_billing_state()
            ?: '';
    } // END if

    $user_id =
        get_current_user_id();

    if ($user_id > 0) {

        if (!$address['country']) {

            $address['country'] =
                get_user_meta($user_id, 'shipping_country', true)
                ?: get_user_meta($user_id, 'billing_country', true)
                ?: '';
        } // END if

        if (!$address['postcode']) {

            $address['postcode'] =
                get_user_meta($user_id, 'shipping_postcode', true)
                ?: get_user_meta($user_id, 'billing_postcode', true)
                ?: '';
        } // END if

        if (!$address['city']) {

            $address['city'] =
                get_user_meta($user_id, 'shipping_city', true)
                ?: get_user_meta($user_id, 'billing_city', true)
                ?: '';
        } // END if

        if (!$address['state']) {

            $address['state'] =
                get_user_meta($user_id, 'shipping_state', true)
                ?: get_user_meta($user_id, 'billing_state', true)
                ?: '';
        } // END if
    } // END if

    $address['country'] =
        $address['country'] ?: 'NL';

    return array_map(
        'sanitize_text_field',
        $address
    );
} // END function free_shipment_progressbar_get_customer_address()



/* =========================================================
 * CENTRAL SHIPPING DATA
========================================================= */

function free_shipment_progressbar_get_shipping_data(
    $package = []
){

    /*
     * WooCommerce check
     */

    if(
        !class_exists('WooCommerce')
    ){
        return [
            'zone' => null,
            'minimum' => 0,
            'has_free_shipping' => false
        ];
    } // END if

    /*
     * Cart check
     */

    if(
        is_null(WC()->cart)
    ){
        wc_load_cart();
    } // END if

    /*
     * Default package
     */

    if(
        empty($package)
    ){

        $address =
            free_shipment_progressbar_get_customer_address();

        $package = [

            'destination' => [

                'country'  => $address['country'],
                'postcode' => $address['postcode'],
                'city'     => $address['city'],
                'state'    => $address['state'],
            ]
        ];
    } // END if

    /*
     * Shipping zone
     */

$zone =
    wc_get_shipping_zone($package);

    if(
        !$zone
    ){
        return [
            'zone' => null,
            'minimum' => 0,
            'has_free_shipping' => false
        ];
    } // END if

    /*
     * Find lowest free shipping threshold
     */

    $min = 0;

    foreach(
        $zone->get_shipping_methods(true)
        as $method
    ){

        /*
         * Native WooCommerce free shipping
         */

        if(
            $method->id === 'free_shipping'
            && $method->enabled === 'yes'
        ){

            $minimum =
                isset($method->min_amount)
                ? (float) $method->min_amount
                : 0;

            if(
                $minimum > 0
                && (
                    $min <= 0
                    || $minimum < $min
                )
            ){
                $min = $minimum;
            } // END if

            continue;
        } // END if

        /*
         * WBSNG
         */

        if(
            $method->id !== 'wbsng'
            || $method->enabled !== 'yes'
        ){
            continue;
        } // END if

        $config =
            get_option(
                'wbsng_' .
                $method->instance_id .
                '_config'
            );

        free_shipment_progressbar_debug_log(
            'WBSNG CONFIG:',
            $config
        );

        free_shipment_progressbar_debug_log(
            'WBSNG METHOD ID:',
            $method->instance_id
        );

        free_shipment_progressbar_debug_log(
            'WBSNG METHOD OBJECT:',
            $method
        );
		
        if(
            empty($config['methods'])
            || !is_array($config['methods'])
        ){
            continue;
        } // END if

        foreach(
            $config['methods']
            as $shipping_method
        ){

            if(
                empty($shipping_method['rules'])
                || !is_array($shipping_method['rules'])
            ){
                continue;
            } // END if

            foreach(
    $shipping_method['rules']
    as $rule
){

    free_shipment_progressbar_debug_log(
        'FREE SHIPPING RULE:',
        $rule
    );

/*
 * Gratis verzending detecteren
 */

$is_free_shipping = false;

/*
 * Expliciet gratis
 */

if(
    isset($rule['charge'])
    && isset($rule['charge']['rate'])
){

    $rate =
        trim(
            (string) $rule['charge']['rate']
        );

    if(
        $rate === '0'
        || $rate === '0.0'
    ){
        $is_free_shipping = true;
    } // END if
} // END if

/*
 * OF:
 * geen charge aanwezig
 * = gratis verzending
 */

if(
    !isset($rule['charge'])
){
    $is_free_shipping = true;
} // END if

if(
    !$is_free_shipping
){
    continue;
} // END if

/*
 * Minimum threshold
 */

$minimum =
    isset($rule['price']['min'])
    ? (float) $rule['price']['min']
    : 0;

    free_shipment_progressbar_debug_log(
        'FREE SHIPPING MINIMUM:',
        $minimum
    );

    if(
        $minimum <= 0
    ){
        continue;
    } // END if

    /*
     * Save lowest threshold
     */

    if(
        $min <= 0
        || $minimum < $min
    ){

        $min = $minimum;

        free_shipment_progressbar_debug_log(
            'FREE SHIPPING FOUND:',
            $min
        );
    } // END if

            } // END foreach
        } // END foreach
    } // END foreach

$debug_methods = [];

foreach(
    $zone->get_shipping_methods(true)
    as $method
){
$config =
    get_option(
        'wbsng_' .
        $method->instance_id .
        '_config'
    );
$debug_methods[] = [

    'id' => $method->id ?? '',

    'instance_id' => $method->instance_id ?? '',

    'title' => $method->title ?? '',

    'enabled' => $method->enabled ?? '',

    'config' => json_decode(
    json_encode($config),
    true
)
];
} // END foreach
	
return [

    'zone' => [],

    'minimum' => $min,

    'has_free_shipping' => $min > 0,

    'debug_methods' => $debug_methods
];
} // END function free_shipment_progressbar_get_shipping_data()

function free_shipment_progressbar_get_shipping_saving_amount(
    $package = []
){

    /*
     * Shipping methods store their configured prices excluding tax.
     * WooCommerce calculated rates include the matching shipping taxes,
     * so this helper returns the lowest paid shipping amount including tax.
     */

    if(
        !class_exists('WooCommerce')
        || is_null(WC()->cart)
        || !WC()->shipping()
    ){
        return 0;
    } // END if

    $packages =
        WC()->cart->get_shipping_packages();

    if(
        empty($packages)
        || !is_array($packages)
    ){
        return 0;
    } // END if

    if(
        !empty($package['destination'])
        && is_array($package['destination'])
    ){
        foreach(
            $packages as $index => $cart_package
        ){
            $packages[$index]['destination'] =
                array_merge(
                    $cart_package['destination'] ?? [],
                    $package['destination']
                );
        } // END foreach
    } // END if

    $calculated_packages =
        WC()->shipping()->calculate_shipping(
            $packages
        );

    $lowest_amount = 0;

    foreach(
        $calculated_packages as $calculated_package
    ){

        if(
            empty($calculated_package['rates'])
            || !is_array($calculated_package['rates'])
        ){
            continue;
        } // END if

        foreach(
            $calculated_package['rates']
            as $rate
        ){

            if(
                !($rate instanceof WC_Shipping_Rate)
            ){
                continue;
            } // END if

            if(
                in_array(
                    $rate->get_method_id(),
                    [
                        'free_shipping',
                        'local_pickup',
                    ],
                    true
                )
            ){
                continue;
            } // END if

            $amount =
                (float) $rate->get_cost()
                +
                array_sum(
                    array_map(
                        'floatval',
                        (array) $rate->get_taxes()
                    )
                );

            if(
                $amount <= 0
            ){
                continue;
            } // END if

            if(
                $lowest_amount <= 0
                || $amount < $lowest_amount
            ){
                $lowest_amount = $amount;
            } // END if
        } // END foreach
    } // END foreach

    return $lowest_amount;
} // END function free_shipment_progressbar_get_shipping_saving_amount()


/* =========================================================
 * FILTER SHIPPING RATES
========================================================= */

add_filter(
    'woocommerce_package_rates',
    'free_shipment_progressbar_force_wbsng_free_shipping',
    9999,
    2
);

function free_shipment_progressbar_force_wbsng_free_shipping(
    $rates,
    $package
){

    if (
        !WC()->cart
    ) {
        return $rates;
    } // END if

    $shipping_data =
        free_shipment_progressbar_get_shipping_data(
            $package
        );

    $minimum =
        (float) (
            $shipping_data['minimum']
            ?? 0
        );

    if (
        $minimum <= 0
    ) {
        return $rates;
    } // END if

    $eligible_totals =
        free_shipment_progressbar_get_shipping_eligible_cart_totals();

    $total =
        (float) $eligible_totals['eligible_total'];

    if (
        $total < $minimum
    ) {
        return $rates;
    } // END if

    foreach (
        $rates as $rate_id => $rate
    ) {

        /*
         * Alleen WBSNG
         */

        if (
            $rate->method_id !== 'wbsng'
        ) {
            continue;
        } // END if

        /*
         * Hard override
         */

        $rate->cost = 0;

        /*
         * Taxes reset
         */

        $rate->taxes = [];

        /*
         * Label aanpassen
         */

        if (
            !str_contains(
                strtolower($rate->label),
                'gratis'
            )
        ) {

            $rate->label .= ' (Gratis)';
        } // END if

        /*
         * Meta data
         */

        $rate->add_meta_data(
            'free_shipment_progressbar_free_shipping',
            'yes',
            true
        );

        /*
         * Overschrijven
         */

        $rates[$rate_id] = $rate;

    } // END foreach

    return $rates;

} // END function

/* =========================================================
 * FRONTEND BAR
========================================================= */

add_action('wp_footer', function () {

    if(
        is_admin()
        || is_order_received_page()
    ){
        return;
    } // END if

?>

<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --fsb-height: 0px;
    --fsb-admin-offset: 0px;
}

/*
 * WordPress adminbar staat fixed boven de site.
 * Als een beheerder is ingelogd, schuiven de free shipping bar
 * en de Sydney headers onder de adminbar mee.
 */

body.admin-bar {

    --fsb-admin-offset: 32px;
}

@media screen and (max-width: 782px) {

    body.admin-bar {

        --fsb-admin-offset: 46px;
    }
}

/* =========================================
   CART COUNTER PULSE
========================================= */

.count-number.pulse {

    animation:
        free-shipment-progressbar-cart-pulse
        0.35s ease;
}

@keyframes free-shipment-progressbar-cart-pulse {

    0% {

        transform: scale(1);
    }

    50% {

        transform: scale(1.35);
    }

    100% {

        transform: scale(1);
    }
}
	
	
/* =========================================================
   FREE SHIPPING BAR
========================================================= */

#free-shipping-bar {

    position: sticky;

    top: var(--fsb-admin-offset);

    width: 100%;

    z-index: 1000;

    background: #111;
    color: #fff;

    padding: 14px 20px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    box-sizing: border-box;

    margin-bottom: 0 !important;
}

/* =========================================================
   DESKTOP HEADER
========================================================= */

#masthead {

    position: sticky !important;

    top: calc(
        var(--fsb-admin-offset)
        +
        max(
            0px,
            var(--fsb-height) - 1px
        )
    ) !important;

    z-index: 990 !important;

    overflow: visible !important;

    margin-top: 0 !important;
}

/* =========================================================
   MOBILE HEADER
========================================================= */

#masthead-mobile {

    position: sticky !important;

    top: calc(
        var(--fsb-admin-offset)
        +
        max(
            0px,
            var(--fsb-height) - 1px
        )
    ) !important;

    z-index: 990 !important;

    overflow: visible !important;

    margin-top: 0 !important;
}

/* =========================================================
   HERO / HEADER IMAGE
========================================================= */

.sydney-hero-area,
.custom-header,
.page-header,
.header-image {

    position: relative;

    z-index: 1;
}

/* =========================================================
   MESSAGE
========================================================= */

#free-shipping-bar .msg {

    width: 100%;
    max-width: 100%;

    text-align: center;

    font-size: clamp(13px, 1.4vw, 20px);

    font-weight: 700;

    line-height: 1.2;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* =========================================================
   PROGRESS
========================================================= */

#free-shipping-bar .progress {

    width: 100%;
    max-width: 700px;

    height: 6px;

    margin-top: 10px;

    background: rgba(255,255,255,.2);

    border-radius: 999px;

    overflow: hidden;
}

#free-shipping-bar .bar {

    width: 0%;
    height: 100%;

    min-width: 6px;

    transition: width .4s ease;

    background: linear-gradient(
        90deg,
        #4CAF50,
        #8BC34A
    );
}

#free-shipping-bar .bar.complete {

    background: linear-gradient(
        90deg,
        #43A047,
        #2E7D32
    );
}

/* =========================================================
   UPSELL WRAPPER
========================================================= */

#free-shipping-bar .upsells-wrapper {

    width: 100%;
    min-height: 78px;

    display: flex;
    align-items: center;

    gap: 14px;

    margin-top: 16px;
}

#free-shipping-bar .upsells {

    flex: 1;

    display: flex;

    gap: 14px;

    overflow: hidden;
}

/* =========================================================
   ARROWS
========================================================= */

#free-shipping-bar .upsells-arrow {

    width: 42px;
    height: 42px;

    border: 0;

    border-radius: 999px;

    background: rgba(255,255,255,.12);

    color: #fff;

    font-size: 28px;

    cursor: pointer;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;
}

/* =========================================================
   CARD
========================================================= */

#free-shipping-bar .upsell {

    flex: 0 0 calc((100% - 28px) / 3);

    min-width: 0;
    min-height: 78px;

    display: flex;
    align-items: center;

    gap: 12px;

    padding: 10px 12px;

    border-radius: 16px;

    background: rgba(255,255,255,.08);

    border: 1px solid rgba(255,255,255,.08);

    backdrop-filter: blur(8px);
}

#free-shipping-bar .upsell img {

    width: 52px;
    height: 52px;

    object-fit: cover;

    border-radius: 999px;

    background: #fff;

    flex-shrink: 0;
}

#free-shipping-bar .upsell-info {

    flex: 1;

    min-width: 0;

    display: flex;
    flex-direction: column;
}

#free-shipping-bar .upsell-title {

    color: #fff;

    font-size: 13px;

    font-weight: 700;

    line-height: 1.25;
    min-height: 32.5px;

    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    overflow: hidden;
}

#free-shipping-bar .upsell-price {

    margin-top: 4px;

    color: rgba(255,255,255,.75);

    font-size: 12px;
}

#free-shipping-bar .upsell-btn {

    border: 0;

    border-radius: 999px;

    padding: 9px 14px;

    background: linear-gradient(
        135deg,
        #4CAF50,
        #66BB6A
    );

    color: #fff;

    font-size: 12px;

    font-weight: 700;

    white-space: nowrap;

    cursor: pointer;

    flex-shrink: 0;
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 1024px) {

    /* =========================================
       SIDEBAR CART
    ========================================= */

    #sidebar-cart,
    #sidebar-cart.is-open {

        z-index: 4000 !important;
    }

    .cart-overlay.show-overlay {

        z-index: 3999 !important;
    }

    /* =========================================
       MOBILE MENU
    ========================================= */

    .offcanvas-menu,
    .offcanvas-menu-wrap,
    .sydney-offcanvas-menu,
    .mobile-menu-container {

        z-index: 3000 !important;
    }

    /* =========================================
       FREE SHIPPING BAR
    ========================================= */

    #free-shipping-bar {

        padding: 10px 12px;
    }

    #free-shipping-bar .msg {

        font-size: clamp(12px, 3.3vw, 17px);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* =========================================
       MOBILE UPSELLS
    ========================================= */

    #free-shipping-bar .upsells-arrow {

        display: none;
    }

    #free-shipping-bar .upsells {

        width: 100%;

        display: flex !important;

        flex-wrap: nowrap !important;

        overflow-x: auto !important;

        overflow-y: hidden !important;

        gap: 12px;

        margin-top: 16px;

        padding-bottom: 4px;

        -webkit-overflow-scrolling: touch;

        scrollbar-width: none;

        scroll-snap-type: x mandatory;
    }

    #free-shipping-bar .upsells::-webkit-scrollbar {

        display: none;
    }

    #free-shipping-bar .upsell {

        width: 100%;

        min-width: 100%;

        max-width: 100%;

        flex: 0 0 auto;

        scroll-snap-align: center;
    }
}

</style>
	
<script>

(function(){

const DEBUG = !!(window.fsb_ajax && window.fsb_ajax.debug);

function debugLog(...args) {

    if (DEBUG) {

        console.log(...args);

    } /* END if (DEBUG) */

} /* END function debugLog() */

function hideBar() {

    const existingBar =
        document.getElementById(
            'free-shipping-bar'
        );

    if (existingBar) {

        existingBar.remove();
    } // END if

    document.documentElement.style.setProperty(
        '--fsb-height',
        '0px'
    );
} // END function hideBar()

function escapeHtml(value) {

    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
} // END function escapeHtml()

function safeImage(value) {

    const url = String(value || '');

    if (
        url.startsWith('http://')
        ||
        url.startsWith('https://')
        ||
        url.startsWith('/')
    ) {
        return url;
    } // END if

    return '';
} // END function safeImage()

function normalizeCartAddress(address) {

    address =
        address || {};

    return {
        country: String(address.country || 'NL').trim() || 'NL',
        postcode: String(address.postcode || '').trim(),
        city: String(address.city || '').trim()
    };
} // END function normalizeCartAddress()

function getCartAddressStorageKey() {

    return fsb_ajax.is_logged_in
        ? 'free_shipment_progressbar_account_address'
        : 'free_shipment_progressbar_checkout_address';
} // END function getCartAddressStorageKey()

function cleanupLegacyStoredAddress() {

    try {
        window.localStorage
            ?.removeItem('free_shipment_progressbar_cart_address');
    } catch(e) {}
} // END function cleanupLegacyStoredAddress()

function getStoredCartAddress() {

    try {

        cleanupLegacyStoredAddress();

        const raw =
            window.localStorage
                ?.getItem(
                    getCartAddressStorageKey()
                );

        if (!raw) {
            return null;
        } // END if

        const address =
            normalizeCartAddress(
                JSON.parse(raw)
            );

        if (
            !address.postcode
            &&
            !address.city
        ) {
            return null;
        } // END if

        return address;

    } catch(e) {
        return null;
    } // END try
} // END function getStoredCartAddress()

function storeCartAddress(address) {

    address =
        normalizeCartAddress(address);

    if (
        !address.postcode
        &&
        !address.city
    ) {
        return;
    } // END if

    try {

        window.localStorage
            ?.setItem(
                getCartAddressStorageKey(),
                JSON.stringify(address)
            );

    } catch(e) {}
} // END function storeCartAddress()

function getVisibleCartItemCount() {

    const cartRoot =
        document.querySelector(
            '.wc-block-cart-items, .woocommerce-cart-form'
        );

    if (!cartRoot) {
        return null;
    } // END if

    const quantityFields =
        cartRoot.querySelectorAll(
            '.wc-block-components-quantity-selector__input, input.qty'
        );

    if (!quantityFields.length) {
        return null;
    } // END if

    let total = 0;

    quantityFields.forEach(function(field){

        const quantity =
            Number(field.value || 0);

        if (!Number.isNaN(quantity)) {
            total += quantity;
        } // END if
    });

    return total;
} // END function getVisibleCartItemCount()

function getCartItemCount(cart) {

    if (isCartVisiblyEmpty()) {
        return 0;
    } // END if

    const visibleCount =
        getVisibleCartItemCount();

    if (
        visibleCount !== null
        &&
        visibleCount > 0
    ) {
        return visibleCount;
    } // END if

    const serverCount =
        Number(window.free_shipment_progressbarServerCartCount);

    if (
        !Number.isNaN(serverCount)
        &&
        serverCount > 0
    ) {
        return serverCount;
    } // END if

    if (Array.isArray(cart?.items)) {

        const itemTotal =
            cart.items.reduce(function(total, item){

            const quantity =
                Number(
                    item?.quantity
                    ?? item?.qty
                    ?? 1
                );

            return total + (
                Number.isNaN(quantity)
                ? 0
                : quantity
            );

        }, 0);

        if (itemTotal > 0) {
            return itemTotal;
        } // END if
    } // END if

    const directCount =
        cart?.itemsCount
        ?? cart?.items_count
        ?? cart?.item_count;

    if (
        directCount !== undefined
        &&
        directCount !== null
        &&
        !Number.isNaN(Number(directCount))
    ) {
        return Number(directCount);
    } // END if

    return 0;
} // END function getCartItemCount()

function isCartVisiblyEmpty() {

    if (
        document.querySelector(
            '.wc-block-cart__empty-cart, .woocommerce-cart .cart-empty, .woocommerce-cart .wc-empty-cart-message'
        )
    ) {
        return true;
    } // END if

    return (
        document.body.classList.contains('woocommerce-cart')
        &&
        document.body.textContent.includes(
            'Your cart is currently empty'
        )
    );
} // END function isCartVisiblyEmpty()

function getBlocksCartData() {

    if (
        !window.wp
        ||
        !wp.data
        ||
        !wp.data.select
    ) {
        return null;
    } // END if

    try {

        const cartStore =
            wp.data.select('wc/store/cart');

        if (
            !cartStore
            ||
            typeof cartStore.getCartData !== 'function'
        ) {
            return null;
        } // END if

        return cartStore.getCartData();

    } catch(e) {
        return null;
    } // END try
} // END function getBlocksCartData()

function getBlocksStoreAddress() {

    if (
        !window.wp
        ||
        !wp.data
        ||
        !wp.data.select
    ) {
        return null;
    } // END if

    const candidates = [];

    try {

        const checkoutStore =
            wp.data.select('wc/store/checkout');

        if (checkoutStore) {

            [
                'getShippingAddress',
                'getBillingAddress'
            ].forEach(function(method){

                if (
                    typeof checkoutStore[method]
                    === 'function'
                ) {
                    candidates.push(
                        checkoutStore[method]()
                    );
                } // END if
            });
        } // END if
    } catch(e) {}

    try {

        const cartStore =
            wp.data.select('wc/store/cart');

        if (cartStore) {

            if (
                typeof cartStore.getCartData
                === 'function'
            ) {

                const cartData =
                    cartStore.getCartData();

                candidates.push(
                    cartData?.shippingAddress,
                    cartData?.billingAddress
                );
            } // END if

            if (
                typeof cartStore.getCustomerData
                === 'function'
            ) {

                const customerData =
                    cartStore.getCustomerData();

                candidates.push(
                    customerData?.shippingAddress,
                    customerData?.billingAddress
                );
            } // END if
        } // END if
    } catch(e) {}

    for (const candidate of candidates) {

        if (
            !candidate
            ||
            (
                !candidate.country
                &&
                !candidate.postcode
                &&
                !candidate.city
            )
        ) {
            continue;
        } // END if

        const address =
            normalizeCartAddress(candidate);

        if (
            address.country
            ||
            address.postcode
            ||
            address.city
        ) {
            return address;
        } // END if
    } // END for

    return null;
} // END function getBlocksStoreAddress()

function normalizeCheckoutCountry(value) {

    const country =
        String(value || '').trim();

    const normalized =
        country.toLowerCase();

    const countryMap = {
        nederland: 'NL',
        netherlands: 'NL',
        belgie: 'BE',
        'belgië': 'BE',
        belgium: 'BE',
        belgique: 'BE',
        deutschland: 'DE',
        duitsland: 'DE',
        germany: 'DE',
        frankrijk: 'FR',
        france: 'FR'
    };

    return countryMap[normalized]
        || country;
} // END function normalizeCheckoutCountry()

function getCheckoutFieldValue(selectors) {

    const roots = [
        document.querySelector('.wc-block-checkout'),
        document.querySelector('.wp-block-woocommerce-checkout'),
        document.querySelector('form.woocommerce-checkout'),
        document.body
    ].filter(Boolean);

    for (const root of roots) {

        for (const selector of selectors) {

            const field =
                root.querySelector(selector);

            if (!field) {
                continue;
            } // END if

            const value =
                field.value
                || field.getAttribute('value')
                || field.selectedOptions?.[0]?.value
                || field.selectedOptions?.[0]?.textContent
                || '';

            if (String(value || '').trim()) {
                return String(value).trim();
            } // END if
        } // END for
    } // END for

    return '';
} // END function getCheckoutFieldValue()

function getCheckoutDomAddress() {

    const country =
        normalizeCheckoutCountry(
            getCheckoutFieldValue([
                '#shipping-country',
                '#billing-country',
                '#shipping_country',
                '#billing_country',
                '#calc_shipping_country',
                '[name="shipping-country"]',
                '[name="billing-country"]',
                '[name="shipping_country"]',
                '[name="billing_country"]',
                '[name="country"]',
                '[autocomplete="shipping country"]',
                '[autocomplete="billing country"]'
            ])
        );

    const postcode =
        getCheckoutFieldValue([
            '#shipping-postcode',
            '#billing-postcode',
            '#shipping_postcode',
            '#billing_postcode',
            '#calc_shipping_postcode',
            '[name="shipping-postcode"]',
            '[name="billing-postcode"]',
            '[name="shipping_postcode"]',
            '[name="billing_postcode"]',
            '[name="postcode"]',
            '[autocomplete="shipping postal-code"]',
            '[autocomplete="billing postal-code"]'
        ]);

    const city =
        getCheckoutFieldValue([
            '#shipping-city',
            '#billing-city',
            '#shipping_city',
            '#billing_city',
            '#calc_shipping_city',
            '[name="shipping-city"]',
            '[name="billing-city"]',
            '[name="shipping_city"]',
            '[name="billing_city"]',
            '[name="city"]',
            '[autocomplete="shipping address-level2"]',
            '[autocomplete="billing address-level2"]'
        ]);

    const address =
        normalizeCartAddress({
            country,
            postcode,
            city
        });

    if (
        country
        ||
        postcode
        ||
        city
    ) {
        return address;
    } // END if

    return null;
} // END function getCheckoutDomAddress()

function setCartCounterValue(count) {

    const normalizedCount =
        String(
            Math.max(
                0,
                Number(count) || 0
            )
        );

    const selectors = [
        '.cart-count .count',
        '.cart-count .count-number',
        '.cart-contents .count',
        '.cart-contents .count-number',
        '.site-header-cart .count',
        '.site-header-cart .count-number',
        '.header-cart .count',
        '.header-cart .count-number',
        '.sydney-header-cart .count',
        '.sydney-header-cart .count-number',
        '.menu-item-cart .count',
        '.menu-item-cart .count-number',
        '#site-header-cart .count',
        '#site-header-cart .count-number',
        '#site-header-cart .cart-count .count',
        '#site-header-cart .cart-count .count-number',
        '#site-header-cart .cart-contents .count',
        '#site-header-cart .cart-contents .count-number',
        '.wc-block-mini-cart__badge'
    ];

    const badges =
        document.querySelectorAll(
            selectors.join(',')
        );

    badges.forEach(function(badge){

        if (
            badge.closest(
                '.wishlist, .yith-wcwl, .header-wishlist-icon, [class*="wishlist"], [class*="favorite"], [class*="favourite"]'
            )
        ) {
            return;
        } // END if

        if (
            badge.textContent.trim()
            === normalizedCount
        ) {
            return;
        } // END if

        badge.textContent =
            normalizedCount;

        badge.classList.remove(
            'pulse'
        );

        void badge.offsetWidth;

        badge.classList.add(
            'pulse'
        );
    });
} // END function setCartCounterValue()

	/*
 * Sydney cart counter sync
 */

function syncSydneyCartCounter() {

    if (
        !window.wp ||
        !wp.data
    ) {
        return;
    } // END if

    /*
     * Kleine delay zodat
     * Woo Blocks klaar is
     */

    setTimeout(() => {

        const cart =
            getBlocksCartData();

        if (!cart) {
            return;
        } // END if

        /*
         * Aantal producten
         */

const count =
    isCartVisiblyEmpty()
    ? 0
    : getCartItemCount(cart);

        if (
            count <= 0
            &&
            !isCartVisiblyEmpty()
        ) {
            return;
        } // END if

        /*
         * Zoek bestaande badge
         */

        let badge =
            document.querySelector(
                '.cart-count .count'
            );

        /*
         * Sydney fallback:
         * oudere themes
         */

        if (!badge) {

            badge =
                document.querySelector(
                    '.cart-count .count-number'
                );

        } // END if (!badge)

        /*
         * Geen badge gevonden?
         * Dan zelf aanmaken
         */

        if (!badge) {

            const wrapper =
                document.querySelector(
                    '.cart-count'
                );

            if (!wrapper) {
                return;
            } // END if

            badge =
                document.createElement(
                    'span'
                );

            badge.className =
                'count';

            wrapper.appendChild(
                badge
            );

        } // END if (!badge create)

        /*
         * Alleen nummer vervangen
         */

        setCartCounterValue(count);

		if (count <= 0) {

          hideBar();

        } // END if (count <= 0)

        /*
         * Pulse animatie
         */

        badge.classList.remove(
            'pulse'
        );

        void badge.offsetWidth;

        badge.classList.add(
            'pulse'
        );

        /*
         * Debug logging
         */

        if (DEBUG) {

            console.log(
                'COUNTER UPDATED:',
                count
            );

        } // END if (DEBUG)

    }, 500);

    /* END setTimeout */

} // END function syncSydneyCartCounter()
	
function initBar() {

    if (typeof fsb_ajax === 'undefined') {
        return;
    } // END if

    let loading = false;
    let pendingLoad = false;
    let storeSubscribed = false;

    const isCartPage =
        document.body.classList.contains(
            'woocommerce-cart'
        );

    function whenCartStoreReady(callback) {

        let tries = 0;

        const timer =
            setInterval(function(){

                tries++;

                let hasStore = false;

                try {

                    hasStore = !!(
                        window.wp
                        &&
                        wp.data
                        &&
                        wp.data.select
                        &&
                        wp.data.select('wc/store/cart')
                    );

                } catch(e) {}

                if (hasStore) {

                    clearInterval(timer);
                    callback();
                    return;
                } // END if

                if (tries >= 50) {
                    clearInterval(timer);
                } // END if
            }, 200);
    } // END function whenCartStoreReady()

    function isMobileProgressText() {

        return window.matchMedia(
            '(max-width: 640px)'
        ).matches;
    } // END function isMobileProgressText()

    function fitProgressMessage(messageElement) {

        if (
            !messageElement
            ||
            messageElement.style.display === 'none'
        ) {
            return;
        } // END if

        messageElement.style.fontSize = '';

        let currentSize =
            parseFloat(
                window.getComputedStyle(messageElement).fontSize
            ) || 16;

        while (
            messageElement.scrollWidth > messageElement.clientWidth
            &&
            currentSize > 10
        ) {
            currentSize -= 0.5;
            messageElement.style.fontSize =
                currentSize + 'px';
        } // END while
    } // END function fitProgressMessage()

    function showBar(message, percent, upsells = [], showProgress = true, mobileMessage = '') {

        let bar =
            document.getElementById(
                'free-shipping-bar'
            );

        /*
         * Nog niet aanwezig?
         */

        if (!bar) {

            bar =
                document.createElement(
                    'div'
                );

            bar.id =
                'free-shipping-bar';

bar.innerHTML = `

    <div class="msg"></div>

    <div class="progress">
        <div class="bar"></div>
    </div>

    <div class="upsells-wrapper">

        <button
            class="upsells-arrow prev"
            type="button"
            aria-label="<?php echo esc_attr__(
    'Previous',
    'free_shipment_progressbar'
); ?>"
        >
            ‹
        </button>

        <div class="upsells"></div>

        <button
            class="upsells-arrow next"
            type="button"
            aria-label="<?php echo esc_attr__(
    'Next',
    'free_shipment_progressbar'
); ?>"
        >
            ›
        </button>

    </div>
`;

            /*
             * Voor header plaatsen
             */

            const header =
                document.querySelector(
                    '#masthead'
                );

            if (header) {

                header.before(bar);

            } else {

                document.body.prepend(bar);
            } // END else
        } // END if

function updateBarHeight() {

	/* Bewust 2x dezelfde functie om zeker te weten dat
	 *  - de DOM volledig gerenderd is
     *  - de upsells daadwerkelijk zichtbaar zijn
     *  - CSS/layout/herflow klaar is
     * Daarna pas de hoogte wordt gemeten
     */
	
    function measureHeight() {

            const height =
                Math.ceil(
                    bar.getBoundingClientRect().height
                );

            document.documentElement.style.setProperty(
                '--fsb-height',
                height + 'px'
            );

    } // END function measureHeight()

    requestAnimationFrame(function(){

        requestAnimationFrame(function(){

            measureHeight();

            setTimeout(
                measureHeight,
                120
            );

        }); // END requestAnimationFrame()

    }); // END requestAnimationFrame()

} // END function updateBarHeight()

updateBarHeight();

        /*
         * Text
         */

        const msg =
            bar.querySelector('.msg');

        msg.textContent =
            (
                isMobileProgressText()
                &&
                mobileMessage
            )
                ? mobileMessage
                : (message || 'Berekenen');

        msg.style.display =
            showProgress ? '' : 'none';

        requestAnimationFrame(function(){

            fitProgressMessage(msg);
            updateBarHeight();

        }); // END requestAnimationFrame()

        /*
         * Progress
         */

        const progress =
            bar.querySelector('.bar');

        const progressWrapper =
            bar.querySelector('.progress');

        progressWrapper.style.display =
            showProgress ? '' : 'none';
		
		const upsellWrapper =
            bar.querySelector('.upsells');

        percent =
            parseInt(percent || 0);

        progress.style.width =
            percent + '%';
		
		upsellWrapper.innerHTML = '';
		
		let currentIndex = 0;

if (
    Array.isArray(upsells)
    && upsells.length
) {

    upsells.forEach(product => {

        const productName =
            escapeHtml(product.name);

        const productPrice =
            escapeHtml(product.price);

        const productImage =
            escapeHtml(safeImage(product.image));

        const productId =
            parseInt(product.id || 0, 10);

        if (!productId) {
            return;
        } // END if

        const html = `

            <div class="upsell">

                <img
                    src="${productImage}"
                    alt="${productName}"
                >

                <div class="upsell-info">

                    <div class="upsell-title">
                        ${productName}
                    </div>

                    <div class="upsell-price">
                        ${productPrice}
                    </div>

                </div>

<button
    class="upsell-btn"
    data-product-id="${productId}"
>
    ${fsb_ajax.i18n.add_to_cart}
</button>
            </div>
        `;

        upsellWrapper.insertAdjacentHTML(
            'beforeend',
            html
        );
    });
} // END if Array.isArray(upsells) && upsells.length
		
		/*
 * Hoogte opnieuw berekenen
 */

updateBarHeight();

        if (percent >= 100) {

            progress.classList.add(
                'complete'
            );

        } else {

            progress.classList.remove(
                'complete'
            );
        } // END else
		
		const prevButton =
    bar.querySelector('.upsells-arrow.prev');

const nextButton =
    bar.querySelector('.upsells-arrow.next');

const carouselInterval =
    Math.max(
        0,
        parseInt(
            fsb_ajax.carousel_interval || 0,
            10
        ) || 0
    );

function updateSlider() {

    const cards =
        bar.querySelectorAll('.upsell');

    cards.forEach((card, index) => {

        if (
            index >= currentIndex
            &&
            index < currentIndex + 3
        ) {

            card.style.display = 'flex';

        } else {

            card.style.display = 'none';
        } // END else
    });

    requestAnimationFrame(function(){

        updateBarHeight();

    }); // END requestAnimationFrame()
} // END function updateSlider()

/*
 * Desktop only
 */

if (
    window.innerWidth > 1024
) {

    updateSlider();

    if (
        window.free_shipment_progressbarFsbCarouselTimer
    ) {
        clearInterval(
            window.free_shipment_progressbarFsbCarouselTimer
        );
    } // END if

    function showPreviousUpsells() {

        currentIndex = Math.max(
            0,
            currentIndex - 1
        );

        updateSlider();
    } // END function showPreviousUpsells()

    function showNextUpsells() {

        const maxIndex =
            Math.max(
                0,
                upsells.length - 3
            );

        if (
            maxIndex <= 0
        ) {
            return;
        } // END if

        currentIndex =
            currentIndex >= maxIndex
                ? 0
                : currentIndex + 1;

        updateSlider();
    } // END function showNextUpsells()

    prevButton.onclick =
        showPreviousUpsells;

    nextButton.onclick =
        showNextUpsells;

    if (
        carouselInterval > 0
        &&
        upsells.length > 3
    ) {
        window.free_shipment_progressbarFsbCarouselTimer =
            setInterval(
                showNextUpsells,
                carouselInterval * 1000
            );
    } // END if

} else {

    prevButton.style.display = 'none';
    nextButton.style.display = 'none';

    /*
     * BELANGRIJK:
     * mobiele cards forceren zichtbaar
     */

    const cards =
        bar.querySelectorAll('.upsell');

    cards.forEach(card => {

        card.style.display = 'flex';
    });
} // END else
		
    } // END function showBar()

function pickupSelected() {

    /*
     * Winkelwagenpagina:
     * geen pickup check nodig
     */

    if (isCartPage) {
        return false;
    } // END if

    /*
     * WooCommerce Blocks tabs
     */

    const deliveryOptions = document.querySelectorAll(
        '.wc-block-components-segmented-control__button'
    );

    for (const option of deliveryOptions) {

        const text =
            option.textContent
            ?.toLowerCase()
            ?.trim() || '';

        const active = (

            option.getAttribute(
                'aria-checked'
            ) === 'true'

            ||

            option.classList.contains(
                'is-active'
            )
        );

        if (
            active
            &&
            (
                text.includes('afhalen')
                || text.includes('pickup')
            )
        ) {

            debugLog(
                'PICKUP TAB ACTIVE'
            );

            return true;
        } // END if
    } // END for

    /*
     * Klassieke radios fallback
     */

    const radios = document.querySelectorAll(
        'input[type="radio"]'
    );

    let shippingValue = '';

    radios.forEach(radio => {

        if (
            radio.value.includes('pickup')
            || radio.value.includes('shipping')
            || radio.value.includes('flat_rate')
            || radio.value.includes('local_pickup')
            || radio.value.includes('wbsng')
        ) {

            if (radio.checked) {

                shippingValue =
                    radio.value;
            } // END if
        } // END if
    });

    if (!shippingValue) {
        return false;
    } // END if

    return (
        shippingValue.includes('pickup')
        || shippingValue.includes('local_pickup')
    );
} // END function pickupSelected()

    function getCartAddress() {

        let country = 'NL';
        let postcode = '';
        let city = '';
        const useCustomerAddress =
            Boolean(fsb_ajax.use_customer_address);
        const canUseStoredAddress =
            !useCustomerAddress;
        let hasFreshCheckoutAddress =
            false;

        /*
         * WooCommerce Blocks
         */

        const blocksAddress =
            getBlocksStoreAddress();

        if (blocksAddress) {

            country =
                blocksAddress.country || country;

            postcode =
                blocksAddress.postcode || postcode;

            city =
                blocksAddress.city || city;

            hasFreshCheckoutAddress =
                true;
        } // END if

        /*
         * Checkout DOM fallback. WooCommerce Blocks uses hyphenated field
         * IDs in some themes, while classic checkout uses underscored IDs.
         */

        if (
            !useCustomerAddress
        ) {

            const domAddress =
                getCheckoutDomAddress();

            if (domAddress) {

                country =
                    domAddress.country || country;

                postcode =
                    domAddress.postcode || postcode;

                city =
                    domAddress.city || city;

                hasFreshCheckoutAddress =
                    true;
            } // END if
        } // END if

        if (
            !postcode
            &&
            !city
            &&
            !hasFreshCheckoutAddress
            &&
            canUseStoredAddress
        ) {

            const storedAddress =
                getStoredCartAddress();

            if (storedAddress) {

                country =
                    storedAddress.country || country;

                postcode =
                    storedAddress.postcode || '';

                city =
                    storedAddress.city || '';
            } // END if
        } // END if

        const address =
            normalizeCartAddress({
                country,
                postcode,
                city
            });

        storeCartAddress(address);

        if (DEBUG) {

            console.log(
                'FREE SHIPPING ADDRESS:',
                address
            );
        } // END if

        return address;
    } // END try

	if (getBlocksCartData()) {

    try {

        const cartData =
            getBlocksCartData();

    } catch(e) {

if (DEBUG) {

    console.error(
        'STORE API DEBUG ERROR',
        e
    );
} // END if
    } // END catch
} // END try
	
    function loadBar(force = false) {

        debugLog(
            '[FREE SHIPPING] LOAD BAR',
            {
                force,
                loading
            }
        );

        if (loading) {

            pendingLoad = true;

            return;
        } // END if

        loading = true;

        /*
         * Pickup?
         */

        if (pickupSelected()) {

            hideBar();

            loading = false;

            return;
        } // END if

        const address =
            getCartAddress();

        /*
         * AJAX
         */

        const params =
            new URLSearchParams({

                action:
                    'get_free_shipping_progress',

                security:
                    fsb_ajax.progress_nonce || '',

                country:
                    address.country,

                postcode:
                    address.postcode,

                city:
                    address.city,

                use_customer_address:
                    fsb_ajax.use_customer_address ? '1' : '0',

                t: Date.now()
            });

        fetch(
            fsb_ajax.url
            + '?'
            + params.toString(),
            {
                cache: 'no-store'
            }
        )

        

.then(async res => {

    const text = await res.text();

    /*
     * HTTP error?
     */

    if (!res.ok) {

        console.group(
            'FREE SHIPPING AJAX ERROR'
        );

        console.error(
            'HTTP STATUS:',
            res.status
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'AJAX HTTP ERROR'
        );
    } // END if

    /*
     * Geen JSON?
     */

    if (
        !text.trim().startsWith('{')
        &&
        !text.trim().startsWith('[')
    ) {

        console.group(
            'FREE SHIPPING INVALID JSON'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'INVALID JSON RESPONSE'
        );
    } // END if

    /*
     * Parse JSON
     */

    try {

        return JSON.parse(text);

    } catch(error) {

        console.group(
            'FREE SHIPPING JSON PARSE ERROR'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.error(
            'PARSE ERROR:',
            error
        );

        console.groupEnd();

        throw error;
    } // END catch
})

.then(data => {

    if (
        data
        &&
        data.cart_count !== undefined
        &&
        data.cart_count !== null
        &&
        !Number.isNaN(Number(data.cart_count))
    ) {

        window.free_shipment_progressbarServerCartCount =
            Number(data.cart_count);

        setCartCounterValue(
            window.free_shipment_progressbarServerCartCount
        );
    } // END if

	if (DEBUG) {
    console.group(
        'FREE SHIPPING DEBUG'
    );

    console.log(
        'FULL AJAX RESPONSE:',
        data
    );

    console.log(
        'TOTAL:',
        data.total
    );

    console.log(
        'MINIMUM:',
        data.minimum
    );

    console.log(
        'REMAINING:',
        data.remaining
    );

    console.log(
        'DEBUG SHIPPING DATA:',
        data.debug_shipping_data
    );

    console.log(
        'ADDRESS SOURCE:',
        data.address_source
    );

    console.log(
        'ELIGIBLE CART COUNT:',
        data.eligible_cart_count
    );

    console.log(
        'EXCLUDED GIFTCARD COUNT:',
        data.excluded_giftcard_count
    );

    console.log(
        'EXCLUDED GIFTCARD TOTAL:',
        data.excluded_giftcard_total
    );

    console.groupEnd();
	} // END if

            /*
             * Geen free shipping?
             */

            if (
                !data
                || data.show === false
            ) {

                if (
                    data?.empty_cart
                    ||
                    isCartVisiblyEmpty()
                ) {

                    setCartCounterValue(0);
                } // END if

                hideBar();

                return;
            } // END try

showBar(
    data.message,
    data.percent,
    data.upsells || [],
    data.show_progress !== false,
    data.mobile_message || ''
);
        })

        .catch(error => {

if (DEBUG) {

    console.error(
        '[FREE SHIPPING]',
        error
    );
} // END if

            hideBar();
        })

        .finally(() => {

            loading = false;

            if (pendingLoad) {

                pendingLoad = false;

                setTimeout(function(){

                    loadBar(true);

                }, 150);
            } // END if
        });
    } // END catch

/*
 * INIT
 */

loadBar(true);
	
	/*
 * FORCE WooCommerce Blocks refresh
 * na page load
 */

whenCartStoreReady(function(){

    setTimeout(function(){

        try {

            wp.data
                .dispatch('wc/store/cart')
                .invalidateResolutionForStore();

            if (DEBUG) {

                console.log(
                    'STORE CACHE INVALIDATED'
                );
            } // END if

        } catch(e) {

            debugLog(
                'STORE INVALIDATE ERROR',
                e
            );
        } // END catch

    }, 1200);
});

/*
 * WooCommerce Blocks store subscribe
 */

whenCartStoreReady(function(){

    if (
        storeSubscribed
        ||
        !wp.data.subscribe
    ) {
        return;
    } // END if

    storeSubscribed = true;

    let previousTotals = '';

    wp.data.subscribe(function(){

        try {

            const cart =
                getBlocksCartData();

            if (!cart?.totals) {
                return;
            } // END if

            const currentTotals =
                JSON.stringify(cart.totals);

            if (
                currentTotals === previousTotals
            ) {
                return;
            } // END try

            previousTotals =
                currentTotals;

            clearTimeout(
                window.fsbStoreTimer
            );

            window.fsbStoreTimer =
                setTimeout(function(){

                    loadBar(true);

                }, 300);

        } catch(e){}
    });
}); // END whenCartStoreReady
	
/*
 * WooCommerce events
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'updated_cart_totals updated_wc_div wc_fragments_refreshed added_to_cart removed_from_cart updated_checkout',

        function(){

            setTimeout(function(){

                loadBar(true);

            }, 500);
        } // END if
    );
} // END if

/*
 * Sync bij normale cart updates
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'updated_cart_totals removed_from_cart added_to_cart updated_wc_div',

        function(){

            setTimeout(function(){

                syncSydneyCartCounter();

            }, 300);
        } // END if
    );
} // END if

/*
 * Alleen WooCommerce fragments observeren
 */

if (window.jQuery) {

    jQuery(document.body).on(

        'wc_fragments_refreshed added_to_cart removed_from_cart updated_checkout',

        function(){

            clearTimeout(
                window.fsbTimer
            );

            window.fsbTimer =
                setTimeout(function(){

                    loadBar(true);

                }, 300); 
        } // END if
    );
} // END if
		
/*
 * Shipping switch
 */

document.body.addEventListener(
    'click',
    function(e){

        /*
         * Zoek shipping methode container
         */

        const shippingWrapper =
            e.target.closest(
                '.wc-block-checkout__shipping-method-option'
            );

        if (!shippingWrapper) {
            return;
        } // END if

        debugLog(
            'SHIPPING CLICK DETECTED'
        );

        /*
         * Kleine delay zodat Blocks
         * checked state kan updaten
         */

        setTimeout(function(){

            const isPickup =
                pickupSelected();

            debugLog(
                'PICKUP:',
                isPickup
            );

            if (isPickup) {

                debugLog(
                    'HIDE BAR'
                );

                hideBar();

            } else {

                debugLog(
                    'SHOW BAR'
                );

                loadBar(true);
            } // END else

        }, 300);
    } // END if
);

	/*
 * ADD TO CART
 */

document.body.addEventListener(
    'click',
    function(e){

        const button =
            e.target.closest('.upsell-btn');

        if (!button) {
            return;
        } // END if

        e.preventDefault();

        const productId =
            button.dataset.productId;

        if (!productId) {
            return;
        } // END if

        button.disabled = true;

        button.innerHTML = '...';

        const formData = new FormData();

        formData.append(
            'action',
            'free_shipment_progressbar_add_upsell_to_cart'
        );

        formData.append(
            'security',
            fsb_ajax.cart_nonce || ''
        );

        formData.append(
            'product_id',
            productId
        );

        fetch(
            fsb_ajax.url,
            {
                method: 'POST',
                body: formData
            } // END if
        )

        .then(async res => {



    const text = await res.text();

    /*
     * HTTP error?
     */

    if (!res.ok) {

        console.group(
            'FREE SHIPPING AJAX ERROR'
        );

        console.error(
            'HTTP STATUS:',
            res.status
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'AJAX HTTP ERROR'
        );
    } // END if

    /*
     * Geen JSON?
     */

    if (
        !text.trim().startsWith('{')
        &&
        !text.trim().startsWith('[')
    ) {

        console.group(
            'FREE SHIPPING INVALID JSON'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.groupEnd();

        throw new Error(
            'INVALID JSON RESPONSE'
        );
    } // END if

    /*
     * Parse JSON
     */

    try {

        return JSON.parse(text);

    } catch(error) {

        console.group(
            'FREE SHIPPING JSON PARSE ERROR'
        );

        console.error(
            'RAW RESPONSE:',
            text
        );

        console.error(
            'PARSE ERROR:',
            error
        );

        console.groupEnd();

        throw error;
    } // END catch
})

        .then(data => {

    if (data.success) {

        button.innerHTML =
            fsb_ajax.i18n.added;

        /*
         * WooCommerce Blocks refresh
         */

        if (
            window.wp
            &&
            wp.data
            &&
            wp.data.dispatch
        ) {

            try {

                wp.data
                    .dispatch('wc/store/cart')
                    .invalidateResolutionForStore();

            } catch(e) {

                console.log(e);
            } // END catch
        } // END try

        /*
         * Classic WooCommerce fallback
         */

/*
 * WooCommerce fragments refresh
 * ZONDER pagina refresh
 */

if (window.jQuery) {

    jQuery.ajax({

        url: fsb_ajax.fragments_url,

        type: 'POST',

        success: function(data) {

            if (
                data
                &&
                data.fragments
            ) {

                Object.keys(data.fragments)
                    .forEach(function(key){

                        jQuery(key).replaceWith(
                            data.fragments[key]
                        );
                    });

                jQuery(document.body)
                    .trigger('wc_fragments_refreshed');
            } // END if
        } // END if
    });
} // END try
		
        /*
         * Free shipping bar refresh
         */

        setTimeout(function(){

            loadBar(true);

        }, 500);
    } // END if
});
});
		
  /*
 * BLOCKS CART OBSERVER
 * Detecteert plus/min updates
 */

function getCartDomSignature() {

    if (isCartVisiblyEmpty()) {
        return 'empty';
    } // END if

    const cartNode =
        document.querySelector(
            '.wc-block-cart-items'
        );

    if (!cartNode) {
        return '';
    } // END if

    const quantities =
        Array.from(
            cartNode.querySelectorAll(
                '.wc-block-components-quantity-selector__input, input.qty'
            )
        )
        .map(function(input){
            return input.value || '';
        });

    return quantities.join('|');
} // END function getCartDomSignature()

const cartObserver = new MutationObserver(function(){

    clearTimeout(window.free_shipment_progressbarCartTimer);

    window.free_shipment_progressbarCartTimer =
        setTimeout(function(){

const cartSignature =
    getCartDomSignature();

if (
    cartSignature
    &&
    cartSignature === window.free_shipment_progressbarLastCartDomSignature
) {
    return;
} // END if

window.free_shipment_progressbarLastCartDomSignature =
    cartSignature;

if (DEBUG) {

    console.log(
        'BLOCKS CART CHANGE DETECTED'
    );

} // END if (DEBUG)

/*
 * Empty cart detectie
 */

const emptyCart =
    document.querySelector(
        '.wc-block-cart__empty-cart'
    );

if (emptyCart) {

    if (DEBUG) {

        console.log(
            'EMPTY CART DETECTED'
        );

    } // END if (DEBUG)

    /*
     * Header reset
     */

    setCartCounterValue(0);

    hideBar();

    return;

} // END if (emptyCart)

            syncSydneyCartCounter();

            loadBar(true);

        }, 400);

}); /* END MutationObserver */

function observeBlocksCartNode() {

    const cartNode =
        document.querySelector(
            '.wc-block-cart-items'
        );

    if (DEBUG) {
        console.log(
            'CART NODE:',
            cartNode
        );
    } // END if

    if (!cartNode) {
        return false;
    } // END if

    window.free_shipment_progressbarLastCartDomSignature =
        getCartDomSignature();

    cartObserver.observe(
        cartNode,
        {
            childList: true,
            subtree: true,
            characterData: true
        } // END if
    );

    return true;
} // END function observeBlocksCartNode()

if (!observeBlocksCartNode()) {

    setTimeout(
        observeBlocksCartNode,
        1000
    );

    setTimeout(
        observeBlocksCartNode,
        2500
    );
} // END if
	
	/*
     * Address changes
     */

    let addressTimeout = null;

    [
        'change',
        'input',
        'blur'
    ]

    .forEach(eventType => {

        document.body.addEventListener(
            eventType,
            function(e){

                const target =
                    e.target;

                if (!target) {
                    return;
                } // END if

                const isAddressField = (

                    target.name?.includes('country')
                    || target.name?.includes('postcode')
                    || target.name?.includes('city')
                    || target.name?.includes('state')

                    || target.id?.includes('country')
                    || target.id?.includes('postcode')
                    || target.id?.includes('city')
                    || target.id?.includes('state')
                );

                if (!isAddressField) {
                    return;
                } // END if

                clearTimeout(
                    addressTimeout
                );

                addressTimeout =
                    setTimeout(function(){

                        loadBar(true);

                    }, 1200);
            } // END if
        );
    });

} // END if

if (
    document.readyState === 'complete'
) {

    initBar();

} else {

    window.addEventListener(
        'load',
        initBar
    );
} // END else

})();

</script>

<?php
});


/* =========================================================
 * AJAX HANDLER
========================================================= */

add_action(
    'wp_ajax_get_free_shipping_progress',
    'free_shipment_progressbar_get_free_shipping_progress'
);

add_action(
    'wp_ajax_nopriv_get_free_shipping_progress',
    'free_shipment_progressbar_get_free_shipping_progress'
);

function free_shipment_progressbar_get_free_shipping_progress(){

    /*
     * Deze endpoint is read-only: hij leest winkelwagen- en verzendzone-data
     * en wijzigt niets. Een verlopen/cached nonce mag daarom de frontend bar
     * niet breken. De schrijfactie add-to-cart houdt wel een harde nonce-check.
     */

    check_ajax_referer(
        'free_shipment_progressbar_progress',
        'security',
        false
    );

    /*
     * WooCommerce loaded?
     */

if(
    !class_exists('WooCommerce')
){

    wp_send_json([
        'show' => false
    ]);

    return;
} // END function free_shipment_progressbar_get_free_shipping_progress()

    /*
     * Cart loaded?
     */

    if(
        is_null(WC()->cart)
        && function_exists('wc_load_cart')
    ){
        wc_load_cart();
    } // END catch

    if(
        !WC()->cart
    ){
        wp_send_json([
            'show' => false
        ]);

        return;
    } // END if

    /*
     * Empty cart?
     */

    if(
        WC()->cart->is_empty()
    ){

        wp_send_json([
            'show' => false,
            'empty_cart' => true,
            'cart_count' => 0
        ]);

        return;
    } // END try


/*
 * Adres uit AJAX
 */

$country =
    sanitize_text_field(
        $_GET['country'] ?? ''
    );

$postcode =
    sanitize_text_field(
        $_GET['postcode'] ?? ''
    );

$city =
    sanitize_text_field(
        $_GET['city'] ?? ''
    );

$has_ajax_address =
    $country !== ''
    ||
    $postcode !== ''
    ||
    $city !== '';

$saved_address =
    free_shipment_progressbar_get_customer_address();

$use_customer_address =
    is_user_logged_in()
    &&
    !empty($_GET['use_customer_address']);

$address_source =
    'ajax';

if (
    $use_customer_address
    ||
    (
        !$has_ajax_address
        &&
        !$country
    )
) {

    $country =
        $saved_address['country'];

    $address_source =
        $use_customer_address
        ? 'account_customer_address'
        : 'customer_country_fallback';
} // END if

if (
    $use_customer_address
    ||
    (
        !$has_ajax_address
        &&
        !$postcode
    )
) {

    $postcode =
        $saved_address['postcode'];

    $address_source =
        $use_customer_address
        ? 'account_customer_address'
        : 'customer_postcode_fallback';
} // END if

if (
    $use_customer_address
    ||
    (
        !$has_ajax_address
        &&
        !$city
    )
) {

    $city =
        $saved_address['city'];

    $address_source =
        $use_customer_address
        ? 'account_customer_address'
        : 'customer_city_fallback';
} // END if

$country =
    $country ?: 'NL';

/*
 * Package opbouwen
 */

$package = [

    'destination' => [

        'country'  => $country,
        'postcode' => $postcode,
        'city'     => $city,
        'state'    => $has_ajax_address
            ? ''
            : $saved_address['state'],
    ]
];

/*
 * Shipping data
 */

$shipping_data =
    free_shipment_progressbar_get_shipping_data(
        $package
    );

$shipping_saving =
    free_shipment_progressbar_get_shipping_saving_amount(
        $package
    );

$upsells =
    free_shipment_progressbar_get_upsells(15);

$eligible_totals =
    free_shipment_progressbar_get_shipping_eligible_cart_totals();

$debug_excluded_cart_items =
    free_shipment_progressbar_get_debug_excluded_cart_items();

	/*
 * Geen gratis verzending beschikbaar?
 */

if(
    empty($shipping_data['minimum'])
    ||
    (float) $shipping_data['minimum'] <= 0
){

    wp_send_json([
        'show' => !empty($upsells),
        'show_progress' => false,
        'percent' => 0,
        'message' => '',
        'total' => $eligible_totals['eligible_total'],
        'minimum' => 0,
        'remaining' => 0,
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'eligible_cart_count' => $eligible_totals['eligible_cart_count'],
        'excluded_giftcard_count' => $eligible_totals['excluded_giftcard_count'],
        'excluded_giftcard_total' => $eligible_totals['excluded_giftcard_total'],
        'has_only_giftcards' => $eligible_totals['has_only_giftcards'],
        'address_source' => $address_source,
        'address' => [
            'country' => $country,
            'postcode' => $postcode,
            'city' => $city,
        ],
        'upsells' => $upsells,
        'debug_shipping_data' => $shipping_data,
        'debug_excluded_cart_items' => $debug_excluded_cart_items,
    ]);

    return;
} // END if

    $total =
        (float) $eligible_totals['eligible_total'];

    if(
        (int) $eligible_totals['eligible_cart_count'] <= 0
    ){
        wp_send_json([
            'show' => false,
            'show_progress' => false,
            'percent' => 0,
            'message' => '',
            'total' => 0,
            'minimum' => (float) $shipping_data['minimum'],
            'remaining' => 0,
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'eligible_cart_count' => 0,
            'excluded_giftcard_count' => $eligible_totals['excluded_giftcard_count'],
            'excluded_giftcard_total' => $eligible_totals['excluded_giftcard_total'],
            'has_only_giftcards' => $eligible_totals['has_only_giftcards'],
            'address_source' => $address_source,
            'address' => [
                'country' => $country,
                'postcode' => $postcode,
                'city' => $city,
            ],
            'upsells' => [],
            'debug_shipping_data' => $shipping_data,
            'debug_excluded_cart_items' => $debug_excluded_cart_items,
        ]);

        return;
    } // END if

    /*
     * Minimum
     */

    $minimum =
        (float) $shipping_data['minimum'];

    /*
     * Remaining
     */

    $remaining =
        max(
            0,
            $minimum - $total
        );

/*
 * SMART UPSELLS ENGINE
 * FREE SHIPPING AND PROGRESSBAR
 * WPML COMPATIBLE
========================================================= */

if(false){

/*
 * WPML taal
 */

$current_language = 'en';

if (
    has_filter('wpml_current_language')
) {

    $current_language =
        apply_filters(
            'wpml_current_language',
            null
        );
} // END function debugLog()

/*
 * CATEGORIEN
 *
 * Food:
 * Bakmix                    59
 * Drank                     32
 * Ingeblikt fruit          204
 * Ingeblikte groente       208
 * Jellies                  172
 * Ketjap                   157
 * Koffie                    30
 * Kruidenmix                21
 * Meel- en bakproducten   1880
 * Noedels                  224
 * Olie                    2019
 * Ongebakken kroepoek       19
 * Rijst                    177
 * Sambal / saus             38
 * Snacks                    34
 * Specerijen              1857
 * Thee                      35
 *
 * Non-Food:
 * Bamboeproducten         3477
 * Diversen                3478
 * Verzorgingsartikelen    3479
 * Vormen                  3480
 */

/*
 * UPSELL RULES
 */

$upsell_rules = [

    59   => [1880,35,30,32,3480,3477],
    32   => [34,35,30],
    204  => [172],
    208  => [34],
    172  => [3480],
    157  => [21,224,2019,19,177,38,1857],
    3478 => [3477,3479,3480]
];

/*
 * Wederkerig maken
 */

foreach ($upsell_rules as $source => $targets) {

    foreach ($targets as $target) {

        if (
            !isset($upsell_rules[$target])
        ) {
            $upsell_rules[$target] = [];
        } // END foreach

        if (
            !in_array(
                $source,
                $upsell_rules[$target]
            )
        ) {

            $upsell_rules[$target][] =
                $source;
        } // END foreach
    } // END foreach
} // END foreach

/*
 * Cart categorieën ophalen
 */

$cart_category_ids = [];

foreach (
    WC()->cart->get_cart() as $cart_item
) {

    $product_id =
        $cart_item['product_id'];

    $terms =
        get_the_terms(
            $product_id,
            'product_cat'
        );

    if (
        empty($terms)
        || is_wp_error($terms)
    ) {
        continue;
    } // END if

    foreach ($terms as $term) {

        $original_id =
            apply_filters(
                'wpml_object_id',
                $term->term_id,
                'product_cat',
                true,
                'en'
            );

        if ($original_id) {

            $cart_category_ids[] =
                (int) $original_id;
        } // END if
    } // END foreach
} // END foreach

$cart_category_ids =
    array_unique(
        $cart_category_ids
    );

/*
 * Gerelateerde categorieën
 */

$target_categories = [];

foreach (
    $cart_category_ids as $category_id
) {

    if (
        empty($upsell_rules[$category_id])
    ) {
        continue;
    } // END if

    $target_categories =
        array_merge(
            $target_categories,
            $upsell_rules[$category_id]
        );
} // END foreach

$target_categories =
    array_unique(
        $target_categories
    );

/*
 * Vertalen naar huidige taal
 */

$wpml_target_categories = [];

foreach (
    $target_categories as $category_id
) {

    $translated_id =
        apply_filters(
            'wpml_object_id',
            $category_id,
            'product_cat',
            true,
            $current_language
        );

    if ($translated_id) {

        $wpml_target_categories[] =
            $translated_id;
    } // END if
} // END foreach

/*
 * Product IDs reeds in cart
 */

$exclude_ids = [];

foreach (
    WC()->cart->get_cart() as $cart_item
) {

    $exclude_ids[] =
        $cart_item['product_id'];

    if (
        !empty($cart_item['variation_id'])
    ) {

        $exclude_ids[] =
            $cart_item['variation_id'];
    } // END if
} // END foreach

/*
 * Producten ophalen
 */

$query = new WC_Product_Query([

    'status' => 'publish',

    'limit' => 30,

    'stock_status' => 'instock',

    'exclude' => $exclude_ids,

    'return' => 'objects',

    'tax_query' => [

        [
            'taxonomy' => 'product_cat',

            'field' => 'term_id',

            'terms' => $wpml_target_categories,

            'operator' => 'IN'
        ]
    ]
]);

$products =
    $query->get_products();

/*
 * Variaties toevoegen
 */

$upsell_candidates = [];

foreach ($products as $product) {

    /*
     * Simpel product
     */

    if (
        $product->is_type('simple')
    ) {

        if (
            (float) $product->get_price() <= 0.01
        ) {
            continue;
        } // END foreach

        $upsell_candidates[] =
            $product;
    } // END if

    /*
     * Variabel product
     */

    elseif (
        $product->is_type('variable')
    ) {

        $variations =
            $product->get_available_variations();

        foreach (
            $variations as $variation_data
        ) {

            $variation =
                wc_get_product(
                    $variation_data['variation_id']
                );

            if (
                !$variation
            ) {
                continue;
            } // END if

            if (
                !$variation->is_in_stock()
            ) {
                continue;
            } // END if

            if (
                (float) $variation->get_price()
                <= 0.01
            ) {
                continue;
            } // END if

            $upsell_candidates[] =
                $variation;
        } // END foreach
    }
} // END foreach

/*
 * Shuffle
 */

shuffle(
    $upsell_candidates
);

/*
 * Maximaal 15
 */

$upsell_candidates =
    array_slice(
        $upsell_candidates,
        0,
        15
    );

/*
 * Frontend array
 */

$upsells = [];

foreach (
    $upsell_candidates as $product
) {

    $image_id =
        $product->get_image_id();

    if (
        !$image_id
        && $product->get_parent_id()
    ) {

        $image_id =
            get_post_thumbnail_id(
                $product->get_parent_id()
            );
    } // END if

    $upsells[] = [

        'id' => $product->get_id(),

        'name' => $product->get_name(),

'price' => html_entity_decode(
    wp_strip_all_tags(
        wc_price(
            $product->get_price()
        )
    )
),
        'image' =>
            wp_get_attachment_image_url(
                $image_id,
                'thumbnail'
            )
    ];
} // END foreach

/*
 * Admin-managed rules override the legacy hardcoded candidates.
 * When no rules are saved, free_shipment_progressbar_get_upsells() uses the
 * same legacy category mappings as its defaults.
 */

} // END if

    /*
     * Percentage
     */

/*
 * Percentage veilig berekenen
 */

if(
    $minimum > 0
){

    $percent = min(
        100,
        ($total / $minimum) * 100
    );

}else{

    $percent = 0;
} // END else

    /*
     * Message
     */

    if(
        $remaining > 0
    ){

        $message = free_shipment_progressbar_safe_sprintf(

            __(
                'Add %s for free shipping 🚚',
                'free_shipment_progressbar'
            ),

            wc_price($remaining)
        );

    }else{

        $message =
            __('You have free shipping 🎉', 'free_shipment_progressbar');
    } // END else

    if(
        $remaining > 0
        && $shipping_saving > 0
    ){
        $message = free_shipment_progressbar_safe_sprintf(

            __(
                'Add %1$s for free shipping and save %2$s 🚚',
                'free_shipment_progressbar'
            ),

            wc_price($remaining),
            wc_price($shipping_saving)
        );
    } // END if

    $mobile_message =
        $message;

    if(
        $remaining > 0
        && $shipping_saving > 0
    ){
        $mobile_message = free_shipment_progressbar_safe_sprintf(

            __(
                '%1$s left for free shipping. Save %2$s',
                'free_shipment_progressbar'
            ),

            wc_price($remaining),
            wc_price($shipping_saving)
        );
    } // END if

    /*
     * Response
     */

wp_send_json([

    'show' => true,

    'show_progress' => true,

    'percent' => round($percent),

    'message' => html_entity_decode(
        wp_strip_all_tags($message),
        ENT_QUOTES,
        get_bloginfo('charset')
    ),

    'mobile_message' => html_entity_decode(
        wp_strip_all_tags($mobile_message),
        ENT_QUOTES,
        get_bloginfo('charset')
    ),

    'total' => $total,

    'minimum' => $minimum,

    'remaining' => $remaining,
    'shipping_saving' => $shipping_saving,
    'cart_count' => WC()->cart->get_cart_contents_count(),
    'eligible_cart_count' => $eligible_totals['eligible_cart_count'],
    'excluded_giftcard_count' => $eligible_totals['excluded_giftcard_count'],
    'excluded_giftcard_total' => $eligible_totals['excluded_giftcard_total'],
    'has_only_giftcards' => $eligible_totals['has_only_giftcards'],
    'address_source' => $address_source,
    'address' => [
        'country' => $country,
        'postcode' => $postcode,
        'city' => $city,
    ],
	'upsells'   => $upsells,

    /*
     * DEBUG
     */

    'debug_shipping_data' => $shipping_data,
    'debug_excluded_cart_items' => $debug_excluded_cart_items
]);
} // END function free_shipment_progressbar_get_free_shipping_progress()

/* =========================
   AJAX ADD TO CART
========================= */

add_action(
    'wp_ajax_free_shipment_progressbar_add_upsell_to_cart',
    'free_shipment_progressbar_add_upsell_to_cart'
);

add_action(
    'wp_ajax_nopriv_free_shipment_progressbar_add_upsell_to_cart',
    'free_shipment_progressbar_add_upsell_to_cart'
);

function free_shipment_progressbar_add_upsell_to_cart(){

    if(
        !check_ajax_referer(
            'free_shipment_progressbar_cart',
            'security',
            false
        )
    ){
        wp_send_json([
            'success' => false,
            'message' => 'Ongeldige sessie'
        ]);

        return;
    } // END if

    if(
        is_null(WC()->cart)
        && function_exists('wc_load_cart')
    ){
        wc_load_cart();
    } // END if

    if(
        !WC()->cart
    ){
        wp_send_json([
            'success' => false,
            'message' => 'Winkelwagen niet beschikbaar'
        ]);

        return;
    } // END if

    /*
     * Product ID
     */

    $product_id =
        isset($_POST['product_id'])
        ? absint($_POST['product_id'])
        : 0;

    if(
        !$product_id
    ){

        wp_send_json([
            'success' => false,
            'message' => 'Geen product ID'
        ]);

        return;

    } // END if (!$product_id)

    /*
     * Product ophalen
     */

    $product =
        wc_get_product(
            $product_id
        );

    if(
        !$product
    ){

        wp_send_json([
            'success' => false,
            'message' => 'Product niet gevonden'
        ]);

        return;

    } // END if (!$product)

    /*
     * Variatie?
     */

    $added = false;

    if(
        $product->is_type('variation')
    ){

        $added =
            WC()->cart->add_to_cart(
                $product->get_parent_id(),
                1,
                $product_id,
                $product->get_variation_attributes()
            );

    }else{

        $added =
            WC()->cart->add_to_cart(
                $product_id,
                1
            );

    } // END if variation

    /*
     * Mislukt?
     */

    if(
        !$added
    ){

        wp_send_json([
            'success' => false,
            'message' => 'Add to cart failed'
        ]);

        return;

    } // END if (!$added)

    /*
     * Totals opnieuw berekenen
     */

    WC()->cart->calculate_totals();

    /*
     * Success
     */

    wp_send_json([
        'success' => true
    ]);

} // END function free_shipment_progressbar_add_upsell_to_cart()

/* =========================
   STORE API SHIPPING FIX
========================= */

add_filter(
    'woocommerce_store_api_cart_shipping_rates',
    'free_shipment_progressbar_fix_store_api_shipping_rates',
    9999,
    2
);

function free_shipment_progressbar_fix_store_api_shipping_rates(
    $shipping_rates,
    $cart
){

    if (
        empty($shipping_rates)
        ||
        !WC()->cart
    ) {
        return $shipping_rates;
    } // END else

    $minimum =
        free_shipment_progressbar_get_shipping_data()['minimum']
        ?? 0;

    $eligible_totals =
        free_shipment_progressbar_get_shipping_eligible_cart_totals();

    $total =
        (float) $eligible_totals['eligible_total'];

    $is_free =
        $minimum > 0
        &&
        $total >= $minimum;

    if (
        !$is_free
    ) {
        return $shipping_rates;
    } // END if

    foreach (
        $shipping_rates as &$package
    ) {

        if (
            empty($package['shipping_rates'])
        ) {
            continue;
        } // END if

        foreach (
            $package['shipping_rates']
            as &$rate
        ) {

            /*
             * Gratis maken
             */

            $rate['price'] = '0';

            if (
                isset($rate['prices'])
            ) {

                $rate['prices']['price'] = '0';

                $rate['prices']['raw_prices']['price']
                    = '0';

                $rate['prices']['raw_prices']['precision']
                    = 6;

            } /* END if prices */

            /*
             * Taxes resetten
             */

            if (
                isset($rate['taxes'])
            ) {

                $rate['taxes'] = [];

            } /* END if taxes */

            /*
             * Label fix
             */

            if (
                !str_contains(
                    strtolower($rate['name'] ?? ''),
                    'gratis'
                )
            ) {

                $rate['name'] .= ' (Gratis)';

            } /* END if label */

        } /* END foreach rate */

    } /* END foreach package */

    return $shipping_rates;

} // END function free_shipment_progressbar_fix_store_api_shipping_rates()
/* =========================
  SUBTOTALS FIX
========================= */

add_action('wp_footer', function () {
?>
<script>

const DEBUG = false;
	
(function(){

function getBlocksCartData(){

    if (
        !window.wp
        ||
        !wp.data
        ||
        !wp.data.select
    ) {
        return null;
    } // END if

    try {

        const cartStore =
            wp.data.select('wc/store/cart');

        if (
            !cartStore
            ||
            typeof cartStore.getCartData !== 'function'
        ) {
            return null;
        } // END if

        return cartStore.getCartData();

    } catch(e) {
        return null;
    } // END try
} // END function getBlocksCartData()

async function waitForBlocksStore(){

    return new Promise(resolve => {

        let tries = 0;

        const timer = setInterval(function(){

            tries++;

            if (getBlocksCartData()) {

                clearInterval(timer);

                resolve(true);

                return;
            } // END function waitForBlocksStore()

            if (tries > 50) {

                clearInterval(timer);

                resolve(false);
            } // END if

        }, 300);
    });
} // END function waitForBlocksStore()

async function injectSubtotalRow(){

    const hasStore =
        await waitForBlocksStore();

    if (!hasStore) {
        return;
    } // END if (!hasStore)

    const totalsWrapper =
        document.querySelector(
            '.wc-block-components-totals-wrapper'
        );

    if (!totalsWrapper) {
        return;
    } // END if (!totalsWrapper)

    const totalsRoot =
        totalsWrapper.closest(
            '.wc-block-cart__totals, .wc-block-components-sidebar, .wp-block-woocommerce-cart-order-summary-block, .wc-block-cart'
        )
        || totalsWrapper.parentElement
        || totalsWrapper;

    const customSubtotalRows =
        totalsRoot.querySelectorAll(
            '.free-shipment-progressbar-subtotal-row'
        );

    customSubtotalRows.forEach(function(row, index){

        if (index > 0) {
            row.remove();
        } // END if
    });

    /*
     * Native subtotal aanwezig?
     */

    const nativeSubtotal =
        Array
            .from(
                totalsRoot.querySelectorAll(
                    '.wc-block-components-totals-item'
                )
            )
            .find(function(row){

                if (
                    row.classList.contains(
                        'free-shipment-progressbar-subtotal-row'
                    )
                ) {
                    return false;
                } // END if

                const label =
                    row.querySelector(
                        '.wc-block-components-totals-item__label'
                    );

                return (
                    label
                    &&
                    label.textContent
                    &&
                    label.textContent
                        .trim()
                        .toLowerCase() === 'subtotaal'
                );
            })
        ||
        totalsRoot.querySelector(
            '.wc-block-components-totals-item--subtotal'
        );

    /*
     * Custom subtotal aanwezig?
     */

    const customSubtotal =
        totalsRoot.querySelector(
            '.free-shipment-progressbar-subtotal-row'
        );

    /*
     * Shipping row
     */

let hasFreeShipping = false;

try {

    const cart =
        getBlocksCartData();

    const rates =
        cart?.shippingRates?.[0]
        ?.shipping_rates
        || [];

    hasFreeShipping =
        rates.some(function(rate){

            const price =
                parseInt(
                    rate?.price || 0,
                    10
                );

            return (
    rate?.price === '0'
    ||
    rate?.price === 0
);

        });

} catch(e) {

    if (DEBUG) {

        console.error(
            'FREE SHIPPING DETECTION ERROR',
            e
        );

    } // END if (DEBUG)

} // END try/catch
	
	
    /*
     * GEEN gratis verzending
     */

    if (!hasFreeShipping) {

        /*
         * Cleanup custom subtotal
         */

        customSubtotalRows.forEach(function(row){

            row.remove();

        });

        return;

    } // END if (!hasFreeShipping)

    /*
     * Native subtotal bestaat al
     */

    if (nativeSubtotal) {

        /*
         * Cleanup custom subtotal
         */

        customSubtotalRows.forEach(function(row){

            row.remove();

        });

        return;

    } // END if (nativeSubtotal)


    /*
     * Cart data
     */

    let cart;

    try {

        cart =
            getBlocksCartData();

    } catch(e) {

        if (DEBUG) {

            console.error(
                'SUBTOTAL ERROR',
                e
            );

        } // END if (DEBUG)

        return;

    } // END try/catch

    if (
        !cart
        ||
        !cart.totals
    ) {
        return;
    } // END if (!cart || !cart.totals)

    /*
     * Subtotal incl. BTW
     */

    const subtotalExclRaw =
        parseInt(
            cart.totals.total_items || 0,
            10
        );

    const subtotalTaxRaw =
        parseInt(
            cart.totals.total_items_tax || 0,
            10
        );

    const subtotalInclRaw =
        subtotalExclRaw
        +
        subtotalTaxRaw;

    /*
     * Currency
     */

    const currency =
        cart.totals.currency_symbol || '€';

    const formattedSubtotal =
        currency
        + ' '
        + (
            subtotalInclRaw / 100
        ).toLocaleString(
            'nl-NL',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            } // END if
        );

	/*
 * Custom subtotal bestaat al?
 * Dan updaten
 */

if (customSubtotal) {

    const value =
        customSubtotal.querySelector(
            '.wc-block-components-totals-item__value'
        );

    if (value) {

        value.innerHTML =
            formattedSubtotal;

    } // END if (value)

    return;

} // END if (customSubtotal)
	
    /*
     * Row
     */

    const row =
        document.createElement('div');

    row.className =
        'wc-block-components-totals-item free-shipment-progressbar-subtotal-row';

    row.style.marginBottom =
        '20px';

    row.innerHTML = `

        <div class="wc-block-components-totals-item__label">
            Subtotaal
        </div>

        <div class="wc-block-components-totals-item__value">
            ${formattedSubtotal}
        </div>
    `;

    /*
     * Shipping row opnieuw ophalen
     */

    const latestShippingRow =
        totalsWrapper.querySelector(
            '.wc-block-components-totals-shipping'
        );

    if (latestShippingRow) {

        latestShippingRow.before(
            row
        );

    } else {

        totalsWrapper.prepend(
            row
        );

    } // END if (latestShippingRow)

} // END async function injectSubtotalRow()

/*
 * Init + observer
 */

window.addEventListener(
    'load',
    function(){

        /*
         * Eerste injectie
         */

        setTimeout(function(){

            injectSubtotalRow();

        }, 1500);

        /*
         * Body observer
         * Woo Blocks rendert async
         */

        const bodyObserver =
            new MutationObserver(function(){

                clearTimeout(
                    window.free_shipment_progressbarSubtotalTimer
                );

                window.free_shipment_progressbarSubtotalTimer =
                    setTimeout(function(){

                        injectSubtotalRow();

                    }, 300);

            }); /* END MutationObserver */

        bodyObserver.observe(
            document.body,
            {
                childList: true,
                subtree: true
            } // END else
        );

    } /* END function */
); /* END window.addEventListener() */

})();
</script>
<?php
}, 999999);
