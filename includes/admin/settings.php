<?php

if (!defined('ABSPATH')) {
    exit;
} // END if

add_action(
    'admin_enqueue_scripts',
    'free_shipment_progressbar_admin_assets'
);

function free_shipment_progressbar_admin_assets(
    $hook
){

    if(
        $hook !== 'woocommerce_page_free-shipment-progressbar-fsb'
    ){
        return;
    } // END if

    if(
        wp_script_is('wc-enhanced-select', 'registered')
    ){
        wp_enqueue_script('wc-enhanced-select');
    } // END if

    if(
        wp_script_is('selectWoo', 'registered')
    ){
        wp_enqueue_script('selectWoo');
    } // END if

    if(
        wp_style_is('woocommerce_admin_styles', 'registered')
    ){
        wp_enqueue_style('woocommerce_admin_styles');
    } // END if

    if(
        wp_style_is('select2', 'registered')
    ){
        wp_enqueue_style('select2');
    } // END if

    if(
        wp_style_is('selectWoo', 'registered')
    ){
        wp_enqueue_style('selectWoo');
    } // END if
} // END function free_shipment_progressbar_admin_assets()

add_action(
    'wp_ajax_free_shipment_progressbar_search_products',
    'free_shipment_progressbar_search_products'
);

function free_shipment_progressbar_search_products(){

    if(
        !current_user_can('manage_woocommerce')
        ||
        !check_ajax_referer(
            'free_shipment_progressbar_product_search',
            'security',
            false
        )
    ){
        wp_send_json_error();
    } // END if

    $term =
        sanitize_text_field(
            wp_unslash($_GET['term'] ?? '')
        );

    $term =
        trim($term);

    if(
        $term === ''
    ){
        wp_send_json([]);
    } // END if

    $language =
        free_shipment_progressbar_admin_get_requested_product_language();

    $products =
        free_shipment_progressbar_find_products_for_admin(
            $term,
            $language
        );

    $results = [];

    foreach(
        $products as $product
    ){
        $results[] = [
            'id' => $product->get_id(),
            'text' => free_shipment_progressbar_admin_product_label($product),
            'categories' => free_shipment_progressbar_admin_product_categories_for_select($product),
        ];
    } // END foreach

    wp_send_json($results);
} // END function free_shipment_progressbar_search_products()

function free_shipment_progressbar_admin_get_requested_product_language(){

    $language =
        sanitize_key(
            wp_unslash(
                $_REQUEST['lang'] ?? ''
            )
        );

    if(
        $language
    ){
        return $language;
    } // END if

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
            return sanitize_key($language);
        } // END if
    } // END if

    return free_shipment_progressbar_get_source_language();
} // END function free_shipment_progressbar_admin_get_requested_product_language()

function free_shipment_progressbar_admin_product_categories_for_select(
    $product
){

    if(
        !$product
    ){
        return [];
    } // END if

    $product_id =
        $product->is_type('variation')
        ? $product->get_parent_id()
        : $product->get_id();

    $terms =
        get_the_terms(
            $product_id,
            'product_cat'
        );

    if(
        empty($terms)
        || is_wp_error($terms)
    ){
        return [];
    } // END if

    $categories = [];

    foreach(
        $terms as $term
    ){
        $categories[] = [
            'id' => (int) $term->term_id,
            'text' => $term->name . ' (#' . $term->term_id . ')',
        ];
    } // END foreach

    return $categories;
} // END function free_shipment_progressbar_admin_product_categories_for_select()

function free_shipment_progressbar_admin_product_label(
    $product
){

    if(
        !$product
    ){
        return '';
    } // END if

    $parts = [
        wp_strip_all_tags($product->get_name()),
    ];

    $sku =
        $product->get_sku();

    if(
        $sku
    ){
        $parts[] =
            $sku;
    } // END if

    $parts[] =
        '#' . $product->get_id();

    return implode(
        ' - ',
        array_filter($parts)
    );
} // END function free_shipment_progressbar_admin_product_label()

function free_shipment_progressbar_admin_product_has_language(
    $product,
    $language
){

    if(
        !$product
    ){
        return false;
    } // END if

    $requested_language =
        sanitize_key($language);

    if(
        !$requested_language
    ){
        return true;
    } // END if

    if(
        !has_filter('wpml_element_language_code')
    ){
        return true;
    } // END if

    $element_id =
        $product->is_type('variation')
        ? $product->get_parent_id()
        : $product->get_id();

    $product_language =
        apply_filters(
            'wpml_element_language_code',
            null,
            [
                'element_id' => $element_id,
                'element_type' => 'post_product',
            ]
        );

    if(
        !$product_language
        &&
        $product->is_type('variation')
    ){
        $product_language =
            apply_filters(
                'wpml_element_language_code',
                null,
                [
                    'element_id' => $product->get_id(),
                    'element_type' => 'post_product_variation',
                ]
            );
    } // END if

    return $product_language === $requested_language;
} // END function free_shipment_progressbar_admin_product_has_language()

