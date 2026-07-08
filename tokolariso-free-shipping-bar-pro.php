<?php
/**
 * Plugin Name: Toko Lariso Free Shipping Bar PRO
 * Plugin URI: https://www.tokolariso.nl/
 * Description: Free shipping bar + smart upsells + WooCommerce Blocks support.
 * Version: 1.2.0.22
 * Author: Toko Lariso
 * Text Domain: tokolariso
 */

if (!defined('ABSPATH')) {
    exit;
} // END if

define(
    'TOKOLARISO_FSB_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'TOKOLARISO_FSB_URL',
    plugin_dir_url(__FILE__)
);

add_action(
    'plugins_loaded',
    'tokolariso_fsb_init'
);

function tokolariso_fsb_init(){

    if (
        !class_exists('WooCommerce')
    ) {
        return;
    } // END if

    require_once TOKOLARISO_FSB_PATH . 'includes/admin/menu.php';
    require_once TOKOLARISO_FSB_PATH . 'includes/admin/settings.php';

    /*
     * Huidige werkende systeem laden
     */

    require_once TOKOLARISO_FSB_PATH . 'includes/free-shipping-bar.php';

} // END function tokolariso_fsb_init()

add_action(
    'admin_init',
    'tokolariso_fsb_sync_wpml_string_source_language'
);

function tokolariso_fsb_sync_wpml_string_source_language(){

    if(
        !is_admin()
        ||
        !tokolariso_fsb_is_wpml_string_translation_available()
    ){
        return;
    } // END if

    global $wpdb;

    $table =
        $wpdb->prefix . 'icl_strings';

    if(
        $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $table
            )
        ) !== $table
    ){
        return;
    } // END if

    update_option(
        'tokolariso_fsb_wpml_source_language',
        'nl',
        false
    );

    $wpdb->update(
        $table,
        [
            'language' => 'nl',
        ],
        [
            'context' => 'tokolariso',
        ],
        [
            '%s',
        ],
        [
            '%s',
        ]
    );
} // END function tokolariso_fsb_sync_wpml_string_source_language()

function tokolariso_fsb_is_wpml_string_translation_available(){

    return
        has_action('wpml_register_single_string')
        ||
        has_filter('wpml_translate_single_string')
        ||
        defined('WPML_ST_VERSION');
} // END function tokolariso_fsb_is_wpml_string_translation_available()
