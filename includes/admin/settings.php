<?php

if (!defined('ABSPATH')) {
    exit;
} // END if

add_action(
    'admin_enqueue_scripts',
    'tokolariso_fsb_admin_assets'
);

function tokolariso_fsb_admin_assets(
    $hook
){

    if(
        $hook !== 'woocommerce_page_tokolariso-fsb'
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
} // END function tokolariso_fsb_admin_assets()

add_action(
    'wp_ajax_tokolariso_fsb_search_products',
    'tokolariso_fsb_search_products'
);

function tokolariso_fsb_search_products(){

    if(
        !current_user_can('manage_woocommerce')
        ||
        !check_ajax_referer(
            'tokolariso_fsb_product_search',
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

    $products =
        tokolariso_fsb_find_products_for_admin($term);

    $results = [];

    foreach(
        $products as $product
    ){
        $results[] = [
            'id' => $product->get_id(),
            'text' => tokolariso_fsb_admin_product_label($product),
            'categories' => tokolariso_fsb_admin_product_categories_for_select($product),
        ];
    } // END foreach

    wp_send_json($results);
} // END function tokolariso_fsb_search_products()

function tokolariso_fsb_admin_product_categories_for_select(
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
} // END function tokolariso_fsb_admin_product_categories_for_select()

function tokolariso_fsb_admin_product_label(
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
} // END function tokolariso_fsb_admin_product_label()

function tokolariso_fsb_admin_normalize_search_text(
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
} // END function tokolariso_fsb_admin_normalize_search_text()

function tokolariso_fsb_admin_search_aliases(
    $token
){

    $aliases = [
        'tiger' => ['tiger', 'tjing', 'tjau', 'tjin', 'tjauw'],
        'tijger' => ['tijger', 'tiger', 'tjing', 'tjau', 'tjin', 'tjauw'],
        'balsem' => ['balsem', 'balm'],
        'balm' => ['balm', 'balsem'],
    ];

    return $aliases[$token] ?? [$token];
} // END function tokolariso_fsb_admin_search_aliases()

function tokolariso_fsb_admin_product_matches_search(
    $product,
    $search
){

    $tokens =
        preg_split(
            '/\s+/',
            tokolariso_fsb_admin_normalize_search_text($search),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

    if(
        empty($tokens)
    ){
        return true;
    } // END if

    $haystack =
        tokolariso_fsb_admin_normalize_search_text(
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
            tokolariso_fsb_admin_search_aliases($token) as $alias
        ){
            $alias =
                tokolariso_fsb_admin_normalize_search_text($alias);

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
} // END function tokolariso_fsb_admin_product_matches_search()

function tokolariso_fsb_find_products_for_admin(
    $search
){

    $candidate_ids = [];

    $query =
        new WP_Query([
            'post_type' => ['product', 'product_variation'],
            'post_status' => 'publish',
            'posts_per_page' => 80,
            'fields' => 'ids',
            's' => $search,
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
            tokolariso_fsb_admin_normalize_search_text($search),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

    foreach(
        $tokens as $token
    ){
        foreach(
            tokolariso_fsb_admin_search_aliases($token) as $alias
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
            tokolariso_fsb_admin_find_product_ids_by_tokens(
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
                function_exists('tokolariso_fsb_is_wcpos_pos_only_product')
                &&
                tokolariso_fsb_is_wcpos_pos_only_product($product)
            )
            ||
            !tokolariso_fsb_admin_product_matches_search($product, $search)
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
} // END function tokolariso_fsb_find_products_for_admin()

function tokolariso_fsb_admin_find_product_ids_by_tokens(
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
            tokolariso_fsb_admin_search_aliases($token);

        $alias_conditions = [];

        foreach(
            $aliases as $alias
        ){
            $alias =
                tokolariso_fsb_admin_normalize_search_text($alias);

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
} // END function tokolariso_fsb_admin_find_product_ids_by_tokens()

function tokolariso_fsb_admin_parse_ids(
    $value
){

    if(
        function_exists('tokolariso_fsb_parse_ids')
    ){
        return tokolariso_fsb_parse_ids($value);
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
} // END function tokolariso_fsb_admin_parse_ids()

function tokolariso_fsb_rules_for_export(){

    if(
        function_exists('tokolariso_fsb_get_upsell_rules')
    ){
        return tokolariso_fsb_get_upsell_rules();
    } // END if

    $rules =
        get_option(
            'tokolariso_fsb_upsell_rules',
            []
        );

    return is_array($rules) ? $rules : [];
} // END function tokolariso_fsb_rules_for_export()

function tokolariso_fsb_get_product_category_terms(){

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
} // END function tokolariso_fsb_get_product_category_terms()

function tokolariso_fsb_render_category_select(
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
        class="tokolariso-fsb-select tokolariso-fsb-category-select"
        name="<?php echo esc_attr($name); ?>[]"
        multiple
        data-placeholder="<?php esc_attr_e('Kies categorieen...', 'tokolariso'); ?>"
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
} // END function tokolariso_fsb_render_category_select()

function tokolariso_fsb_render_product_select(
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
        class="tokolariso-fsb-select tokolariso-fsb-product-select"
        name="<?php echo esc_attr($name); ?>[]"
        multiple
        data-placeholder="<?php esc_attr_e('Zoek producten...', 'tokolariso'); ?>"
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
                <?php echo esc_html(tokolariso_fsb_admin_product_label($product)); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
} // END function tokolariso_fsb_render_product_select()

function tokolariso_fsb_render_rule_card(
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
    <section class="tokolariso-fsb-rule" data-rule>
        <div class="tokolariso-fsb-rule__top">
            <label class="tokolariso-fsb-toggle">
                <input
                    type="checkbox"
                    name="upsell_rules[<?php echo esc_attr($index); ?>][enabled]"
                    value="1"
                    <?php checked($enabled); ?>
                >
                <span><?php esc_html_e('Actief', 'tokolariso'); ?></span>
            </label>

            <label class="tokolariso-fsb-priority">
                <span><?php esc_html_e('Prioriteit', 'tokolariso'); ?></span>
                <input
                    type="number"
                    name="upsell_rules[<?php echo esc_attr($index); ?>][priority]"
                    value="<?php echo esc_attr($priority); ?>"
                    step="1"
                >
            </label>

            <button
                type="button"
                class="button-link-delete tokolariso-fsb-remove-rule"
            >
                <?php esc_html_e('Regel verwijderen', 'tokolariso'); ?>
            </button>
        </div>

        <div class="tokolariso-fsb-flow">
            <div class="tokolariso-fsb-panel">
                <h3><?php esc_html_e('Als dit in de winkelwagen zit', 'tokolariso'); ?></h3>

                <label>
                    <span><?php esc_html_e('Broncategorieen', 'tokolariso'); ?></span>
                    <?php
                    tokolariso_fsb_render_category_select(
                        'upsell_rules[' . $index . '][source_categories]',
                        $rule['source_categories'] ?? [],
                        $terms
                    );
                    ?>
                </label>

                <label>
                    <span><?php esc_html_e('Bronproducten', 'tokolariso'); ?></span>
                    <?php
                    tokolariso_fsb_render_product_select(
                        'upsell_rules[' . $index . '][source_products]',
                        $rule['source_products'] ?? []
                    );
                    ?>
                </label>
            </div>

            <div class="tokolariso-fsb-arrow" aria-hidden="true">→</div>

            <div class="tokolariso-fsb-panel">
                <h3><?php esc_html_e('Toon deze upsells', 'tokolariso'); ?></h3>

                <label>
                    <span><?php esc_html_e('Doelcategorieen', 'tokolariso'); ?></span>
                    <?php
                    tokolariso_fsb_render_category_select(
                        'upsell_rules[' . $index . '][target_categories]',
                        $rule['target_categories'] ?? [],
                        $terms
                    );
                    ?>
                </label>

                <label>
                    <span><?php esc_html_e('Doelproducten', 'tokolariso'); ?></span>
                    <?php
                    tokolariso_fsb_render_product_select(
                        'upsell_rules[' . $index . '][target_products]',
                        $rule['target_products'] ?? []
                    );
                    ?>
                </label>
            </div>
        </div>
    </section>
    <?php
} // END function tokolariso_fsb_render_rule_card()

function tokolariso_fsb_settings_page(){

    if(
        !current_user_can('manage_woocommerce')
    ){
        wp_die(
            esc_html__('Je hebt geen rechten voor deze pagina.', 'tokolariso')
        );
    } // END if

    $message = '';
    $message_type = 'success';

    if(
        (
            isset($_POST['tokolariso_save'])
            || isset($_POST['reset_default_rules'])
        )
        && check_admin_referer(
            'tokolariso_fsb_save_settings',
            'tokolariso_fsb_nonce'
        )
    ){
        update_option(
            'tokolariso_fsb_debug',
            isset($_POST['debug_mode'])
                ? 'yes'
                : 'no'
        );

        update_option(
            'tokolariso_fsb_carousel_interval',
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
            'tokolariso_fsb_wpml_source_language',
            'nl',
            false
        );

        if(
            isset($_POST['reset_default_rules'])
        ){
            delete_option('tokolariso_fsb_upsell_rules');
            $message = __('Standaard upsellregels hersteld.', 'tokolariso');
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
                    function_exists('tokolariso_fsb_normalize_upsell_rules')
                    ? tokolariso_fsb_normalize_upsell_rules($decoded)
                    : $decoded;

                update_option(
                    'tokolariso_fsb_upsell_rules',
                    $rules,
                    false
                );

                $message = __('Upsellregels geimporteerd.', 'tokolariso');
            }else{
                $message = __('Import mislukt: JSON is ongeldig.', 'tokolariso');
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
                    'source_categories' => tokolariso_fsb_admin_parse_ids($posted_rule['source_categories'] ?? []),
                    'target_categories' => tokolariso_fsb_admin_parse_ids($posted_rule['target_categories'] ?? []),
                    'source_products' => tokolariso_fsb_admin_parse_ids($posted_rule['source_products'] ?? []),
                    'target_products' => tokolariso_fsb_admin_parse_ids($posted_rule['target_products'] ?? []),
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
                function_exists('tokolariso_fsb_normalize_upsell_rules')
            ){
                $rules =
                    tokolariso_fsb_normalize_upsell_rules($rules);
            } // END if

            update_option(
                'tokolariso_fsb_upsell_rules',
                $rules,
                false
            );

            $message = __('Instellingen opgeslagen.', 'tokolariso');
        }
    } // END if

    $debug =
        get_option(
            'tokolariso_fsb_debug',
            'no'
        );

    $carousel_interval =
        function_exists('tokolariso_fsb_get_carousel_interval_seconds')
        ? tokolariso_fsb_get_carousel_interval_seconds()
        : absint(
            get_option(
                'tokolariso_fsb_carousel_interval',
                30
            )
        );

    $rules =
        tokolariso_fsb_rules_for_export();

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
        tokolariso_fsb_get_product_category_terms();

    $export =
        wp_json_encode(
            $rules,
            JSON_PRETTY_PRINT
        );

?>

<div class="wrap tokolariso-fsb-admin">

    <h1><?php esc_html_e('Toko Lariso Free Shipping Bar PRO', 'tokolariso'); ?></h1>

    <?php if ($message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <style>
        .tokolariso-fsb-admin {
            max-width: 1280px;
        }

        .tokolariso-fsb-toolbar,
        .tokolariso-fsb-card,
        .tokolariso-fsb-rule {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }

        .tokolariso-fsb-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 18px;
            margin: 18px 0;
        }

        .tokolariso-fsb-toolbar p,
        .tokolariso-fsb-card p {
            margin: 4px 0 0;
            color: #646970;
        }

        .tokolariso-fsb-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tokolariso-fsb-card {
            padding: 18px;
            margin-top: 20px;
        }

        .tokolariso-fsb-card h2,
        .tokolariso-fsb-rule h3 {
            margin-top: 0;
        }

        .tokolariso-fsb-rule {
            margin: 14px 0;
            padding: 16px;
        }

        .tokolariso-fsb-rule__top {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f0f0f1;
        }

        .tokolariso-fsb-toggle,
        .tokolariso-fsb-priority {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .tokolariso-fsb-priority input {
            width: 90px;
        }

        .tokolariso-fsb-remove-rule {
            margin-left: auto;
        }

        .tokolariso-fsb-flow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 44px minmax(0, 1fr);
            gap: 16px;
            align-items: stretch;
            margin-top: 16px;
        }

        .tokolariso-fsb-panel {
            background: #f6f7f7;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 16px;
            position: relative;
        }

        .tokolariso-fsb-panel label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
        }

        .tokolariso-fsb-panel label > span {
            display: block;
            margin-bottom: 6px;
        }

        .tokolariso-fsb-select,
        .tokolariso-fsb-panel .select2,
        .tokolariso-fsb-panel .selectWoo,
        .tokolariso-fsb-panel .select2-container,
        .tokolariso-fsb-panel .selectWoo-container,
        .tokolariso-fsb-panel .woocommerce-enhanced-select {
            width: 100% !important;
            max-width: 100%;
        }

        .tokolariso-fsb-panel select.tokolariso-fsb-select {
            min-height: 74px;
        }

        .tokolariso-fsb-panel .select2-container,
        .tokolariso-fsb-panel .selectWoo-container {
            display: block;
            min-height: 64px;
        }

        .tokolariso-fsb-panel select.select2-hidden-accessible,
        .tokolariso-fsb-panel select.enhanced,
        .tokolariso-fsb-panel select.wc-enhanced-select {
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

        .tokolariso-fsb-panel .select2-selection--multiple,
        .tokolariso-fsb-panel .selectWoo-selection--multiple {
            min-height: 64px !important;
            padding: 6px 8px !important;
            border-color: #8c8f94 !important;
            border-radius: 4px !important;
            background: #fff !important;
            box-sizing: border-box;
        }

        .tokolariso-fsb-panel .select2-selection__rendered,
        .tokolariso-fsb-panel .selectWoo-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 6px !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .tokolariso-fsb-panel .select2-selection__choice,
        .tokolariso-fsb-panel .selectWoo-selection__choice {
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

        .tokolariso-fsb-panel .select2-selection__choice__remove,
        .tokolariso-fsb-panel .selectWoo-selection__choice__remove,
        .tokolariso-fsb-panel .select2-search-choice-close {
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

        .tokolariso-fsb-panel .select2-selection__choice__remove:hover,
        .tokolariso-fsb-panel .selectWoo-selection__choice__remove:hover,
        .tokolariso-fsb-panel .select2-search-choice-close:hover {
            background: #dcdcde !important;
            color: #b32d2e !important;
        }

        .tokolariso-fsb-panel .select2-search--inline,
        .tokolariso-fsb-panel .selectWoo-search--inline {
            flex: 1 1 240px !important;
            min-width: 220px !important;
        }

        .tokolariso-fsb-panel .select2-search__field,
        .tokolariso-fsb-panel .selectWoo-search__field {
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

        .tokolariso-fsb-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2271b1;
            font-size: 28px;
            font-weight: 700;
        }

        .tokolariso-fsb-import-export {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 18px;
        }

        .tokolariso-fsb-admin textarea {
            min-height: 220px;
        }

        @media (max-width: 960px) {
            .tokolariso-fsb-toolbar,
            .tokolariso-fsb-rule__top,
            .tokolariso-fsb-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .tokolariso-fsb-flow,
            .tokolariso-fsb-import-export {
                grid-template-columns: 1fr;
            }

            .tokolariso-fsb-arrow {
                display: none;
            }
        }
    </style>

    <form method="post">

        <?php wp_nonce_field('tokolariso_fsb_save_settings', 'tokolariso_fsb_nonce'); ?>

        <div class="tokolariso-fsb-toolbar">
            <div>
                <h2><?php esc_html_e('Upsellregels', 'tokolariso'); ?></h2>
                <p><?php esc_html_e('Maak regels op basis van categorieen of specifieke producten. Hogere prioriteit wordt eerst getoond.', 'tokolariso'); ?></p>
                <p><?php esc_html_e('WPML-brontaal voor regels: Nederlands (nl).', 'tokolariso'); ?></p>
            </div>

            <div class="tokolariso-fsb-actions">
                <label>
                    <input
                        type="checkbox"
                        name="debug_mode"
                        <?php checked($debug, 'yes'); ?>
                    >
                    <?php esc_html_e('Debug logging', 'tokolariso'); ?>
                </label>

                <label class="tokolariso-fsb-interval">
                    <span><?php esc_html_e('Carouselinterval', 'tokolariso'); ?></span>
                    <input
                        type="number"
                        name="carousel_interval"
                        value="<?php echo esc_attr($carousel_interval); ?>"
                        min="0"
                        max="300"
                        step="1"
                    >
                    <span><?php esc_html_e('seconden', 'tokolariso'); ?></span>
                </label>

                <button
                    type="button"
                    class="button"
                    id="tokolariso-fsb-add-rule"
                >
                    <?php esc_html_e('Nieuwe regel', 'tokolariso'); ?>
                </button>

                <button
                    class="button button-primary"
                    type="submit"
                    name="tokolariso_save"
                    value="1"
                >
                    <?php esc_html_e('Opslaan', 'tokolariso'); ?>
                </button>
            </div>
        </div>

        <div id="tokolariso-fsb-rules">
            <?php
            $row_index = 0;

            foreach(
                $rules as $rule
            ){
                tokolariso_fsb_render_rule_card(
                    $rule,
                    $row_index,
                    $terms
                );

                $row_index++;
            } // END foreach
            ?>
        </div>

        <div class="tokolariso-fsb-card">
            <h2><?php esc_html_e('Import / export', 'tokolariso'); ?></h2>
            <p><?php esc_html_e('Export bevat dezelfde ID-structuur als eerdere versies. Import vervangt alle huidige regels.', 'tokolariso'); ?></p>

            <div class="tokolariso-fsb-import-export">
                <label>
                    <strong><?php esc_html_e('Export', 'tokolariso'); ?></strong>
                    <textarea readonly class="large-text code"><?php echo esc_textarea($export); ?></textarea>
                </label>

                <label>
                    <strong><?php esc_html_e('Import', 'tokolariso'); ?></strong>
                    <textarea
                        name="rules_import"
                        class="large-text code"
                        placeholder="<?php esc_attr_e('Plak JSON import hier', 'tokolariso'); ?>"
                    ></textarea>
                </label>
            </div>

            <p class="tokolariso-fsb-actions">
                <button
                    class="button"
                    type="submit"
                    name="tokolariso_save"
                    value="1"
                >
                    <?php esc_html_e('Import uitvoeren', 'tokolariso'); ?>
                </button>

                <button
                    class="button"
                    type="submit"
                    name="reset_default_rules"
                    value="1"
                    onclick="return confirm('<?php echo esc_js(__('Standaard upsellregels herstellen?', 'tokolariso')); ?>');"
                >
                    <?php esc_html_e('Standaardregels herstellen', 'tokolariso'); ?>
                </button>
            </p>
        </div>

    </form>

    <template id="tokolariso-fsb-rule-template">
        <?php
        tokolariso_fsb_render_rule_card(
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
            '<?php echo esc_js(wp_create_nonce('tokolariso_fsb_product_search')); ?>';

        let nextIndex =
            <?php echo (int) $row_index; ?>;

        function enhance(context) {

            const root =
                context ? $(context) : $(document);

            root.find('.tokolariso-fsb-select').each(function(){

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
                    select.hasClass('tokolariso-fsb-product-select')
                ) {

                    const config = {
                        width: '100%',
                        dropdownParent: $(document.body),
                        placeholder: select.data('placeholder') || '<?php echo esc_js(__('Zoek producten...', 'tokolariso')); ?>',
                        minimumInputLength: 2,
                        closeOnSelect: false,
                        ajax: {
                            url: ajaxurl,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    action: 'tokolariso_fsb_search_products',
                                    security: productSearchNonce,
                                    term: params.term || ''
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
                productSelect.closest('.tokolariso-fsb-panel');

            const categorySelect =
                panel.find('.tokolariso-fsb-category-select').first();

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
            '.tokolariso-fsb-product-select',
            function(event){

                addProductCategoriesToPanel(
                    $(this),
                    event.params ? event.params.data : null
                );
            }
        );

        $(document).on(
            'select2-selecting',
            '.tokolariso-fsb-product-select',
            function(event){

                addProductCategoriesToPanel(
                    $(this),
                    event.choice || null
                );
            }
        );

        $('#tokolariso-fsb-add-rule').on('click', function(){

            const template =
                $('#tokolariso-fsb-rule-template')
                    .html()
                    .split('__INDEX__')
                    .join(String(nextIndex));

            const node =
                $(template);

            $('#tokolariso-fsb-rules')
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
                    .find('.tokolariso-fsb-product-select')
                    .first()
                    .trigger('focus');

            }, 250);
        });

        $(document).on('click', '.tokolariso-fsb-remove-rule', function(){

            $(this)
                .closest('[data-rule]')
                .remove();
        });
    });
    </script>

</div>

<?php

} // END function tokolariso_fsb_settings_page()