function free_shipment_progressbar_admin_normalize_search_text(
    $value
){

    $value =
        strtolower(
            remove_accents(
                wp_strip_all_tags(
                    (string) $value
                )
            )
        );

    return preg_replace(
        '/[^a-z0-9]+/',
        ' ',
        $value
    );
} // END function free_shipment_progressbar_admin_normalize_search_text()

function free_shipment_progressbar_admin_search_aliases(
    $token
){

    $aliases = [
        'tiger' => ['tiger', 'tjing', 'tjau', 'tjin', 'tjauw'],
        'tijger' => ['tijger', 'tiger', 'tjing', 'tjau', 'tjin', 'tjauw'],
        'balsem' => ['balsem', 'balm'],
        'balm' => ['balm', 'balsem'],
    ];

    return $aliases[$token] ?? [$token];
} // END function free_shipment_progressbar_admin_search_aliases()

function free_shipment_progressbar_admin_product_matches_search(
    $product,
    $search
){

    $tokens =
        preg_split(
            '/\s+/',
            free_shipment_progressbar_admin_normalize_search_text($search),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

    if(
        empty($tokens)
    ){
        return true;
    } // END if

    $haystack =
        free_shipment_progressbar_admin_normalize_search_text(
            implode(
                ' ',
                [
                    $product->get_name(),
                    $product->get_sku(),
                    $product->get_slug(),
                ]
            )
        );

    foreach(
        $tokens as $token
    ){
        $matched = false;

        foreach(
            free_shipment_progressbar_admin_search_aliases($token) as $alias
        ){
            $alias =
                free_shipment_progressbar_admin_normalize_search_text($alias);

            if(
                $alias === ''
            ){
                continue;
            } // END if

            if(
                strpos($haystack, $alias) !== false
                ||
                preg_match('/\b' . preg_quote($alias, '/') . '/i', $haystack)
            ){
                $matched = true;
                break;
            } // END if
        } // END foreach

        if(
            !$matched
        ){
            return false;
        } // END if
    } // END foreach

    return true;
} // END function free_shipment_progressbar_admin_product_matches_search()

function free_shipment_progressbar_find_products_for_admin(
    $search,
    $language = ''
){

    $candidate_ids = [];

    $query =
        new WP_Query([
            'post_type' => ['product', 'product_variation'],
            'post_status' => 'publish',
            'posts_per_page' => 80,
            'fields' => 'ids',
            's' => $search,
            'lang' => $language,
            'suppress_filters' => false,
        ]);

    $candidate_ids =
        array_merge(
            $candidate_ids,
            $query->posts
        );

    $sku_query =
        new WP_Query([
            'post_type' => ['product', 'product_variation'],
            'post_status' => 'publish',
            'posts_per_page' => 80,
            'fields' => 'ids',
            'lang' => $language,
            'suppress_filters' => false,
            'meta_query' => [
                [
                    'key' => '_sku',
                    'value' => $search,
                    'compare' => 'LIKE',
                ],
            ],
        ]);

    $candidate_ids =
        array_merge(
            $candidate_ids,
            $sku_query->posts
        );

    $tokens =
        preg_split(
            '/\s+/',
            free_shipment_progressbar_admin_normalize_search_text($search),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

    foreach(
        $tokens as $token
    ){
        foreach(
            free_shipment_progressbar_admin_search_aliases($token) as $alias
        ){
            $alias =
                trim($alias);

            if(
                strlen($alias) < 3
            ){
                continue;
            } // END if

                $alias_query =
                    new WP_Query([
                        'post_type' => ['product', 'product_variation'],
                        'post_status' => 'publish',
                        'posts_per_page' => 80,
                        'fields' => 'ids',
                        's' => $alias,
                        'lang' => $language,
                        'suppress_filters' => false,
                    ]);

            $candidate_ids =
                array_merge(
                    $candidate_ids,
                    $alias_query->posts
                );
        } // END foreach
    } // END foreach

    $candidate_ids =
        array_merge(
            $candidate_ids,
            free_shipment_progressbar_admin_find_product_ids_by_tokens(
                $tokens
            )
        );

    $broad_query =
        new WP_Query([
            'post_type' => ['product', 'product_variation'],
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'fields' => 'ids',
            'orderby' => 'title',
            'order' => 'ASC',
            'lang' => $language,
            'suppress_filters' => false,
        ]);

    $candidate_ids =
        array_merge(
            $candidate_ids,
            $broad_query->posts
        );

    $products = [];

    foreach(
        array_values(array_unique(array_map('absint', $candidate_ids))) as $product_id
    ){
        $product =
            wc_get_product($product_id);

        if(
            !$product
            ||
            (
                function_exists('free_shipment_progressbar_is_wcpos_pos_only_product')
                &&
                free_shipment_progressbar_is_wcpos_pos_only_product($product)
            )
            ||
            (
                function_exists('free_shipment_progressbar_is_giftcard_product')
                &&
                free_shipment_progressbar_is_giftcard_product($product)
            )
            ||
            !free_shipment_progressbar_admin_product_has_language($product, $language)
            ||
            !free_shipment_progressbar_admin_product_matches_search($product, $search)
        ){
            continue;
        } // END if

        $products[$product->get_id()] = $product;

        if(
            count($products) >= 30
        ){
            break;
        } // END if
    } // END foreach

    return array_values($products);
} // END function free_shipment_progressbar_find_products_for_admin()

function free_shipment_progressbar_admin_find_product_ids_by_tokens(
    $tokens
){

    global $wpdb;

    $tokens =
        array_values(
            array_filter(
                array_map(
                    'trim',
                    (array) $tokens
                )
            )
        );

    if(
        empty($tokens)
    ){
        return [];
    } // END if

    $where_groups = [];
    $params = [];

    foreach(
        $tokens as $token
    ){
        $aliases =
            free_shipment_progressbar_admin_search_aliases($token);

        $alias_conditions = [];

        foreach(
            $aliases as $alias
        ){
            $alias =
                free_shipment_progressbar_admin_normalize_search_text($alias);

            if(
                $alias === ''
            ){
                continue;
            } // END if

            $like =
                '%' . $wpdb->esc_like($alias) . '%';

            $alias_conditions[] =
                '(p.post_title LIKE %s OR p.post_name LIKE %s OR sku.meta_value LIKE %s)';

            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        } // END foreach

        if(
            empty($alias_conditions)
        ){
            continue;
        } // END if

        $where_groups[] =
            '(' . implode(' OR ', $alias_conditions) . ')';
    } // END foreach

    if(
        empty($where_groups)
    ){
        return [];
    } // END if

    $sql =
        "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} sku
            ON sku.post_id = p.ID
            AND sku.meta_key = '_sku'
        WHERE p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
            AND " . implode(' AND ', $where_groups) . "
        ORDER BY p.post_title ASC
        LIMIT 120
        ";

    return array_map(
        'absint',
        $wpdb->get_col(
            $wpdb->prepare(
                $sql,
                $params
            )
        )
    );
} // END function free_shipment_progressbar_admin_find_product_ids_by_tokens()

function free_shipment_progressbar_admin_parse_ids(
    $value
){

    if(
        function_exists('free_shipment_progressbar_parse_ids')
    ){
        return free_shipment_progressbar_parse_ids($value);
    } // END if

    $parts =
        is_array($value)
        ? $value
        : preg_split(
            '/[\s,;|]+/',
            (string) $value
        );

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
} // END function free_shipment_progressbar_admin_parse_ids()

function free_shipment_progressbar_rules_for_export(){

    if(
        function_exists('free_shipment_progressbar_get_upsell_rules')
    ){
        return free_shipment_progressbar_get_upsell_rules();
    } // END if

    $rules =
        get_option(
            'free_shipment_progressbar_upsell_rules',
            []
        );

    return is_array($rules) ? $rules : [];
} // END function free_shipment_progressbar_rules_for_export()

function free_shipment_progressbar_get_product_category_terms(){

    $terms =
        get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

    if(
        is_wp_error($terms)
        || empty($terms)
    ){
        return [];
    } // END if

    return $terms;
} // END function free_shipment_progressbar_get_product_category_terms()

function free_shipment_progressbar_render_category_select(
    $name,
    $selected_ids,
    $terms
){

    $selected_ids =
        array_map(
            'absint',
            (array) $selected_ids
        );

    ?>
    <select
        class="free-shipment-progressbar-fsb-select free-shipment-progressbar-fsb-category-select"
        name="<?php echo esc_attr($name); ?>[]"
        multiple
        data-placeholder="<?php esc_attr_e('Choose categories...', 'free_shipment_progressbar'); ?>"
    >
        <?php foreach($terms as $term) : ?>
            <option
                value="<?php echo esc_attr($term->term_id); ?>"
                <?php selected(in_array((int) $term->term_id, $selected_ids, true)); ?>
            >
                <?php echo esc_html($term->name . ' (#' . $term->term_id . ')'); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
} // END function free_shipment_progressbar_render_category_select()

function free_shipment_progressbar_render_product_select(
    $name,
    $selected_ids
){

    $selected_ids =
        array_map(
            'absint',
            (array) $selected_ids
        );

    ?>
    <select
        class="free-shipment-progressbar-fsb-select free-shipment-progressbar-fsb-product-select"
        name="<?php echo esc_attr($name); ?>[]"
        multiple
        data-placeholder="<?php esc_attr_e('Search products...', 'free_shipment_progressbar'); ?>"
    >
        <?php foreach($selected_ids as $product_id) : ?>
            <?php
            $product =
                wc_get_product($product_id);

            if(
                !$product
            ){
                continue;
            } // END if
            ?>
            <option
                value="<?php echo esc_attr($product_id); ?>"
                selected
            >
                <?php echo esc_html(free_shipment_progressbar_admin_product_label($product)); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
} // END function free_shipment_progressbar_render_product_select()

function free_shipment_progressbar_render_rule_card(
    $rule,
    $index,
    $terms
){

    $enabled =
        !empty($rule['enabled']);

    $priority =
        isset($rule['priority'])
        ? (int) $rule['priority']
        : 0;

    ?>
    <section class="free-shipment-progressbar-fsb-rule" data-rule>
        <div class="free-shipment-progressbar-fsb-rule__top">
            <label class="free-shipment-progressbar-fsb-toggle">
                <input
                    type="checkbox"
                    name="upsell_rules[<?php echo esc_attr($index); ?>][enabled]"
                    value="1"
                    <?php checked($enabled); ?>
                >
                <span><?php esc_html_e('Active', 'free_shipment_progressbar'); ?></span>
            </label>

            <label class="free-shipment-progressbar-fsb-priority">
                <span><?php esc_html_e('Priority', 'free_shipment_progressbar'); ?></span>
                <input
                    type="number"
                    name="upsell_rules[<?php echo esc_attr($index); ?>][priority]"
                    value="<?php echo esc_attr($priority); ?>"
                    step="1"
                >
            </label>

            <button
                type="button"
                class="button-link-delete free-shipment-progressbar-fsb-remove-rule"
            >
                <?php esc_html_e('Remove rule', 'free_shipment_progressbar'); ?>
            </button>
        </div>

        <div class="free-shipment-progressbar-fsb-flow">
            <div class="free-shipment-progressbar-fsb-panel">
                <h3><?php esc_html_e('When this is in the cart', 'free_shipment_progressbar'); ?></h3>

                <label>
                    <span><?php esc_html_e('Source categories', 'free_shipment_progressbar'); ?></span>
                    <?php
                    free_shipment_progressbar_render_category_select(
                        'upsell_rules[' . $index . '][source_categories]',
                        $rule['source_categories'] ?? [],
                        $terms
                    );
                    ?>
                </label>

                <label>
                    <span><?php esc_html_e('Source products', 'free_shipment_progressbar'); ?></span>
                    <?php
                    free_shipment_progressbar_render_product_select(
                        'upsell_rules[' . $index . '][source_products]',
                        $rule['source_products'] ?? []
                    );
                    ?>
                </label>
            </div>

            <div class="free-shipment-progressbar-fsb-arrow" aria-hidden="true">→</div>

            <div class="free-shipment-progressbar-fsb-panel">
                <h3><?php esc_html_e('Show these upsells', 'free_shipment_progressbar'); ?></h3>

                <label>
                    <span><?php esc_html_e('Target categories', 'free_shipment_progressbar'); ?></span>
                    <?php
                    free_shipment_progressbar_render_category_select(
                        'upsell_rules[' . $index . '][target_categories]',
                        $rule['target_categories'] ?? [],
                        $terms
                    );
                    ?>
                </label>

                <label>
                    <span><?php esc_html_e('Target products', 'free_shipment_progressbar'); ?></span>
                    <?php
                    free_shipment_progressbar_render_product_select(
                        'upsell_rules[' . $index . '][target_products]',
                        $rule['target_products'] ?? []
                    );
                    ?>
                </label>
            </div>
        </div>
    </section>
    <?php
} // END function free_shipment_progressbar_render_rule_card()

function free_shipment_progressbar_settings_page(){

    if(
        !current_user_can('manage_woocommerce')
    ){
        wp_die(
            esc_html__('You do not have permission to access this page.', 'free_shipment_progressbar')
        );
    } // END if

    $message = '';
    $message_type = 'success';

    if(
        (
            isset($_POST['free_shipment_progressbar_save'])
            || isset($_POST['reset_default_rules'])
        )
        && check_admin_referer(
            'free_shipment_progressbar_save_settings',
            'free_shipment_progressbar_nonce'
        )
    ){
        update_option(
            'free_shipment_progressbar_debug',
            isset($_POST['debug_mode'])
                ? 'yes'
                : 'no'
        );

        update_option(
            'free_shipment_progressbar_carousel_interval',
            isset($_POST['carousel_interval'])
                ? min(
                    300,
                    max(
                        0,
                        absint($_POST['carousel_interval'])
                    )
                )
                : 30,
            false
        );

        update_option(
            'free_shipment_progressbar_wpml_source_language',
            'en',
            false
        );

        if(
            isset($_POST['reset_default_rules'])
        ){
            delete_option('free_shipment_progressbar_upsell_rules');
            $message = __('Default upsell rules restored.', 'free_shipment_progressbar');
        }elseif(
            !empty($_POST['rules_import'])
        ){
            $decoded =
                json_decode(
                    wp_unslash($_POST['rules_import']),
                    true
                );

            if(
                is_array($decoded)
            ){
                $rules =
                    function_exists('free_shipment_progressbar_normalize_upsell_rules')
                    ? free_shipment_progressbar_normalize_upsell_rules($decoded)
                    : $decoded;

                update_option(
                    'free_shipment_progressbar_upsell_rules',
                    $rules,
                    false
                );

                $message = __('Upsell rules imported.', 'free_shipment_progressbar');
            }else{
                $message = __('Import failed: JSON is invalid.', 'free_shipment_progressbar');
                $message_type = 'error';
            } // END if
        }else{
            $posted_rules =
                isset($_POST['upsell_rules'])
                && is_array($_POST['upsell_rules'])
                ? wp_unslash($_POST['upsell_rules'])
                : [];

            $rules = [];

            foreach(
                $posted_rules as $posted_rule
            ){
                if(
                    !is_array($posted_rule)
                ){
                    continue;
                } // END if

                $rule = [
                    'enabled' => !empty($posted_rule['enabled']),
                    'priority' => isset($posted_rule['priority'])
                        ? (int) $posted_rule['priority']
                        : 0,
                    'source_categories' => free_shipment_progressbar_admin_parse_ids($posted_rule['source_categories'] ?? []),
                    'target_categories' => free_shipment_progressbar_admin_parse_ids($posted_rule['target_categories'] ?? []),
                    'source_products' => free_shipment_progressbar_admin_parse_ids($posted_rule['source_products'] ?? []),
                    'target_products' => free_shipment_progressbar_admin_parse_ids($posted_rule['target_products'] ?? []),
                ];

                if(
                    empty($rule['source_categories'])
                    && empty($rule['source_products'])
                    && empty($rule['target_categories'])
                    && empty($rule['target_products'])
                ){
                    continue;
                } // END if

                $rules[] = $rule;
            } // END foreach

            if(
                function_exists('free_shipment_progressbar_normalize_upsell_rules')
            ){
                $rules =
                    free_shipment_progressbar_normalize_upsell_rules($rules);
            } // END if

            update_option(
                'free_shipment_progressbar_upsell_rules',
                $rules,
                false
            );

            $message = __('Settings saved.', 'free_shipment_progressbar');
        }
    } // END if

    $debug =
        get_option(
            'free_shipment_progressbar_debug',
            'no'
        );

    $carousel_interval =
        function_exists('free_shipment_progressbar_get_carousel_interval_seconds')
        ? free_shipment_progressbar_get_carousel_interval_seconds()
        : absint(
            get_option(
                'free_shipment_progressbar_carousel_interval',
                30
            )
        );

    $rules =
        free_shipment_progressbar_rules_for_export();

    if(
        empty($rules)
    ){
        $rules = [
            [
                'enabled' => true,
                'priority' => 100,
                'source_categories' => [],
                'target_categories' => [],
                'source_products' => [],
                'target_products' => [],
            ],
        ];
    } // END if

    $terms =
        free_shipment_progressbar_get_product_category_terms();

    $export =
        wp_json_encode(
            $rules,
            JSON_PRETTY_PRINT
        );

?>

<div class="wrap free-shipment-progressbar-fsb-admin">

    <h1><?php esc_html_e('Free Shipping and Progressbar PRO', 'free_shipment_progressbar'); ?></h1>

    <?php if ($message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <style>
        .free-shipment-progressbar-fsb-admin {
            max-width: 1280px;
        }

        .free-shipment-progressbar-fsb-toolbar,
        .free-shipment-progressbar-fsb-card,
        .free-shipment-progressbar-fsb-rule {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }

        .free-shipment-progressbar-fsb-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 18px;
            margin: 18px 0;
        }

        .free-shipment-progressbar-fsb-toolbar p,
        .free-shipment-progressbar-fsb-card p {
            margin: 4px 0 0;
            color: #646970;
        }

        .free-shipment-progressbar-fsb-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .free-shipment-progressbar-fsb-card {
            padding: 18px;
            margin-top: 20px;
        }

        .free-shipment-progressbar-fsb-card h2,
        .free-shipment-progressbar-fsb-rule h3 {
            margin-top: 0;
        }

        .free-shipment-progressbar-fsb-rule {
            margin: 14px 0;
            padding: 16px;
        }

        .free-shipment-progressbar-fsb-rule__top {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f0f0f1;
        }

        .free-shipment-progressbar-fsb-toggle,
        .free-shipment-progressbar-fsb-priority {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .free-shipment-progressbar-fsb-priority input {
            width: 90px;
        }

        .free-shipment-progressbar-fsb-remove-rule {
            margin-left: auto;
        }

        .free-shipment-progressbar-fsb-flow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 44px minmax(0, 1fr);
            gap: 16px;
            align-items: stretch;
            margin-top: 16px;
        }

        .free-shipment-progressbar-fsb-panel {
            background: #f6f7f7;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 16px;
            position: relative;
        }

        .free-shipment-progressbar-fsb-panel label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
        }

        .free-shipment-progressbar-fsb-panel label > span {
            display: block;
            margin-bottom: 6px;
        }

        .free-shipment-progressbar-fsb-select,
        .free-shipment-progressbar-fsb-panel .select2,
        .free-shipment-progressbar-fsb-panel .selectWoo,
        .free-shipment-progressbar-fsb-panel .select2-container,
        .free-shipment-progressbar-fsb-panel .selectWoo-container,
        .free-shipment-progressbar-fsb-panel .woocommerce-enhanced-select {
            width: 100% !important;
            max-width: 100%;
        }

        .free-shipment-progressbar-fsb-panel select.free-shipment-progressbar-fsb-select {
            min-height: 74px;
        }

        .free-shipment-progressbar-fsb-panel .select2-container,
        .free-shipment-progressbar-fsb-panel .selectWoo-container {
            display: block;
            min-height: 64px;
        }

        .free-shipment-progressbar-fsb-panel select.select2-hidden-accessible,
        .free-shipment-progressbar-fsb-panel select.enhanced,
        .free-shipment-progressbar-fsb-panel select.wc-enhanced-select {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            min-height: 1px !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            clip: rect(0 0 0 0) !important;
            border: 0 !important;
        }

        /*
         * WordPress/WooCommerce installs can expose either SelectWoo,
         * Select2 v4-style classes, or older Select2 v3-style classes.
         * These fallback rules keep search results as a real overlay even
         * when the matching library stylesheet is not registered.
         */
        body.wp-admin .select2-container,
        body.wp-admin .selectWoo-container {
            box-sizing: border-box;
            display: inline-block;
            margin: 0;
            position: relative;
            vertical-align: middle;
        }

        body.wp-admin .select2-container--open,
        body.wp-admin .selectWoo-container--open {
            z-index: 100000 !important;
        }

        body.wp-admin .select2-dropdown,
        body.wp-admin .selectWoo-dropdown {
            background: #fff;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
            box-sizing: border-box;
            display: block;
            left: -100000px;
            position: absolute;
            width: 100%;
            z-index: 100001 !important;
        }

        body.wp-admin .select2-container--open .select2-dropdown,
        body.wp-admin .selectWoo-container--open .selectWoo-dropdown {
            left: 0;
        }

        body.wp-admin .select2-results,
        body.wp-admin .selectWoo-results {
            display: block;
        }

        body.wp-admin .select2-results__options,
        body.wp-admin .selectWoo-results__options {
            list-style: none;
            margin: 0;
            max-height: 260px;
            overflow-y: auto;
            padding: 0;
        }

        body.wp-admin .select2-results__option,
        body.wp-admin .selectWoo-results__option {
            cursor: pointer;
            display: block;
            list-style: none;
            margin: 0;
            padding: 8px 10px;
            white-space: normal;
        }

        body.wp-admin .select2-results__option--highlighted,
        body.wp-admin .selectWoo-results__option--highlighted,
        body.wp-admin .select2-results__option[aria-selected="true"],
        body.wp-admin .selectWoo-results__option[aria-selected="true"] {
            background: #2271b1;
            color: #fff;
        }

        body.wp-admin .select2-drop {
            background: #fff;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
            box-sizing: border-box;
            margin-top: 2px;
            position: absolute !important;
            z-index: 100001 !important;
        }

        body.wp-admin .select2-drop .select2-results {
            list-style: none;
            margin: 0;
            max-height: 260px;
            overflow-y: auto;
            padding: 0;
        }

        body.wp-admin .select2-drop .select2-results li {
            cursor: pointer;
            display: block;
            list-style: none;
            margin: 0;
            padding: 8px 10px;
            white-space: normal;
        }

        body.wp-admin .select2-offscreen,
        body.wp-admin .select2-offscreen:focus {
            clip: rect(0 0 0 0) !important;
            height: 1px !important;
            left: 0 !important;
            margin: 0 !important;
            outline: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            top: 0 !important;
            width: 1px !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-selection--multiple,
        .free-shipment-progressbar-fsb-panel .selectWoo-selection--multiple {
            min-height: 64px !important;
            padding: 6px 8px !important;
            border-color: #8c8f94 !important;
            border-radius: 4px !important;
            background: #fff !important;
            box-sizing: border-box;
        }

        .free-shipment-progressbar-fsb-panel .select2-selection__rendered,
        .free-shipment-progressbar-fsb-panel .selectWoo-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-selection__choice,
        .free-shipment-progressbar-fsb-panel .selectWoo-selection__choice {
            display: inline-flex !important;
            align-items: center !important;
            gap: 5px !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 4px 8px !important;
            background: #f0f6fc !important;
            border: 1px solid #b8d6f3 !important;
            border-radius: 999px !important;
            color: #1d2327 !important;
            line-height: 1.4 !important;
            white-space: normal !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-selection__choice__remove,
        .free-shipment-progressbar-fsb-panel .selectWoo-selection__choice__remove,
        .free-shipment-progressbar-fsb-panel .select2-search-choice-close {
            align-items: center !important;
            background: transparent !important;
            border: 0 !important;
            border-radius: 999px !important;
            color: #1d2327 !important;
            cursor: pointer !important;
            display: inline-flex !important;
            flex: 0 0 auto !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            height: 18px !important;
            justify-content: center !important;
            line-height: 18px !important;
            margin: 0 2px 0 0 !important;
            order: -1 !important;
            padding: 0 !important;
            position: static !important;
            text-decoration: none !important;
            width: 18px !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-selection__choice__remove:hover,
        .free-shipment-progressbar-fsb-panel .selectWoo-selection__choice__remove:hover,
        .free-shipment-progressbar-fsb-panel .select2-search-choice-close:hover {
            background: #dcdcde !important;
            color: #b32d2e !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-search--inline,
        .free-shipment-progressbar-fsb-panel .selectWoo-search--inline {
            flex: 1 1 240px !important;
            min-width: 220px !important;
        }

        .free-shipment-progressbar-fsb-panel .select2-search__field,
        .free-shipment-progressbar-fsb-panel .selectWoo-search__field {
            width: 100% !important;
            min-width: 220px !important;
            margin: 3px 0 !important;
            box-shadow: none !important;
        }

        .select2-container--open,
        .selectWoo-container--open {
            z-index: 100000 !important;
        }

        body.wp-admin .select2-dropdown,
        body.wp-admin .selectWoo-dropdown {
            z-index: 100001 !important;
        }

        body.wp-admin .select2-results__option,
        body.wp-admin .selectWoo-results__option {
            padding: 8px 10px;
            white-space: normal;
        }

        body.wp-admin .select2-container--open .select2-dropdown,
        body.wp-admin .selectWoo-container--open .selectWoo-dropdown {
            box-shadow: 0 8px 24px rgba(0,0,0,.18);
        }

        .free-shipment-progressbar-fsb-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2271b1;
            font-size: 28px;
            font-weight: 700;
        }

        .free-shipment-progressbar-fsb-import-export {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 18px;
        }

        .free-shipment-progressbar-fsb-admin textarea {
            min-height: 220px;
        }

        @media (max-width: 960px) {
            .free-shipment-progressbar-fsb-toolbar,
            .free-shipment-progressbar-fsb-rule__top,
            .free-shipment-progressbar-fsb-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .free-shipment-progressbar-fsb-flow,
            .free-shipment-progressbar-fsb-import-export {
                grid-template-columns: 1fr;
            }

            .free-shipment-progressbar-fsb-arrow {
                display: none;
            }
        }
    </style>

    <form method="post">

        <?php wp_nonce_field('free_shipment_progressbar_save_settings', 'free_shipment_progressbar_nonce'); ?>

        <div class="free-shipment-progressbar-fsb-toolbar">
            <div>
                <h2><?php esc_html_e('Upsell rules', 'free_shipment_progressbar'); ?></h2>
                <p><?php esc_html_e('Create rules based on categories or specific products. Higher priority is shown first.', 'free_shipment_progressbar'); ?></p>
                <p><?php esc_html_e('WPML source language for plugin strings: English (en). Product selectors follow the active product language.', 'free_shipment_progressbar'); ?></p>
            </div>

            <div class="free-shipment-progressbar-fsb-actions">
                <label>
                    <input
                        type="checkbox"
                        name="debug_mode"
                        <?php checked($debug, 'yes'); ?>
                    >
                    <?php esc_html_e('Debug logging', 'free_shipment_progressbar'); ?>
                </label>

                <label class="free-shipment-progressbar-fsb-interval">
                    <span><?php esc_html_e('Carousel interval', 'free_shipment_progressbar'); ?></span>
                    <input
                        type="number"
                        name="carousel_interval"
                        value="<?php echo esc_attr($carousel_interval); ?>"
                        min="0"
                        max="300"
                        step="1"
                    >
                    <span><?php esc_html_e('seconds', 'free_shipment_progressbar'); ?></span>
                </label>

                <button
                    type="button"
                    class="button"
                    id="free-shipment-progressbar-fsb-add-rule"
                >
                    <?php esc_html_e('New rule', 'free_shipment_progressbar'); ?>
                </button>

                <button
                    class="button button-primary"
                    type="submit"
                    name="free_shipment_progressbar_save"
                    value="1"
                >
                    <?php esc_html_e('Save', 'free_shipment_progressbar'); ?>
                </button>
            </div>
        </div>

        <div id="free-shipment-progressbar-fsb-rules">
            <?php
            $row_index = 0;

            foreach(
                $rules as $rule
            ){
                free_shipment_progressbar_render_rule_card(
                    $rule,
                    $row_index,
                    $terms
                );

                $row_index++;
            } // END foreach
            ?>
        </div>

        <div class="free-shipment-progressbar-fsb-card">
            <h2><?php esc_html_e('Import / export', 'free_shipment_progressbar'); ?></h2>
            <p><?php esc_html_e('Export uses the same ID structure as earlier versions. Import replaces all current rules.', 'free_shipment_progressbar'); ?></p>

            <div class="free-shipment-progressbar-fsb-import-export">
                <label>
                    <strong><?php esc_html_e('Export', 'free_shipment_progressbar'); ?></strong>
                    <textarea readonly class="large-text code"><?php echo esc_textarea($export); ?></textarea>
                </label>

                <label>
                    <strong><?php esc_html_e('Import', 'free_shipment_progressbar'); ?></strong>
                    <textarea
                        name="rules_import"
                        class="large-text code"
                        placeholder="<?php esc_attr_e('Paste JSON import here', 'free_shipment_progressbar'); ?>"
                    ></textarea>
                </label>
            </div>

            <p class="free-shipment-progressbar-fsb-actions">
                <button
                    class="button"
                    type="submit"
                    name="free_shipment_progressbar_save"
                    value="1"
                >
                    <?php esc_html_e('Run import', 'free_shipment_progressbar'); ?>
                </button>

                <button
                    class="button"
                    type="submit"
                    name="reset_default_rules"
                    value="1"
                    onclick="return confirm('<?php echo esc_js(__('Restore default upsell rules?', 'free_shipment_progressbar')); ?>');"
                >
                    <?php esc_html_e('Restore default rules', 'free_shipment_progressbar'); ?>
                </button>
            </p>
        </div>

    </form>

    <template id="free-shipment-progressbar-fsb-rule-template">
        <?php
        free_shipment_progressbar_render_rule_card(
            [
                'enabled' => true,
                'priority' => 0,
                'source_categories' => [],
                'target_categories' => [],
                'source_products' => [],
                'target_products' => [],
            ],
            '__INDEX__',
            $terms
        );
        ?>
    </template>

    <script>
    jQuery(function($){

        const productSearchNonce =
            '<?php echo esc_js(wp_create_nonce('free_shipment_progressbar_product_search')); ?>';

        const productSearchLanguage =
            new URLSearchParams(window.location.search).get('lang')
            || '<?php echo esc_js(free_shipment_progressbar_admin_get_requested_product_language()); ?>';

        let nextIndex =
            <?php echo (int) $row_index; ?>;

        function enhance(context) {

            const root =
                context ? $(context) : $(document);

            root.find('.free-shipment-progressbar-fsb-select').each(function(){

                const select =
                    $(this);

                if (
                    select.hasClass('enhanced')
                    ||
                    select.data('select2')
                    ||
                    select.data('selectWoo')
                ) {
                    return;
                } // END if

                if (
                    select.hasClass('free-shipment-progressbar-fsb-product-select')
                ) {

                    const config = {
                        width: '100%',
                        dropdownParent: $(document.body),
                        placeholder: select.data('placeholder') || '<?php echo esc_js(__('Search products...', 'free_shipment_progressbar')); ?>',
                        minimumInputLength: 2,
                        closeOnSelect: false,
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    action: 'free_shipment_progressbar_search_products',
                                    security: productSearchNonce,
                                    term: params.term || '',
                                    lang: productSearchLanguage
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data || []
                                };
                            }
                        }
                    };

                    if ($.fn.selectWoo) {
                        select.selectWoo(config);
                        select.addClass('enhanced');
                        return;
                    } // END if

                    if ($.fn.select2) {
                        select.select2(config);
                        select.addClass('enhanced');
                        return;
                    } // END if

                    return;
                } // END if

                if ($.fn.selectWoo) {
                    select.selectWoo({
                        width: '100%',
                        dropdownParent: $(document.body),
                        closeOnSelect: false,
                        placeholder: select.data('placeholder') || ''
                    });
                    select.addClass('enhanced');
                    return;
                } // END if

                if ($.fn.select2) {
                    select.select2({
                        width: '100%',
                        dropdownParent: $(document.body),
                        closeOnSelect: false,
                        placeholder: select.data('placeholder') || ''
                    });
                    select.addClass('enhanced');
                } // END if
            });
        } // END function enhance()

        function addProductCategoriesToPanel(productSelect, selectedProduct) {

            if (
                !selectedProduct
                ||
                !Array.isArray(selectedProduct.categories)
                ||
                !selectedProduct.categories.length
            ) {
                return;
            } // END if

            const panel =
                productSelect.closest('.free-shipment-progressbar-fsb-panel');

            const categorySelect =
                panel.find('.free-shipment-progressbar-fsb-category-select').first();

            if (
                !categorySelect.length
            ) {
                return;
            } // END if

            let changed = false;

            selectedProduct.categories.forEach(function(category){

                const categoryId =
                    String(category.id || '');

                if (
                    !categoryId
                ) {
                    return;
                } // END if

                let option =
                    categorySelect.find('option[value="' + categoryId.replace(/"/g, '\\"') + '"]');

                if (
                    !option.length
                ) {
                    option =
                        $('<option>')
                            .val(categoryId)
                            .text(category.text || categoryId);

                    categorySelect.append(option);
                } // END if

                if (
                    !option.prop('selected')
                ) {
                    option.prop('selected', true);
                    changed = true;
                } // END if
            });

            if (
                changed
            ) {
                categorySelect.trigger('change');
            } // END if
        } // END function addProductCategoriesToPanel()

        enhance(document);

        $(document).on(
            'select2:select selectWoo:select',
            '.free-shipment-progressbar-fsb-product-select',
            function(event){

                addProductCategoriesToPanel(
                    $(this),
                    event.params ? event.params.data : null
                );
            }
        );

        $(document).on(
            'select2-selecting',
            '.free-shipment-progressbar-fsb-product-select',
            function(event){

                addProductCategoriesToPanel(
                    $(this),
                    event.choice || null
                );
            }
        );

        $('#free-shipment-progressbar-fsb-add-rule').on('click', function(){

            const template =
                $('#free-shipment-progressbar-fsb-rule-template')
                    .html()
                    .split('__INDEX__')
                    .join(String(nextIndex));

            const node =
                $(template);

            $('#free-shipment-progressbar-fsb-rules')
                .prepend(node);

            nextIndex++;
            enhance(node);

            $('html, body').animate(
                {
                    scrollTop: Math.max(
                        0,
                        node.offset().top - 90
                    )
                },
                200
            );

            window.setTimeout(function(){

                node
                    .find('.free-shipment-progressbar-fsb-product-select')
                    .first()
                    .trigger('focus');

            }, 250);
        });

        $(document).on('click', '.free-shipment-progressbar-fsb-remove-rule', function(){

            $(this)
                .closest('[data-rule]')
                .remove();
        });
    });
    </script>

</div>

<?php

} // END function free_shipment_progressbar_settings_page()
