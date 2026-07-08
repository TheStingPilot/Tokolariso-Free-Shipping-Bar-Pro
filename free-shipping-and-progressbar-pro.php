<?php
/**
 * Plugin Name: Free Shipping and Progressbar PRO
 * Plugin URI: https://github.com/TheStingPilot/Tokolariso-Free-Shipping-Bar-Pro
 * Description: Free shipping bar + smart upsells + WooCommerce Blocks support.
 * Version: 2.0.0
 * Author: TheStingPilot and Codex
 * WPML Text Domain: free_shipment_progressbar
 */

if (!defined('ABSPATH')) {
    exit;
} // END if

define(
    'FREE_SHIPMENT_PROGRESSBAR_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'FREE_SHIPMENT_PROGRESSBAR_URL',
    plugin_dir_url(__FILE__)
);

add_action(
    'plugins_loaded',
    'free_shipment_progressbar_init'
);

function free_shipment_progressbar_init(){

    if (
        !class_exists('WooCommerce')
    ) {
        return;
    } // END if

    require_once FREE_SHIPMENT_PROGRESSBAR_PATH . 'includes/admin/menu.php';
    require_once FREE_SHIPMENT_PROGRESSBAR_PATH . 'includes/admin/settings.php';

    /*
     * Huidige werkende systeem laden
     */

    require_once FREE_SHIPMENT_PROGRESSBAR_PATH . 'includes/free-shipping-bar.php';

} // END function free_shipment_progressbar_init()

add_action(
    'admin_init',
    'free_shipment_progressbar_sync_wpml_string_source_language'
);

function free_shipment_progressbar_sync_wpml_string_source_language(){

    if(
        !is_admin()
        ||
        !free_shipment_progressbar_is_wpml_string_translation_available()
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
        'free_shipment_progressbar_wpml_source_language',
        'en',
        false
    );

    $wpdb->update(
        $table,
        [
            'language' => 'en',
        ],
        [
            'context' => 'free_shipment_progressbar',
        ],
        [
            '%s',
        ],
        [
            '%s',
        ]
    );
} // END function free_shipment_progressbar_sync_wpml_string_source_language()

function free_shipment_progressbar_is_wpml_string_translation_available(){

    return
        has_action('wpml_register_single_string')
        ||
        has_filter('wpml_translate_single_string')
        ||
        defined('WPML_ST_VERSION');
} // END function free_shipment_progressbar_is_wpml_string_translation_available()
